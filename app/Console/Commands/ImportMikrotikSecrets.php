<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Router;
use App\Models\Customer;
use App\Models\Package;
use App\Services\MikrotikService;
use RouterOS\Query;

class ImportMikrotikSecrets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mikrotik:import-secrets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import all PPPoE secrets from MikroTik bypassing MTU drops using chunking';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $router = Router::where('status', 'online')->first();
        if (!$router) {
            $this->error('Tidak ada router yang online.');
            return;
        }

        $this->info("Menghubungkan ke MikroTik {$router->ip_address}...");
        
        $mikrotikService = new MikrotikService($router);
        
        // Expose the client to do raw queries
        $client = null;
        try {
            $reflection = new \ReflectionClass($mikrotikService);
            $property = $reflection->getProperty('client');
            $property->setAccessible(true);
            
            // Connect first
            $mikrotikService->getPppoeProfiles(); // dummy call to connect
            $client = $property->getValue($mikrotikService);
        } catch (\Exception $e) {
            $this->error('Gagal koneksi: ' . $e->getMessage());
            return;
        }

        if (!$client) {
            $this->error('Koneksi client kosong.');
            return;
        }

        $chars = array_merge(range('a', 'z'), range('A', 'Z'), range('0', '9'), ['_', '-']);
        $totalImported = 0;

        foreach ($chars as $char) {
            $this->info("Menarik data pelanggan dengan awalan huruf: $char ...");
            
            try {
                // Fetch chunk
                $q = (new Query('/ppp/secret/print'))->where('name', '~', '^' . $char);
                $secrets = $client->query($q)->read();
                
                $count = 0;
                foreach ($secrets as $secret) {
                    if (!isset($secret['name'])) continue;

                    $exists = Customer::where('pppoe_username', $secret['name'])->exists();
                    if (!$exists) {
                        $profileName = $secret['profile'] ?? 'default';
                        $package = Package::firstOrCreate(
                            ['name' => $profileName],
                            ['price' => 0, 'speed' => '0 Mbps', 'description' => 'Otomatis dibuat saat Import MikroTik (CLI)']
                        );

                        Customer::create([
                            'package_id' => $package->id,
                            'name' => $secret['name'],
                            'pppoe_username' => $secret['name'],
                            'pppoe_password' => $secret['password'] ?? '',
                            'status' => (isset($secret['disabled']) && $secret['disabled'] === 'true') ? 'inactive' : 'active',
                            'phone' => '-',
                            'address' => 'Di-import dari MikroTik (CLI)'
                        ]);
                        $count++;
                        $totalImported++;
                    }
                }
                
                if ($count > 0) {
                    $this->info("Berhasil menarik $count akun.");
                }
                
            } catch (\Exception $e) {
                $this->warn("Gagal menarik data untuk huruf $char: " . $e->getMessage());
            }
        }

        $this->info("Selesai! Total akun ditarik: $totalImported");
    }
}
