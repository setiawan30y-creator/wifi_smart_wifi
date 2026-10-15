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
        Schema::table('customers', function (Blueprint $table) {
            $table->date('tanggal_pasang')->nullable()->after('billing_day');
            $table->string('pppoe_username')->nullable()->after('tanggal_pasang');
            $table->string('pppoe_password')->nullable()->after('pppoe_username');
            $table->string('status_rumah')->default('milik_sendiri')->after('pppoe_password'); // milik_sendiri | ngontrak
            $table->string('pemilik_rumah')->nullable()->after('status_rumah');
            $table->string('foto_ktp')->nullable()->after('pemilik_rumah');
            $table->string('foto_rumah')->nullable()->after('foto_ktp');
            $table->string('foto_perangkat')->nullable()->after('foto_rumah');
            $table->string('foto_odp')->nullable()->after('foto_perangkat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'tanggal_pasang',
                'pppoe_username',
                'pppoe_password',
                'status_rumah',
                'pemilik_rumah',
                'foto_ktp',
                'foto_rumah',
                'foto_perangkat',
                'foto_odp'
            ]);
        });
    }
};
