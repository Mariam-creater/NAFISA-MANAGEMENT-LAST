<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('block_id')->constrained('blocks')->cascadeOnDelete();
            $table->unsignedInteger('floor_number'); // 1 through 6
            $table->string('room_number'); // e.g. "101", "204"
            $table->unsignedBigInteger('base_price'); // UGX
            $table->unsignedBigInteger('custom_price')->nullable(); // Optional override
            $table->enum('status', ['vacant', 'occupied', 'maintenance'])->default('vacant');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['block_id', 'room_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
