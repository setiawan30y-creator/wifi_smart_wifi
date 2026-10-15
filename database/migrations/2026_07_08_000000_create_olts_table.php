<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('olts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('brand')->default('Generic');
            $table->string('model')->nullable();
            $table->string('host');
            $table->unsignedInteger('port')->default(23);
            $table->string('protocol')->default('telnet');
            $table->string('username')->nullable();
            $table->text('password')->nullable();
            $table->string('snmp_community')->nullable();
            $table->unsignedInteger('snmp_port')->default(161);
            $table->unsignedInteger('pon_ports')->default(0);
            $table->string('status')->default('offline');
            $table->timestamp('last_checked_at')->nullable();
            $table->text('last_error')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'brand']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('olts');
    }
};
