<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify existing ENUM column to include pending_verification
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE saas_invoices MODIFY COLUMN status ENUM('unpaid', 'pending_verification', 'paid', 'cancelled') DEFAULT 'unpaid'");
        }

        Schema::table('saas_invoices', function (Blueprint $table) {
            $table->string('payment_proof')->nullable()->after('payment_method');
            $table->string('tripay_reference')->nullable()->after('payment_proof');
            $table->string('checkout_url')->nullable()->after('tripay_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saas_invoices', function (Blueprint $table) {
            $table->dropColumn(['payment_proof', 'tripay_reference', 'checkout_url']);
        });
        
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE saas_invoices MODIFY COLUMN status ENUM('unpaid', 'paid', 'cancelled') DEFAULT 'unpaid'");
        }
    }
};
