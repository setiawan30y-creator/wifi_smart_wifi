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
            $table->decimal('latitude', 10, 8)->nullable()->after('maps_link');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('odp_id')->nullable()->after('package_id')->constrained('odps')->onDelete('set null');
            $table->string('maps_link', 500)->nullable()->after('address');
            $table->decimal('latitude', 10, 8)->nullable()->after('maps_link');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['odp_id']);
            $table->dropColumn(['odp_id', 'maps_link', 'latitude', 'longitude']);
        });

        Schema::table('odps', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
