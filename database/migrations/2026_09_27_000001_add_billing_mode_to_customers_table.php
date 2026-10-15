<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('customers', 'billing_mode')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('billing_mode', 20)->default('postpaid')->after('billing_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('customers', 'billing_mode')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('billing_mode');
            });
        }
    }
};
