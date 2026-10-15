<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['credit', 'debit'])->comment('Kredit = Masuk, Debit = Keluar');
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2)->default(0)->comment('Saldo Klien setelah transaksi');
            $table->string('reference_id')->nullable()->comment('No Order / No Tiket / ID Pembayaran');
            $table->string('description')->nullable()->comment('Contoh: Topup Tripay, Beli Pulsa Telkomsel, Bayar Tagihan Internet');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
