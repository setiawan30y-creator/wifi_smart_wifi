<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat pengingat WA per tagihan (sebelum / sesudah jatuh tempo).
 * Mencegah pesan yang sama terkirim dua kali. Tabel baru; tidak mengubah data lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('type', 20);                 // before | after
            $table->smallInteger('offset_days');        // H-3 => 3 (before), H+7 => 7 (after)
            $table->string('status', 20);               // sent | failed
            $table->timestamp('attempted_at');
            $table->string('error', 500)->nullable();
            $table->timestamps();

            $table->unique(['invoice_id', 'type', 'offset_days']);
            $table->index(['tenant_id', 'attempted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_reminders');
    }
};
