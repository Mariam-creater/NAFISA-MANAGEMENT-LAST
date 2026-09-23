<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Room;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Render Financial Reports view with date filters and aggregations
     */
    public function index(Request $request): Response
    {
        $range = $request->input('range', 'this_month');
        $customStart = $request->input('start_date');
        $customEnd = $request->input('end_date');

        [$startDate, $endDate, $label] = $this->resolveDateRange($range, $customStart, $customEnd);

        $query = Payment::with(['tenant', 'room.block'])
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->orderByDesc('payment_date');

        $payments = (clone $query)->get();

        // Financial KPIs
        $totalCollected = (int) $payments->sum('amount_received');
        $totalTax = (int) $payments->sum('tax_amount');
        $totalInvoiced = (int) $payments->sum('total_due');

        // Expected rent calculation in the window
        $occupiedRooms = Room::where('status', 'occupied')->get();
        $monthlyExpected = (int) $occupiedRooms->sum(fn ($r) => $r->effective_price);
        $outstandingDeficit = max(0, $monthlyExpected - $totalCollected);

        $stats = [
            'total_collected' => $totalCollected,
            'total_tax' => $totalTax,
            'total_invoiced' => $totalInvoiced,
            'monthly_expected' => $monthlyExpected,
            'outstanding_deficit' => $outstandingDeficit,
        ];

        return Inertia::render('Reports/Index', [
            'payments' => $payments,
            'stats' => $stats,
            'range' => $range,
            'dateRangeLabel' => $label,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    /**
     * One-Click Excel / CSV Export
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $range = $request->input('range', 'this_month');
        [$startDate, $endDate, $label] = $this->resolveDateRange($range, $request->input('start_date'), $request->input('end_date'));

        $payments = Payment::with(['tenant', 'room.block'])
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->orderBy('payment_date', 'asc')
            ->get();

        $filename = "Nafisa_Financial_Ledger_" . str_replace(' ', '_', $label) . ".csv";

        return response()->streamDownload(function () use ($payments, $label) {
            $handle = fopen('php://output', 'w');
            
            // UTF-8 BOM for Microsoft Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Title Rows
            fputcsv($handle, ['NAFISA HOSTEL MANAGEMENT SYSTEM']);
            fputcsv($handle, ['OFFICIAL FINANCIAL PERFORMANCE REPORT - ' . $label]);
            fputcsv($handle, ['Exported At', date('Y-m-d H:i:s')]);
            fputcsv($handle, []);

            // Headers
            fputcsv($handle, [
                'Receipt Serial',
                'Payment Date',
                'Tenant Full Name',
                'Block',
                'Room Number',
                'Payment Method',
                'Base Rent Charged (UGX)',
                'Tax Amount (UGX)',
                'Amount Received (UGX)',
                'Notes',
            ]);

            $totalBase = 0;
            $totalTax = 0;
            $totalReceived = 0;

            foreach ($payments as $p) {
                $totalBase += $p->base_rent_charged;
                $totalTax += $p->tax_amount;
                $totalReceived += $p->amount_received;

                fputcsv($handle, [
                    $p->receipt_serial_no,
                    $p->payment_date->format('Y-m-d'),
                    $p->tenant->full_name,
                    $p->room ? $p->room->block->name : 'N/A',
                    $p->room ? $p->room->room_number : 'N/A',
                    strtoupper(str_replace('_', ' ', $p->payment_method)),
                    $p->base_rent_charged,
                    $p->tax_amount,
                    $p->amount_received,
                    $p->notes ?? '',
                ]);
            }

            // Totals Row
            fputcsv($handle, []);
            fputcsv($handle, [
                'TOTALS',
                '',
                '',
                '',
                '',
                '',
                $totalBase,
                $totalTax,
                $totalReceived,
                '',
            ]);

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Formal Ledger PDF Export
     */
    public function exportPdf(Request $request)
    {
        $range = $request->input('range', 'this_month');
        [$startDate, $endDate, $label] = $this->resolveDateRange($range, $request->input('start_date'), $request->input('end_date'));

        $payments = Payment::with(['tenant', 'room.block'])
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->orderBy('payment_date', 'asc')
            ->get();

        $totalCollected = (int) $payments->sum('amount_received');
        $totalTax = (int) $payments->sum('tax_amount');
        $totalInvoiced = (int) $payments->sum('total_due');

        $occupiedRooms = Room::where('status', 'occupied')->get();
        $monthlyExpected = (int) $occupiedRooms->sum(fn ($r) => $r->effective_price);
        $outstandingDeficit = max(0, $monthlyExpected - $totalCollected);

        $stats = [
            'total_collected' => $totalCollected,
            'total_tax' => $totalTax,
            'total_invoiced' => $totalInvoiced,
            'monthly_expected' => $monthlyExpected,
            'outstanding_deficit' => $outstandingDeficit,
        ];

        $pdf = Pdf::loadView('reports.ledger_pdf', [
            'payments' => $payments,
            'stats' => $stats,
            'dateRangeLabel' => $label,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("Nafisa_Ledger_Statement_{$startDate}_{$endDate}.pdf");
    }

    /**
     * Helper to resolve range to [start, end, label]
     */
    protected function resolveDateRange(string $range, ?string $start, ?string $end): array
    {
        $now = Carbon::now();

        return match ($range) {
            'today' => [
                $now->toDateString(),
                $now->toDateString(),
                'Today (' . $now->format('d M Y') . ')',
            ],
            'last_6_months' => [
                $now->copy()->subMonths(6)->startOfDay()->toDateString(),
                $now->toDateString(),
                'Trailing 6 Months',
            ],
            'ytd' => [
                $now->copy()->startOfYear()->toDateString(),
                $now->toDateString(),
                'Year-to-Date (' . $now->year . ')',
            ],
            'custom' => [
                $start ?? $now->copy()->startOfMonth()->toDateString(),
                $end ?? $now->toDateString(),
                'Custom Range (' . ($start ?? '') . ' to ' . ($end ?? '') . ')',
            ],
            default => [
                $now->copy()->startOfMonth()->toDateString(),
                $now->copy()->endOfMonth()->toDateString(),
                'Current Month (' . $now->format('F Y') . ')',
            ],
        };
    }
}
