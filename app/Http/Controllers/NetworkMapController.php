<?php

namespace App\Http\Controllers;

use App\Models\Odp;
use App\Models\Customer;
use App\Models\Package;
use Illuminate\Http\Request;

class NetworkMapController extends Controller
{
    /**
     * Display the Network Map.
     */
    public function index()
    {
        // Load all ODPs with their child devices and connected customers
        $odps = Odp::with(['devices', 'customers'])->get();
        
        // Load customers that have location coordinates
        $customers = Customer::with(['package', 'odp'])->whereNotNull('latitude')->whereNotNull('longitude')->get();
        $packages = Package::orderBy('name', 'asc')->get();

        $isps = [];
        
        $name1 = \App\Models\Setting::get('network_isp_name_1', \App\Models\Setting::get('network_isp_name', config('network.isp.name')));
        $lat1 = \App\Models\Setting::get('network_isp_latitude_1', \App\Models\Setting::get('network_isp_latitude', config('network.isp.latitude')));
        $lng1 = \App\Models\Setting::get('network_isp_longitude_1', \App\Models\Setting::get('network_isp_longitude', config('network.isp.longitude')));
        $desc1 = \App\Models\Setting::get('network_isp_description_1', \App\Models\Setting::get('network_isp_description', config('network.isp.description')));
        
        if ($name1 && $lat1 && $lng1) {
            $isps[] = [
                'name' => $name1,
                'latitude' => $lat1,
                'longitude' => $lng1,
                'description' => $desc1
            ];
        }

        $name2 = \App\Models\Setting::get('network_isp_name_2');
        if ($name2 && \App\Models\Setting::get('network_isp_latitude_2') && \App\Models\Setting::get('network_isp_longitude_2')) {
            $isps[] = [
                'name' => $name2,
                'latitude' => \App\Models\Setting::get('network_isp_latitude_2'),
                'longitude' => \App\Models\Setting::get('network_isp_longitude_2'),
                'description' => \App\Models\Setting::get('network_isp_description_2')
            ];
        }

        $name3 = \App\Models\Setting::get('network_isp_name_3');
        if ($name3 && \App\Models\Setting::get('network_isp_latitude_3') && \App\Models\Setting::get('network_isp_longitude_3')) {
            $isps[] = [
                'name' => $name3,
                'latitude' => \App\Models\Setting::get('network_isp_latitude_3'),
                'longitude' => \App\Models\Setting::get('network_isp_longitude_3'),
                'description' => \App\Models\Setting::get('network_isp_description_3')
            ];
        }

        $server = [
            'name' => \App\Models\Setting::get('network_server_name', config('network.server.name')),
            'latitude' => \App\Models\Setting::get('network_server_latitude', config('network.server.latitude')),
            'longitude' => \App\Models\Setting::get('network_server_longitude', config('network.server.longitude')),
            'description' => \App\Models\Setting::get('network_server_description', config('network.server.description')),
        ];

        return view('network_map.index', compact('odps', 'customers', 'isps', 'server', 'packages'));
    }
}
