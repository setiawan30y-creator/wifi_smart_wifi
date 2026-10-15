<?php

namespace App\Http\Controllers;

use App\Models\Odp;
use App\Models\DeviceSpecification;
use App\Models\OdpDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OdpController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Odp::query();

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $odps = $query->with(['devices', 'customers'])->orderBy('name', 'asc')->paginate(10)->withQueryString();

        // Calculate totals for stats
        $allOdps = Odp::all();
        $totalOdps = $allOdps->count();
        $totalFoPorts = $allOdps->sum('fo_ports');
        $totalLanPorts = $allOdps->sum('lan_ports');
        
        $waTemplates = \App\Models\WaTemplate::orderBy('is_system', 'desc')->orderBy('title')->get();

        return view('odps.index', compact('odps', 'totalOdps', 'totalFoPorts', 'totalLanPorts', 'waTemplates'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $deviceSpecs = DeviceSpecification::orderBy('name', 'asc')->get();
        $foSpecs = $deviceSpecs->where('device_type', 'FO');
        $lanSpecs = $deviceSpecs->where('device_type', 'LAN');
        $allOdps = Odp::orderBy('name', 'asc')->get();
        return view('odps.create', compact('foSpecs', 'lanSpecs', 'allOdps'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'parent_odp_id' => 'nullable|exists:odps,id',
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'description' => 'nullable|string',
            'maps_link' => 'nullable|url|max:500',
            'photo_1' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4096',
            'photo_2' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4096',
            'photo_3' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4096',
            'photo_4' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4096',
            'devices' => 'required|array|min:1',
            'devices.*.device_type' => 'required|string|in:FO,LAN',
            'devices.*.device_spec' => 'required|string',
            'devices.*.device_spec_custom' => 'nullable|string|max:255',
        ]);

        $data = $request->except(['photo_1', 'photo_2', 'photo_3', 'photo_4', 'devices']);

        $data = $this->extractCoordinates($data);

        for ($i = 1; $i <= 4; $i++) {
            $key = 'photo_' . $i;
            if ($request->hasFile($key)) {
                $file = $request->file($key);
                $fileName = time() . '_' . $i . '_' . preg_replace('/[^A-Za-z0-9\-\._]/', '', $file->getClientOriginalName());
                $path = $file->storeAs('odps', $fileName, 'public');
                $data[$key] = $path;
            }
        }

        $totalFo = 0;
        $totalLan = 0;
        $devicesToSave = [];

        foreach ($request->devices as $deviceInput) {
            $spec = $deviceInput['device_spec'];
            if ($spec === 'custom') {
                $spec = $deviceInput['device_spec_custom'] ?? 'kustom';
            }

            list($foPorts, $lanPorts) = $this->parsePorts($deviceInput['device_type'], $spec);
            
            $totalFo += $foPorts;
            $totalLan += $lanPorts;

            $devicesToSave[] = [
                'device_type' => $deviceInput['device_type'],
                'device_spec' => $spec,
                'fo_ports' => $foPorts,
                'lan_ports' => $lanPorts,
            ];
        }

        $data['fo_ports'] = $totalFo;
        $data['lan_ports'] = $totalLan;

        $odp = Odp::create($data);

        foreach ($devicesToSave as $deviceData) {
            $odp->devices()->create($deviceData);
        }

        $redirectTo = $this->safeRedirectTarget($request->input('redirect_to'), route('odps.index'));

        return redirect($redirectTo)->with('success', 'ODP berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Odp $odp)
    {
        $odp->load('devices');
        $deviceSpecs = DeviceSpecification::orderBy('name', 'asc')->get();
        $foSpecs = $deviceSpecs->where('device_type', 'FO');
        $lanSpecs = $deviceSpecs->where('device_type', 'LAN');
        $allOdps = Odp::where('id', '!=', $odp->id)->orderBy('name', 'asc')->get();
        return view('odps.edit', compact('odp', 'foSpecs', 'lanSpecs', 'allOdps'));
    }

    private function safeRedirectTarget(?string $target, string $fallback): string
    {
        if (!$target) {
            return $fallback;
        }

        if (str_starts_with($target, '/') || str_starts_with($target, url('/'))) {
            return $target;
        }

        return $fallback;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Odp $odp)
    {
        $request->validate([
            'parent_odp_id' => 'nullable|exists:odps,id',
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'description' => 'nullable|string',
            'maps_link' => 'nullable|url|max:500',
            'photo_1' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4096',
            'photo_2' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4096',
            'photo_3' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4096',
            'photo_4' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4096',
            'devices' => 'required|array|min:1',
            'devices.*.device_type' => 'required|string|in:FO,LAN',
            'devices.*.device_spec' => 'required|string',
            'devices.*.device_spec_custom' => 'nullable|string|max:255',
        ]);

        $data = $request->except(['photo_1', 'photo_2', 'photo_3', 'photo_4', 'devices']);

        $data = $this->extractCoordinates($data);

        for ($i = 1; $i <= 4; $i++) {
            $key = 'photo_' . $i;
            if ($request->hasFile($key)) {
                // Delete old file if exists
                if ($odp->$key && Storage::disk('public')->exists($odp->$key)) {
                    Storage::disk('public')->delete($odp->$key);
                }

                $file = $request->file($key);
                $fileName = time() . '_' . $i . '_' . preg_replace('/[^A-Za-z0-9\-\._]/', '', $file->getClientOriginalName());
                $path = $file->storeAs('odps', $fileName, 'public');
                $data[$key] = $path;
            }
        }

        $totalFo = 0;
        $totalLan = 0;
        $devicesToSave = [];

        foreach ($request->devices as $deviceInput) {
            $spec = $deviceInput['device_spec'];
            if ($spec === 'custom') {
                $spec = $deviceInput['device_spec_custom'] ?? 'kustom';
            }

            list($foPorts, $lanPorts) = $this->parsePorts($deviceInput['device_type'], $spec);
            
            $totalFo += $foPorts;
            $totalLan += $lanPorts;

            $devicesToSave[] = [
                'device_type' => $deviceInput['device_type'],
                'device_spec' => $spec,
                'fo_ports' => $foPorts,
                'lan_ports' => $lanPorts,
            ];
        }

        $data['fo_ports'] = $totalFo;
        $data['lan_ports'] = $totalLan;

        $odp->update($data);

        // Sync devices (delete old, create new)
        $odp->devices()->delete();
        foreach ($devicesToSave as $deviceData) {
            $odp->devices()->create($deviceData);
        }

        return redirect()->route('odps.index')->with('success', 'ODP berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Odp $odp)
    {
        for ($i = 1; $i <= 4; $i++) {
            $key = 'photo_' . $i;
            if ($odp->$key && Storage::disk('public')->exists($odp->$key)) {
                Storage::disk('public')->delete($odp->$key);
            }
        }

        $odp->delete();

        return redirect()->route('odps.index')->with('success', 'ODP berhasil dihapus.');
    }

    /**
     * Store a newly created device specification via AJAX.
     */
    public function storeDeviceSpecification(Request $request)
    {
        $request->validate([
            'device_type' => 'required|string|in:FO,LAN',
            'name' => 'required|string|max:100',
            'label' => 'required|string|max:255',
        ]);

        // Check if unique constraint is violated
        $exists = DeviceSpecification::where('device_type', $request->device_type)
            ->where('name', $request->name)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Spesifikasi tersebut sudah terdaftar.',
            ], 422);
        }

        $spec = DeviceSpecification::create([
            'device_type' => $request->device_type,
            'name' => $request->name,
            'label' => $request->label,
        ]);

        return response()->json([
            'success' => true,
            'spec' => $spec,
        ]);
    }

    /**
     * Parse device_spec and return an array [fo_ports, lan_ports]
     */
    private function parsePorts($deviceType, $deviceSpec)
    {
        $deviceSpec = strtolower(trim($deviceSpec));
        
        $foPorts = 0;
        $lanPorts = 0;

        // Pattern 1: Splitter like 1/8, 1/16, 1/32, 1/64
        if (preg_match('/^1\/(\d+)$/', $deviceSpec, $matches)) {
            $foPorts = (int) $matches[1];
            $lanPorts = 0;
            return [$foPorts, $lanPorts];
        }

        // Pattern 2: Check for direct patterns like '2fo4lan', '2lan6fo', '6lan', '12fo', etc.
        // We look for 'fo' parts
        if (preg_match('/(\d+)\s*fo/', $deviceSpec, $matches)) {
            $foPorts = (int) $matches[1];
        }
        
        // We look for 'lan' parts
        if (preg_match('/(\d+)\s*lan/', $deviceSpec, $matches)) {
            $lanPorts = (int) $matches[1];
        }
        // Fallback: if it's number only
        if ($foPorts === 0 && $lanPorts === 0) {
            if (is_numeric($deviceSpec)) {
                $num = (int) $deviceSpec;
                if ($deviceType === 'FO') {
                    $foPorts = $num;
                } else {
                    $lanPorts = $num;
                }
            }
        }

        return [$foPorts, $lanPorts];
    }

    /**
     * Extract latitude and longitude from maps_link
     */
    protected function extractCoordinates(array $data): array
    {
        if (!empty($data['maps_link'])) {
            $link = $data['maps_link'];
            $lat = null;
            $lng = null;

            $decodedLink = urldecode($link);
            $patterns = [
                '/@(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/',
                '/(?:q|query|center|ll)=(-?\d+(?:\.\d+)?)(?:%2C|,\+|,|\s+)(-?\d+(?:\.\d+)?)/',
                '/!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/',
                '/(?:place|dir|search)\/.*?\/?(-?\d+(?:\.\d+)?)(?:%2C|,\+|,|\s+)(-?\d+(?:\.\d+)?)/',
                '/[?&](?:destination|saddr|daddr)=(-?\d+(?:\.\d+)?)(?:%2C|,\+|,|\s+)(-?\d+(?:\.\d+)?)/',
                '/^(-?\d+(?:\.\d+)?)(?:\s*,\s*|\s+)(-?\d+(?:\.\d+)?)$/',
                '/(-?\d{1,2}\.\d{4,})\s*,\s*(-?\d{2,3}\.\d{4,})/',
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $decodedLink, $matches) || preg_match($pattern, trim($link), $matches)) {
                    $latCandidate = (float) $matches[1];
                    $lngCandidate = (float) $matches[2];

                    if ($latCandidate >= -90 && $latCandidate <= 90 && $lngCandidate >= -180 && $lngCandidate <= 180) {
                        $lat = $matches[1];
                        $lng = $matches[2];
                        break;
                    }
                }
            }

            if ($lat && $lng) {
                $data['latitude'] = $lat;
                $data['longitude'] = $lng;
            }
        }
        return $data;
    }
    public function extractCoordinatesApi(\Illuminate\Http\Request $request)
    {
        $link = $request->input('link');
        if (!$link) return response()->json(['success' => false]);

        $lat = null;
        $lng = null;

        // Helper function to extract from a string (url or html body)
        $extract = function($text) use (&$lat, &$lng) {
            $decoded = urldecode((string) $text);
            $patterns = [
                '/@(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/',
                '/(?:q|query|center|ll)=(-?\d+(?:\.\d+)?)(?:%2C|,\+|,|\s+)(-?\d+(?:\.\d+)?)/',
                '/!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/',
                '/(?:place|dir|search)\/.*?\/?(-?\d+(?:\.\d+)?)(?:%2C|,\+|,|\s+)(-?\d+(?:\.\d+)?)/',
                '/[?&](?:destination|saddr|daddr)=(-?\d+(?:\.\d+)?)(?:%2C|,\+|,|\s+)(-?\d+(?:\.\d+)?)/',
                '/^(-?\d+(?:\.\d+)?)(?:\s*,\s*|\s+)(-?\d+(?:\.\d+)?)$/',
                '/(-?\d{1,2}\.\d{4,})\s*,\s*(-?\d{2,3}\.\d{4,})/',
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $decoded, $matches) || preg_match($pattern, (string) $text, $matches)) {
                    $latCandidate = (float) $matches[1];
                    $lngCandidate = (float) $matches[2];

                    if ($latCandidate >= -90 && $latCandidate <= 90 && $lngCandidate >= -180 && $lngCandidate <= 180) {
                        $lat = $matches[1];
                        $lng = $matches[2];
                        return true;
                    }
                }
            }

            return false;
        };

        // 1. Try to extract from the raw input link FIRST!
        if ($extract($link)) {
            return response()->json(['success' => true, 'lat' => $lat, 'lng' => $lng, 'resolved_url' => $link]);
        }

        // 2. If no coordinates found (e.g. shortlink), try to resolve it via cURL
        if (strpos($link, 'goo.gl') !== false || strpos($link, 'maps.app.goo.gl') !== false || strpos($link, 'maps.google.com') !== false) {
            if (!function_exists('curl_init')) {
                return response()->json([
                    'success' => false,
                    'message' => 'PHP cURL belum aktif',
                ]);
            }

            try {
                $ch = curl_init($link);
                curl_setopt($ch, CURLOPT_HEADER, true);
                curl_setopt($ch, CURLOPT_NOBODY, false);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                $result = curl_exec($ch);
                $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
                curl_close($ch);
                
                // 3. Try parsing the final URL
                if ($finalUrl && $extract($finalUrl)) {
                    return response()->json(['success' => true, 'lat' => $lat, 'lng' => $lng, 'resolved_url' => $finalUrl]);
                }
                
                // 4. Try parsing the HTML body if Google blocked the redirect but sent an HTML page
                if ($result && $extract($result)) {
                    return response()->json(['success' => true, 'lat' => $lat, 'lng' => $lng, 'resolved_url' => $finalUrl]);
                }
            } catch (\Throwable $exception) {
                report($exception);

                return response()->json([
                    'success' => false,
                    'message' => 'Gagal akses link Maps',
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Koordinat tidak ditemukan',
        ]);
    }
}
