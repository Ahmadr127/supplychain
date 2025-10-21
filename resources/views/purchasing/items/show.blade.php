@extends('layouts.app')

@section('title', 'Purchasing Item Detail')

@section('content')
<div class="space-y-3">
    <!-- Received Date (Tanggal Diterima) -->
    <div class="bg-white border border-gray-200 rounded-lg p-3">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm font-semibold text-gray-900">Tanggal Diterima Dokumen</div>
                <div class="text-xs text-gray-600">Set tanggal diterima di level request.</div>
            </div>
            @if(auth()->user()->hasPermission('manage_purchasing'))
            <form method="POST" action="{{ route('approval-requests.set-received-date', $item->approvalRequest) }}" class="flex items-end gap-2">
                @csrf
                <div>
                    <label class="block text-xs text-gray-600 mb-0.5">Tanggal</label>
                    <input type="date" name="received_at" value="{{ $item->approvalRequest->received_at ? $item->approvalRequest->received_at->format('Y-m-d') : '' }}" class="h-8 px-2 border border-gray-300 rounded text-sm" />
                </div>
                <button class="px-3 py-1.5 bg-blue-600 text-white rounded text-sm">Simpan</button>
            </form>
            @else
                <div class="text-sm text-gray-700">Tanggal: <span class="font-medium text-gray-900">{{ $item->approvalRequest->received_at ? $item->approvalRequest->received_at->format('Y-m-d') : '-' }}</span></div>
            @endif
        </div>
    </div>
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">Purchasing Item</h2>
            <p class="text-sm text-gray-600">Request: {{ $item->approvalRequest->request_number ?? '-' }} • Item: {{ $item->masterItem->name ?? '-' }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('purchasing.items.index') }}" class="px-3 py-1.5 text-sm rounded border border-gray-300 hover:bg-gray-50">Kembali</a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
        <div class="bg-white border border-gray-200 rounded-lg p-3">
            <div class="text-xs text-gray-600">Status</div>
            @php
                $ps = $item->status ?? 'unprocessed';
                $psText = match($ps){
                    'unprocessed' => 'Belum diproses',
                    'benchmarking' => 'Pemilihan vendor',
                    'selected' => 'Uji coba/Proses PR sistem',
                    'po_issued' => 'Proses di vendor',
                    'grn_received' => 'Barang sudah diterima',
                    'done' => 'Selesai',
                    default => strtoupper($ps),
                };
                $psColor = match($ps){
                    'unprocessed' => 'bg-gray-100 text-gray-700',
                    'benchmarking' => 'bg-yellow-100 text-yellow-800',
                    'selected' => 'bg-blue-100 text-blue-800',
                    'po_issued' => 'bg-indigo-100 text-indigo-800',
                    'grn_received' => 'bg-teal-100 text-teal-800',
                    'done' => 'bg-green-100 text-green-800',
                    default => 'bg-gray-100 text-gray-700',
                };
            @endphp
            <div class="mt-1"><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $psColor }}">{{ $psText }}</span></div>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-3">
            <div class="text-xs text-gray-600">Preferred Vendor</div>
            <div class="text-sm font-medium text-gray-900">{{ $item->preferredVendor->name ?? '-' }}</div>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-3">
            <div class="text-xs text-gray-600">PO</div>
            <div class="text-sm font-medium text-gray-900">{{ $item->po_number ?? '-' }}</div>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-3">
            <div class="text-xs text-gray-600">GRN Date • Proc Cycle</div>
            <div class="text-sm font-medium text-gray-900">{{ $item->grn_date ? $item->grn_date->format('Y-m-d') : '-' }} @if($item->proc_cycle_days) • {{ $item->proc_cycle_days }} hari @endif</div>
        </div>
    </div>

    <!-- Benchmarking Vendors -->
    <div class="bg-white border border-gray-200 rounded-lg">
        <div class="px-3 py-2 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900">Vendor Benchmarking</h3>
            <div class="text-xs text-gray-500 flex items-center gap-2">Qty:
                <input type="text" class="h-6 w-16 text-center border border-gray-200 rounded bg-gray-50" value="{{ (int) $item->quantity }}" disabled>
            </div>
        </div>
        <div class="p-3">
            @if(auth()->user()->hasPermission('manage_purchasing'))
            <form method="POST" action="{{ route('purchasing.items.benchmarking', $item) }}" class="space-y-2" id="benchmarking-form">
                @csrf
                <div class="text-xs text-gray-600">Tambah/Replace Benchmarking (min 1, disarankan 3)</div>
                <div id="vendors-wrapper" class="space-y-2">
                    @for($i=0; $i<3; $i++)
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                        <div class="relative">
                            <input type="hidden" name="vendors[{{ $i }}][supplier_id]" class="supplier-id" />
                            <input type="text" class="supplier-name h-8 w-full px-2 border border-gray-300 rounded text-sm" placeholder="Cari supplier..." autocomplete="off" />
                            <div class="supplier-suggest absolute left-0 right-0 mt-0.5 bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-auto hidden z-50 text-sm"></div>
                        </div>
                        <input type="text" name="vendors[{{ $i }}][unit_price]" class="h-8 px-2 border border-gray-300 rounded text-sm" placeholder="Unit Price (Rp)" />
                        <input type="text" name="vendors[{{ $i }}][total_price]" class="h-8 px-2 border border-gray-300 rounded text-sm" placeholder="Total Price (Rp)" />
                        <input type="text" name="vendors[{{ $i }}][notes]" class="h-8 px-2 border border-gray-300 rounded text-sm" placeholder="Notes" />
                    </div>
                    @endfor
                </div>
                <div>
                    <button class="px-3 py-1.5 bg-blue-600 text-white rounded text-sm">Simpan Benchmarking</button>
                </div>
            </form>
            @endif
        </div>
    </div>

    <!-- Preferred Vendor -->
    <div class="bg-white border border-gray-200 rounded-lg">
        <div class="px-3 py-2 border-b border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900">Preferred Vendor</h3>
        </div>
        <div class="p-3">
            @if(auth()->user()->hasPermission('manage_purchasing'))
            <form method="POST" action="{{ route('purchasing.items.preferred', $item) }}" class="grid grid-cols-1 md:grid-cols-5 gap-2 items-end" id="preferred-form">
                @csrf
                <div class="md:col-span-2 relative">
                    <label class="block text-xs text-gray-600 mb-0.5">Vendor (benchmark)</label>
                    <input type="hidden" name="supplier_id" class="preferred-supplier-id" value="{{ $item->preferred_vendor_id }}" />
                    <input type="text" class="preferred-supplier-name h-8 w-full px-2 border border-gray-300 rounded text-sm" placeholder="Cari vendor dari hasil benchmarking..." autocomplete="off" value="{{ $item->preferredVendor->name ?? '' }}" />
                    <div class="preferred-supplier-suggest absolute left-0 right-0 mt-0.5 bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-auto hidden z-50 text-sm"></div>
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-0.5">Unit Price</label>
                    <input type="text" name="unit_price" value="{{ $item->preferred_unit_price }}" class="w-full h-8 px-2 border border-gray-300 rounded text-sm currency-input" placeholder="Rp" />
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-0.5">Total Price</label>
                    <input type="text" name="total_price" value="{{ $item->preferred_total_price }}" class="w-full h-8 px-2 border border-gray-300 rounded text-sm currency-input" placeholder="Rp" />
                </div>
                <div>
                    <button class="px-3 py-1.5 bg-blue-600 text-white rounded text-sm">Simpan Preferred</button>
                </div>
            </form>
            @else
                <div class="text-sm text-gray-600">Preferred Vendor: <span class="font-medium text-gray-900">{{ $item->preferredVendor->name ?? '-' }}</span></div>
            @endif
        </div>
    </div>

    <!-- PO & GRN -->
    <div class="bg-white border border-gray-200 rounded-lg">
        <div class="px-3 py-2 border-b border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900">PO & GRN</h3>
        </div>
        <div class="p-3 grid grid-cols-1 md:grid-cols-2 gap-3">
            @if(auth()->user()->hasPermission('manage_purchasing'))
            <form method="POST" action="{{ route('purchasing.items.po', $item) }}" class="space-y-2">
                @csrf
                <label class="block text-xs text-gray-600 mb-0.5">PO Number</label>
                <div class="flex gap-2">
                    <input type="text" name="po_number" value="{{ $item->po_number }}" class="flex-1 h-8 px-2 border border-gray-300 rounded text-sm" placeholder="PO..." />
                    <button class="px-3 py-1.5 bg-blue-600 text-white rounded text-sm">Simpan PO</button>
                </div>
            </form>
            <form method="POST" action="{{ route('purchasing.items.grn', $item) }}" class="space-y-2">
                @csrf
                <label class="block text-xs text-gray-600 mb-0.5">GRN Date</label>
                <div class="flex gap-2">
                    <input type="date" name="grn_date" value="{{ $item->grn_date ? $item->grn_date->format('Y-m-d') : '' }}" class="h-8 px-2 border border-gray-300 rounded text-sm" />
                    <button class="px-3 py-1.5 bg-blue-600 text-white rounded text-sm">Simpan GRN</button>
                </div>
            </form>
            @else
                <div class="text-sm text-gray-700">PO: <span class="font-medium text-gray-900">{{ $item->po_number ?? '-' }}</span></div>
                <div class="text-sm text-gray-700">GRN: <span class="font-medium text-gray-900">{{ $item->grn_date ? $item->grn_date->format('Y-m-d') : '-' }}</span></div>
            @endif
        </div>
    </div>

    <!-- Mark DONE -->
    @if(auth()->user()->hasPermission('manage_purchasing'))
    <div class="flex justify-end">
        <form method="POST" action="{{ route('purchasing.items.done', $item) }}" onsubmit="return confirm('Tandai item ini sebagai DONE?');" class="w-full md:w-auto grid grid-cols-1 md:grid-cols-3 gap-2 md:items-end">
            @csrf
            <div class="md:col-span-2">
                <label class="block text-xs text-gray-600 mb-0.5">Catatan (opsional)</label>
                <textarea name="done_notes" rows="2" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" placeholder="Tulis catatan untuk penutupan (DONE)...">{{ old('done_notes', $item->done_notes) }}</textarea>
            </div>
            <div>
                <button class="px-4 py-2 bg-green-600 text-white rounded text-sm w-full">Mark as DONE</button>
            </div>
        </form>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const wrapper = document.getElementById('vendors-wrapper');
    if (!wrapper) return;

    const bindRow = (row) => {
        const nameInput = row.querySelector('.supplier-name');
        const idInput = row.querySelector('.supplier-id');
        const box = row.querySelector('.supplier-suggest');
        if (!nameInput || !idInput || !box) return;

        let timer;
        nameInput.addEventListener('input', function() {
            idInput.value = '';
            const q = (this.value || '').trim();
            clearTimeout(timer);
            if (q.length < 2) { box.classList.add('hidden'); box.innerHTML=''; return; }
            timer = setTimeout(async () => {
                try {
                    const url = new URL("{{ route('api.suppliers.suggest') }}", window.location.origin);
                    url.searchParams.set('search', q);
                    const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    const data = await res.json();
                    const list = (data.suppliers || []).slice(0, 10);
                    if (!list.length) { box.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500">Tidak ada hasil</div>'; box.classList.remove('hidden'); return; }
                    box.innerHTML = list.map(s => `
                        <div class="px-3 py-2 hover:bg-gray-50 cursor-pointer" data-id="${s.id}" data-name="${s.name}">
                            <div class="flex justify-between">
                                <span>${s.name}</span>
                                <span class="text-xs text-gray-500">${s.code || ''}</span>
                            </div>
                            <div class="text-xs text-gray-400">${s.email || ''}${s.phone? ' • '+s.phone:''}</div>
                        </div>
                    `).join('');
                    box.classList.remove('hidden');
                } catch (e) { /* ignore */ }
            }, 200);
        });

        box.addEventListener('click', function(e) {
            const target = e.target.closest('[data-id]');
            if (!target) return;
            idInput.value = target.getAttribute('data-id');
            nameInput.value = target.getAttribute('data-name');
            box.classList.add('hidden');
        });

        nameInput.addEventListener('blur', () => setTimeout(() => box.classList.add('hidden'), 200));
    };

    // Currency helpers
    const parseRupiah = (val) => {
        if (val == null) return 0;
        const s = String(val).replace(/[^0-9]/g,'');
        return s ? parseInt(s, 10) : 0;
    };
    const formatRupiah = (n) => {
        try { return 'Rp ' + (parseInt(n,10)||0).toLocaleString('id-ID'); } catch { return 'Rp ' + n; }
    };

    // Bind currency inputs and auto-calc total for benchmarking rows
    const qty = {{ (int) $item->quantity }};
    const bindCurrencyRow = (row) => {
        const unit = row.querySelector('input[name$="[unit_price]"]');
        const total = row.querySelector('input[name$="[total_price]"]');
        if (!unit || !total) return;
        const onInput = () => {
            const unitVal = parseRupiah(unit.value);
            unit.value = formatRupiah(unitVal);
            if (!total.dataset.manual) {
                const t = unitVal * Math.max(1, qty);
                total.value = formatRupiah(t);
            }
        };
        const onTotalInput = () => {
            const tVal = parseRupiah(total.value);
            total.value = formatRupiah(tVal);
            total.dataset.manual = '1';
        };
        unit.addEventListener('input', onInput);
        unit.addEventListener('blur', onInput);
        total.addEventListener('input', onTotalInput);
        total.addEventListener('blur', onTotalInput);
    };

    wrapper.querySelectorAll('.grid').forEach(r => { bindRow(r); bindCurrencyRow(r); });

    // Preferred vendor suggest limited to benchmark vendors
    const preferredForm = document.getElementById('preferred-form');
    if (preferredForm) {
        const pName = preferredForm.querySelector('.preferred-supplier-name');
        const pId = preferredForm.querySelector('.preferred-supplier-id');
        const pBox = preferredForm.querySelector('.preferred-supplier-suggest');
        const benchVendors = @json($item->vendors->map(fn($v)=>['id'=>$v->supplier_id,'name'=>$v->supplier->name])->values());
        if (pName && pId && pBox) {
            pName.addEventListener('input', function(){
                const q = (this.value||'').toLowerCase().trim();
                pId.value = '';
                const list = benchVendors.filter(v => v.name.toLowerCase().includes(q)).slice(0,10);
                if (!q || list.length === 0) { pBox.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500">Ketik untuk mencari vendor hasil benchmarking</div>'; pBox.classList.remove('hidden'); return; }
                pBox.innerHTML = list.map(v => `<div class="px-3 py-2 hover:bg-gray-50 cursor-pointer" data-id="${v.id}" data-name="${v.name}">${v.name}</div>`).join('');
                pBox.classList.remove('hidden');
            });
            pBox.addEventListener('click', function(e){
                const t = e.target.closest('[data-id]'); if (!t) return;
                pId.value = t.getAttribute('data-id');
                pName.value = t.getAttribute('data-name');
                pBox.classList.add('hidden');
            });
            pName.addEventListener('blur', () => setTimeout(()=> pBox.classList.add('hidden'), 200));
        }

        // Currency formatting and auto total for preferred
        const unitPref = preferredForm.querySelector('input[name="unit_price"]');
        const totalPref = preferredForm.querySelector('input[name="total_price"]');
        const onPrefUnit = () => {
            const uv = parseRupiah(unitPref.value);
            unitPref.value = formatRupiah(uv);
            if (!totalPref.dataset.manual) {
                totalPref.value = formatRupiah(uv * Math.max(1, qty));
            }
        };
        const onPrefTotal = () => {
            const tv = parseRupiah(totalPref.value);
            totalPref.value = formatRupiah(tv);
            totalPref.dataset.manual = '1';
        };
        if (unitPref && totalPref) {
            unitPref.addEventListener('input', onPrefUnit);
            unitPref.addEventListener('blur', onPrefUnit);
            totalPref.addEventListener('input', onPrefTotal);
            totalPref.addEventListener('blur', onPrefTotal);
            // initial format
            if (unitPref.value) onPrefUnit();
            if (totalPref.value) onPrefTotal();
        }
    }
});
</script>
@endpush
