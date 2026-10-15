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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category'); // Pembelian Perangkat | Operasional | Gaji/Honor | Perbaikan | Lain-lain
            $table->bigInteger('amount');
            $table->date('date');
            $table->foreignId('device_stock_id')->nullable()->constrained('device_stocks')->onDelete('set null');
            $table->integer('quantity')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
