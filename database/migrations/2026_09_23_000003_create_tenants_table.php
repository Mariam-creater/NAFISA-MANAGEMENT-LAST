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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->string('full_name');
            $table->unsignedInteger('age');
            $table->string('email');
            $table->string('whatsapp_number');
            $table->enum('occupation', ['student', 'employed', 'self_employed', 'unemployed'])->default('student');
            $table->string('avatar_path')->nullable();
            $table->string('id_document_path')->nullable();
            
            // Emergency Contact
            $table->string('emergency_name');
            $table->string('emergency_phone');
            $table->string('emergency_relationship');

            // Lease & Allocation
            $table->date('check_in_date');
            $table->date('rent_due_date');
            $table->enum('status', ['active', 'inactive'])->default('active');
            
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
