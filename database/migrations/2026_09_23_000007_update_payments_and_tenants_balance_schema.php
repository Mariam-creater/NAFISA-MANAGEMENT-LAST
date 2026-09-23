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
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'balance_remaining')) {
                $table->unsignedBigInteger('balance_remaining')->default(0)->after('amount_received');
            }
            if (!Schema::hasColumn('payments', 'payment_status')) {
                $table->string('payment_status', 30)->default('paid')->after('balance_remaining'); // 'paid', 'partial', 'overpaid'
            }
        });

        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'current_balance')) {
                $table->bigInteger('current_balance')->default(0)->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'balance_remaining')) {
                $table->dropColumn('balance_remaining');
            }
            if (Schema::hasColumn('payments', 'payment_status')) {
                $table->dropColumn('payment_status');
            }
        });

        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'current_balance')) {
                $table->dropColumn('current_balance');
            }
        });
    }
};
