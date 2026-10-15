<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ppob_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('api_key')->nullable();
            $table->string('api_secret')->nullable();
            $table->string('endpoint')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('ppob_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('category')->comment('Misal: Pulsa, Data, Token Listrik');
            $table->string('name')->comment('Nama Produk Tampil ke User');
            $table->string('provider')->nullable()->comment('Misal: Telkomsel, Indosat');
            $table->decimal('sell_price', 15, 2)->comment('Harga Jual ke User');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('ppob_supplier_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('ppob_suppliers')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('ppob_products')->onDelete('cascade');
            $table->string('supplier_sku')->comment('Kode Produk Unik dari Suplier');
            $table->decimal('buy_price', 15, 2)->comment('Harga Modal dari Suplier');
            $table->boolean('is_available')->default(true)->comment('Status Produk di Suplier (Gangguan/Tersedia)');
            $table->timestamps();
        });

        Schema::create('ppob_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained('ppob_products');
            $table->foreignId('supplier_id')->nullable()->constrained('ppob_suppliers');
            $table->string('customer_number')->comment('No HP / ID Pelanggan PPOB yang dituju');
            $table->string('supplier_sku')->nullable()->comment('SKU yang dieksekusi saat itu');
            $table->decimal('sell_price', 15, 2);
            $table->decimal('buy_price', 15, 2);
            $table->enum('status', ['pending', 'processing', 'success', 'failed', 'refunded'])->default('pending');
            $table->string('sn')->nullable()->comment('Serial Number / Token / SN Bukti Berhasil');
            $table->string('reference_id')->unique()->comment('Invoice/Transaction ID unik internal');
            $table->string('supplier_reference_id')->nullable()->comment('Transaction ID dari API Suplier');
            $table->text('message')->nullable()->comment('Pesan error/sukses dari suplier');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ppob_transactions');
        Schema::dropIfExists('ppob_supplier_products');
        Schema::dropIfExists('ppob_products');
        Schema::dropIfExists('ppob_suppliers');
    }
};
