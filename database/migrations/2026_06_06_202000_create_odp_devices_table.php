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
        Schema::create('odp_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('odp_id')->constrained('odps')->onDelete('cascade');
            $table->string('device_type'); // FO or LAN
            $table->string('device_spec'); // e.g. "1/8", "2fo4lan", etc.
            $table->integer('fo_ports')->default(0);
            $table->integer('lan_ports')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('odp_devices');
    }
};
