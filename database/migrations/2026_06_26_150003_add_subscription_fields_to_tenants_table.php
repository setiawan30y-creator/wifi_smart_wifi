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
        Schema::table('tenants', function (Blueprint $table) {
            $table->enum('subscription_status', ['trial', 'active', 'expired', 'suspended'])->default('trial')->after('status')->comment('Status Berlangganan SaaS');
            $table->timestamp('trial_ends_at')->nullable()->after('subscription_status')->comment('Tanggal berakhirnya masa gratis (14 hari)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['subscription_status', 'trial_ends_at']);
        });
    }
};
