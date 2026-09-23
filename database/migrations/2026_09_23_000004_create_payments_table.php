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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->string('receipt_serial_no')->unique(); // e.g. NFH-2026-0001
            $table->unsignedBigInteger('amount_received'); // UGX
            $table->unsignedBigInteger('base_rent_charged'); // UGX
            $table->unsignedBigInteger('tax_amount')->default(0); // UGX
            $table->unsignedBigInteger('total_due'); // UGX
            $table->enum('payment_method', ['cash', 'bank_transfer', 'mobile_money'])->default('cash');
            $table->date('payment_date');
            $table->string('pdf_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
