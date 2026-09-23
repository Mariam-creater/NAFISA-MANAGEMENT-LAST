<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Tenant;
use App\Models\Payment;
use App\Models\Block;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth()->toDateString();
        $endOfMonth = $now->copy()->endOfMonth()->toDateString();

        // 1. KPI Cards
        // Card 1: Total Revenue Collected This Month
        $collectedThisMonth = (int) Payment::whereBetween('payment_date', [$startOfMonth, $endOfMonth])
            ->sum('amount_received');

        // Card 2: Total Expected Monthly Rent (aggregate of effective_price for all occupied rooms)
        $occupiedRooms = Room::with('block')->where('status', 'occupied')->get();
        $expectedMonthlyRent = (int) $occupiedRooms->sum(fn ($room) => $room->effective_price);

        // Card 3: Collection Progress Rate & Deficit
        $collectionRate = $expectedMonthlyRent > 0
            ? round(($collectedThisMonth / $expectedMonthlyRent) * 100, 1)
            : 0;
        $deficit = max(0, $expectedMonthlyRent - $collectedThisMonth);

        // Card 4: Occupancy Snapshot
        $totalRooms = Room::count();
        $occupiedCount = $occupiedRooms->count();
        $vacantCount = Room::where('status', 'vacant')->count();
        $maintenanceCount = Room::where('status', 'maintenance')->count();
        $activeTenantsCount = Tenant::where('status', 'active')->count();

        // 2. Visualizations
        // Donut Chart: Occupancy & Block Revenue Share
        $block1 = Block::where('code', 'B1')->first();
        $block2 = Block::where('code', 'B2')->first();

        $block1Revenue = (int) Payment::whereHas('room', fn($q) => $q->where('block_id', $block1?->id))
            ->whereBetween('payment_date', [$startOfMonth, $endOfMonth])
            ->sum('amount_received');

        $block2Revenue = (int) Payment::whereHas('room', fn($q) => $q->where('block_id', $block2?->id))
            ->whereBetween('payment_date', [$startOfMonth, $endOfMonth])
            ->sum('amount_received');

        $donutChart = [
            'occupancy' => [
                'occupied' => $occupiedCount,
                'vacant' => $vacantCount,
                'maintenance' => $maintenanceCount,
            ],
            'blockShare' => [
                'block1' => $block1Revenue,
                'block2' => $block2Revenue,
            ],
        ];

        // 12-Month Financial Comparison (Grouped Bar Chart)
        $monthlyComparison = [];
        for ($i = 11; $i >= 0; $i--) {
            $monthDate = $now->copy()->subMonths($i);
            $mStart = $monthDate->copy()->startOfMonth()->toDateString();
            $mEnd = $monthDate->copy()->endOfMonth()->toDateString();
            $monthLabel = $monthDate->format('M Y');

            $actualCollected = (int) Payment::whereBetween('payment_date', [$mStart, $mEnd])
                ->sum('amount_received');

            // For past months, approximate expected based on occupied rooms count or standard rate
            $expected = $expectedMonthlyRent;

            $monthlyComparison[] = [
                'month' => $monthLabel,
                'expected' => $expected,
                'collected' => $actualCollected,
            ];
        }

        // 3. Imminent Due Date Watchlist (leases expiring within the next 7 days)
        $sevenDaysAhead = $now->copy()->addDays(7)->toDateString();
        $imminentTenants = Tenant::with(['room.block'])
            ->where('status', 'active')
            ->where('rent_due_date', '<=', $sevenDaysAhead)
            ->orderBy('rent_due_date', 'asc')
            ->take(10)
            ->get();

        return Inertia::render('Dashboard', [
            'kpis' => [
                'collectedThisMonth' => $collectedThisMonth,
                'expectedMonthlyRent' => $expectedMonthlyRent,
                'collectionRate' => $collectionRate,
                'deficit' => $deficit,
                'totalRooms' => $totalRooms,
                'occupiedCount' => $occupiedCount,
                'vacantCount' => $vacantCount,
                'maintenanceCount' => $maintenanceCount,
                'activeTenantsCount' => $activeTenantsCount,
            ],
            'donutChart' => $donutChart,
            'monthlyComparison' => $monthlyComparison,
            'imminentTenants' => $imminentTenants,
        ]);
    }
}
