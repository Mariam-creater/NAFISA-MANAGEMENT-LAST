<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Block;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\Payment;
use App\Models\NotificationLog;
use App\Services\ReceiptService;
use App\Services\BackupService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;

class HostelManagementTest extends TestCase
{
    public function test_dashboard_renders_successfully_with_props(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('Dashboard')
                ->has('kpis')
                ->has('donutChart')
                ->has('monthlyComparison')
                ->has('imminentTenants')
        );
    }

    public function test_room_effective_price_dynamic_override(): void
    {
        $block = Block::first();
        $room = Room::create([
            'block_id' => $block->id,
            'floor_number' => 1,
            'room_number' => 'TEST-101',
            'base_price' => 420000,
            'custom_price' => null,
            'status' => 'vacant',
        ]);

        // When custom_price is null, effective_price equals base_price
        $this->assertEquals(420000, $room->effective_price);

        // When custom_price is set, effective_price reflects override
        $room->update(['custom_price' => 500000]);
        $this->assertEquals(500000, $room->fresh()->effective_price);
    }

    public function test_tenant_registration_and_room_status_update(): void
    {
        $room = Room::where('status', 'vacant')->first();
        $this->assertNotNull($room);

        $payload = [
            'full_name' => 'Aisha Nakimera',
            'age' => 21,
            'email' => 'aisha@example.com',
            'whatsapp_number' => '+256788112233',
            'occupation' => 'student',
            'emergency_name' => 'Mariam Nakimera',
            'emergency_phone' => '+256701223344',
            'emergency_relationship' => 'Mother',
            'room_id' => $room->id,
            'check_in_date' => Carbon::today()->toDateString(),
            'rent_due_date' => Carbon::today()->addDays(30)->toDateString(),
        ];

        $response = $this->post('/tenants', $payload);
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('tenants', [
            'full_name' => 'Aisha Nakimera',
            'room_id' => $room->id,
            'status' => 'active',
        ]);

        // Room should now be occupied
        $this->assertEquals('occupied', $room->fresh()->status);
    }

    public function test_tenant_checkout_soft_deletes_and_frees_room(): void
    {
        $tenant = Tenant::where('status', 'active')->first();
        $this->assertNotNull($tenant);
        $roomId = $tenant->room_id;

        $response = $this->delete("/tenants/{$tenant->id}");
        $response->assertSessionHas('success');

        // Tenant is soft deleted
        $this->assertSoftDeleted('tenants', ['id' => $tenant->id]);

        // Room is vacated
        if ($roomId) {
            $this->assertEquals('vacant', Room::find($roomId)->status);
        }
    }

    public function test_payment_recording_and_receipt_generation(): void
    {
        $tenant = Tenant::where('status', 'active')->first();
        $this->assertNotNull($tenant);

        $payload = [
            'tenant_id' => $tenant->id,
            'amount_received' => 420000,
            'base_rent_charged' => 420000,
            'tax_amount' => 0,
            'payment_method' => 'mobile_money',
            'payment_date' => Carbon::today()->toDateString(),
            'advance_due_date' => true,
            'notes' => 'Test payment settlement',
            'dispatch_channels' => ['email'],
        ];

        $response = $this->post('/payments', $payload);
        $response->assertSessionHas('success');

        $payment = Payment::where('tenant_id', $tenant->id)->latest('id')->first();
        $this->assertNotNull($payment);
        $this->assertStringStartsWith('NFH-' . date('Y') . '-', $payment->receipt_serial_no);

        // Verify PDF receipt file exists
        $this->assertNotNull($payment->pdf_path);
        Storage::disk('public')->assertExists($payment->pdf_path);
    }

    public function test_automated_send_reminders_command(): void
    {
        // Set a tenant due in 3 days
        $tenant = Tenant::where('status', 'active')->first();
        $tenant->update(['rent_due_date' => Carbon::today()->addDays(3)->toDateString()]);

        $this->artisan('hostel:send-reminders')
            ->expectsOutputToContain('Starting automated rent reminder scan...')
            ->assertExitCode(0);

        $this->assertDatabaseHas('notification_logs', [
            'tenant_id' => $tenant->id,
            'type' => '3_day_reminder',
            'status' => 'sent',
        ]);
    }

    public function test_automated_backup_command(): void
    {
        $this->artisan('backup:email')
            ->expectsOutputToContain('Starting automated SQLite & media cloud backup...')
            ->assertExitCode(0);
    }

    public function test_excel_export_endpoint(): void
    {
        $response = $this->get('/reports/export/excel?range=this_month');
        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-type'), 'text/csv'));
    }

    public function test_pdf_ledger_export_endpoint(): void
    {
        $response = $this->get('/reports/export/pdf?range=this_month');
        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }
}
