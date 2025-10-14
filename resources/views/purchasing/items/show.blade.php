@extends('layouts.app')

@section('title', 'Purchasing Item Detail')

@section('content')
<div class="space-y-3">
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
            <div class="text-base font-semibold {{ $item->status==='done' ? 'text-green-700' : 'text-gray-900' }}">{{ strtoupper($item->status) }}</div>
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
            <div class="text-xs text-gray-500">Qty: {{ $item->quantity }}</div>
        </div>
        <div class="p-3">
            <div class="overflow-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-2 py-1 text-left">Supplier</th>
                            <th class="px-2 py-1 text-right">Unit Price</th>
                            <th class="px-2 py-1 text-right">Total Price</th>
                            <th class="px-2 py-1 text-left">Notes</th>
                            <th class="px-2 py-1 text-left">Preferred</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($item->vendors as $v)
                            <tr class="border-t">
                                <td class="px-2 py-1">{{ $v->supplier->name ?? '-' }}</td>
                                <td class="px-2 py-1 text-right">{{ 'Rp '.number_format((float)$v->unit_price, 0, ',', '.') }}</td>
                                <td class="px-2 py-1 text-right">{{ 'Rp '.number_format((float)$v->total_price, 0, ',', '.') }}</td>
                                <td class="px-2 py-1">{{ $v->notes ?? '-' }}</td>
                                <td class="px-2 py-1">{!! $v->is_preferred ? '<span class="px-2 py-0.5 rounded text-xs bg-green-100 text-green-800">YES</span>' : '-' !!}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-2 py-3 text-center text-sm text-gray-500">Belum ada data benchmarking</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(auth()->user()->hasPermission('manage_purchasing'))
            <form method="POST" action="{{ route('purchasing.items.benchmarking', $item) }}" class="mt-3 space-y-2" id="benchmarking-form">
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
            <form method="POST" action="{{ route('purchasing.items.preferred', $item) }}" class="grid grid-cols-1 md:grid-cols-5 gap-2 items-end">
                @csrf
                <div class="md:col-span-2">
                    <label class="block text-xs text-gray-600 mb-0.5">Vendor</label>
                    <select name="supplier_id" class="w-full h-8 px-2 border border-gray-300 rounded text-sm">
                        @foreach($item->vendors as $v)
                            <option value="{{ $v->supplier_id }}" {{ $item->preferred_vendor_id===$v->supplier_id ? 'selected' : '' }}>{{ $v->supplier->name ?? ('Supplier #'.$v->supplier_id) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-0.5">Unit Price</label>
                    <input type="text" name="unit_price" value="{{ $item->preferred_unit_price }}" class="w-full h-8 px-2 border border-gray-300 rounded text-sm" placeholder="Rp" />
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-0.5">Total Price</label>
                    <input type="text" name="total_price" value="{{ $item->preferred_total_price }}" class="w-full h-8 px-2 border border-gray-300 rounded text-sm" placeholder="Rp" />
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
        <form method="POST" action="{{ route('purchasing.items.done', $item) }}" onsubmit="return confirm('Tandai item ini sebagai DONE?');">
            @csrf
            <button class="px-4 py-2 bg-green-600 text-white rounded text-sm">Mark as DONE</button>
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

    wrapper.querySelectorAll('.grid').forEach(bindRow);
});
</script>
@endpush
