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
        // Add balance to tenants
        Schema::table('tenants', function (Blueprint $table) {
            $table->decimal('balance', 15, 2)->default(0)->after('status');
        });

        // Remove tenant_id from suppliers
        Schema::table('ppob_suppliers', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        // Remove tenant_id from products
        Schema::table('ppob_products', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        // Add profit tracking to transactions
        Schema::table('ppob_transactions', function (Blueprint $table) {
            $table->decimal('saas_profit', 15, 2)->default(0)->after('buy_price');
            $table->decimal('tenant_profit', 15, 2)->default(0)->after('saas_profit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ppob_transactions', function (Blueprint $table) {
            $table->dropColumn(['saas_profit', 'tenant_profit']);
        });

        Schema::table('ppob_products', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
        });

        Schema::table('ppob_suppliers', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('balance');
        });
    }
};
