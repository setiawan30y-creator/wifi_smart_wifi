<?php

namespace App\Http\Controllers\Saas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class TenantController extends Controller
{
    public function index()
    {
        $tenants = Tenant::withCount([
            'users' => function($q) {
                $q->withoutGlobalScope(\App\Models\Scopes\TenantScope::class);
            }
        ])->latest()->paginate(15);
        
        return view('saas.tenants.index', compact('tenants'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'domain' => 'nullable|string|max:255|unique:tenants,domain',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255|unique:users,email',
            'admin_password' => 'required|string|min:8',
        ]);

        DB::beginTransaction();
        try {
            // Create Tenant
            $tenant = Tenant::create([
                'name' => $validated['name'],
                'domain' => $validated['domain'],
                'status' => 'active',
            ]);

            // Create Admin User for this Tenant
            // We need to bypass the global scope because the current session tenant_id is 1 (SaaS Admin)
            $user = new User();
            $user->tenant_id = $tenant->id;
            $user->name = $validated['admin_name'];
            $user->email = $validated['admin_email'];
            $user->password = Hash::make($validated['admin_password']);
            $user->role = 'superadmin'; // Default role for tenant owner
            $user->save();

            DB::commit();

            return redirect()->route('saas.tenants.index')->with('success', 'Perusahaan Klien berhasil ditambahkan beserta akun Admin-nya.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Gagal menambahkan tenant.', ['error' => $e->getMessage()]);

            return back()->withInput()->with('error', 'Gagal menambahkan klien. Periksa kembali data lalu coba lagi.');
        }
    }

    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'domain' => 'nullable|string|max:255|unique:tenants,domain,' . $tenant->id,
        ]);

        $tenant->update($validated);

        return redirect()->route('saas.tenants.index')->with('success', 'Data Perusahaan berhasil diperbarui.');
    }

    public function toggleStatus(Tenant $tenant)
    {
        // Don't allow suspending the default tenant 1
        if ($tenant->id === 1) {
            return back()->with('error', 'Perusahaan Utama tidak dapat dinonaktifkan.');
        }

        $tenant->status = $tenant->status === 'active' ? 'suspended' : 'active';
        $tenant->save();

        $statusText = $tenant->status === 'active' ? 'diaktifkan' : 'dinonaktifkan (suspended)';
        return back()->with('success', "Perusahaan {$tenant->name} berhasil {$statusText}.");
    }

    public function destroy(Tenant $tenant)
    {
        if ($tenant->id === 1) {
            return back()->with('error', 'Perusahaan Utama tidak dapat dihapus.');
        }

        $tenant->delete();
        return redirect()->route('saas.tenants.index')->with('success', 'Perusahaan berhasil dihapus beserta seluruh datanya.');
    }
}
