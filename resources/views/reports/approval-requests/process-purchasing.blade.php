@extends('layouts.app')

@section('title', 'Purchasing Item Detail')

@section('content')
<div class="space-y-3">
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            <div class="font-medium mb-1">Terdapat kesalahan:</div>
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Header (with inline received date on the right) -->
    <div class="flex items-center justify-between">
        <div class="flex items-start gap-3">
            <!-- Back button -->
            <a href="{{ route('reports.approval-requests') }}" class="mt-1 p-1.5 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <div>
                <h2 class="text-xl font-semibold text-gray-900">Purchasing Item</h2>
                <p class="text-sm text-gray-600">Request: {{ $item->approvalRequest->request_number ?? '-' }} • Item: {{ $item->masterItem->name ?? '-' }}</p>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-xs text-gray-500">Status:</span>
                    @php
                        $statusColor = match($item->status ?? 'unprocessed') {
                            'done' => 'bg-green-100 text-green-800',
                            'in_progress' => 'bg-yellow-100 text-yellow-800',
                            'unprocessed' => 'bg-gray-100 text-gray-800',
                            default => 'bg-gray-100 text-gray-800'
                        };
                    @endphp
                    <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $statusColor }}">
                        {{ ucfirst($item->status ?? 'unprocessed') }}
                    </span>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            @if(auth()->user()->hasPermission('manage_purchasing'))
            <form method="POST" action="{{ route('approval-requests.set-received-date', $item->approvalRequest) }}" class="flex items-center gap-2">
                @csrf
                <span class="text-xs text-gray-500">Tgl dokumen diterima</span>
                <input type="date" name="received_at" value="{{ $item->approvalRequest->received_at ? $item->approvalRequest->received_at->format('Y-m-d') : '' }}" class="h-8 px-2 border border-gray-300 rounded text-sm" />
                <button class="px-2.5 py-1 bg-blue-600 text-white rounded text-xs">Simpan</button>
            </form>
            @else
            <div class="text-xs text-gray-500">Tgl dokumen diterima
                <span class="ml-2 text-sm text-gray-800 font-medium">{{ $item->approvalRequest->received_at ? $item->approvalRequest->received_at->format('Y-m-d') : '-' }}</span>
            </div>
            @endif
        </div>
    </div>

    
    <!-- Benchmarking Vendors -->
    <div class="bg-white border border-gray-200 rounded-lg">
        <div class="px-3 py-1.5 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900">Vendor Benchmarking</h3>
            <div class="text-xs text-gray-500 flex items-center gap-2">Qty:
                <input type="text" class="h-6 w-16 text-center border border-gray-200 rounded bg-gray-50" value="{{ (int) $item->quantity }}" disabled>
            </div>
        </div>
        <div class="p-2">
            @if(auth()->user()->hasPermission('manage_purchasing'))
            <form method="POST" action="{{ route('purchasing.items.benchmarking', $item) }}" class="space-y-2" id="benchmarking-form">
                @csrf
                <div class="text-xs text-gray-600">Tambah/Replace Benchmarking (min 1, disarankan 3)</div>
                <div id="vendors-wrapper" class="space-y-2">
                    @for($i=0; $i<3; $i++)
                    @php($v = optional($item->vendors->values()->get($i)))
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                        <div class="relative">
                            <input type="hidden" name="vendors[{{ $i }}][supplier_id]" class="supplier-id" value="{{ $v->supplier_id ?? '' }}" />
                            <input type="text" class="supplier-name h-8 w-full px-2 border border-gray-300 rounded text-sm" placeholder="Cari supplier..." autocomplete="off" value="{{ $v && $v->supplier ? $v->supplier->name : '' }}" />
                            <div class="supplier-suggest absolute left-0 right-0 mt-0.5 bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-auto hidden z-50 text-sm"></div>
                        </div>
                        <input type="text" name="vendors[{{ $i }}][unit_price]" class="h-8 px-2 border border-gray-300 rounded text-sm" placeholder="Unit Price (Rp)" value="{{ isset($v->unit_price) ? ('Rp '.number_format((float)$v->unit_price, 0, ',', '.')) : '' }}" />
                        <input type="text" name="vendors[{{ $i }}][total_price]" class="h-8 px-2 border border-gray-300 rounded text-sm" placeholder="Total Price (Rp)" value="{{ isset($v->total_price) ? ('Rp '.number_format((float)$v->total_price, 0, ',', '.')) : '' }}" />
                        <input type="text" name="vendors[{{ $i }}][notes]" class="h-8 px-2 border border-gray-300 rounded text-sm" placeholder="Notes" value="{{ $v->notes ?? '' }}" />
                    </div>
                    @endfor
                </div>
                <div>
                    <button class="px-2.5 py-1 bg-blue-600 text-white rounded text-xs">Simpan Benchmarking</button>
                </div>
            </form>
            @endif
        </div>
    </div>

    <!-- Preferred Vendor -->
    <div class="bg-white border border-gray-200 rounded-lg">
        <div class="px-3 py-1.5 border-b border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900">Preferred Vendor</h3>
        </div>
        <div class="p-2">
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
                    <button class="px-2.5 py-1 bg-blue-600 text-white rounded text-xs">Simpan Preferred</button>
                </div>
            </form>
            @else
                <div class="text-sm text-gray-600">Preferred Vendor: <span class="font-medium text-gray-900">{{ $item->preferredVendor->name ?? '-' }}</span></div>
            @endif
        </div>
    </div>

    <!-- PO & GRN -->
    <div class="bg-white border border-gray-200 rounded-lg">
        <div class="px-3 py-1.5 border-b border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900">PO & GRN</h3>
        </div>
        <div class="p-2 grid grid-cols-1 md:grid-cols-3 gap-3">
            @if(auth()->user()->hasPermission('manage_purchasing'))
            <form method="POST" action="{{ route('purchasing.items.po', $item) }}" class="space-y-2">
                @csrf
                <label class="block text-xs text-gray-600 mb-0.5">PO Number</label>
                <div class="flex gap-2">
                    <input type="text" name="po_number" value="{{ $item->po_number }}" class="flex-1 h-8 px-2 border border-gray-300 rounded text-sm" placeholder="PO..." />
                    <button class="px-2.5 py-1 bg-blue-600 text-white rounded text-xs">Simpan PO</button>
                </div>
            </form>
            <form method="POST" action="{{ route('purchasing.items.grn', $item) }}" class="space-y-2">
                @csrf
                <label class="block text-xs text-gray-600 mb-0.5">GRN Date</label>
                <div class="flex gap-2">
                    <input type="date" name="grn_date" value="{{ $item->grn_date ? $item->grn_date->format('Y-m-d') : '' }}" class="h-8 px-2 border border-gray-300 rounded text-sm" />
                    <button class="px-2.5 py-1 bg-blue-600 text-white rounded text-xs">Simpan GRN</button>
                </div>
            </form>
            <form method="POST" action="{{ route('purchasing.items.invoice', $item) }}" class="space-y-2">
                @csrf
                <label class="block text-xs text-gray-600 mb-0.5">Invoice Number</label>
                <div class="flex gap-2">
                    <input type="text" name="invoice_number" value="{{ $item->invoice_number }}" class="flex-1 h-8 px-2 border border-gray-300 rounded text-sm" placeholder="INV..." />
                    <button class="px-2.5 py-1 bg-blue-600 text-white rounded text-xs">Simpan Invoice</button>
                </div>
            </form>
            @else
                <div class="text-sm text-gray-700">PO: <span class="font-medium text-gray-900">{{ $item->po_number ?? '-' }}</span></div>
                <div class="text-sm text-gray-700">GRN: <span class="font-medium text-gray-900">{{ $item->grn_date ? $item->grn_date->format('Y-m-d') : '-' }}</span></div>
                <div class="text-sm text-gray-700">INV: <span class="font-medium text-gray-900">{{ $item->invoice_number ?? '-' }}</span></div>
            @endif
        </div>
    </div>

    <!-- Action Buttons -->
    @if(auth()->user()->hasPermission('manage_purchasing'))
    <div class="flex items-center justify-between gap-4 flex-wrap">
        <!-- Delete button -->
        <form method="POST" action="{{ route('purchasing.items.delete', $item) }}" onsubmit="return confirm('Hapus purchasing item ini? Data benchmarking dan semua data terkait akan dihapus.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-3 py-1.5 bg-red-600 text-white rounded text-sm hover:bg-red-700 transition-colors">
                <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                Hapus Item
            </button>
        </form>
        
        <!-- Mark DONE with notes (refined layout) -->
        <form method="POST" action="{{ route('purchasing.items.done', $item) }}" onsubmit="return confirm('Tandai item ini sebagai DONE?');" class="w-full md:w-auto">
            @csrf
            <label class="sr-only">Catatan (opsional)</label>
            <div class="flex flex-col md:flex-row gap-2 md:items-center">
                <textarea name="done_notes" class="flex-1 h-10 px-3 border border-gray-300 rounded text-sm resize-y md:resize-none" placeholder="Tulis catatan untuk penutupan (DONE)...">{{ old('done_notes', $item->done_notes) }}</textarea>
                <button type="submit" class="h-10 px-4 bg-green-600 text-white rounded text-sm hover:bg-green-700 transition-colors flex items-center justify-center">
                    <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Mark as DONE
                </button>
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
            const pickedId = String(target.getAttribute('data-id') || '');
            // Prevent duplicates across other rows
            const exists = Array.from(wrapper.querySelectorAll('.supplier-id'))
                .some(input => input !== idInput && String(input.value || '') === pickedId);
            if (exists) {
                alert('Vendor ini sudah dipilih di baris lain. Silakan pilih vendor berbeda.');
                return;
            }
            idInput.value = pickedId;
            nameInput.value = target.getAttribute('data-name') || '';
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

    // Prevent submitting duplicate vendors in benchmarking
    const benchForm = document.getElementById('benchmarking-form');
    if (benchForm) {
        benchForm.addEventListener('submit', async function(ev){
            ev.preventDefault();

            // 1) Auto-resolve/create suppliers for rows that have name typed but no ID
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const rows = Array.from(wrapper.querySelectorAll('#vendors-wrapper > .grid'));
            const toResolve = rows
                .map(r => ({ idInput: r.querySelector('.supplier-id'), nameInput: r.querySelector('.supplier-name') }))
                .filter(x => x && x.nameInput && !x.idInput.value && (x.nameInput.value || '').trim().length >= 2);
            if (toResolve.length) {
                try {
                    await Promise.all(toResolve.map(async ({ idInput, nameInput }) => {
                        const res = await fetch("{{ route('api.suppliers.resolve') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({ name: nameInput.value.trim() }),
                        });
                        const data = await res.json();
                        if (data && data.success && data.supplier && data.supplier.id) {
                            idInput.value = String(data.supplier.id);
                            if (data.supplier.name) nameInput.value = data.supplier.name;
                        }
                    }));
                } catch (e) {
                    console.error('Supplier resolve failed', e);
                }
            }

            // 2) Duplicate check after resolve
            const ids = Array.from(wrapper.querySelectorAll('.supplier-id'))
                .map(i => String(i.value || '').trim())
                .filter(v => v !== '');
            const uniq = new Set(ids);
            if (ids.length !== uniq.size) {
                alert('Terdapat vendor duplikat pada benchmarking. Mohon gunakan vendor yang berbeda.');
                return;
            }

            // 3) Remove/disable empty rows so they are not submitted
            rows.forEach(r => {
                const sid = r.querySelector('.supplier-id');
                if (!sid || !sid.value) {
                    r.querySelectorAll('input').forEach(inp => { inp.disabled = true; });
                }
            });

            // 4) Unformat currency to plain numbers before submit
            rows.forEach(r => {
                const unit = r.querySelector('input[name$="[unit_price]"]');
                const total = r.querySelector('input[name$="[total_price]"]');
                if (unit) unit.value = String(parseRupiah(unit.value));
                if (total) total.value = String(parseRupiah(total.value));
            });

            // 5) Submit form now
            benchForm.submit();
        });
    }

    // Preferred vendor suggest limited to benchmark vendors (live from DOM) and auto-fill prices
    const preferredForm = document.getElementById('preferred-form');
    if (preferredForm) {
        const pName = preferredForm.querySelector('.preferred-supplier-name');
        const pId = preferredForm.querySelector('.preferred-supplier-id');
        const pBox = preferredForm.querySelector('.preferred-supplier-suggest');

        // Safeguard server vendors list (for initial state only)
        const serverVendors = @json($item->vendors->map(fn($v)=>['id'=>$v->supplier_id,'name'=>optional($v->supplier)->name])->values());

        // Read current benchmarking rows from DOM (including unsaved edits)
        const readBenchmarkFromDOM = () => {
            const rows = Array.from(wrapper.querySelectorAll('#vendors-wrapper > .grid'));
            return rows.map(r => {
                const id = r.querySelector('.supplier-id')?.value || '';
                const name = r.querySelector('.supplier-name')?.value || '';
                const unit = r.querySelector('input[name$="[unit_price]"]')?.value || '';
                const total = r.querySelector('input[name$="[total_price]"]')?.value || '';
                return { id, name, unit, total };
            }).filter(v => v.id && v.name);
        };

        // Merge server vendors and DOM vendors (unique by id)
        const getBenchmarkVendors = () => {
            const live = readBenchmarkFromDOM();
            const map = new Map();
            serverVendors.forEach(v => { if (v && v.id) map.set(String(v.id), { id: String(v.id), name: v.name || '' }); });
            live.forEach(v => { map.set(String(v.id), { id: String(v.id), name: v.name, unit: v.unit, total: v.total }); });
            return Array.from(map.values());
        };

        if (pName && pId && pBox) {
            pName.addEventListener('input', function(){
                const q = (this.value||'').toLowerCase().trim();
                pId.value = '';
                const list = getBenchmarkVendors().filter(v => (v.name||'').toLowerCase().includes(q)).slice(0,10);
                if (!q || list.length === 0) { pBox.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500">Ketik untuk mencari vendor hasil benchmarking</div>'; pBox.classList.remove('hidden'); return; }
                pBox.innerHTML = list.map(v => `<div class="px-3 py-2 hover:bg-gray-50 cursor-pointer" data-id="${v.id}" data-name="${v.name}" data-unit="${v.unit||''}" data-total="${v.total||''}">${v.name}</div>`).join('');
                pBox.classList.remove('hidden');
            });
            pBox.addEventListener('click', function(e){
                const t = e.target.closest('[data-id]'); if (!t) return;
                pId.value = t.getAttribute('data-id');
                pName.value = t.getAttribute('data-name');
                // Auto-fill prices from matching benchmark row if available
                const unitPref = preferredForm.querySelector('input[name="unit_price"]');
                const totalPref = preferredForm.querySelector('input[name="total_price"]');
                const unitVal = t.getAttribute('data-unit');
                const totalVal = t.getAttribute('data-total');
                if (unitPref && unitVal) unitPref.value = unitVal;
                if (totalPref && totalVal) totalPref.value = totalVal;
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

        // Ensure plain numbers on submit for preferred form
        preferredForm.addEventListener('submit', function(){
            if (unitPref) unitPref.value = String(parseRupiah(unitPref.value));
            if (totalPref) totalPref.value = String(parseRupiah(totalPref.value));
        });
    }
});
</script>
@endpush
