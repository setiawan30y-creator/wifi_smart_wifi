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
        Schema::create('tenant_ppob_markups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained('ppob_products')->onDelete('cascade');
            $table->decimal('markup_amount', 15, 2)->default(0)->comment('Keuntungan/selisih harga untuk penyewa');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // A tenant can only have one markup entry per product
            $table->unique(['tenant_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_ppob_markups');
    }
};
