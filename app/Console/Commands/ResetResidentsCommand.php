<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\Payment;
use App\Models\NotificationLog;
use App\Models\Room;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ResetResidentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hostel:reset-residents {--force : Force the operation without interactive confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears all test/dummy tenants, payments, and notification logs, and resets occupied rooms to vacant';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!$this->option('force') && !$this->confirm('This will permanently delete all tenants, payments, and notification logs. Do you wish to continue?')) {
            $this->warn('Operation cancelled.');
            return Command::SUCCESS;
        }

        $this->info('Resetting Nafisa Hostel resident and billing data...');

        DB::transaction(function () {
            // 1. Delete all payments
            Payment::query()->forceDelete();

            // 2. Delete all notification logs
            NotificationLog::query()->delete();

            // 3. Delete all tenants (including soft deleted)
            Tenant::withTrashed()->forceDelete();

            // 4. Clean up any leftover test-generated rooms
            Room::where('room_number', 'like', 'TEST%')->delete();

            // 5. Update blocks defaults
            $b1Id = DB::table('blocks')->where('code', 'A')->orWhere('code', 'B1')->orWhere('name', 'Block A')->orWhere('id', 1)->value('id') ?? 1;
            $b2Id = DB::table('blocks')->where('code', 'B')->orWhere('code', 'B2')->orWhere('name', 'Block B')->orWhere('id', 2)->value('id') ?? 2;

            DB::table('blocks')->where('id', $b1Id)->update(['default_monthly_price' => 420000]);
            DB::table('blocks')->where('id', $b2Id)->update(['default_monthly_price' => 1000000]);

            // 6. Reset all room statuses and enforce base_price
            Room::where('block_id', $b1Id)->update([
                'status' => 'vacant',
                'custom_price' => null,
                'base_price' => 420000,
            ]);

            Room::where('block_id', $b2Id)->update([
                'status' => 'vacant',
                'custom_price' => null,
                'base_price' => 1000000,
            ]);
        });

        // 5. Clean up stored PDF receipts in public disk
        Storage::disk('public')->deleteDirectory('receipts');
        Storage::disk('public')->makeDirectory('receipts');

        $this->info('✓ All dummy tenants and payment records have been cleared.');
        $this->info('✓ All rooms have been reset to vacant status.');
        $this->info('✓ Block A (420,000 UGX) and Block B (1,000,000 UGX) inventory is ready for fresh registration.');

        return Command::SUCCESS;
    }
}
