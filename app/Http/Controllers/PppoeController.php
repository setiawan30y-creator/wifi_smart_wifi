<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Package;
use App\Models\Router;
use App\Services\MikrotikService;
use Illuminate\Http\Request;

class PppoeController extends Controller
{
    public function index(Request $request)
    {
        $router = Router::where('status', 'online')->first();
        
        $query = Customer::whereNotNull('pppoe_username')->with(['package', 'odp']);

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function($q) use ($search) {
                $q->where('pppoe_username', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhereHas('odp', function ($odpQuery) use ($search) {
                      $odpQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        // Sorting
        $sort = $request->query('sort', 'newest');
        if ($sort === 'az') {
            $query->orderBy('pppoe_username', 'asc');
        } elseif ($sort === 'za') {
            $query->orderBy('pppoe_username', 'desc');
        } else {
            $query->orderBy('id', 'desc'); // newest
        }
        
        $customers = $query->get();
        $activeConnections = [];

        if ($router) {
            $mikrotikService = new MikrotikService($router);
            try {
                // Pass the usernames to fetch active status ONLY for current displayed list
                // This makes the query extremely fast and avoids stream timeout!
                $usernames = $customers->pluck('pppoe_username')->filter()->values()->toArray();
                $activeList = $mikrotikService->getActiveConnections($usernames);
                
                foreach ($activeList as $act) {
                    if (isset($act['name'])) {
                        $activeConnections[trim($act['name'])] = $act;
                    }
                }
            } catch (\Exception $e) {
                \Log::error('MikroTik Get Active Connections Error: ' . $e->getMessage());
                session()->flash('warning', 'Gagal memuat status live (Online/Offline) dari MikroTik. Halaman ini hanya menampilkan data dari database lokal.');
            }
        }
        
        // Filtering
        $filter = $request->query('filter', 'all');
        if ($filter !== 'all') {
            $customers = $customers->filter(function($customer) use ($filter, $activeConnections) {
                $isActive = isset($activeConnections[trim((string) $customer->pppoe_username)]);
                if ($filter === 'online') return $isActive;
                if ($filter === 'offline') return !$isActive && $customer->status === 'active';
                if ($filter === 'disabled') return $customer->status !== 'active';
                // Package filter (e.g., pkg_1)
                if (str_starts_with($filter, 'pkg_')) {
                    return $customer->package_id == substr($filter, 4);
                }
                return true;
            });
        }

        if (in_array($sort, ['online_new', 'online_old'], true)) {
            $customers = $customers
                ->sortBy(function ($customer) use ($activeConnections, $sort) {
                    $connection = $activeConnections[trim((string) $customer->pppoe_username)] ?? null;

                    if (!$connection) {
                        return PHP_INT_MAX;
                    }

                    $uptimeSeconds = $this->parseRouterOsDurationToSeconds($connection['uptime'] ?? '');

                    return $sort === 'online_new' ? $uptimeSeconds : -$uptimeSeconds;
                })
                ->values();
        }

        $totalCustomers = $customers->count();

        // Grouping
        $group = $request->query('group', 'none');
        if ($group === 'package') {
            $customers = $customers->groupBy(function($c) {
                return $c->package ? $c->package->name : 'Tanpa Paket';
            });
        } elseif ($group === 'odp') {
            $customers = $customers->groupBy(function($c) {
                return $c->odp ? $c->odp->name : 'Tanpa ODP';
            });
        }
        
        $packages = Package::all();

        return view('pppoe.index', compact('customers', 'totalCustomers', 'activeConnections', 'router', 'sort', 'filter', 'group', 'packages'));
    }

    private function parseRouterOsDurationToSeconds(?string $duration): int
    {
        $duration = trim((string) $duration);

        if ($duration === '') {
            return 0;
        }

        $seconds = 0;

        if (preg_match_all('/(\d+)(w|d|h|m|s)/', $duration, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $value = (int) $match[1];
                $seconds += match ($match[2]) {
                    'w' => $value * 604800,
                    'd' => $value * 86400,
                    'h' => $value * 3600,
                    'm' => $value * 60,
                    's' => $value,
                    default => 0,
                };
            }

            return $seconds;
        }

        if (preg_match('/(?:(\d+)d)?(\d{1,2}):(\d{2}):(\d{2})$/', $duration, $clockMatch)) {
            $seconds += ((int) ($clockMatch[1] ?? 0)) * 86400;
            $seconds += ((int) $clockMatch[2]) * 3600;
            $seconds += ((int) $clockMatch[3]) * 60;
            $seconds += (int) $clockMatch[4];
        }

        return $seconds;
    }

    public function import(Request $request)
    {
        // This is the direct API import which might timeout for large data
        $router = Router::where('status', 'online')->first();
        if (!$router) {
            return redirect()->route('pppoe.index')->with('error', 'Tidak ada router yang online.');
        }

        $mikrotikService = new MikrotikService($router);
        
        try {
            $secrets = $mikrotikService->getPppoeSecrets();
        } catch (\Exception $e) {
            return redirect()->route('pppoe.index')->with('error', 'Gagal menarik data dari MikroTik karena Koneksi Timeout atau Terputus. Silakan gunakan fitur "Import dari File .rsc" sebagai alternatif.');
        }
        
        $importedCount = 0;

        foreach ($secrets as $secret) {
            if (!isset($secret['name'])) continue;

            // Check if customer already exists in database with this pppoe username
            $exists = Customer::where('pppoe_username', $secret['name'])->exists();
            
            if (!$exists) {
                $profileName = $secret['profile'] ?? 'default';
                $package = Package::firstOrCreate(
                    ['name' => $profileName],
                    ['price' => 0, 'speed' => '0 Mbps', 'description' => 'Otomatis dibuat saat Import MikroTik']
                );

                $linkableCustomer = $this->findLinkableCustomerForSecret($secret['name']);
                $pppoeData = [
                    'package_id' => $package->id,
                    'pppoe_username' => $secret['name'],
                    'pppoe_password' => $secret['password'] ?? '',
                    'status' => (isset($secret['disabled']) && $secret['disabled'] === 'true') ? 'inactive' : 'active',
                ];

                if ($linkableCustomer) {
                    $linkableCustomer->update($pppoeData);
                } else {
                    Customer::create($pppoeData + [
                        'name' => $secret['name'],
                        'phone' => '-',
                        'address' => 'Di-import dari MikroTik'
                    ]);
                }
                $importedCount++;
            }
        }

        return redirect()->route('pppoe.index')->with('success', "Berhasil menarik $importedCount data pelanggan dari MikroTik.");
    }

    public function importFile(Request $request)
    {
        $request->validate([
            'rsc_file' => 'required|file|max:2048' // max 2MB
        ]);

        $content = file_get_contents($request->file('rsc_file')->getRealPath());
        $lines = explode("\n", $content);
        
        $importedCount = 0;
        
        foreach ($lines as $line) {
            $line = trim($line);
            // We only care about 'add name=...' lines which belong to /ppp secret
            if (str_starts_with($line, 'add ') && str_contains($line, 'name=')) {
                
                // Simple parser using regex
                preg_match('/name=(?:"([^"]+)"|([^\s]+))/', $line, $nameMatch);
                preg_match('/password=(?:"([^"]+)"|([^\s]+))/', $line, $passMatch);
                preg_match('/profile=(?:"([^"]+)"|([^\s]+))/', $line, $profMatch);
                preg_match('/disabled=(yes|no)/', $line, $disMatch);
                
                $name = (!empty($nameMatch[1])) ? $nameMatch[1] : ($nameMatch[2] ?? null);
                $password = (!empty($passMatch[1])) ? $passMatch[1] : ($passMatch[2] ?? '');
                $profile = (!empty($profMatch[1])) ? $profMatch[1] : ($profMatch[2] ?? 'default');
                $disabled = ($disMatch[1] ?? 'no') === 'yes';
                
                if (!$name) continue;

                $exists = Customer::where('pppoe_username', $name)->exists();
                
                if (!$exists) {
                    $package = Package::firstOrCreate(
                        ['name' => $profile],
                        ['price' => 0, 'speed' => '0 Mbps', 'description' => 'Otomatis dibuat saat Import File .rsc']
                    );

                    $linkableCustomer = $this->findLinkableCustomerForSecret($name);
                    $pppoeData = [
                        'package_id' => $package->id,
                        'pppoe_username' => $name,
                        'pppoe_password' => $password,
                        'status' => $disabled ? 'inactive' : 'active',
                    ];

                    if ($linkableCustomer) {
                        $linkableCustomer->update($pppoeData);
                    } else {
                        Customer::create($pppoeData + [
                            'name' => $name,
                            'phone' => '-',
                            'address' => 'Di-import dari File .rsc MikroTik'
                        ]);
                    }
                    $importedCount++;
                }
            }
        }

        return redirect()->route('pppoe.index')->with('success', "Berhasil membaca file dan meng-import $importedCount data pelanggan.");
    }

    public function create()
    {
        $router = Router::where('status', 'online')->first();
        
        if ($router) {
            $mikrotikService = new MikrotikService($router);
            $profiles = $mikrotikService->getPppoeProfiles();
            
            // Auto-sync MikroTik profiles to Packages
            foreach ($profiles as $profile) {
                if (isset($profile['name'])) {
                    Package::firstOrCreate(
                        ['name' => $profile['name']],
                        ['price' => 0, 'speed' => '0 Mbps', 'description' => 'Otomatis dibuat dari Profil MikroTik']
                    );
                }
            }
        }
        
        $packages = Package::all();
        $availableCustomers = Customer::where(function ($query) {
                $query->whereNull('pppoe_username')
                    ->orWhere('pppoe_username', '');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'address']);

        return view('pppoe.create', compact('router', 'packages', 'availableCustomers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:customers,pppoe_username',
            'password' => 'required|string',
            'package_id' => 'required|exists:packages,id',
            'service' => 'nullable|string',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        $package = Package::findOrFail($validated['package_id']);
        $profile = $package->name;
        $service = $validated['service'] ?? 'pppoe';
        $targetCustomer = null;

        if (!empty($validated['customer_id'])) {
            $targetCustomer = Customer::findOrFail($validated['customer_id']);
            if (filled($targetCustomer->pppoe_username)) {
                return back()->withInput()->with('error', 'Pelanggan yang dipilih sudah memiliki username PPPoE.');
            }
        }

        $router = Router::where('status', 'online')->first();
        if (!$router) {
            return redirect()->route('pppoe.index')->with('error', 'Router sedang offline. Tidak dapat menambahkan rahasia PPPoE.');
        }

        // Add to MikroTik
        $mikrotikService = new MikrotikService($router);
        
        try {
            $mikrotikService->addPppoeSecret($validated['name'], $validated['password'], $profile, $service);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            // If profile does not exist, try creating it automatically first
            if (str_contains($msg, 'input does not match any value of profile')) {
                // Parse speed to rate limit
                $rateLimit = null;
                if ($package->speed) {
                    preg_match('/(\d+)/', $package->speed, $matches);
                    if (isset($matches[1])) {
                        $num = $matches[1];
                        $rateLimit = stripos($package->speed, 'kb') !== false ? $num . 'k/' . $num . 'k' : $num . 'M/' . $num . 'M';
                    }
                }
                
                // Create profile
                $profileCreated = $mikrotikService->addPppoeProfile($profile, $rateLimit);
                
                if ($profileCreated) {
                    // Try adding secret again
                    try {
                        $mikrotikService->addPppoeSecret($validated['name'], $validated['password'], $profile, $service);
                    } catch (\Exception $e2) {
                        return back()->with('error', 'Gagal membuat akun di MikroTik setelah profil dibuat otomatis: ' . $e2->getMessage());
                    }
                } else {
                    return back()->with('error', "Profil '$profile' tidak ditemukan di MikroTik dan gagal dibuat secara otomatis!");
                }
            } else {
                return back()->with('error', 'Gagal membuat akun di MikroTik: ' . $msg);
            }
        }
        
        if ($targetCustomer) {
            $targetCustomer->update([
                'package_id' => $package->id,
                'pppoe_username' => $validated['name'],
                'pppoe_password' => $validated['password'],
                'status' => 'active',
            ]);

            return redirect()->route('pppoe.index')->with('success', 'Akun PPPoE berhasil dibuat dan dihubungkan ke pelanggan yang sudah ada.');
        }

        Customer::create([
            'package_id' => $package->id,
            'name' => $validated['name'],
            'pppoe_username' => $validated['name'],
            'pppoe_password' => $validated['password'],
            'status' => 'active',
            'phone' => '-',
            'address' => 'Dibuat dari halaman PPPoE'
        ]);

        return redirect()->route('pppoe.index')->with('success', 'Akun PPPoE berhasil dibuat di MikroTik dan Database.');
    }

    public function destroy(Request $request, $id)
    {
        $router = Router::where('status', 'online')->first();
        if (!$router) {
            return redirect()->route('pppoe.index')->with('error', 'Router sedang offline.');
        }

        $customer = Customer::findOrFail($id);
        $name = $customer->pppoe_username;

        // Delete from MikroTik
        $mikrotikService = new MikrotikService($router);
        $success = $mikrotikService->deletePppoeSecret($name);

        if ($success) {
            // Remove PPPoE access but keep the customer history
            $customer->update(['pppoe_username' => null, 'pppoe_password' => null]);
            return redirect()->route('pppoe.index')->with('success', "Akun PPPoE '$name' berhasil dihapus dari MikroTik dan akses dicabut.");
        } else {
            return redirect()->route('pppoe.index')->with('error', 'Gagal menghapus akun dari MikroTik karena Koneksi Timeout atau Error.');
        }
    }

    public function edit($id)
    {
        $customer = Customer::findOrFail($id);
        if (!$customer->pppoe_username) {
            return redirect()->route('pppoe.index')->with('error', 'Pelanggan ini bukan pengguna PPPoE aktif.');
        }
        
        $router = Router::where('status', 'online')->first();
        $packages = Package::all();
        $availableCustomers = Customer::where(function ($query) use ($customer) {
                $query->whereNull('pppoe_username')
                    ->orWhere('pppoe_username', '')
                    ->orWhere('id', $customer->id);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'address', 'pppoe_username']);
        
        return view('pppoe.edit', compact('customer', 'router', 'packages', 'availableCustomers'));
    }

    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);
        
        $validated = $request->validate([
            'pppoe_username' => 'required|string|max:255|unique:customers,pppoe_username,' . $customer->id,
            'pppoe_password' => 'required|string',
            'package_id' => 'required|exists:packages,id',
            'status' => 'required|in:active,inactive',
            'customer_id' => 'required|exists:customers,id',
        ]);

        $router = Router::where('status', 'online')->first();
        if (!$router) {
            return back()->with('error', 'Router offline. Tidak dapat mengubah data di MikroTik.');
        }

        $package = Package::findOrFail($validated['package_id']);
        $mikrotikService = new MikrotikService($router);
        
        $oldUsername = $customer->pppoe_username;
        $newUsername = $validated['pppoe_username'];
        $password = $validated['pppoe_password'];
        $profile = $package->name;
        $targetCustomer = Customer::findOrFail($validated['customer_id']);

        if ($targetCustomer->id !== $customer->id && filled($targetCustomer->pppoe_username)) {
            return back()->withInput()->with('error', 'Pelanggan tujuan sudah memiliki username PPPoE.');
        }
        
        try {
            // Update on MikroTik
            $mikrotikService->updatePppoeSecret($oldUsername, $newUsername, $password, $profile);
            
            // Enable/disable in Mikrotik? (Optional logic, let's keep it simple and just update DB status)
            
            $targetCustomer->update([
                'pppoe_username' => $newUsername,
                'pppoe_password' => $password,
                'package_id' => $package->id,
                'status' => $validated['status']
            ]);

            if ($targetCustomer->id !== $customer->id) {
                if ($this->isImportedPppoePlaceholder($customer)) {
                    $customer->delete();
                } else {
                    $customer->update([
                        'pppoe_username' => null,
                        'pppoe_password' => null,
                    ]);
                }
            }
            
            return redirect()->route('pppoe.index')->with('success', 'Akun PPPoE berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memperbarui data di MikroTik: ' . $e->getMessage());
        }
    }

    public function toggle(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);
        if (!$customer->pppoe_username) {
            return back()->with('error', 'Pelanggan ini bukan pengguna PPPoE aktif.');
        }

        $router = Router::where('status', 'online')->first();
        if (!$router) {
            return back()->with('error', 'Router sedang offline.');
        }

        $mikrotikService = new MikrotikService($router);
        $newStatus = $customer->status === 'active' ? 'inactive' : 'active';
        $disable = $newStatus === 'inactive';

        try {
            $mikrotikService->togglePppoeSecret($customer->pppoe_username, $disable);
            $customer->update(['status' => $newStatus]);
            
            $msg = $disable ? 'dinonaktifkan' : 'diaktifkan';
            return back()->with('success', "Akun PPPoE '{$customer->pppoe_username}' berhasil $msg.");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengubah status di MikroTik: ' . $e->getMessage());
        }
    }

    public function resetTable(Request $request)
    {
        $validated = $request->validate([
            'confirmation' => 'required|in:HAPUS',
        ], [
            'confirmation.in' => 'Konfirmasi reset tabel PPPoE harus diisi dengan kata HAPUS.',
        ]);

        $deletedPlaceholders = 0;
        $clearedCustomers = 0;

        Customer::whereNotNull('pppoe_username')
            ->chunkById(200, function ($customers) use (&$deletedPlaceholders, &$clearedCustomers) {
                foreach ($customers as $customer) {
                    if ($this->isImportedPppoePlaceholder($customer)) {
                        $customer->delete();
                        $deletedPlaceholders++;
                        continue;
                    }

                    $customer->update([
                        'pppoe_username' => null,
                        'pppoe_password' => null,
                    ]);
                    $clearedCustomers++;
                }
            });

        return redirect()
            ->route('pppoe.index')
            ->with('success', "Tabel PPPoE berhasil direset. {$clearedCustomers} pelanggan asli dikosongkan PPPoE-nya, {$deletedPlaceholders} data import sementara dihapus. Secret di MikroTik tidak dihapus.");
    }

    private function isImportedPppoePlaceholder(Customer $customer): bool
    {
        $address = strtolower((string) $customer->address);

        return $customer->phone === '-'
            && (
                str_contains($address, 'di-import dari mikrotik')
                || str_contains($address, 'di-import dari file .rsc mikrotik')
                || $customer->name === $customer->pppoe_username
            );
    }

    private function findLinkableCustomerForSecret(string $username): ?Customer
    {
        $normalizedUsername = strtolower(trim($username));

        return Customer::where(function ($query) {
                $query->whereNull('pppoe_username')
                    ->orWhere('pppoe_username', '');
            })
            ->get()
            ->first(function (Customer $customer) use ($normalizedUsername) {
                return strtolower(trim((string) $customer->name)) === $normalizedUsername;
            });
    }
}
