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
use Illuminate\Http\UploadedFile;

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
        $roomNumber = 'TEST-' . rand(1000, 9999);
        $room = Room::create([
            'block_id' => $block->id,
            'floor_number' => 1,
            'room_number' => $roomNumber,
            'base_price' => 420000,
            'custom_price' => null,
            'status' => 'vacant',
        ]);

        // When custom_price is null, effective_price equals base_price
        $this->assertEquals(420000, $room->effective_price);

        // When custom_price is set, effective_price reflects override
        $room->update(['custom_price' => 500000]);
        $this->assertEquals(500000, $room->fresh()->effective_price);

        $room->delete();
    }

    public function test_strict_block_pricing_resolution(): void
    {
        $blockA = Block::where('code', 'A')->orWhere('id', 1)->first();
        $blockB = Block::where('code', 'B')->orWhere('id', 2)->first();

        $this->assertEquals(420000, $blockA->default_monthly_price);
        $this->assertEquals(1000000, $blockB->default_monthly_price);

        $roomA = Room::where('block_id', $blockA->id)->first();
        $roomB = Room::where('block_id', $blockB->id)->first();

        // Block A Room defaults strictly to 420,000 UGX
        $this->assertEquals(420000, $roomA->effective_price);

        // Block B Room defaults strictly to 1,000,000 UGX
        $this->assertEquals(1000000, $roomB->effective_price);

        // Custom price override takes precedence
        $roomA->update(['custom_price' => 450000]);
        $this->assertEquals(450000, $roomA->fresh()->effective_price);

        $roomA->update(['custom_price' => null]);
        $this->assertEquals(420000, $roomA->fresh()->effective_price);
    }

    public function test_tenant_registration_and_room_status_update(): void
    {
        Storage::fake('public');

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
            'avatar' => UploadedFile::fake()->image('aisha_avatar.jpg'),
            'id_document' => UploadedFile::fake()->create('aisha_passport.pdf', 500, 'application/pdf'),
        ];

        $response = $this->post('/tenants', $payload);
        $response->assertSessionHas('flash.type', 'success');
        $response->assertSessionHas('flash.title', 'Resident Registered Successfully');

        $tenant = Tenant::where('email', 'aisha@example.com')->first();
        $this->assertNotNull($tenant);
        $this->assertEquals('Aisha Nakimera', $tenant->full_name);
        $this->assertEquals($room->id, $tenant->room_id);
        $this->assertEquals('active', $tenant->status);
        $this->assertEquals(0, $tenant->current_balance);
        $this->assertNotNull($tenant->avatar_path);
        $this->assertNotNull($tenant->id_document_path);

        Storage::disk('public')->assertExists($tenant->avatar_path);
        Storage::disk('public')->assertExists($tenant->id_document_path);

        // Room should now be occupied
        $this->assertEquals('occupied', $room->fresh()->status);

        // Ensure newly registered tenant appears at the top of active directory
        $indexResponse = $this->get('/tenants');
        $indexResponse->assertInertia(fn ($page) =>
            $page->component('Tenants/Index')
                ->where('activeTenants.0.id', $tenant->id)
                ->where('activeTenants.0.full_name', 'Aisha Nakimera')
        );

        // Ensure room is no longer in vacantRooms
        $indexResponse->assertInertia(fn ($page) =>
            $page->component('Tenants/Index')
                ->whereNot('vacantRooms', fn ($rooms) => collect($rooms)->pluck('id')->contains($room->id))
        );
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

    private function getOrCreateActiveTenant(): Tenant
    {
        $tenant = Tenant::where('status', 'active')->first();
        if ($tenant) {
            return $tenant;
        }

        $room = Room::where('status', 'vacant')->first() ?? Room::first();

        return Tenant::create([
            'full_name' => 'Faduma Test Resident',
            'room_id' => $room ? $room->id : null,
            'age' => 22,
            'whatsapp_number' => '+256700112233',
            'email' => 'faduma.test@example.com',
            'occupation' => 'student',
            'emergency_name' => 'Khadija Test',
            'emergency_phone' => '+256700998877',
            'emergency_relationship' => 'Sister',
            'check_in_date' => Carbon::today()->subDays(10)->toDateString(),
            'rent_due_date' => Carbon::today()->addDays(20)->toDateString(),
            'status' => 'active',
            'current_balance' => 0,
        ]);
    }

    public function test_payment_recording_and_receipt_generation(): void
    {
        $tenant = $this->getOrCreateActiveTenant();
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

    public function test_partial_payment_calculates_balance_remaining_and_records_advance_date(): void
    {
        $tenant = $this->getOrCreateActiveTenant();
        $this->assertNotNull($tenant);
        $tenant->update(['current_balance' => 0]);

        // Block A rent is 420,000 UGX. Tenant pays 200,000 UGX partial payment.
        $payload = [
            'tenant_id' => $tenant->id,
            'amount_received' => 200000,
            'base_rent_charged' => 420000,
            'tax_amount' => 0,
            'include_previous_balance' => false,
            'payment_method' => 'cash',
            'payment_date' => Carbon::today()->addDays(5)->toDateString(), // advance payment date
            'advance_due_date' => false,
            'notes' => 'Partial advance payment installment',
            'dispatch_channels' => [],
        ];

        $response = $this->post('/payments', $payload);
        $response->assertSessionHas('success');

        $payment = Payment::where('tenant_id', $tenant->id)->latest('id')->first();
        $this->assertNotNull($payment);
        $this->assertEquals(220000, $payment->balance_remaining);
        $this->assertTrue($payment->is_partial);
        $this->assertEquals('220,000 UGX', $payment->formatted_balance);
        $this->assertEquals(Carbon::today()->addDays(5)->toDateString(), $payment->payment_date->toDateString());
        $this->assertEquals('partial', $payment->payment_status);
        $this->assertEquals(220000, $tenant->fresh()->current_balance);
    }

    public function test_tax_and_previous_balance_addition_to_total_invoice_due(): void
    {
        $tenant = $this->getOrCreateActiveTenant();
        $this->assertNotNull($tenant);

        // Set previous debt on tenant
        $tenant->update(['current_balance' => 20000]);

        // Pay base 420,000 + tax 10,000 + arrears 20,000 = Total Due: 450,000
        $payload = [
            'tenant_id' => $tenant->id,
            'amount_received' => 449000, // pays 449k, leaving 1,000 deficit
            'base_rent_charged' => 420000,
            'tax_amount' => 10000,
            'include_previous_balance' => true,
            'payment_method' => 'mobile_money',
            'payment_date' => Carbon::today()->toDateString(),
            'notes' => 'Tax and previous arrears test',
            'dispatch_channels' => ['whatsapp'],
        ];

        $response = $this->post('/payments', $payload);
        $response->assertSessionHas('success');

        $payment = Payment::where('tenant_id', $tenant->id)->latest('id')->first();
        $this->assertNotNull($payment);
        $this->assertEquals(450000, $payment->total_due);
        $this->assertEquals(449000, $payment->amount_received);
        $this->assertEquals(1000, $payment->balance_remaining);
        $this->assertEquals('partial', $payment->payment_status);
        $this->assertEquals(1000, $tenant->fresh()->current_balance);
    }

    public function test_resend_channel_dispatches_via_single_channel(): void
    {
        $payment = Payment::latest('id')->first();
        if (!$payment) {
            $tenant = $this->getOrCreateActiveTenant();
            $payment = Payment::create([
                'tenant_id' => $tenant->id,
                'room_id' => $tenant->room_id,
                'receipt_serial_no' => 'NFH-' . date('Y') . '-9999',
                'amount_received' => 420000,
                'total_due' => 420000,
                'balance_remaining' => 0,
                'payment_status' => 'settled',
                'is_partial' => false,
                'base_rent_charged' => 420000,
                'tax_amount' => 0,
                'payment_method' => 'cash',
                'payment_date' => Carbon::today()->toDateString(),
            ]);
        }
        $this->assertNotNull($payment);

        $response = $this->post("/payments/{$payment->id}/resend/whatsapp");
        $response->assertSessionHas('success');

        $responseEmail = $this->post("/payments/{$payment->id}/resend/email");
        $responseEmail->assertSessionHas('success');
    }

    public function test_automated_send_reminders_command(): void
    {
        // Set a tenant due in 3 days
        $tenant = $this->getOrCreateActiveTenant();
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

    public function test_manual_backup_trigger_endpoint(): void
    {
        $response = $this->post('/backup/trigger');
        $response->assertSessionHas('flash.type', 'success');
        $response->assertSessionHas('flash.message', 'Database and assets packaged and sent to nafisa.hostel.system@gmail.com.');
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
