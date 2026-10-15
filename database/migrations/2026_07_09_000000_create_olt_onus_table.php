<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('olt_onus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('olt_id')->constrained('olts')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('pon_port')->nullable();
            $table->string('onu_id')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('name')->nullable();
            $table->string('pppoe_username')->nullable();
            $table->string('status')->default('unknown');
            $table->string('pppoe_status')->default('unknown');
            $table->decimal('rx_power', 8, 2)->nullable();
            $table->decimal('tx_power', 8, 2)->nullable();
            $table->unsignedInteger('distance')->nullable();
            $table->string('uptime')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('last_polled_at')->nullable();
            $table->text('last_error')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'olt_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'pppoe_status']);
            $table->index(['tenant_id', 'pppoe_username']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('olt_onus');
    }
};
