<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update Blocks table explicitly by ID and exact names/codes
        DB::table('blocks')
            ->where('id', 1)
            ->orWhere('name', 'Block A')
            ->orWhere('name', 'Block 1')
            ->orWhere('code', 'A')
            ->orWhere('code', 'B1')
            ->update([
                'name' => 'Block A',
                'code' => 'A',
                'default_monthly_price' => 420000,
            ]);

        DB::table('blocks')
            ->where('id', 2)
            ->orWhere('name', 'Block B')
            ->orWhere('name', 'Block 2')
            ->orWhere('code', 'B')
            ->orWhere('code', 'B2')
            ->update([
                'name' => 'Block B',
                'code' => 'B',
                'default_monthly_price' => 1000000,
            ]);

        // 2. Fetch verified block IDs
        $block1Id = DB::table('blocks')
            ->where('code', 'A')
            ->orWhere('name', 'Block A')
            ->orWhere('id', 1)
            ->value('id') ?? 1;

        $block2Id = DB::table('blocks')
            ->where('code', 'B')
            ->orWhere('name', 'Block B')
            ->orWhere('id', 2)
            ->value('id') ?? 2;

        // 3. Update Rooms base_price
        DB::table('rooms')
            ->where('block_id', $block1Id)
            ->update([
                'base_price' => 420000,
            ]);

        DB::table('rooms')
            ->where('block_id', $block2Id)
            ->update([
                'base_price' => 1000000,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
