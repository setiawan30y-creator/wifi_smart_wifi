@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-white mb-2">Edit Akun PPPoE</h1>
        <p class="text-slate-400">Edit rahasia (secret) PPPoE untuk pelanggan {{ $customer->name }}.</p>
    </div>

    @if(session('error'))
        <div class="mb-6 p-4 bg-rose-500/10 border border-rose-500/20 rounded-xl flex items-start gap-3">
            <svg class="w-5 h-5 text-rose-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <div class="text-sm font-medium text-rose-400">{{ session('error') }}</div>
        </div>
    @endif

    <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="p-6">
            <form action="{{ route('pppoe.update', $customer->id) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Data Pelanggan Billing</label>
                        <div class="relative mb-2">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.35-5.15a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0z" />
                                </svg>
                            </div>
                            <input
                                type="text"
                                id="customer-billing-search"
                                class="w-full bg-slate-950 border border-slate-800 text-slate-200 text-sm rounded-xl focus:ring-indigo-500 focus:border-indigo-500 block pl-10 pr-24 py-2.5 transition-colors"
                                placeholder="Cari nama, nomor HP, alamat..."
                                autocomplete="off">
                            <button
                                type="button"
                                onclick="clearCustomerBillingSearch()"
                                class="absolute inset-y-1.5 right-1.5 px-3 rounded-lg text-[11px] font-bold text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">
                                Reset
                            </button>
                        </div>
                        <select name="customer_id" class="w-full bg-slate-800 border border-slate-700 text-white text-sm rounded-xl focus:ring-indigo-500 focus:border-indigo-500 block p-3 transition-colors" required>
                            @foreach($availableCustomers as $availableCustomer)
                                <option
                                    value="{{ $availableCustomer->id }}"
                                    data-search="{{ strtolower($availableCustomer->name . ' ' . ($availableCustomer->phone ?? '') . ' ' . ($availableCustomer->address ?? '') . ' ' . ($availableCustomer->pppoe_username ?? '')) }}"
                                    data-name="{{ $availableCustomer->name }}"
                                    data-phone="{{ $availableCustomer->phone ?? '-' }}"
                                    data-address="{{ $availableCustomer->address ?? '-' }}"
                                    {{ (old('customer_id', $customer->id) == $availableCustomer->id) ? 'selected' : '' }}>
                                    {{ $availableCustomer->name }} - {{ $availableCustomer->phone ?? '-' }} - {{ \Illuminate\Support\Str::limit($availableCustomer->address ?? '-', 45) }}
                                    @if($availableCustomer->id === $customer->id)
                                        (data saat ini)
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <p id="customer-billing-search-info" class="mt-1 text-[11px] text-indigo-400 hidden"></p>
                        <div id="selected-customer-billing-preview" class="mt-3 rounded-xl border border-slate-800 bg-slate-950/50 px-4 py-3 text-xs text-slate-300"></div>
                        <p class="mt-2 text-xs text-slate-500">Untuk menghindari data dobel, pilih pelanggan asli di sini. Jika data lama hanya hasil import MikroTik, sistem akan membersihkannya setelah dipindahkan.</p>
                        @error('customer_id')
                            <p class="mt-2 text-sm text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Username PPPoE</label>
                        <input type="text" name="pppoe_username" value="{{ old('pppoe_username', $customer->pppoe_username) }}" class="w-full bg-slate-800 border border-slate-700 text-white text-sm rounded-xl focus:ring-indigo-500 focus:border-indigo-500 block p-3 transition-colors" required>
                        @error('pppoe_username')
                            <p class="mt-2 text-sm text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Password PPPoE</label>
                        <input type="text" name="pppoe_password" value="{{ old('pppoe_password', $customer->pppoe_password) }}" class="w-full bg-slate-800 border border-slate-700 text-white text-sm rounded-xl focus:ring-indigo-500 focus:border-indigo-500 block p-3 transition-colors" required>
                        @error('pppoe_password')
                            <p class="mt-2 text-sm text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Paket Layanan (Profile MikroTik)</label>
                        <select name="package_id" class="w-full bg-slate-800 border border-slate-700 text-white text-sm rounded-xl focus:ring-indigo-500 focus:border-indigo-500 block p-3 transition-colors" required>
                            @foreach($packages as $pkg)
                                <option value="{{ $pkg->id }}" {{ (old('package_id', $customer->package_id) == $pkg->id) ? 'selected' : '' }}>
                                    {{ $pkg->name }} - {{ $pkg->speed }} (Rp {{ number_format($pkg->price, 0, ',', '.') }})
                                </option>
                            @endforeach
                        </select>
                        @error('package_id')
                            <p class="mt-2 text-sm text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Status Koneksi</label>
                        <select name="status" class="w-full bg-slate-800 border border-slate-700 text-white text-sm rounded-xl focus:ring-indigo-500 focus:border-indigo-500 block p-3 transition-colors" required>
                            <option value="active" {{ (old('status', $customer->status) == 'active') ? 'selected' : '' }}>Aktif</option>
                            <option value="inactive" {{ (old('status', $customer->status) == 'inactive') ? 'selected' : '' }}>Nonaktif (Disabled)</option>
                        </select>
                        @error('status')
                            <p class="mt-2 text-sm text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-8 flex items-center gap-3">
                    <button type="submit" class="px-6 py-2.5 rounded-xl font-bold text-sm bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-500/25 transition-all">
                        Simpan Perubahan
                    </button>
                    <a href="{{ route('pppoe.index') }}" class="px-6 py-2.5 rounded-xl font-bold text-sm bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition-all">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    const customerBillingSearch = document.getElementById('customer-billing-search');
    const customerBillingSelect = document.querySelector('select[name="customer_id"]');
    const customerBillingSearchInfo = document.getElementById('customer-billing-search-info');
    const selectedCustomerBillingPreview = document.getElementById('selected-customer-billing-preview');

    function filterCustomerBillingOptions() {
        if (!customerBillingSearch || !customerBillingSelect) {
            return;
        }

        const keyword = customerBillingSearch.value.trim().toLowerCase();
        let visibleCount = 0;

        Array.from(customerBillingSelect.options).forEach((option) => {
            const match = !keyword || (option.dataset.search || option.textContent.toLowerCase()).includes(keyword);
            option.hidden = !match;
            option.disabled = !match;
            if (match) {
                visibleCount++;
            }
        });

        const selectedOption = customerBillingSelect.options[customerBillingSelect.selectedIndex];
        if (selectedOption && selectedOption.hidden) {
            const firstVisible = Array.from(customerBillingSelect.options).find((option) => !option.hidden);
            if (firstVisible) {
                customerBillingSelect.value = firstVisible.value;
            }
        }

        if (customerBillingSearchInfo) {
            customerBillingSearchInfo.textContent = keyword ? `${visibleCount} pelanggan cocok ditemukan.` : '';
            customerBillingSearchInfo.classList.toggle('hidden', !keyword);
        }

        updateSelectedCustomerBillingPreview();
    }

    function updateSelectedCustomerBillingPreview() {
        if (!customerBillingSelect || !selectedCustomerBillingPreview) {
            return;
        }

        const option = customerBillingSelect.options[customerBillingSelect.selectedIndex];
        if (!option) {
            selectedCustomerBillingPreview.innerHTML = '<span class="text-slate-500">Belum ada pelanggan dipilih.</span>';
            return;
        }

        selectedCustomerBillingPreview.innerHTML = `
            <div class="font-bold text-white">${option.dataset.name || option.textContent.trim()}</div>
            <div class="mt-1 grid gap-1 sm:grid-cols-2">
                <div><span class="text-slate-500">HP:</span> ${option.dataset.phone || '-'}</div>
                <div class="sm:col-span-2"><span class="text-slate-500">Alamat:</span> ${option.dataset.address || '-'}</div>
            </div>
        `;
    }

    function clearCustomerBillingSearch() {
        if (!customerBillingSearch) {
            return;
        }

        customerBillingSearch.value = '';
        filterCustomerBillingOptions();
        customerBillingSearch.focus();
    }

    if (customerBillingSearch) {
        customerBillingSearch.addEventListener('input', filterCustomerBillingOptions);
    }

    if (customerBillingSelect) {
        customerBillingSelect.addEventListener('change', updateSelectedCustomerBillingPreview);
        updateSelectedCustomerBillingPreview();
    }
</script>
@endsection
