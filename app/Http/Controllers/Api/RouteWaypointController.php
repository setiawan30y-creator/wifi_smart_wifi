<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Odp;
use App\Models\Customer;
use Illuminate\Http\Request;

class RouteWaypointController extends Controller
{
    public function updateOdp(Request $request, Odp $odp)
    {
        $request->validate([
            'waypoints' => 'required|array'
        ]);

        $odp->update([
            'route_waypoints' => $request->waypoints
        ]);

        return response()->json(['success' => true, 'message' => 'Rute ODP berhasil disimpan.']);
    }

    public function updateCustomer(Request $request, Customer $customer)
    {
        $request->validate([
            'waypoints' => 'required|array'
        ]);

        $customer->update([
            'route_waypoints' => $request->waypoints
        ]);

        return response()->json(['success' => true, 'message' => 'Rute Pelanggan berhasil disimpan.']);
    }
}
