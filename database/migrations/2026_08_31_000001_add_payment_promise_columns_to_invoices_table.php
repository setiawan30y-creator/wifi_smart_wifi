<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'payment_promise_date')) {
                $table->date('payment_promise_date')->nullable();
            }

            if (!Schema::hasColumn('invoices', 'payment_promise_note')) {
                $table->text('payment_promise_note')->nullable();
            }

            if (!Schema::hasColumn('invoices', 'payment_promise_wa_template_id')) {
                $table->unsignedBigInteger('payment_promise_wa_template_id')->nullable();
            }

            if (!Schema::hasColumn('invoices', 'payment_promise_wa_sent_at')) {
                $table->timestamp('payment_promise_wa_sent_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'payment_promise_wa_sent_at')) {
                $table->dropColumn('payment_promise_wa_sent_at');
            }

            if (Schema::hasColumn('invoices', 'payment_promise_wa_template_id')) {
                $table->dropColumn('payment_promise_wa_template_id');
            }

            if (Schema::hasColumn('invoices', 'payment_promise_note')) {
                $table->dropColumn('payment_promise_note');
            }

            if (Schema::hasColumn('invoices', 'payment_promise_date')) {
                $table->dropColumn('payment_promise_date');
            }
        });
    }
};
