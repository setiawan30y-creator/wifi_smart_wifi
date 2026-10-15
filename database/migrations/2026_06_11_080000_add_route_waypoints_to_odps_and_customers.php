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
        Schema::table('odps', function (Blueprint $table) {
            $table->json('route_waypoints')->nullable()->after('longitude');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->json('route_waypoints')->nullable()->after('longitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('odps', function (Blueprint $table) {
            $table->dropColumn('route_waypoints');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('route_waypoints');
        });
    }
};
