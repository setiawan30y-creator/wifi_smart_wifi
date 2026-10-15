<?php

namespace App\Http\Controllers;

use App\Models\Router;
use Illuminate\Http\Request;
use App\Services\MikrotikService;

class RouterController extends Controller
{
    public function index()
    {
        $routers = Router::all();
        return view('routers.index', compact('routers'));
    }

    public function create()
    {
        return view('routers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'required|string|max:255',
            'password' => 'required|string',
            'description' => 'nullable|string'
        ]);

        Router::create($validated);

        return redirect()->route('routers.index')->with('success', 'Router MikroTik berhasil ditambahkan.');
    }

    public function edit(Router $router)
    {
        return view('routers.edit', compact('router'));
    }

    public function update(Request $request, Router $router)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'required|string|max:255',
            'password' => 'nullable|string',
            'description' => 'nullable|string'
        ]);

        // If password is not provided, remove it from validated array so it doesn't get updated to null
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $router->update($validated);

        return redirect()->route('routers.index')->with('success', 'Router MikroTik berhasil diperbarui.');
    }

    public function destroy(Router $router)
    {
        $router->delete();
        return redirect()->route('routers.index')->with('success', 'Router MikroTik berhasil dihapus.');
    }

    public function testConnection(Router $router)
    {
        $normalizedHost = Router::normalizeHost($router->ip_address);

        if ($normalizedHost !== $router->ip_address) {
            $router->update(['ip_address' => $normalizedHost]);
            $router->refresh();
        }

        $service = new MikrotikService($router);
        
        if ($service->connect()) {
            $router->update(['status' => 'online']);
            return redirect()->route('routers.index')->with('success', "Koneksi ke Router MikroTik berhasil: {$router->ip_address}:{$router->port}");
        } else {
            $router->update(['status' => 'offline']);
            return redirect()->route('routers.index')->with('error', "Gagal terhubung ke Router MikroTik {$router->ip_address}:{$router->port}. Pastikan service RouterOS API aktif di port tersebut, firewall input TCP mengizinkan akses dari server billing, dan user memiliki policy api/read/write.");
        }
    }
}
