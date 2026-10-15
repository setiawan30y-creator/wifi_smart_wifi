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
        Schema::create('device_specifications', function (Blueprint $table) {
            $table->id();
            $table->string('device_type'); // FO or LAN
            $table->string('name');        // e.g. "1/8", "2fo4lan"
            $table->string('label');       // e.g. "Splitter 1/8 (8 Port FO)"
            $table->timestamps();
            
            // Add a unique constraint to prevent duplicate specifications per device type
            $table->unique(['device_type', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_specifications');
    }
};
