<div class="grid gap-4 md:grid-cols-2">
    <label class="space-y-1">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Nama OLT</span>
        <input type="text" name="name" required value="{{ old('name', $olt?->name) }}" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
        <span class="block text-[11px] text-slate-500">Nama bebas untuk identifikasi, contoh: OLT POP Utama, OLT Blok A.</span>
    </label>
    <label class="space-y-1">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Brand</span>
        <select name="brand" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
            @foreach($brands as $brand)
                <option value="{{ $brand }}" @selected(old('brand', $olt?->brand ?? 'Generic') === $brand)>{{ $brand }}</option>
            @endforeach
        </select>
        <span class="block text-[11px] text-slate-500">Pilih merek OLT. Jika tidak ada, gunakan Generic.</span>
    </label>
    <label class="space-y-1">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Model</span>
        <input type="text" name="model" value="{{ old('model', $olt?->model) }}" placeholder="C320, MA5608T, V1600..." class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
        <span class="block text-[11px] text-slate-500">Isi tipe perangkat jika tahu, contoh: ZTE C320, Huawei MA5608T, VSOL V1600.</span>
    </label>
    <label class="space-y-1">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Jumlah Port PON</span>
        <input type="number" name="pon_ports" min="0" value="{{ old('pon_ports', $olt?->pon_ports ?? 0) }}" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
        <span class="block text-[11px] text-slate-500">Jumlah port PON fisik, contoh: 4, 8, 16, atau 32.</span>
    </label>
    <label class="space-y-1">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">IP / Host</span>
        <input type="text" name="host" required value="{{ old('host', $olt?->host) }}" placeholder="192.168.1.2" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
        <span class="block text-[11px] text-slate-500">Alamat manajemen OLT yang bisa diakses server billing.</span>
    </label>
    <label class="space-y-1">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Protocol</span>
        <select name="protocol" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
            @foreach($protocols as $value => $label)
                <option value="{{ $value }}" @selected(old('protocol', $olt?->protocol ?? 'telnet') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <span class="block text-[11px] text-slate-500">Telnet/SSH untuk command, SNMP untuk monitoring dasar.</span>
    </label>
    <label class="space-y-1">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Port Telnet/SSH</span>
        <input type="number" name="port" min="1" max="65535" required value="{{ old('port', $olt?->port ?? 23) }}" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
        <span class="block text-[11px] text-slate-500">Default Telnet 23, SSH 22. Abaikan jika hanya memakai SNMP.</span>
    </label>
    <label class="space-y-1">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Port SNMP</span>
        <input type="number" name="snmp_port" min="1" max="65535" required value="{{ old('snmp_port', $olt?->snmp_port ?? 161) }}" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
        <span class="block text-[11px] text-slate-500">Default SNMP 161. Harus terbuka dari server billing ke OLT.</span>
    </label>
    <label class="space-y-1">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Username</span>
        <input type="text" name="username" value="{{ old('username', $olt?->username) }}" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
        <span class="block text-[11px] text-slate-500">Username login OLT untuk Telnet/SSH. Boleh kosong jika hanya SNMP.</span>
    </label>
    <label class="space-y-1">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Password</span>
        <input type="password" name="password" @if(!$olt) required @endif placeholder="{{ $olt ? 'Kosongkan jika tidak diubah' : '' }}" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
        <span class="block text-[11px] text-slate-500">Password disimpan terenkripsi. Saat edit, kosongkan jika tidak ingin mengganti.</span>
    </label>
    <label class="space-y-1 md:col-span-2">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">SNMP Community</span>
        <input type="password" name="snmp_community" placeholder="{{ $olt ? 'Kosongkan jika tidak diubah' : 'public / private / community custom' }}" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
        <span class="block text-[11px] text-slate-500">Isi community SNMP jika OLT mengaktifkan SNMP. Contoh umum: public.</span>
    </label>
    <label class="space-y-1 md:col-span-2">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Catatan</span>
        <textarea name="description" rows="3" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">{{ old('description', $olt?->description) }}</textarea>
        <span class="block text-[11px] text-slate-500">Catatan lokasi, uplink, VLAN, atau informasi teknis lain untuk teknisi.</span>
    </label>
</div>

<div class="mt-6 flex justify-end gap-2">
    <button type="button" onclick="this.closest('.fixed') ? closeOltModal(this.closest('.fixed').id) : null" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-300 hover:bg-slate-800">Batal</button>
    <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-500">Simpan</button>
</div>
