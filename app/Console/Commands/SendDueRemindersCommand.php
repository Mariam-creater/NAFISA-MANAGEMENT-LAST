<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Services\NotificationService;
use Carbon\Carbon;

class SendDueRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hostel:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatches automated 3-day rent due notifications to upcoming tenants via Email, WhatsApp, and SMS';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService): int
    {
        $this->info('Starting automated rent reminder scan...');

        $targetDate = Carbon::today()->addDays(3)->toDateString();

        // Tenants whose rent is due in exactly 3 days
        $upcomingTenants = Tenant::where('status', 'active')
            ->whereDate('rent_due_date', $targetDate)
            ->with('room.block')
            ->get();

        $this->info("Found {$upcomingTenants->count()} tenants due in 3 days.");

        $count = 0;
        foreach ($upcomingTenants as $tenant) {
            $results = $notificationService->dispatchRentDueReminder($tenant, 3, ['email', 'whatsapp', 'sms']);
            $this->line("Dispatched alerts to {$tenant->full_name} ({$tenant->whatsapp_number})");
            $count++;
        }

        $this->info("Completed. Dispatched {$count} tenant reminders.");
        return Command::SUCCESS;
    }
}
