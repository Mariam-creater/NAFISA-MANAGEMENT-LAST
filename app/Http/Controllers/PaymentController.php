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

        // Active tenants with occupied rooms for intake modal
        $activeTenants = Tenant::with(['room.block', 'payments' => fn($p) => $p->take(3)])
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
            'amount_received' => 'required|integer|min:1000',
            'base_rent_charged' => 'required|integer|min:1000',
            'tax_amount' => 'nullable|integer|min:0',
            'payment_method' => 'required|in:cash,bank_transfer,mobile_money',
            'payment_date' => 'required|date',
            'advance_due_date' => 'nullable|boolean', // if true, extend tenant's rent_due_date by 30 days
            'notes' => 'nullable|string|max:1000',
            'dispatch_channels' => 'nullable|array', // ['email', 'whatsapp', 'sms']
        ]);

        $tenant = Tenant::with('room')->findOrFail($validated['tenant_id']);
        $serialNo = $receiptService->generateNextSerialNumber();
        $tax = $validated['tax_amount'] ?? 0;
        $totalDue = $validated['base_rent_charged'] + $tax;

        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'room_id' => $tenant->room_id,
            'receipt_serial_no' => $serialNo,
            'amount_received' => $validated['amount_received'],
            'base_rent_charged' => $validated['base_rent_charged'],
            'tax_amount' => $tax,
            'total_due' => $totalDue,
            'payment_method' => $validated['payment_method'],
            'payment_date' => $validated['payment_date'],
            'notes' => $validated['notes'] ?? null,
        ]);

        // Generate vector-sharp PDF receipt and save to storage/app/public/receipts/YYYY/
        $pdfPath = $receiptService->generateAndSavePdf($payment);

        // Advance rent due date by 30 days if requested (default behavior)
        if ($request->boolean('advance_due_date', true)) {
            $currentDue = Carbon::parse($tenant->rent_due_date);
            $newDue = $currentDue->isPast()
                ? Carbon::today()->addDays(30)
                : $currentDue->addDays(30);
            $tenant->update(['rent_due_date' => $newDue->toDateString()]);
        }

        // Automated multi-channel dispatch
        $channels = $validated['dispatch_channels'] ?? ['email', 'whatsapp', 'sms'];
        $notificationService->dispatchReceipt($payment, $channels);

        return redirect()->back()->with([
            'success' => "Payment of " . number_format($payment->amount_received) . " UGX recorded. Receipt {$serialNo} issued.",
            'new_payment' => $payment->load('room.block', 'tenant'),
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
     * Resend receipt notifications
     */
    public function resend(Payment $payment, NotificationService $notificationService)
    {
        $payment->loadMissing(['tenant', 'room.block']);
        $notificationService->dispatchReceipt($payment, ['email', 'whatsapp', 'sms']);

        return redirect()->back()->with('success', "Receipt notifications re-sent to {$payment->tenant->full_name}.");
    }
}
