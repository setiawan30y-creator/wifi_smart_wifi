<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'overdue_reminder_count')) {
                $column = $table->unsignedTinyInteger('overdue_reminder_count')->default(0);

                if (Schema::hasColumn('invoices', 'payment_proof')) {
                    $column->after('payment_proof');
                }
            }

            if (!Schema::hasColumn('invoices', 'last_overdue_reminder_sent_at')) {
                $column = $table->timestamp('last_overdue_reminder_sent_at')->nullable();

                if (Schema::hasColumn('invoices', 'overdue_reminder_count')) {
                    $column->after('overdue_reminder_count');
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'last_overdue_reminder_sent_at')) {
                $table->dropColumn('last_overdue_reminder_sent_at');
            }

            if (Schema::hasColumn('invoices', 'overdue_reminder_count')) {
                $table->dropColumn('overdue_reminder_count');
            }
        });
    }
};
