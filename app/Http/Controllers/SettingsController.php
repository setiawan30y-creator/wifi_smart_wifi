<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Setting;
use App\Support\Permissions;
use App\Models\WaTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function companyLogo()
    {
        $logo = Setting::get('company_logo');

        if (!$logo || !Storage::disk('public')->exists($logo)) {
            abort(404);
        }

        return Storage::disk('public')->response($logo);
    }

    public function companyFavicon()
    {
        $favicon = Setting::get('company_favicon');

        if (!$favicon || !Storage::disk('public')->exists($favicon)) {
            abort(404);
        }

        return Storage::disk('public')->response($favicon);
    }

    public function userPhoto(User $user)
    {
        if (!$user->photo || !Storage::disk('public')->exists($user->photo)) {
            abort(404);
        }

        return Storage::disk('public')->response($user->photo);
    }

    /**
     * Display the settings index page.
     */
    public function index()
    {
        // Company Settings
        $companyName = Setting::get('company_name', 'Billing RT/RW');
        $companyAddress = Setting::get('company_address', 'Jl. Raya RT/RW No. 1');
        $companyPhone = Setting::get('company_phone', '081234567890');
        $companyLogo = Setting::get('company_logo');
        $companyFavicon = Setting::get('company_favicon');
        $fonnteToken = Setting::get('fonnte_token', '');
        $waGatewayProvider = Setting::get('wa_gateway_provider', 'fonnte');
        $waGatewayToken = Setting::get('wa_gateway_token', $fonnteToken);
        $waGatewayEndpoint = Setting::get('wa_gateway_endpoint', 'https://api.fonnte.com/send');
        $waGatewayAuthHeader = Setting::get('wa_gateway_auth_header', 'Authorization');
        $waGatewayAuthPrefix = Setting::get('wa_gateway_auth_prefix', '');
        $waGatewayPayloadFormat = Setting::get('wa_gateway_payload_format', 'multipart');
        $waGatewayTargetField = Setting::get('wa_gateway_target_field', 'target');
        $waGatewayMessageField = Setting::get('wa_gateway_message_field', 'message');
        $waGatewayExtraPayload = Setting::get('wa_gateway_extra_payload', '{"countryCode":"62"}');
        $waGatewaySuccessKey = Setting::get('wa_gateway_success_key', 'status');
        $waGatewaySuccessValue = Setting::get('wa_gateway_success_value', 'true');
        $waGatewayDocumentation = Setting::get('wa_gateway_documentation', "Fonnte:\nEndpoint: https://api.fonnte.com/send\nHeader: Authorization: TOKEN\nBody: target, message, countryCode");
        $adminWhatsapp = Setting::get('admin_whatsapp', '');
        
        // Tripay Payment Gateway Settings
        $tripayApiKey = Setting::get('tripay_api_key', '');
        $tripayPrivateKey = Setting::get('tripay_private_key', '');
        $tripayMerchantCode = Setting::get('tripay_merchant_code', '');
        $tripayEnvironment = Setting::get('tripay_environment', 'sandbox');

        // Network Coordinates Settings (Up to 3 ISPs)
        $networkIspName1 = Setting::get('network_isp_name_1', Setting::get('network_isp_name', config('network.isp.name')));
        $networkIspLatitude1 = Setting::get('network_isp_latitude_1', Setting::get('network_isp_latitude', config('network.isp.latitude')));
        $networkIspLongitude1 = Setting::get('network_isp_longitude_1', Setting::get('network_isp_longitude', config('network.isp.longitude')));
        $networkIspDescription1 = Setting::get('network_isp_description_1', Setting::get('network_isp_description', config('network.isp.description')));

        $networkIspName2 = Setting::get('network_isp_name_2');
        $networkIspLatitude2 = Setting::get('network_isp_latitude_2');
        $networkIspLongitude2 = Setting::get('network_isp_longitude_2');
        $networkIspDescription2 = Setting::get('network_isp_description_2');

        $networkIspName3 = Setting::get('network_isp_name_3');
        $networkIspLatitude3 = Setting::get('network_isp_latitude_3');
        $networkIspLongitude3 = Setting::get('network_isp_longitude_3');
        $networkIspDescription3 = Setting::get('network_isp_description_3');

        $networkServerName = Setting::get('network_server_name', config('network.server.name'));
        $networkServerLatitude = Setting::get('network_server_latitude', config('network.server.latitude'));
        $networkServerLongitude = Setting::get('network_server_longitude', config('network.server.longitude'));
        $networkServerDescription = Setting::get('network_server_description', config('network.server.description'));

        // User list
        $users = User::orderBy('name')->get();

        // WA Templates
        $waTemplates = WaTemplate::orderBy('is_system', 'desc')->orderBy('title')->get();

        // Placeholders guide
        $placeholders = [
            '{nama_pelanggan}' => 'Nama lengkap pelanggan',
            '{nominal}' => 'Nominal rupiah tagihan (contoh: 150.000)',
            '{nomor_tagihan}' => 'Nomor invoice tagihan',
            '{bulan}' => 'Nama bulan tagihan (contoh: Juni 2026)',
            '{tanggal_jatuh_tempo}' => 'Tanggal jatuh tempo invoice (bisa beberapa tanggal jika bayar beberapa periode)',
            '{periode_bayar}' => 'Periode yang dibayar, otomatis bisa 1 bulan atau beberapa bulan',
            '{periode_bayar_baris}' => 'Periode yang dibayar dalam format per baris',
            '{nama_odp}' => 'Nama ODP terkait (contoh: ODP-BLOK-A01)',
            '{rincian_tagihan}' => 'Daftar tagihan yang dibayar',
            '{sisa_tagihan}' => 'Status lengkap sisa tagihan, otomatis menampilkan tidak ada sisa jika sudah lunas semua',
            '{rincian_sisa_tagihan}' => 'Daftar periode tagihan yang belum dibayar',
            '{periode_sisa_tagihan}' => 'Sisa periode tagihan yang belum dibayar tanpa rincian invoice',
            '{total_sisa_tagihan}' => 'Total nominal tunggakan yang belum dibayar',
            '{status_sisa_tagihan}' => 'Kalimat singkat ada/tidak ada sisa tagihan',
            '{info_sisa_tagihan}' => 'Blok sisa tagihan: jika lunas muncul tidak ada sisa, jika belum lunas muncul periode dan total',
            '{metode_bayar}' => 'Metode pembayaran manual',
            '{tanggal_bayar}' => 'Tanggal pembayaran manual tanpa jam untuk dikirim ke pelanggan',
            '{tanggal_bayar_lengkap}' => 'Tanggal pembayaran manual lengkap dengan jam',
            '{tanggal_janji_bayar}' => 'Tanggal janji bayar pelanggan',
            '{catatan_janji_bayar}' => 'Catatan janji bayar dari admin',
            '{jumlah_bulan}' => 'Jumlah invoice/bulan yang dibayar',
            '{nominal_dibayar}' => 'Total nominal yang dibayarkan',
            '{nominal_dibayar_miring}' => 'Total nominal terbilang, contoh: (seratus lima puluh ribu Rupiah)',
            '{nominal_dibayar_terbilang}' => 'Total nominal terbilang, contoh: (seratus lima puluh ribu Rupiah)',
        ];

        // Hak akses: matriks tenant aktif + daftar modul (App\Support\Permissions)
        $permissions = Permissions::matrix();
        $modules = Permissions::labels();

        // Dashboard Interfaces
        $mikrotikService = new \App\Services\MikrotikService();
        $mikrotikInterfaces = [];
        if ($mikrotikService->connect()) {
            $mikrotikInterfaces = $mikrotikService->getInterfaces();
        }
        $dashboardInterfaces = explode(',', Setting::get('dashboard_interfaces', ''));

        $mikrotikWebhookUrl = app(\App\Services\MikrotikWebhookToken::class)->url();

        return view('settings.index', compact(
            'mikrotikWebhookUrl',
            'companyName',
            'companyAddress',
            'companyPhone',
            'companyLogo',
            'companyFavicon',
            'users',
            'permissions',
            'modules',
            'waTemplates',
            'placeholders',
            'fonnteToken',
            'waGatewayProvider',
            'waGatewayToken',
            'waGatewayEndpoint',
            'waGatewayAuthHeader',
            'waGatewayAuthPrefix',
            'waGatewayPayloadFormat',
            'waGatewayTargetField',
            'waGatewayMessageField',
            'waGatewayExtraPayload',
            'waGatewaySuccessKey',
            'waGatewaySuccessValue',
            'waGatewayDocumentation',
            'adminWhatsapp',
            'tripayApiKey',
            'tripayPrivateKey',
            'tripayMerchantCode',
            'tripayEnvironment',
            'networkIspName1',
            'networkIspLatitude1',
            'networkIspLongitude1',
            'networkIspDescription1',
            'networkIspName2',
            'networkIspLatitude2',
            'networkIspLongitude2',
            'networkIspDescription2',
            'networkIspName3',
            'networkIspLatitude3',
            'networkIspLongitude3',
            'networkIspDescription3',
            'networkServerName',
            'networkServerLatitude',
            'networkServerLongitude',
            'networkServerDescription',
            'mikrotikInterfaces',
            'dashboardInterfaces'
        ));
    }

    public function updateDashboardInterfaces(Request $request)
    {
        $interfaces = $request->input('dashboard_interfaces', []);
        $interfacesString = is_array($interfaces) ? implode(',', $interfaces) : '';
        
        Setting::set('dashboard_interfaces', $interfacesString);

        return redirect()->back()->with('success', 'Pengaturan Trafik Dashboard berhasil disimpan.');
    }

    public function updateCompany(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'company_address' => 'required|string',
            'company_phone' => 'required|string|max:20',
            'company_logo' => 'nullable|image|max:2048',
            'company_favicon' => 'nullable|image|mimes:ico,png,jpg,jpeg,webp|max:1024',
            'fonnte_token' => 'nullable|string|max:255',
            'wa_gateway_provider' => 'nullable|in:fonnte,custom',
            'wa_gateway_token' => 'nullable|string|max:1000',
            'wa_gateway_endpoint' => 'nullable|url|max:1000',
            'wa_gateway_auth_header' => 'nullable|string|max:100',
            'wa_gateway_auth_prefix' => 'nullable|string|max:50',
            'wa_gateway_payload_format' => 'nullable|in:multipart,form,json',
            'wa_gateway_target_field' => 'nullable|string|max:100',
            'wa_gateway_message_field' => 'nullable|string|max:100',
            'wa_gateway_extra_payload' => 'nullable|string|max:5000',
            'wa_gateway_success_key' => 'nullable|string|max:100',
            'wa_gateway_success_value' => 'nullable|string|max:100',
            'wa_gateway_documentation' => 'nullable|string|max:10000',
            'admin_whatsapp' => 'nullable|string|max:20',
            'tripay_api_key' => 'nullable|string|max:255',
            'tripay_private_key' => 'nullable|string|max:255',
            'tripay_merchant_code' => 'nullable|string|max:255',
            'tripay_environment' => 'nullable|in:sandbox,production',
            
            'network_isp_name_1' => 'nullable|string|max:255',
            'network_isp_latitude_1' => 'nullable|numeric',
            'network_isp_longitude_1' => 'nullable|numeric',
            'network_isp_description_1' => 'nullable|string',
            
            'network_isp_name_2' => 'nullable|string|max:255',
            'network_isp_latitude_2' => 'nullable|numeric',
            'network_isp_longitude_2' => 'nullable|numeric',
            'network_isp_description_2' => 'nullable|string',
            
            'network_isp_name_3' => 'nullable|string|max:255',
            'network_isp_latitude_3' => 'nullable|numeric',
            'network_isp_longitude_3' => 'nullable|numeric',
            'network_isp_description_3' => 'nullable|string',
            
            'network_server_name' => 'nullable|string|max:255',
            'network_server_latitude' => 'nullable|numeric',
            'network_server_longitude' => 'nullable|numeric',
            'network_server_description' => 'nullable|string',
        ]);

        Setting::set('company_name', $request->company_name);
        Setting::set('company_address', $request->company_address);
        Setting::set('company_phone', $request->company_phone);
        
        if ($request->has('fonnte_token')) {
            Setting::set('fonnte_token', $request->fonnte_token);
        }
        foreach ([
            'wa_gateway_provider',
            'wa_gateway_token',
            'wa_gateway_endpoint',
            'wa_gateway_auth_header',
            'wa_gateway_auth_prefix',
            'wa_gateway_payload_format',
            'wa_gateway_target_field',
            'wa_gateway_message_field',
            'wa_gateway_extra_payload',
            'wa_gateway_success_key',
            'wa_gateway_success_value',
            'wa_gateway_documentation',
        ] as $waSettingKey) {
            if ($request->has($waSettingKey)) {
                Setting::set($waSettingKey, $request->input($waSettingKey));
            }
        }

        if ($request->filled('wa_gateway_token')) {
            Setting::set('fonnte_token', $request->input('wa_gateway_token'));
        }
        if ($request->has('admin_whatsapp')) {
            Setting::set('admin_whatsapp', $request->admin_whatsapp);
        }

        if ($request->has('tripay_api_key')) {
            Setting::set('tripay_api_key', $request->tripay_api_key);
        }
        if ($request->has('tripay_private_key')) {
            Setting::set('tripay_private_key', $request->tripay_private_key);
        }
        if ($request->has('tripay_merchant_code')) {
            Setting::set('tripay_merchant_code', $request->tripay_merchant_code);
        }
        if ($request->has('tripay_environment')) {
            Setting::set('tripay_environment', $request->tripay_environment);
        }

        for ($i = 1; $i <= 3; $i++) {
            if ($request->has("network_isp_name_$i")) {
                Setting::set("network_isp_name_$i", $request->input("network_isp_name_$i"));
            }
            if ($request->has("network_isp_latitude_$i")) {
                Setting::set("network_isp_latitude_$i", $request->input("network_isp_latitude_$i"));
            }
            if ($request->has("network_isp_longitude_$i")) {
                Setting::set("network_isp_longitude_$i", $request->input("network_isp_longitude_$i"));
            }
            if ($request->has("network_isp_description_$i")) {
                Setting::set("network_isp_description_$i", $request->input("network_isp_description_$i"));
            }
        }

        if ($request->has('network_server_name')) {
            Setting::set('network_server_name', $request->network_server_name);
        }
        if ($request->has('network_server_latitude')) {
            Setting::set('network_server_latitude', $request->network_server_latitude);
        }
        if ($request->has('network_server_longitude')) {
            Setting::set('network_server_longitude', $request->network_server_longitude);
        }
        if ($request->has('network_server_description')) {
            Setting::set('network_server_description', $request->network_server_description);
        }

        if ($request->hasFile('company_logo')) {
            $oldLogo = Setting::get('company_logo');
            $path = $request->file('company_logo')->store('logos', 'public');
            Setting::set('company_logo', $path);

            if ($oldLogo && $oldLogo !== $path) {
                Storage::disk('public')->delete($oldLogo);
            }
        }

        if ($request->hasFile('company_favicon')) {
            $oldFavicon = Setting::get('company_favicon');
            $path = $request->file('company_favicon')->store('favicons', 'public');
            Setting::set('company_favicon', $path);

            if ($oldFavicon && $oldFavicon !== $path) {
                Storage::disk('public')->delete($oldFavicon);
            }
        }

        return redirect()->route('settings.index', ['tab' => 'company'])->with('success', 'Profil perusahaan berhasil diperbarui.');
    }

    /**
     * Store a newly created user/employee in storage.
     */
    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => ['required', 'string', \Illuminate\Validation\Rule::in($this->assignableRoles())],
            'photo' => 'nullable|image|max:2048',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('photos', 'public');
        }

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'photo' => $photoPath,
            'phone' => $request->phone,
            'address' => $request->address,
        ]);

        return redirect()->route('settings.index', ['tab' => 'users'])->with('success', 'Pengguna baru berhasil ditambahkan.');
    }

    /**
     * Update user profile data.
     */
    public function updateUser(Request $request, User $user)
    {
        $this->ensureCanManage($user);

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
            'password' => 'nullable|string|min:8',
        ]);

        $user->name = $request->name;
        $user->phone = $request->phone;
        $user->address = $request->address;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        if ($request->hasFile('photo')) {
            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }
            $user->photo = $request->file('photo')->store('photos', 'public');
        }

        $user->save();

        return redirect()->route('settings.index', ['tab' => 'users'])->with('success', 'Profil pengguna berhasil diperbarui.');
    }

    /**
     * Update login account credentials and role.
     */
    public function updateAccount(Request $request, User $user)
    {
        $this->ensureCanManage($user);

        $request->validate([
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'role' => ['required', 'string', \Illuminate\Validation\Rule::in($this->assignableRoles())],
            'password' => 'nullable|string|min:8',
        ]);

        // Prevent superadmin self-demotion
        if ($user->id === Auth::id() && $user->role === 'superadmin' && $request->role !== 'superadmin') {
            return back()->with('error', 'Anda tidak dapat menurunkan peran (demote) akun Anda sendiri dari Superadmin.');
        }

        $user->email = $request->email;
        $user->role = $request->role;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return redirect()->route('settings.index', ['tab' => 'accounts'])->with('success', 'Kredensial akun pengguna berhasil diperbarui.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroyUser(User $user)
    {
        $this->ensureCanManage($user);

        if ($user->id === Auth::id()) {
            return redirect()->route('settings.index', ['tab' => 'users'])->with('error', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang digunakan.');
        }

        // Prevent deleting the only superadmin
        if ($user->role === 'superadmin') {
            $superadminCount = User::where('role', 'superadmin')->count();
            if ($superadminCount <= 1) {
                return redirect()->route('settings.index', ['tab' => 'users'])->with('error', 'Tidak dapat menghapus satu-satunya akun Superadmin di sistem.');
            }
        }

        if ($user->photo) {
            Storage::disk('public')->delete($user->photo);
        }

        $user->delete();

        return redirect()->route('settings.index', ['tab' => 'users'])->with('success', 'Pengguna berhasil dihapus dari sistem.');
    }

    /**
     * Update access control rights matrix.
     */
    public function updatePermissions(Request $request)
    {
        $submitted = $request->input('permissions', []);
        $stored = [];

        // Checkbox yang tidak dicentang tidak terkirim, jadi semua modul dianggap false dulu.
        foreach (Permissions::EDITABLE_ROLES as $role) {
            foreach (Permissions::modules() as $module) {
                $stored[$role][$module] = (bool) ($submitted[$role][$module] ?? false);
            }
        }

        // Batasan tetap (mis. teknisi tidak boleh membuka Pengaturan) diterapkan oleh normalize().
        $matrix = Permissions::normalize($stored);
        Setting::set('permissions_map', array_intersect_key($matrix, array_flip(Permissions::EDITABLE_ROLES)));

        return redirect()->route('settings.index', ['tab' => 'permissions'])->with('success', 'Pengaturan hak akses berhasil disimpan.');
    }


    /**
     * Role yang boleh diberikan oleh user yang sedang login.
     * Hanya superadmin yang boleh membuat/menjadikan user lain superadmin.
     */
    private function assignableRoles(): array
    {
        return Auth::user()?->role === 'superadmin'
            ? ['superadmin', 'admin', 'tehnisi']
            : ['admin', 'tehnisi'];
    }

    /**
     * Admin biasa tidak boleh mengubah/menghapus akun superadmin.
     */
    private function ensureCanManage(User $target): void
    {
        abort_if(
            $target->role === 'superadmin' && Auth::user()?->role !== 'superadmin',
            403,
            'Hanya Superadmin yang dapat mengubah akun Superadmin.'
        );
    }

    public function regenerateMikrotikWebhookToken(\App\Services\MikrotikWebhookToken $tokens)
    {
        $tokens->regenerate();

        return redirect()->route('settings.index', ['tab' => 'traffic'])
            ->with('success', 'Token webhook MikroTik diganti. Perbarui URL di script PPP Profile MikroTik.');
    }
}
