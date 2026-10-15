<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'billing_type')) {
                $table->string('billing_type')->default('paid')->after('billing_day');
            }

            if (!Schema::hasColumn('customers', 'billing_note')) {
                $table->string('billing_note')->nullable()->after('billing_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'billing_note')) {
                $table->dropColumn('billing_note');
            }

            if (Schema::hasColumn('customers', 'billing_type')) {
                $table->dropColumn('billing_type');
            }
        });
    }
};
