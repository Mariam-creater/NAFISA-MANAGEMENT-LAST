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
        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Block 1, Block 2
            $table->string('code')->unique(); // B1, B2
            $table->text('description')->nullable();
            $table->unsignedInteger('total_floors')->default(6);
            $table->unsignedBigInteger('default_monthly_price'); // UGX: 420000 (Block 1), 1000000 (Block 2)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blocks');
    }
};
