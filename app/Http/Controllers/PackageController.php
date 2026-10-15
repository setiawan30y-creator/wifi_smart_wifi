<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Router;
use App\Services\MikrotikService;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    /**
     * Parse speed string (e.g. "10 Mbps" or "10M") to MikroTik rate-limit format (e.g. "10M/10M")
     */
    private function parseSpeedToRateLimit($speed)
    {
        if (!$speed) return null;
        // Basic parsing: Extract numbers
        preg_match('/(\d+)/', $speed, $matches);
        if (isset($matches[1])) {
            $num = $matches[1];
            // Check if kbps
            if (stripos($speed, 'kb') !== false) {
                return $num . 'k/' . $num . 'k';
            }
            // Default to Mbps (M)
            return $num . 'M/' . $num . 'M';
        }
        return null;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $packages = Package::withCount('customers')->get();
        return view('packages.index', compact('packages'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|integer|min:0',
            'speed' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $package = Package::create($request->all());

        // Sync to MikroTik
        $router = Router::where('status', 'online')->first();
        if ($router) {
            $mikrotikService = new MikrotikService($router);
            $rateLimit = $this->parseSpeedToRateLimit($package->speed);
            $mikrotikService->addPppoeProfile($package->name, $rateLimit);
        }

        return redirect()->route('packages.index')->with('success', 'Paket berhasil ditambahkan ke Web dan MikroTik (jika online).');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Package $package)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|integer|min:0',
            'speed' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $oldName = $package->name;
        $package->update($request->all());

        // Sync to MikroTik
        $router = Router::where('status', 'online')->first();
        if ($router) {
            $mikrotikService = new MikrotikService($router);
            $rateLimit = $this->parseSpeedToRateLimit($package->speed);
            $mikrotikService->updatePppoeProfile($oldName, $package->name, $rateLimit);
        }

        return redirect()->route('packages.index')->with('success', 'Paket berhasil diperbarui di Web dan MikroTik.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Package $package)
    {
        if ($package->customers()->count() > 0) {
            return redirect()->route('packages.index')->with('error', 'Paket tidak dapat dihapus karena masih digunakan oleh pelanggan.');
        }

        $name = $package->name;
        $package->delete();

        // Sync to MikroTik
        $router = Router::where('status', 'online')->first();
        if ($router) {
            $mikrotikService = new MikrotikService($router);
            $mikrotikService->deletePppoeProfile($name);
        }

        return redirect()->route('packages.index')->with('success', 'Paket berhasil dihapus dari Web dan MikroTik.');
    }
}
