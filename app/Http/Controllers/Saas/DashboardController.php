<?php

namespace App\Http\Controllers\Saas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Customer;

class DashboardController extends Controller
{
    public function index()
    {
        // Bypass global scopes just in case we need to count totals
        $totalTenants = Tenant::count();
        $activeTenants = Tenant::where('status', 'active')->count();
        
        $totalUsers = User::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->count();
        $totalCustomers = Customer::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->count();

        // Latest tenants
        $latestTenants = Tenant::latest()->take(5)->get();

        return view('saas.dashboard', compact(
            'totalTenants', 
            'activeTenants', 
            'totalUsers', 
            'totalCustomers',
            'latestTenants'
        ));
    }
}
