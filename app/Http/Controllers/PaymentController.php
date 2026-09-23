<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Room;
use App\Services\ReceiptService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Display payments ledger and intake screen
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $method = $request->input('method');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = Payment::with(['tenant', 'room.block'])->orderByDesc('payment_date')->orderByDesc('id');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('receipt_serial_no', 'like', "%{$search}%")
                  ->orWhereHas('tenant', fn($t) => $t->where('full_name', 'like', "%{$search}%"));
            });
        }

        if ($method) {
            $query->where('payment_method', $method);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('payment_date', [$startDate, $endDate]);
        }

        $payments = $query->paginate(15)->withQueryString();

        // Active tenants with occupied rooms for intake modal, including previous balance
        $activeTenants = Tenant::with(['room.block', 'payments' => fn($p) => $p->take(5)])
            ->where('status', 'active')
            ->orderBy('full_name')
            ->get();

        return Inertia::render('Payments/Index', [
            'payments' => $payments,
            'activeTenants' => $activeTenants,
            'filters' => [
                'search' => $search,
                'method' => $method,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    /**
     * Store new payment intake, generate PDF receipt, dispatch notifications
     */
    public function store(Request $request, ReceiptService $receiptService, NotificationService $notificationService)
    {
        $validated = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'amount_received' => 'required|integer|min:0',
            'base_rent_charged' => 'required|integer|min:0',
            'tax_amount' => 'nullable|integer|min:0',
            'include_previous_balance' => 'nullable|boolean',
            'payment_method' => 'required|in:cash,bank_transfer,mobile_money',
            'payment_date' => 'required|date',
            'advance_due_date' => 'nullable|boolean',
            'notes' => 'nullable|string|max:1000',
            'dispatch_channels' => 'nullable|array', // ['email', 'whatsapp', 'sms']
        ]);

        $payment = DB::transaction(function () use ($validated, $request, $receiptService) {
            $tenant = Tenant::with('room')->findOrFail($validated['tenant_id']);
            $serialNo = $receiptService->generateNextSerialNumber();
            $tax = (int) ($validated['tax_amount'] ?? 0);
            
            // Previous balance carryover
            $previousBalance = 0;
            if ($request->boolean('include_previous_balance', true)) {
                $previousBalance = max(0, (int) $tenant->current_balance);
            }

            // Total Invoice Due = Base Rent Charged + Optional Tax + Previous Outstanding Balance
            $totalDue = (int) $validated['base_rent_charged'] + $tax + $previousBalance;
            $amountReceived = (int) $validated['amount_received'];
            $balanceRemaining = max(0, $totalDue - $amountReceived);

            $paymentStatus = 'paid';
            if ($amountReceived < $totalDue) {
                $paymentStatus = 'partial';
            } elseif ($amountReceived > $totalDue) {
                $paymentStatus = 'overpaid';
            }

            $pay = Payment::create([
                'tenant_id' => $tenant->id,
                'room_id' => $tenant->room_id,
                'receipt_serial_no' => $serialNo,
                'amount_received' => $amountReceived,
                'base_rent_charged' => (int) $validated['base_rent_charged'],
                'tax_amount' => $tax,
                'total_due' => $totalDue,
                'balance_remaining' => $balanceRemaining,
                'payment_status' => $paymentStatus,
                'payment_method' => $validated['payment_method'],
                'payment_date' => $validated['payment_date'],
                'notes' => $validated['notes'] ?? null,
            ]);

            // Update tenant's active cumulative debt / credit
            $tenant->update([
                'current_balance' => $balanceRemaining,
            ]);

            // Advance rent due date by 30 days if requested or if cycle is settled
            if ($request->boolean('advance_due_date', true) || ($balanceRemaining === 0 && $amountReceived >= $validated['base_rent_charged'])) {
                $currentDue = Carbon::parse($tenant->rent_due_date);
                $newDue = $currentDue->isPast()
                    ? Carbon::today()->addDays(30)
                    : $currentDue->addDays(30);
                $tenant->update(['rent_due_date' => $newDue->toDateString()]);
            }

            return $pay;
        });

        // Generate vector-sharp PDF receipt and save to storage/app/public/receipts/YYYY/
        $pdfPath = $receiptService->generateAndSavePdf($payment);

        // Automated multi-channel dispatch
        $channels = $validated['dispatch_channels'] ?? [];
        if (!empty($channels)) {
            $notificationService->dispatchReceipt($payment, $channels);
        }

        $payment->loadMissing(['tenant', 'room.block']);
        $tenantName = $payment->tenant?->full_name ?? 'Resident';
        $paidFormatted = number_format($payment->amount_received);

        if ($payment->balance_remaining === 0) {
            $title = 'Payment Recorded Successfully';
            $description = "Payment of {$paidFormatted} UGX received for {$tenantName}. Rent settled in full.";
        } else {
            $deficitFormatted = number_format($payment->balance_remaining);
            $title = 'Partial Payment Processed';
            $description = "Payment of {$paidFormatted} UGX recorded. Outstanding balance: {$deficitFormatted} UGX.";
        }

        return redirect()->back()->with([
            'success' => $description,
            'new_payment' => $payment,
            'flash' => [
                'type' => 'success',
                'title' => $title,
                'description' => $description,
                'message' => $description,
            ],
        ]);
    }

    /**
     * Download or view PDF receipt directly
     */
    public function downloadReceipt(Payment $payment, ReceiptService $receiptService)
    {
        $binary = $receiptService->getPdfBinary($payment);

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $payment->receipt_serial_no . '.pdf"',
        ]);
    }

    /**
     * Resend receipt notifications across channels
     */
    public function resend(Payment $payment, NotificationService $notificationService)
    {
        $payment->loadMissing(['tenant', 'room.block']);
        $notificationService->dispatchReceipt($payment, ['email', 'whatsapp', 'sms']);

        $email = $payment->tenant?->email ?? 'email';
        $phone = $payment->tenant?->whatsapp_number ?? 'phone';
        $title = 'Receipt Dispatched';
        $description = "Official PDF receipt sent to {$email} and WhatsApp ({$phone}).";

        return redirect()->back()->with([
            'success' => $description,
            'flash' => [
                'type' => 'success',
                'title' => $title,
                'description' => $description,
                'message' => $description,
            ],
        ]);
    }

    /**
     * Resend receipt notification to a specific channel (whatsapp or email)
     */
    public function resendChannel(Payment $payment, string $channel, NotificationService $notificationService)
    {
        if (!in_array($channel, ['email', 'whatsapp', 'sms'])) {
            return redirect()->back()->with('error', "Invalid notification channel requested.");
        }

        $payment->loadMissing(['tenant', 'room.block']);
        $notificationService->dispatchReceipt($payment, [$channel]);

        $email = $payment->tenant?->email ?? 'email';
        $phone = $payment->tenant?->whatsapp_number ?? 'phone';
        $title = 'Receipt Dispatched';
        $description = "Official PDF receipt sent to {$email} and WhatsApp ({$phone}).";

        return redirect()->back()->with([
            'success' => $description,
            'flash' => [
                'type' => 'success',
                'title' => $title,
                'description' => $description,
                'message' => $description,
            ],
        ]);
    }
}
