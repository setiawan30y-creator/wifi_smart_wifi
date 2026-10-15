<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Package;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Odp;
use App\Models\DeviceSpecification;
use App\Models\DeviceStock;
use App\Models\Expense;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Session;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Session::put('tenant_id', 1);

        // Create Default SaaS Owner User
        User::factory()->create([
            'tenant_id' => 1,
            'name' => 'Pemilik SaaS',
            'email' => 'admin@rtrw.net',
            'password' => bcrypt('password'),
            'role' => 'superadmin',
            'is_saas_admin' => true,
        ]);

        // Seed default settings
        \App\Models\Setting::set('company_name', 'Billing RT/RW');
        \App\Models\Setting::set('company_address', 'Jl. Raya RT/RW No. 1');
        \App\Models\Setting::set('company_phone', '081234567890');
        \App\Models\Setting::set('company_logo', null);

        $permissions = [
            'admin' => [
                'dashboard' => true,
                'dashboard_card_pppoe_active' => true,
                'dashboard_card_pppoe_offline' => true,
                'dashboard_card_pppoe_disabled' => true,
                'dashboard_card_pppoe_isolated' => true,
                'dashboard_card_traffic_live' => true,
                'dashboard_card_active_customers' => true,
                'dashboard_card_monthly_target' => true,
                'dashboard_card_collected_revenue' => true,
                'dashboard_card_unpaid_amount' => true,
                'dashboard_card_collection_progress' => true,
                'dashboard_card_invoice_generator' => true,
                'dashboard_card_recent_payments' => true,
                'customers' => true,
                'installation_requests' => true,
                'packages' => true,
                'invoices' => true,
                'odps' => true,
                'olts' => true,
                'network_map' => true,
                'device_stocks' => true,
                'expenses' => true,
                'financial_reports' => true,
                'wa_templates' => true,
                'tickets' => true,
                'wallet' => true,
                'ppob' => true,
                'settings' => true
            ],
            'tehnisi' => [
                'dashboard' => true,
                'dashboard_card_pppoe_active' => true,
                'dashboard_card_pppoe_offline' => true,
                'dashboard_card_pppoe_disabled' => true,
                'dashboard_card_pppoe_isolated' => true,
                'dashboard_card_traffic_live' => true,
                'dashboard_card_active_customers' => true,
                'dashboard_card_monthly_target' => true,
                'dashboard_card_collected_revenue' => true,
                'dashboard_card_unpaid_amount' => true,
                'dashboard_card_collection_progress' => true,
                'dashboard_card_invoice_generator' => true,
                'dashboard_card_recent_payments' => true,
                'customers' => true,
                'installation_requests' => true,
                'packages' => false,
                'invoices' => false,
                'odps' => true,
                'olts' => true,
                'network_map' => true,
                'device_stocks' => true,
                'expenses' => false,
                'financial_reports' => false,
                'wa_templates' => false,
                'tickets' => true,
                'wallet' => false,
                'ppob' => false,
                'settings' => false
            ]
        ];
        \App\Models\Setting::set('permissions_map', $permissions);

        // Create Device Stocks
        $ds1 = DeviceStock::create([
            'name' => 'ZTE F640',
            'brand' => 'ZTE',
            'type' => 'FO',
            'stock' => 10,
            'unit_price' => 150000,
            'description' => 'ONT GPON 1GE + 3FE + 1POTS + Wifi'
        ]);

        $ds2 = DeviceStock::create([
            'name' => 'Huawei HG8245H',
            'brand' => 'Huawei',
            'type' => 'FO',
            'stock' => 5,
            'unit_price' => 220000,
            'description' => 'ONT GPON 4GE + 2POTS + Wifi'
        ]);

        $ds3 = DeviceStock::create([
            'name' => 'Tenda',
            'brand' => 'Tenda',
            'type' => 'LAN',
            'stock' => 15,
            'unit_price' => 120000,
            'description' => 'Router AP Wireless N300'
        ]);

        $ds4 = DeviceStock::create([
            'name' => 'Tenda F3',
            'brand' => 'Tenda',
            'type' => 'LAN',
            'stock' => 8,
            'unit_price' => 135000,
            'description' => 'Router AP Wireless 300Mbps'
        ]);

        $ds5 = DeviceStock::create([
            'name' => 'ZTE F609',
            'brand' => 'ZTE',
            'type' => 'FO',
            'stock' => 3,
            'unit_price' => 180000,
            'description' => 'ONT GPON 4FE + 2POTS + Wifi'
        ]);

        // Seed Expenses
        Expense::create([
            'title' => 'Pembelian Router ZTE F640 (10 pcs)',
            'category' => 'Pembelian Perangkat',
            'amount' => 1500000,
            'date' => '2026-05-10',
            'device_stock_id' => $ds1->id,
            'quantity' => 10,
            'description' => 'Stok router FO baru untuk pelanggan Blok A'
        ]);

        Expense::create([
            'title' => 'Pembelian Router Tenda N300 (15 pcs)',
            'category' => 'Pembelian Perangkat',
            'amount' => 1800000,
            'date' => '2026-05-15',
            'device_stock_id' => $ds3->id,
            'quantity' => 15,
            'description' => 'Stok router LAN baru'
        ]);

        Expense::create([
            'title' => 'Sewa Bandwidth Dedicated 100 Mbps',
            'category' => 'Operasional',
            'amount' => 2000000,
            'date' => '2026-06-01',
            'description' => 'Biaya bulanan bandwidth ISP Gateway'
        ]);

        Expense::create([
            'title' => 'Perbaikan Kabel FO Putus ODP-BLOK-A01',
            'category' => 'Perbaikan',
            'amount' => 350000,
            'date' => '2026-06-03',
            'description' => 'Splicing core kabel drop & pembersihan ODP'
        ]);

        // Create Packages
        $p1 = Package::create([
            'name' => 'Paket Basic (10 Mbps)',
            'price' => 150000,
            'speed' => '10 Mbps',
            'description' => 'Paket internet hemat untuk kebutuhan harian rumah tangga.',
        ]);

        $p2 = Package::create([
            'name' => 'Paket Standard (20 Mbps)',
            'price' => 250000,
            'speed' => '20 Mbps',
            'description' => 'Kecepatan stabil untuk bekerja (WFH) dan belajar online.',
        ]);

        $p3 = Package::create([
            'name' => 'Paket Premium (50 Mbps)',
            'price' => 400000,
            'speed' => '50 Mbps',
            'description' => 'Kecepatan tinggi untuk streaming 4K dan online gaming keluarga.',
        ]);

        // Seed Default Device Specifications
        DeviceSpecification::create([
            'device_type' => 'FO',
            'name' => '1/8',
            'label' => 'Splitter 1/8 (8 Port FO)',
        ]);
        DeviceSpecification::create([
            'device_type' => 'FO',
            'name' => '1/16',
            'label' => 'Splitter 1/16 (16 Port FO)',
        ]);
        DeviceSpecification::create([
            'device_type' => 'LAN',
            'name' => '2fo4lan',
            'label' => '2 Port FO & 4 Port LAN',
        ]);
        DeviceSpecification::create([
            'device_type' => 'LAN',
            'name' => '2lan6fo',
            'label' => '6 Port FO & 2 Port LAN',
        ]);
        DeviceSpecification::create([
            'device_type' => 'LAN',
            'name' => '6lan',
            'label' => '6 Port LAN',
        ]);

        // Seed ODPs
        $odp1 = Odp::create([
            'name' => 'ODP-BLOK-A01',
            'fo_ports' => 8,
            'lan_ports' => 0,
            'address' => 'Tiang Listrik depan Rumah Blok A No. 12',
            'description' => 'Splitter 1/8 terpasang. Sisa 3 port kosong.',
            'maps_link' => 'https://maps.google.com/?q=-6.2080,106.8450',
        ]);
        $odp1->devices()->create([
            'device_type' => 'FO',
            'device_spec' => '1/8',
            'fo_ports' => 8,
            'lan_ports' => 0,
        ]);

        $odp2 = Odp::create([
            'name' => 'ODP-BLOK-B05',
            'fo_ports' => 2,
            'lan_ports' => 4,
            'address' => 'Tiang Telepon Blok B pertigaan RT 02',
            'description' => 'Perangkat HTB NetLink 2fo4lan. Port LAN 1-3 terisi.',
            'maps_link' => 'https://maps.google.com/?q=-6.2090,106.8460',
        ]);
        $odp2->devices()->create([
            'device_type' => 'LAN',
            'device_spec' => '2fo4lan',
            'fo_ports' => 2,
            'lan_ports' => 4,
        ]);

        $odp3 = Odp::create([
            'name' => 'ODP-BLOK-C15',
            'fo_ports' => 16,
            'lan_ports' => 0,
            'address' => 'Tiang Listrik utama pintu masuk Blok C',
            'description' => 'Splitter 1/16 utama. Kondisi terisi penuh.',
            'maps_link' => 'https://maps.google.com/?q=-6.2075,106.8440',
        ]);
        $odp3->devices()->create([
            'device_type' => 'FO',
            'device_spec' => '1/16',
            'fo_ports' => 16,
            'lan_ports' => 0,
        ]);

        // Create Customers
        $customers = [
            [
                'package_id' => $p2->id,
                'odp_id' => $odp1->id,
                'name' => 'Budi Santoso',
                'email' => 'budi.santoso@gmail.com',
                'phone' => '081234567890',
                'address' => 'Perum Indah Lestari Blok A/12',
                'maps_link' => 'https://maps.google.com/?q=-6.2082,106.8452',
                'status' => 'active',
                'billing_day' => 5,
                'tanggal_pasang' => '2026-01-10',
                'pppoe_username' => 'pppoe-budi',
                'pppoe_password' => 'budi123',
                'status_rumah' => 'milik_sendiri',
                'jalur' => 'FO',
                'perangkat' => 'ZTE F640',
            ],
            [
                'package_id' => $p1->id,
                'odp_id' => $odp2->id,
                'name' => 'Ani Wijaya',
                'email' => 'ani.wijaya@yahoo.com',
                'phone' => '087788990011',
                'address' => 'Perum Indah Lestari Blok B/4',
                'maps_link' => 'https://maps.google.com/?q=-6.2092,106.8462',
                'status' => 'active',
                'billing_day' => 5,
                'tanggal_pasang' => '2026-02-15',
                'pppoe_username' => 'pppoe-ani',
                'pppoe_password' => 'ani456',
                'status_rumah' => 'ngontrak',
                'pemilik_rumah' => 'H. Sulaiman',
                'jalur' => 'LAN',
                'perangkat' => 'Tenda',
            ],
            [
                'package_id' => $p3->id,
                'odp_id' => $odp3->id,
                'name' => 'Joko Widodo',
                'email' => 'jokowi@gmail.com',
                'phone' => '089999888777',
                'address' => 'Perum Indah Lestari Blok C/1',
                'maps_link' => 'https://maps.google.com/?q=-6.2076,106.8441',
                'status' => 'active',
                'billing_day' => 5,
                'tanggal_pasang' => '2025-11-01',
                'pppoe_username' => 'pppoe-joko',
                'pppoe_password' => 'joko789',
                'status_rumah' => 'milik_sendiri',
                'jalur' => 'FO',
                'perangkat' => 'Huawei HG8245H',
            ],
            [
                'package_id' => $p1->id,
                'odp_id' => $odp1->id,
                'name' => 'Siti Aminah',
                'email' => 'siti.aminah@hotmail.com',
                'phone' => '085222333444',
                'address' => 'Perum Indah Lestari Blok A/18',
                'maps_link' => 'https://maps.google.com/?q=-6.2085,106.8454',
                'status' => 'active',
                'billing_day' => 10,
                'tanggal_pasang' => '2026-03-20',
                'pppoe_username' => 'pppoe-siti',
                'pppoe_password' => 'siti321',
                'status_rumah' => 'milik_sendiri',
            ],
            [
                'package_id' => $p2->id,
                'odp_id' => $odp2->id,
                'name' => 'Ahmad Yani',
                'email' => 'yani.ahmad@gmail.com',
                'phone' => '081344556677',
                'address' => 'Perum Indah Lestari Blok B/10',
                'maps_link' => 'https://maps.google.com/?q=-6.2095,106.8465',
                'status' => 'active',
                'billing_day' => 10,
                'tanggal_pasang' => '2026-04-05',
                'pppoe_username' => 'pppoe-yani',
                'pppoe_password' => 'yani654',
                'status_rumah' => 'ngontrak',
                'pemilik_rumah' => 'Hj. Fatimah',
            ],
            [
                'package_id' => $p1->id,
                'odp_id' => $odp1->id,
                'name' => 'Rina Marlina',
                'email' => 'rina.marlina@outlook.com',
                'phone' => '081122334455',
                'address' => 'Perum Indah Lestari Blok A/5',
                'maps_link' => 'https://maps.google.com/?q=-6.2079,106.8448',
                'status' => 'active',
                'billing_day' => 5,
                'tanggal_pasang' => '2026-05-12',
                'pppoe_username' => 'pppoe-rina',
                'pppoe_password' => 'rina987',
                'status_rumah' => 'milik_sendiri',
            ],
            [
                'package_id' => $p2->id,
                'odp_id' => null,
                'name' => 'Eko Prasetyo',
                'email' => 'eko.prasetyo@gmail.com',
                'phone' => '081299887766',
                'address' => 'Perum Indah Lestari Blok D/7',
                'maps_link' => null,
                'status' => 'inactive',
                'billing_day' => 5,
                'tanggal_pasang' => null,
                'pppoe_username' => null,
                'pppoe_password' => null,
                'status_rumah' => 'milik_sendiri',
            ],
            [
                'package_id' => $p3->id,
                'odp_id' => $odp3->id,
                'name' => 'Dewi Lestari',
                'email' => 'dewi.lestari@gmail.com',
                'phone' => '081377665544',
                'address' => 'Perum Indah Lestari Blok C/15',
                'maps_link' => 'https://maps.google.com/?q=-6.2072,106.8438',
                'status' => 'active',
                'billing_day' => 5,
                'tanggal_pasang' => '2025-08-25',
                'pppoe_username' => 'pppoe-dewi',
                'pppoe_password' => 'dewi888',
                'status_rumah' => 'milik_sendiri',
            ],
        ];

        $createdCustomers = [];
        foreach ($customers as $cData) {
            $createdCustomers[] = Customer::create($cData);
        }

        // Seed Invoices for May 2026 (Last month) - All active customers paid
        foreach ($createdCustomers as $customer) {
            if ($customer->status === 'inactive') {
                continue;
            }
            Invoice::create([
                'customer_id' => $customer->id,
                'month' => 5,
                'year' => 2026,
                'amount' => $customer->package->price,
                'status' => 'paid',
                'paid_at' => Carbon::create(2026, 5, $customer->billing_day)->addHours(mt_rand(1, 48)),
                'payment_method' => mt_rand(0, 1) ? 'Transfer' : 'Cash',
            ]);
        }

        // Seed Invoices for June 2026 (Current month) - Mixed paid/unpaid
        $junePayments = [
            'Budi Santoso' => ['status' => 'paid', 'method' => 'Transfer', 'day_offset' => 0],
            'Ani Wijaya' => ['status' => 'unpaid'],
            'Joko Widodo' => ['status' => 'paid', 'method' => 'Transfer', 'day_offset' => -1],
            'Siti Aminah' => ['status' => 'unpaid'],
            'Ahmad Yani' => ['status' => 'unpaid'],
            'Rina Marlina' => ['status' => 'paid', 'method' => 'Cash', 'day_offset' => 1],
            'Dewi Lestari' => ['status' => 'unpaid'],
        ];

        foreach ($createdCustomers as $customer) {
            if ($customer->status === 'inactive') {
                continue;
            }

            $paymentInfo = $junePayments[$customer->name] ?? ['status' => 'unpaid'];

            if ($paymentInfo['status'] === 'paid') {
                Invoice::create([
                    'customer_id' => $customer->id,
                    'month' => 6,
                    'year' => 2026,
                    'amount' => $customer->package->price,
                    'status' => 'paid',
                    'paid_at' => Carbon::create(2026, 6, $customer->billing_day)->addDays($paymentInfo['day_offset'])->addHours(mt_rand(2, 12)),
                    'payment_method' => $paymentInfo['method'],
                ]);
            } else {
                Invoice::create([
                    'customer_id' => $customer->id,
                    'month' => 6,
                    'year' => 2026,
                    'amount' => $customer->package->price,
                    'status' => 'unpaid',
                    'paid_at' => null,
                    'payment_method' => null,
                ]);
            }
        }

        // Seed WA templates
        $this->call(WaTemplateSeeder::class);
        $this->call(RbacSeeder::class);
    }
}
