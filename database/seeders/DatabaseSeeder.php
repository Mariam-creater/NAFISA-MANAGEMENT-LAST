<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Block;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\Payment;
use App\Models\NotificationLog;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Blocks
        $block1 = Block::create([
            'name' => 'Block A',
            'code' => 'A',
            'description' => 'Single Room with attached modern toilet & private balcony.',
            'total_floors' => 6,
            'default_monthly_price' => 420000, // 420,000 UGX/month
        ]);

        $block2 = Block::create([
            'name' => 'Block B',
            'code' => 'B',
            'description' => 'Spacious 1-Bedroom suite with separate living area, kitchenette & balcony.',
            'total_floors' => 6,
            'default_monthly_price' => 1000000, // 1,000,000 UGX/month
        ]);

        // 2. Generate Rooms for Block 1 (6 floors x 6 rooms = 36 rooms)
        $b1Rooms = [];
        for ($floor = 1; $floor <= 6; $floor++) {
            for ($r = 1; $r <= 6; $r++) {
                $roomNumber = sprintf('%d0%d', $floor, $r);
                $b1Rooms[] = Room::create([
                    'block_id' => $block1->id,
                    'floor_number' => $floor,
                    'room_number' => $roomNumber,
                    'base_price' => 420000,
                    'custom_price' => null,
                    'status' => 'vacant',
                    'notes' => 'Fitted wardrobe, study desk, private balcony.',
                ]);
            }
        }

        // Generate Rooms for Block 2 (6 floors x 4 rooms = 24 rooms)
        $b2Rooms = [];
        for ($floor = 1; $floor <= 6; $floor++) {
            for ($r = 1; $r <= 4; $r++) {
                $roomNumber = sprintf('%d0%d', $floor, $r);
                $b2Rooms[] = Room::create([
                    'block_id' => $block2->id,
                    'floor_number' => $floor,
                    'room_number' => $roomNumber,
                    'base_price' => 1000000,
                    'custom_price' => null,
                    'status' => 'vacant',
                    'notes' => 'Furnished living room, kitchenette with marble counter, modern bathroom.',
                ]);
            }
        }

        // Set one or two rooms to maintenance
        $b1Rooms[10]->update(['status' => 'maintenance', 'notes' => 'Plumbing refurbishment in progress.']);
        $b2Rooms[5]->update(['status' => 'maintenance', 'notes' => 'Repainting and air-conditioning maintenance.']);

        // 3. Create Sample Tenants
        $sampleTenantsData = [
            [
                'full_name' => 'Nakato Sarah Nabukalu',
                'age' => 22,
                'email' => 'sarah.nakato@example.com',
                'whatsapp_number' => '+256772123456',
                'occupation' => 'student',
                'emergency_name' => 'Hajjat Fatuma Nabukalu',
                'emergency_phone' => '+256782998877',
                'emergency_relationship' => 'Mother',
                'room' => $b1Rooms[0], // Rm 101
                'check_in' => Carbon::now()->subMonths(3)->startOfDay(),
                'rent_due' => Carbon::now()->addDays(2)->startOfDay(), // Due in 2 days (Pink Pulse!)
            ],
            [
                'full_name' => 'Kato Emmanuel Ssenyonga',
                'age' => 25,
                'email' => 'emmanuel.kato@example.com',
                'whatsapp_number' => '+256701987654',
                'occupation' => 'employed',
                'emergency_name' => 'Paul Ssenyonga',
                'emergency_phone' => '+256772554433',
                'emergency_relationship' => 'Brother',
                'room' => $b2Rooms[0], // Rm 101 Block 2
                'check_in' => Carbon::now()->subMonths(2)->startOfDay(),
                'rent_due' => Carbon::now()->addDays(3)->startOfDay(), // Due in 3 days (Pink Pulse!)
            ],
            [
                'full_name' => 'Namubiru Fiona Brenda',
                'age' => 24,
                'email' => 'fiona.namubiru@example.com',
                'whatsapp_number' => '+256755443322',
                'occupation' => 'self_employed',
                'emergency_name' => 'Grace Namubiru',
                'emergency_phone' => '+256700112233',
                'emergency_relationship' => 'Sister',
                'room' => $b1Rooms[1], // Rm 102
                'check_in' => Carbon::now()->subMonths(5)->startOfDay(),
                'rent_due' => Carbon::now()->subDays(2)->startOfDay(), // Overdue by 2 days!
            ],
            [
                'full_name' => 'Mugisha Brian Kwesiga',
                'age' => 27,
                'email' => 'brian.mugisha@example.com',
                'whatsapp_number' => '+256788665544',
                'occupation' => 'employed',
                'emergency_name' => 'Arthur Kwesiga',
                'emergency_phone' => '+256779887766',
                'emergency_relationship' => 'Father',
                'room' => $b2Rooms[1], // Rm 102 Block 2
                'check_in' => Carbon::now()->subMonths(6)->startOfDay(),
                'rent_due' => Carbon::now()->addDays(15)->startOfDay(),
            ],
            [
                'full_name' => 'Babirye Grace Nalwanga',
                'age' => 21,
                'email' => 'grace.babirye@example.com',
                'whatsapp_number' => '+256703112244',
                'occupation' => 'student',
                'emergency_name' => 'Florence Nalwanga',
                'emergency_phone' => '+256751223344',
                'emergency_relationship' => 'Aunt',
                'room' => $b1Rooms[2], // Rm 103
                'check_in' => Carbon::now()->subMonths(1)->startOfDay(),
                'rent_due' => Carbon::now()->addDays(20)->startOfDay(),
            ],
            [
                'full_name' => 'Kigozi David Mukasa',
                'age' => 29,
                'email' => 'david.kigozi@example.com',
                'whatsapp_number' => '+256782334455',
                'occupation' => 'employed',
                'emergency_name' => 'Joshua Mukasa',
                'emergency_phone' => '+256771009988',
                'emergency_relationship' => 'Father',
                'room' => $b2Rooms[2], // Rm 103 Block 2
                'check_in' => Carbon::now()->subMonths(4)->startOfDay(),
                'rent_due' => Carbon::now()->addDays(25)->startOfDay(),
            ],
            [
                'full_name' => 'Akello Patricia Okello',
                'age' => 23,
                'email' => 'patricia.akello@example.com',
                'whatsapp_number' => '+256752991122',
                'occupation' => 'student',
                'emergency_name' => 'Dr. James Okello',
                'emergency_phone' => '+256782001122',
                'emergency_relationship' => 'Father',
                'room' => $b1Rooms[3], // Rm 104
                'check_in' => Carbon::now()->subMonths(2)->startOfDay(),
                'rent_due' => Carbon::now()->addDays(5)->startOfDay(),
            ],
        ];

        $serialCounter = 1;
        $year = date('Y');

        foreach ($sampleTenantsData as $data) {
            $room = $data['room'];
            $room->update(['status' => 'occupied']);

            $tenant = Tenant::create([
                'room_id' => $room->id,
                'full_name' => $data['full_name'],
                'age' => $data['age'],
                'email' => $data['email'],
                'whatsapp_number' => $data['whatsapp_number'],
                'occupation' => $data['occupation'],
                'emergency_name' => $data['emergency_name'],
                'emergency_phone' => $data['emergency_phone'],
                'emergency_relationship' => $data['emergency_relationship'],
                'check_in_date' => $data['check_in']->toDateString(),
                'rent_due_date' => $data['rent_due']->toDateString(),
                'status' => 'active',
            ]);

            // Add previous payments
            $rate = $room->effective_price;
            Payment::create([
                'tenant_id' => $tenant->id,
                'room_id' => $room->id,
                'receipt_serial_no' => sprintf('NFH-%s-%04d', $year, $serialCounter++),
                'amount_received' => $rate,
                'base_rent_charged' => $rate,
                'tax_amount' => 0,
                'total_due' => $rate,
                'payment_method' => $serialCounter % 2 === 0 ? 'mobile_money' : 'bank_transfer',
                'payment_date' => Carbon::now()->subDays(28)->toDateString(),
                'notes' => 'Rent payment for previous cycle.',
            ]);

            // For some tenants, add current month payment
            if ($data['rent_due'] > Carbon::now()->addDays(10)) {
                Payment::create([
                    'tenant_id' => $tenant->id,
                    'room_id' => $room->id,
                    'receipt_serial_no' => sprintf('NFH-%s-%04d', $year, $serialCounter++),
                    'amount_received' => $rate,
                    'base_rent_charged' => $rate,
                    'tax_amount' => 0,
                    'total_due' => $rate,
                    'payment_method' => 'cash',
                    'payment_date' => Carbon::now()->subDays(5)->toDateString(),
                    'notes' => 'Advance monthly rent settlement.',
                ]);
            }
        }

        // Add 1 soft-deleted tenant to demonstrate the 1-Year Retention Archive
        $archivedTenant = Tenant::create([
            'room_id' => null,
            'full_name' => 'Wasswa Derrick Kalema',
            'age' => 26,
            'email' => 'derrick.wasswa@example.com',
            'whatsapp_number' => '+256704998877',
            'occupation' => 'employed',
            'emergency_name' => 'Miriam Kalema',
            'emergency_phone' => '+256772118899',
            'emergency_relationship' => 'Mother',
            'check_in_date' => Carbon::now()->subMonths(10)->toDateString(),
            'rent_due_date' => Carbon::now()->subMonths(1)->toDateString(),
            'status' => 'inactive',
            'deleted_at' => Carbon::now()->subDays(30), // checked out 30 days ago
        ]);

        Payment::create([
            'tenant_id' => $archivedTenant->id,
            'room_id' => $b1Rooms[5]->id,
            'receipt_serial_no' => sprintf('NFH-%s-%04d', $year, $serialCounter++),
            'amount_received' => 420000,
            'base_rent_charged' => 420000,
            'tax_amount' => 0,
            'total_due' => 420000,
            'payment_method' => 'cash',
            'payment_date' => Carbon::now()->subMonths(2)->toDateString(),
            'notes' => 'Final checkout month payment.',
        ]);
    }
}
