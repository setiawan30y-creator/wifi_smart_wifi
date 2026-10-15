@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <!-- Header -->
    <div class="flex items-center gap-4 border-b border-slate-800 pb-5">
        <a href="{{ route('odps.index') }}" class="p-2 rounded-xl bg-slate-900 border border-slate-800 text-slate-400 hover:text-white transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
        </a>
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-white">Tambah ODP Baru</h1>
            <p class="text-slate-400 mt-1">Daftarkan titik jaringan fiber optik baru dengan koordinat lokasi dan bukti foto fisik.</p>
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-slate-900/40 border border-slate-800/60 backdrop-blur-md rounded-3xl p-8 max-w-4xl">
        <form action="{{ route('odps.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Nama ODP -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5 uppercase">Nama / Kode ODP</label>
                    <input type="text" name="name" placeholder="Contoh: ODP-BLOK-A01" required value="{{ old('name') }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                    @error('name')
                        <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Sumber Jaringan -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5 uppercase">Sumber Jaringan</label>
                    <select name="parent_odp_id" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors appearance-none">
                        <option value="">Langsung dari Server</option>
                        @foreach($allOdps as $o)
                            <option value="{{ $o->id }}" {{ old('parent_odp_id') == $o->id ? 'selected' : '' }}>Dari: {{ $o->name }}</option>
                        @endforeach
                    </select>
                    @error('parent_odp_id')
                        <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Link Maps -->
                <div class="lg:col-span-2">
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-semibold text-slate-300 uppercase">Link Maps</label>
                        <span class="text-[10px] text-slate-500">Bisa juga isi koordinat manual di bawah</span>
                    </div>
                    <div class="flex gap-2">
                        <input type="text" id="maps_link_input" name="maps_link" placeholder="Paste Link Google Maps di sini..." value="{{ old('maps_link') }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                        <button type="button" onclick="extractCoordinatesBtn()" id="btn_extract_maps" class="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-3 rounded-xl text-xs font-bold transition-colors flex items-center justify-center gap-2 whitespace-nowrap shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span>Ekstrak</span>
                        </button>
                    </div>
                    @error('maps_link')
                        <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Latitude -->
                <div class="lg:col-start-3">
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5 uppercase">Latitude</label>
                    <input type="text" id="latitude_input" name="latitude" placeholder="-6.123456" value="{{ old('latitude') }}" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-slate-300 font-mono focus:outline-none focus:border-indigo-500 transition-colors">
                </div>

                <!-- Longitude -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5 uppercase">Longitude</label>
                    <input type="text" id="longitude_input" name="longitude" placeholder="106.123456" value="{{ old('longitude') }}" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-slate-300 font-mono focus:outline-none focus:border-indigo-500 transition-colors">
                </div>
            </div>

            <!-- Section Perangkat ODP -->
            <div class="space-y-4 bg-slate-950/20 p-5 rounded-2xl border border-slate-800">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div>
                        <h3 class="text-sm font-bold text-white uppercase tracking-wider">Daftar Perangkat Terpasang</h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">Satu box ODP dapat berisi lebih dari satu perangkat untuk memperbanyak kapasitas port.</p>
                    </div>
                    <button type="button" onclick="addDeviceRow()" class="bg-indigo-500/10 hover:bg-indigo-500 text-indigo-400 hover:text-white border border-indigo-500/20 px-3.5 py-2 rounded-xl text-xs font-bold transition-all duration-300 flex items-center gap-1.5 shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Tambah Perangkat</span>
                    </button>
                </div>

                <div id="devices_container" class="space-y-4">
                    <!-- Dynamic rows inserted by JS -->
                </div>
            </div>

            <!-- Live Port Preview Info Box -->
            <div class="bg-indigo-500/5 border border-indigo-500/10 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="p-2 rounded-xl bg-indigo-500/10 text-indigo-400 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                        </svg>
                    </span>
                    <div>
                        <span class="text-xs font-semibold text-slate-300 uppercase tracking-wider block">Total Kapasitas Terakumulasi</span>
                        <span class="text-[11px] text-slate-400 mt-0.5">Penjumlahan otomatis dari kapasitas seluruh perangkat terpasang di atas.</span>
                    </div>
                </div>
                <div class="flex items-center gap-6 self-end sm:self-auto">
                    <div>
                        <span class="text-[10px] text-slate-400 block font-semibold uppercase">Total Port FO</span>
                        <span id="preview_fo_ports" class="text-lg font-bold text-emerald-400 font-mono">0 Port</span>
                    </div>
                    <div class="border-l border-slate-800 h-8 hidden sm:block"></div>
                    <div>
                        <span class="text-[10px] text-slate-400 block font-semibold uppercase">Total Port LAN</span>
                        <span id="preview_lan_ports" class="text-lg font-bold text-purple-400 font-mono">0 Port</span>
                    </div>
                </div>
            </div>

            <!-- Alamat -->
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5 uppercase">Alamat Lengkap Tiang / Lokasi ODP</label>
                <textarea name="address" rows="3" placeholder="Contoh: Jalan Melati Raya Blok A No.15 (Dekat tiang listrik depan pos satpam)" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">{{ old('address') }}</textarea>
                @error('address')
                    <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- Keterangan -->
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5 uppercase">Keterangan Tambahan</label>
                <textarea name="description" rows="2" placeholder="Contoh: Port 1-4 terisi, sisa 4 port kosong. Kondisi box bersih." class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">{{ old('description') }}</textarea>
                @error('description')
                    <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- Foto Upload Grid -->
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-4 uppercase">Upload Foto Fisik ODP (Maks 4 Foto)</label>
                
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    @for ($i = 1; $i <= 4; $i++)
                        <div class="space-y-2 text-center">
                            <span class="text-[11px] font-bold text-slate-400">Foto {{ $i }}</span>
                            
                            <!-- Custom File Upload Card -->
                            <div class="relative w-full aspect-square bg-slate-950 border border-slate-800 hover:border-indigo-500 rounded-2xl overflow-hidden transition-colors flex items-center justify-center group cursor-pointer">
                                <!-- Image Preview Tag -->
                                <img id="preview-img-{{ $i }}" class="hidden w-full h-full object-cover z-10" />
                                
                                <!-- Icon / Instructions Placeholder -->
                                <div id="preview-placeholder-{{ $i }}" class="flex flex-col items-center gap-1.5 p-2 text-slate-500 group-hover:text-indigo-400 transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span class="text-[10px] font-bold uppercase">Pilih Foto</span>
                                </div>

                                <!-- File Input (Invisible but spans full card) -->
                                <input type="file" name="photo_{{{ $i }}}" accept="image/*" onchange="previewFile(this, {{ $i }})" class="absolute inset-0 opacity-0 z-20 cursor-pointer">
                            </div>
                            @error('photo_' . $i)
                                <span class="text-[10px] text-rose-400 mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>
                    @endfor
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="pt-6 border-t border-slate-800/60 flex items-center justify-end gap-3">
                <a href="{{ route('odps.index') }}" class="bg-slate-800 hover:bg-slate-700 text-white font-semibold px-5 py-3 rounded-xl transition-colors">Batal</a>
                <button type="submit" class="bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-650 hover:to-purple-700 text-white font-semibold px-6 py-3 rounded-xl shadow-lg shadow-indigo-600/15 hover:shadow-indigo-600/25 transition-all duration-300">Simpan ODP</button>
            </div>

        </form>
    </div>
</div>

<!-- Modal Tambah Spesifikasi Baru -->
<div id="addSpecModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-md">
    <div class="bg-slate-900/95 border border-slate-800 rounded-3xl p-6 w-full max-w-md shadow-2xl relative">
        <!-- Close button -->
        <button type="button" onclick="closeSpecModal()" class="absolute top-4 right-4 p-1.5 text-slate-400 hover:text-white bg-slate-950 border border-slate-800 rounded-xl transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        
        <h3 class="text-xl font-extrabold text-white mb-4">Tambah Spesifikasi Baru</h3>
        
        <div class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5 uppercase">Tipe Perangkat</label>
                <input type="text" id="modal_device_type_label" readonly class="w-full bg-slate-950/60 border border-slate-850 rounded-xl px-4 py-3 text-slate-400 font-bold select-none focus:outline-none">
                <input type="hidden" id="modal_device_type_val">
            </div>
            
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5 uppercase">Kode / Nama Spesifikasi</label>
                <input type="text" id="modal_spec_name" placeholder="Contoh: 1/32 atau 4fo8lan" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                <span class="text-[10px] text-slate-500 mt-1 block">Pola standard: "1/32" (Splitter FO), atau "4fo8lan" / "6lan" (LAN).</span>
            </div>
            
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5 uppercase">Label Deskripsi</label>
                <input type="text" id="modal_spec_label" placeholder="Contoh: Splitter 1/32 (32 Port FO)" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
            </div>
            
            <div id="modal_error_box" class="hidden p-3 rounded-xl bg-rose-500/10 text-rose-400 text-xs font-semibold border border-rose-500/20"></div>
        </div>
        
        <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-slate-800/60">
            <button type="button" onclick="closeSpecModal()" class="bg-slate-850 hover:bg-slate-800 border border-slate-800 text-slate-300 hover:text-white font-semibold px-4 py-2.5 rounded-xl text-sm transition-colors">Batal</button>
            <button type="button" onclick="submitSpecModal()" class="bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-650 hover:to-purple-700 text-white font-semibold px-5 py-2.5 rounded-xl text-sm transition-all duration-300">Simpan</button>
        </div>
    </div>
</div>

<!-- JavaScript for Live Photo Preview & Dynamic Device Specs -->
<script>
    function previewFile(input, index) {
        const file = input.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const previewImg = document.getElementById('preview-img-' + index);
                const placeholder = document.getElementById('preview-placeholder-' + index);
                
                previewImg.src = e.target.result;
                previewImg.classList.remove('hidden');
                placeholder.classList.add('hidden');
            }
            reader.readAsDataURL(file);
        }
    }

    const specOptions = {
        'FO': [
            @foreach($foSpecs as $spec)
                { value: "{{ $spec->name }}", label: "{{ $spec->label }}" },
            @endforeach
            { value: 'custom', label: 'Lainnya (Kustom Tulis Sendiri)' }
        ],
        'LAN': [
            @foreach($lanSpecs as $spec)
                { value: "{{ $spec->name }}", label: "{{ $spec->label }}" },
            @endforeach
            { value: 'custom', label: 'Lainnya (Kustom Tulis Sendiri)' }
        ]
    };

    let deviceIndex = 0;
    let activeModalRowIndex = null;

    function populateSpecDropdown(row, type, selectedValue = null) {
        const specSelect = row.querySelector('.device-spec');
        const customContainer = row.querySelector('.custom-spec-container');
        const customInput = row.querySelector('.device-spec-custom');
        
        // Clear previous options
        specSelect.innerHTML = '';
        
        // Populate options
        if (specOptions[type]) {
            specOptions[type].forEach(option => {
                const opt = document.createElement('option');
                opt.value = option.value;
                opt.textContent = option.label;
                if (selectedValue && option.value === selectedValue) {
                    opt.selected = true;
                }
                specSelect.appendChild(opt);
            });
        }
        
        // Check custom spec
        if (specSelect.value === 'custom') {
            customContainer.classList.remove('hidden');
            customInput.required = true;
        } else {
            customContainer.classList.add('hidden');
            customInput.required = false;
            customInput.value = '';
        }
    }

    function addDeviceRow(deviceData = null) {
        const container = document.getElementById('devices_container');
        const index = deviceIndex++;
        
        const rowHtml = `
            <div class="device-row bg-slate-900/60 border border-slate-800/80 p-5 rounded-2xl relative animate-fade-in" data-index="${index}">
                <!-- Row Header -->
                <div class="flex items-center justify-between mb-4 border-b border-slate-800/60 pb-2.5">
                    <span class="text-xs font-bold text-indigo-400 uppercase tracking-wider font-mono">Perangkat #${index + 1}</span>
                    <button type="button" onclick="removeDeviceRow(this)" class="remove-btn text-xs font-bold text-rose-400 hover:text-rose-350 transition-colors flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <span>Hapus</span>
                    </button>
                </div>

                <!-- Fields Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Tipe Perangkat -->
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-400 mb-1.5 uppercase font-bold">Tipe Perangkat</label>
                        <select name="devices[${index}][device_type]" onchange="onDeviceTypeChange(this)" class="device-type w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                            <option value="FO">FO (Fiber Optic)</option>
                            <option value="LAN">LAN (Ethernet)</option>
                        </select>
                    </div>

                    <!-- Spesifikasi -->
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-400 mb-1.5 uppercase font-bold">Spesifikasi / Jumlah Port</label>
                        <div class="flex gap-2">
                            <select name="devices[${index}][device_spec]" onchange="onDeviceSpecChange(this)" class="device-spec w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                                <!-- Populated by JS -->
                            </select>
                            <button type="button" onclick="openSpecModalForRow(${index})" title="Tambah Spesifikasi Baru" class="bg-indigo-500/10 hover:bg-indigo-500 text-indigo-400 hover:text-white border border-indigo-500/20 px-3.5 rounded-xl transition-all duration-300 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Spesifikasi Kustom -->
                    <div class="custom-spec-container hidden">
                        <label class="block text-[10px] font-semibold text-slate-400 mb-1.5 uppercase font-bold text-purple-400">Tulis Spesifikasi Kustom</label>
                        <input type="text" name="devices[${index}][device_spec_custom]" oninput="updateLivePreview()" placeholder="Contoh: 4fo8lan" class="device-spec-custom w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-xs text-slate-200 focus:outline-none focus:border-indigo-500 transition-colors">
                    </div>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', rowHtml);
        
        const row = container.querySelector(`[data-index="${index}"]`);
        const typeSelect = row.querySelector('.device-type');
        const specSelect = row.querySelector('.device-spec');
        const customInput = row.querySelector('.device-spec-custom');
        
        if (deviceData) {
            typeSelect.value = deviceData.device_type;
            populateSpecDropdown(row, deviceData.device_type, deviceData.device_spec);
            if (deviceData.device_spec_is_custom) {
                specSelect.value = 'custom';
                customInput.value = deviceData.device_spec_custom_val;
                row.querySelector('.custom-spec-container').classList.remove('hidden');
                customInput.required = true;
            }
        } else {
            populateSpecDropdown(row, 'FO');
        }
        
        updateRemoveButtonsVisibility();
        updateLivePreview();
        reindexLabels();
    }

    function removeDeviceRow(btn) {
        const row = btn.closest('.device-row');
        row.remove();
        updateRemoveButtonsVisibility();
        updateLivePreview();
        reindexLabels();
    }

    function updateRemoveButtonsVisibility() {
        const rows = document.querySelectorAll('.device-row');
        rows.forEach(row => {
            const removeBtn = row.querySelector('.remove-btn');
            if (rows.length <= 1) {
                removeBtn.classList.add('hidden');
            } else {
                removeBtn.classList.remove('hidden');
            }
        });
    }

    function reindexLabels() {
        const rows = document.querySelectorAll('.device-row');
        rows.forEach((row, idx) => {
            const titleSpan = row.querySelector('.text-indigo-400');
            if (titleSpan) {
                titleSpan.textContent = `Perangkat #${idx + 1}`;
            }
        });
    }

    function onDeviceTypeChange(selectEl) {
        const row = selectEl.closest('.device-row');
        populateSpecDropdown(row, selectEl.value);
        updateLivePreview();
    }

    function onDeviceSpecChange(selectEl) {
        const row = selectEl.closest('.device-row');
        const customContainer = row.querySelector('.custom-spec-container');
        const customInput = row.querySelector('.device-spec-custom');
        
        if (selectEl.value === 'custom') {
            customContainer.classList.remove('hidden');
            customInput.required = true;
            customInput.focus();
        } else {
            customContainer.classList.add('hidden');
            customInput.required = false;
            customInput.value = '';
        }
        updateLivePreview();
    }

    function parsePortsJs(deviceType, spec) {
        spec = spec.toLowerCase().trim();
        let foPorts = 0;
        let lanPorts = 0;

        if (spec.match(/^1\/(\d+)$/)) {
            const match = spec.match(/^1\/(\d+)$/);
            foPorts = parseInt(match[1], 10);
            lanPorts = 0;
            return [foPorts, lanPorts];
        }

        const foMatch = spec.match(/(\d+)\s*fo/);
        if (foMatch) {
            foPorts = parseInt(foMatch[1], 10);
        }

        const lanMatch = spec.match(/(\d+)\s*lan/);
        if (lanMatch) {
            lanPorts = parseInt(lanMatch[1], 10);
        }

        if (foPorts === 0 && lanPorts === 0) {
            if (!isNaN(spec) && spec.length > 0) {
                const num = parseInt(spec, 10);
                if (deviceType === 'FO') {
                    foPorts = num;
                } else {
                    lanPorts = num;
                }
            }
        }

        return [foPorts, lanPorts];
    }

    function updateLivePreview() {
        let totalFo = 0;
        let totalLan = 0;
        
        const rows = document.querySelectorAll('.device-row');
        rows.forEach(row => {
            const typeSelect = row.querySelector('.device-type');
            const specSelect = row.querySelector('.device-spec');
            const customInput = row.querySelector('.device-spec-custom');
            
            let spec = specSelect.value;
            if (spec === 'custom') {
                spec = customInput.value;
            }
            
            const [foPorts, lanPorts] = parsePortsJs(typeSelect.value, spec);
            totalFo += foPorts;
            totalLan += lanPorts;
        });
        
        document.getElementById('preview_fo_ports').textContent = totalFo + ' Port';
        document.getElementById('preview_lan_ports').textContent = totalLan + ' Port';
    }

    // Modal Handlers
    function openSpecModalForRow(index) {
        activeModalRowIndex = index;
        const row = document.querySelector(`.device-row[data-index="${index}"]`);
        const typeVal = row.querySelector('.device-type').value;
        const typeLabel = typeVal === 'FO' ? 'Fiber Optic (FO)' : 'Ethernet (LAN)';
        
        document.getElementById('modal_device_type_label').value = typeLabel;
        document.getElementById('modal_device_type_val').value = typeVal;
        document.getElementById('modal_spec_name').value = '';
        document.getElementById('modal_spec_label').value = '';
        
        // Hide error box
        const errBox = document.getElementById('modal_error_box');
        errBox.classList.add('hidden');
        errBox.textContent = '';

        document.getElementById('addSpecModal').classList.remove('hidden');
        document.getElementById('modal_spec_name').focus();
    }

    function closeSpecModal() {
        document.getElementById('addSpecModal').classList.add('hidden');
    }

    function submitSpecModal() {
        const typeVal = document.getElementById('modal_device_type_val').value;
        const nameVal = document.getElementById('modal_spec_name').value.trim();
        const labelVal = document.getElementById('modal_spec_label').value.trim();
        const errBox = document.getElementById('modal_error_box');

        if (!nameVal || !labelVal) {
            errBox.textContent = 'Harap isi semua kolom spesifikasi.';
            errBox.classList.remove('hidden');
            return;
        }

        errBox.classList.add('hidden');

        // Make AJAX request
        fetch("{{ route('device-specifications.store') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': "{{ csrf_token() }}",
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                device_type: typeVal,
                name: nameVal,
                label: labelVal
            })
        })
        .then(response => response.json().then(data => ({ status: response.status, body: data })))
        .then(res => {
            if (res.status === 200 && res.body.success) {
                // Add to specOptions
                const newSpec = res.body.spec;
                const arr = specOptions[typeVal];
                
                // Add before 'custom' option which is always last
                const customOptIdx = arr.findIndex(opt => opt.value === 'custom');
                if (customOptIdx !== -1) {
                    arr.splice(customOptIdx, 0, { value: newSpec.name, label: newSpec.label });
                } else {
                    arr.push({ value: newSpec.name, label: newSpec.label });
                }

                // Update all rows with this device type to have the new option
                const rows = document.querySelectorAll('.device-row');
                rows.forEach(row => {
                    const rowTypeSelect = row.querySelector('.device-type');
                    const rowSpecSelect = row.querySelector('.device-spec');
                    const rowIndex = parseInt(row.getAttribute('data-index'), 10);
                    
                    if (rowTypeSelect.value === typeVal) {
                        const currentSelectedValue = rowSpecSelect.value;
                        const targetVal = (rowIndex === activeModalRowIndex) ? newSpec.name : currentSelectedValue;
                        populateSpecDropdown(row, typeVal, targetVal);
                    }
                });
                
                // Close Modal
                closeSpecModal();
                updateLivePreview();
            } else {
                errBox.textContent = res.body.message || 'Terjadi kesalahan saat menyimpan.';
                errBox.classList.remove('hidden');
            }
        })
        .catch(err => {
            errBox.textContent = 'Gagal mengirim data. Silakan coba lagi.';
            errBox.classList.remove('hidden');
            console.error(err);
        });
    }

    // Run on load to set initial state
    document.addEventListener('DOMContentLoaded', function() {
        @if(old('devices'))
            @foreach(old('devices') as $oldDevice)
                addDeviceRow({
                    device_type: "{{ $oldDevice['device_type'] }}",
                    device_spec: "{{ $oldDevice['device_spec'] }}",
                    device_spec_is_custom: {{ $oldDevice['device_spec'] === 'custom' ? 'true' : 'false' }},
                    device_spec_custom_val: "{{ $oldDevice['device_spec_custom'] ?? '' }}"
                });
            @endforeach
        @else
            // Add one default empty row on new page load
            addDeviceRow();
        @endif
    });

    async function extractCoordinatesBtn() {
        const link = document.getElementById('maps_link_input').value.trim();
        if (!link) {
            alert('Harap isi Link Google Maps terlebih dahulu!');
            document.getElementById('latitude_input').value = '';
            document.getElementById('longitude_input').value = '';
            return;
        }

        const btn = document.getElementById('btn_extract_maps');
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><span>Mengekstrak...</span>`;

        try {
            const response = await fetch("{{ route('api.extract-coordinates') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ link: link })
            });
            const data = await response.json();
            
            if (data.success) {
                document.getElementById('latitude_input').value = data.lat;
                document.getElementById('longitude_input').value = data.lng;
                
                // Show success flash effect on inputs
                document.getElementById('latitude_input').classList.add('bg-emerald-500/20', 'border-emerald-500');
                document.getElementById('longitude_input').classList.add('bg-emerald-500/20', 'border-emerald-500');
                setTimeout(() => {
                    document.getElementById('latitude_input').classList.remove('bg-emerald-500/20', 'border-emerald-500');
                    document.getElementById('longitude_input').classList.remove('bg-emerald-500/20', 'border-emerald-500');
                }, 1500);
            } else {
                alert('Gagal menemukan titik koordinat dari link tersebut. Pastikan link valid atau isi manual.');
            }
        } catch (error) {
            console.error('Failed to extract coordinates via API', error);
            alert('Terjadi kesalahan jaringan.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    }
</script>
@endsection
