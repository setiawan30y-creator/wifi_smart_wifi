<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan kolom tenant_id pada tabel yang sebelumnya belum terikat tenant.
 * Hanya MENAMBAH kolom dan MENGISI nilainya; tidak ada data yang dihapus.
 *
 * - routers            : diisi tenant utama (id terkecil) karena sebelumnya dipakai bersama.
 * - wallet_transactions: diisi dari tenant pelanggan pemilik transaksi.
 * - ticket_replies     : diisi dari tenant tiket induknya.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['routers', 'wallet_transactions', 'ticket_replies'] as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'tenant_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->cascadeOnDelete();
                    $table->index('tenant_id');
                });
            }
        }

        $defaultTenantId = DB::table('tenants')->min('id');
        if ($defaultTenantId) {
            DB::table('routers')->whereNull('tenant_id')->update(['tenant_id' => $defaultTenantId]);
        }

        DB::table('wallet_transactions')
            ->whereNull('tenant_id')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                $tenantByCustomer = DB::table('customers')
                    ->whereIn('id', $rows->pluck('customer_id')->unique())
                    ->pluck('tenant_id', 'id');

                foreach ($rows as $row) {
                    DB::table('wallet_transactions')->where('id', $row->id)
                        ->update(['tenant_id' => $tenantByCustomer[$row->customer_id] ?? null]);
                }
            });

        DB::table('ticket_replies')
            ->whereNull('tenant_id')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                $tenantByTicket = DB::table('tickets')
                    ->whereIn('id', $rows->pluck('ticket_id')->unique())
                    ->pluck('tenant_id', 'id');

                foreach ($rows as $row) {
                    DB::table('ticket_replies')->where('id', $row->id)
                        ->update(['tenant_id' => $tenantByTicket[$row->ticket_id] ?? null]);
                }
            });
    }

    public function down(): void
    {
        foreach (['routers', 'wallet_transactions', 'ticket_replies'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'tenant_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropConstrainedForeignId('tenant_id');
                });
            }
        }
    }
};
