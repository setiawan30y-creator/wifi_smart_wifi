<div class="grid gap-4 md:grid-cols-2">
    <label class="space-y-1">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Nama</span>
        <input type="text" name="name" required value="{{ old('name', $item?->name) }}" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
    </label>
    <label class="space-y-1">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">No. Telepon</span>
        <input type="text" name="phone" required value="{{ old('phone', $item?->phone) }}" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
    </label>
    <label class="space-y-1">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Email</span>
        <input type="email" name="email" value="{{ old('email', $item?->email) }}" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
    </label>
    <label class="space-y-1">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Sumber</span>
        <select name="source" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
            @foreach(['manual' => 'Input Manual', 'whatsapp' => 'WhatsApp', 'website' => 'Website', 'referensi' => 'Referensi'] as $key => $label)
                <option value="{{ $key }}" @selected(old('source', $item?->source ?? 'manual') === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <label class="space-y-1 md:col-span-2">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Alamat</span>
        <textarea name="address" rows="3" required class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">{{ old('address', $item?->address) }}</textarea>
    </label>
    <label class="space-y-1 md:col-span-2">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Link Maps</span>
        <input type="text" name="maps_link" value="{{ old('maps_link', $item?->maps_link) }}" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
    </label>
    <label class="space-y-1">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Paket Diminati</span>
        <select name="package_id" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
            <option value="">Belum pilih paket</option>
            @foreach($packages as $package)
                <option value="{{ $package->id }}" @selected((int) old('package_id', $item?->package_id) === $package->id)>{{ $package->name }}</option>
            @endforeach
        </select>
    </label>
    <label class="space-y-1">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">ODP Rencana</span>
        <select name="odp_id" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
            <option value="">Belum pilih ODP</option>
            @foreach($odps as $odp)
                <option value="{{ $odp->id }}" @selected((int) old('odp_id', $item?->odp_id) === $odp->id)>{{ $odp->name }}</option>
            @endforeach
        </select>
    </label>
    <label class="space-y-1">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Preferensi Tanggal</span>
        <input type="date" name="preferred_installation_date" value="{{ old('preferred_installation_date', $item?->preferred_installation_date?->format('Y-m-d')) }}" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
    </label>
    <label class="space-y-1">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Jadwal Pasang</span>
        <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at', $item?->scheduled_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
    </label>
    <label class="space-y-1">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Teknisi</span>
        <select name="assigned_to" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
            <option value="">Belum ditugaskan</option>
            @foreach($technicians as $technician)
                <option value="{{ $technician->id }}" @selected((int) old('assigned_to', $item?->assigned_to) === $technician->id)>{{ $technician->name }}</option>
            @endforeach
        </select>
    </label>
    <label class="space-y-1">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Status</span>
        <select name="status" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">
            @foreach($statuses as $key => $label)
                <option value="{{ $key }}" @selected(old('status', $item?->status ?? 'new') === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <label class="space-y-1 md:col-span-2">
        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Catatan</span>
        <textarea name="notes" rows="3" class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2.5 text-sm text-slate-200 outline-none focus:border-indigo-500">{{ old('notes', $item?->notes) }}</textarea>
    </label>
</div>
<div class="mt-6 flex justify-end gap-2">
    <button type="button" onclick="this.closest('.fixed') ? closeModal(this.closest('.fixed').id) : null" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-300 hover:bg-slate-800">Batal</button>
    <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">Simpan</button>
</div>
