<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom untuk pembayaran satu tagihan via Tripay.
 * Migrasi lama "add_payment_fields_to_invoices_table" ternyata kosong, sehingga
 * pembayaran QRIS per tagihan selalu gagal. Hanya menambah kolom.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'payment_ref')) {
                $table->string('payment_ref')->nullable()->index();
            }
            if (!Schema::hasColumn('invoices', 'payment_url')) {
                $table->string('payment_url', 1000)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'payment_url')) {
                $table->dropColumn('payment_url');
            }
            if (Schema::hasColumn('invoices', 'payment_ref')) {
                $table->dropIndex(['payment_ref']);
                $table->dropColumn('payment_ref');
            }
        });
    }
};
