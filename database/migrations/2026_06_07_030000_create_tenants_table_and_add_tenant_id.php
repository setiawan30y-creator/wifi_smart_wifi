<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create tenants table
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('domain')->nullable();
            $table->string('status')->default('active'); // active, suspended
            $table->timestamps();
        });

        // 2. Insert Default Tenant
        DB::table('tenants')->insert([
            'id' => 1,
            'name' => 'Sistem Pusat (Default)',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Add tenant_id to tables
        $tables = [
            'users', 'customers', 'packages', 'invoices', 
            'odps', 'odp_devices', 'device_stocks', 'expenses', 
            'wa_templates', 'settings'
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (!Schema::hasColumn($tableName, 'tenant_id')) {
                        $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                    }
                });
                
                // Set all existing records to tenant_id = 1
                DB::table($tableName)->update(['tenant_id' => 1]);

                // Make tenant_id non-nullable and add foreign key
                Schema::table($tableName, function (Blueprint $table) {
                    // It's safer to not enforce strict foreign keys if there's an issue, but let's try
                    $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
                });
            }
        }

        // 4. Add is_saas_admin to users
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_saas_admin')->default(false)->after('role');
        });

        // Set user 1 as SaaS Admin
        DB::table('users')->where('id', 1)->update(['is_saas_admin' => true]);

        // 5. Update settings unique constraint
        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique(['key']);
            $table->unique(['tenant_id', 'key']);
        });
        
        // 6. Update users email unique constraint
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->unique(['tenant_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'email']);
            $table->unique(['email']);
            $table->dropColumn('is_saas_admin');
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'key']);
            $table->unique(['key']);
        });

        $tables = [
            'users', 'customers', 'packages', 'invoices', 
            'odps', 'odp_devices', 'device_stocks', 'expenses', 
            'wa_templates', 'settings'
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'tenant_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['tenant_id']);
                    $table->dropColumn('tenant_id');
                });
            }
        }

        Schema::dropIfExists('tenants');
    }
};
