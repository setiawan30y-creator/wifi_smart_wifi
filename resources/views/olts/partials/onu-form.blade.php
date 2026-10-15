@php
    $selectedCustomer = old('customer_id', $onu?->customer_id);
@endphp

<div id="{{ $modalId }}" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm">
    <div class="w-full max-w-4xl rounded-2xl border border-slate-800 bg-slate-900 shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-800 px-6 py-4">
            <h3 class="text-lg font-bold text-white">{{ $onu ? 'Edit ONU' : 'Tambah ONU' }}</h3>
            <button type="button" onclick="closeOltModal('{{ $modalId }}')" class="text-slate-400 hover:text-white">x</button>
        </div>
        <form action="{{ $action }}" method="POST" class="p-6">
            @csrf
            @if($method)
                @method($method)
            @endif

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-400">Pelanggan</label>
                    <select name="customer_id" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-sm text-white focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Tidak dikaitkan</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" @selected((string) $selectedCustomer === (string) $customer->id)>
                                {{ $customer->name }} - {{ $customer->pppoe_username }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Pilih pelanggan jika ONU ini milik pelanggan yang sudah terdaftar.</p>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-400">Nama ONU</label>
                    <input type="text" name="name" value="{{ old('name', $onu?->name) }}" placeholder="Contoh: Rumah Pak Budi" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-sm text-white focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-400">PON Port</label>
                    <input type="text" name="pon_port" value="{{ old('pon_port', $onu?->pon_port) }}" placeholder="Contoh: GPON 1/1/3" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-sm text-white focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-400">ONU ID</label>
                    <input type="text" name="onu_id" value="{{ old('onu_id', $onu?->onu_id) }}" placeholder="Contoh: gpon-onu_1/1/3:12" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-sm text-white focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-400">Serial Number</label>
                    <input type="text" name="serial_number" value="{{ old('serial_number', $onu?->serial_number) }}" placeholder="Contoh: ZTEGABC12345" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-sm text-white focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-400">Username PPPoE</label>
                    <input type="text" name="pppoe_username" value="{{ old('pppoe_username', $onu?->pppoe_username) }}" placeholder="Username yang login di MikroTik" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-sm text-white focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-400">Status ONU</label>
                    <select name="status" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-sm text-white focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach($onuStatuses as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $onu?->status ?? 'unknown') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-400">Status PPPoE</label>
                    <select name="pppoe_status" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-sm text-white focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach($pppoeStatuses as $value => $label)
                            <option value="{{ $value }}" @selected(old('pppoe_status', $onu?->pppoe_status ?? 'unknown') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-400">RX Power (dBm)</label>
                    <input type="number" step="0.01" name="rx_power" value="{{ old('rx_power', $onu?->rx_power) }}" placeholder="-22.50" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-sm text-white focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-400">TX Power (dBm)</label>
                    <input type="number" step="0.01" name="tx_power" value="{{ old('tx_power', $onu?->tx_power) }}" placeholder="2.30" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-sm text-white focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-400">Jarak (meter)</label>
                    <input type="number" name="distance" value="{{ old('distance', $onu?->distance) }}" placeholder="350" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-sm text-white focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-400">Uptime</label>
                    <input type="text" name="uptime" value="{{ old('uptime', $onu?->uptime) }}" placeholder="1d 04:22:10" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-sm text-white focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>

            <div class="mt-4">
                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-400">Catatan</label>
                <textarea name="notes" rows="3" class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-sm text-white focus:border-indigo-500 focus:ring-indigo-500" placeholder="Catatan teknis, splitter, jalur, atau kendala">{{ old('notes', $onu?->notes) }}</textarea>
            </div>

            <div class="mt-6 flex justify-end gap-3 border-t border-slate-800 pt-5">
                <button type="button" onclick="closeOltModal('{{ $modalId }}')" class="rounded-xl bg-slate-800 px-4 py-2 text-sm font-bold text-slate-300 hover:bg-slate-700">Batal</button>
                <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-bold text-white hover:bg-indigo-500">Simpan ONU</button>
            </div>
        </form>
    </div>
</div>
