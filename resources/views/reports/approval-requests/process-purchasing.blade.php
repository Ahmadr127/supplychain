@extends('layouts.app')
@section('title', 'Purchasing Item Detail')
@section('content')
@php
    $canPurchasing = $canPurchasing ?? (auth()->user()->hasPermission('manage_purchasing') || auth()->user()->hasPermission('process_purchasing_item'));
    $canVendor     = $canVendor ?? auth()->user()->hasPermission('manage_vendor');
    $ps = $item->status ?? 'unprocessed';
    $statusLabel = match($ps) {
        'unprocessed'  => 'Belum diproses',
        'benchmarking' => 'Benchmarking Vendor',
        'selected'     => 'Vendor Dipilih',
        'po_issued'    => 'PO Dikirim',
        'grn_received' => 'Barang Diterima',
        'done'         => 'Selesai',
        default        => strtoupper($ps),
    };
    $statusColor = match($ps) {
        'unprocessed'  => 'bg-gray-100 text-gray-700',
        'benchmarking' => 'bg-yellow-100 text-yellow-800',
        'selected'     => 'bg-blue-100 text-blue-800',
        'po_issued'    => 'bg-indigo-100 text-indigo-800',
        'grn_received' => 'bg-teal-100 text-teal-800',
        'done'         => 'bg-green-100 text-green-800',
        default        => 'bg-gray-100 text-gray-700',
    };
    $pSteps = $pSteps ?? collect();
    $purchasingSteps = $purchasingSteps ?? collect();
    $releaseSteps = $purchasingSteps->where('step_phase', 'release');
@endphp

<div class="space-y-2 w-full pb-8">
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            <div class="font-medium mb-1">Terdapat kesalahan:</div>
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex items-center gap-2">
        <a href="{{ route('reports.approval-requests') }}" class="p-1 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors">
            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div class="flex-1">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-gray-900 leading-tight">Proses Purchasing</h2>
                    <p class="text-[11px] text-gray-500">
                        Request: <strong class="text-gray-700">{{ $item->approvalRequest->request_number ?? '-' }}</strong>
                        &bull; Barang: <strong class="text-gray-700">{{ $item->masterItem->name ?? '-' }}</strong>
                        &bull; Qty: <strong class="text-gray-700">{{ (int) $item->quantity }}</strong>
                    </p>
                </div>
                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full {{ $statusColor }}">{{ $statusLabel }}</span>
            </div>
        </div>
    </div>

    {{-- Status Overview --}}
    <div class="mb-1">
        <x-info-status variant="purchasing" size="sm" :counts="$purchasingCounts ?? []" />
    </div>

    {{-- ═══ DYNAMIC PURCHASING STEPS (driven by PurchasingTypeService) ═══ --}}
    @foreach($pSteps as $pIdx => $pStep)
        @php
            $pState   = $pStep->done ? 'done' : ($pStep->locked ? 'locked' : 'active');
            $pFormKey = $pStep->form;
        @endphp

        <div class="bg-white border rounded-lg overflow-hidden {{ $pState === 'done' ? 'border-green-200' : ($pState === 'active' ? 'border-blue-300 ring-1 ring-blue-100' : 'border-gray-200') }}">
            <div class="flex items-center justify-between px-3 py-1.5 border-b {{ $pState === 'done' ? 'bg-green-50 border-green-100' : ($pState === 'active' ? 'bg-blue-50 border-blue-100' : 'bg-gray-50 border-gray-100') }}">
                <div class="flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full {{ $pState === 'done' ? 'bg-green-500' : ($pState === 'active' ? 'bg-blue-600' : 'bg-gray-300') }} text-white flex items-center justify-center text-xs font-bold">
                        {{ $pState === 'done' ? '✓' : ($pIdx + 1) }}
                    </span>
                    <span class="font-semibold {{ $pState === 'locked' ? 'text-gray-400' : 'text-gray-800' }}">{{ $pStep->label }}</span>
                </div>
                @if($pStep->allow_skip && $pState === 'locked')
                @elseif($pState === 'done')
                    <span class="text-xs text-green-700 bg-green-100 px-2 py-0.5 rounded-full">Done</span>
                @endif
            </div>

            <div class="p-3">
                @if($pState === 'locked')
                    <div class="flex items-center gap-2 text-sm text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H9m3-6V7a3 3 0 00-6 0v4m9 0H6a2 2 0 00-2 2v5a2 2 0 002 2h12a2 2 0 002-2v-5a2 2 0 00-2-2h-3z"/></svg>
                        Step ini masih terkunci. Selesaikan step sebelumnya terlebih dahulu.
                    </div>

                @elseif($pFormKey === 'benchmarking')
                    @include('purchasing.partials._form-benchmarking', ['item' => $item, 'pStep' => $pStep])

                @elseif($pFormKey === 'trial')
                    @include('purchasing.partials._form-trial', ['item' => $item, 'pStep' => $pStep])

                @elseif($pFormKey === 'preferred')
                    @include('purchasing.partials._form-preferred', ['item' => $item, 'canVendor' => $canVendor, 'pStep' => $pStep])

                @elseif($pFormKey === 'po')
                    @include('purchasing.partials._form-po', ['item' => $item, 'pStep' => $pStep])

                @elseif($pFormKey === 'grn')
                    @include('purchasing.partials._form-invoice-grn', ['item' => $item, 'pStep' => $pStep])

                @endif
            </div>
        </div>
    @endforeach

    {{-- Release Steps --}}
    @if($releaseSteps->isNotEmpty())
        <div class="border-t pt-3 mt-1">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Proses Release</p>
            @foreach($releaseSteps->sortBy('step_number') as $rs)
                @php
                    $rsState = $rs->status === 'approved' ? 'done'
                             : ($rs->status === 'pending' ? 'active' : 'locked');
                @endphp
                <div class="bg-white border rounded-lg overflow-hidden mb-2 {{ $rsState === 'done' ? 'border-green-200' : ($rsState === 'active' ? 'border-blue-300 ring-1 ring-blue-100' : 'border-gray-200') }}">
                    <div class="flex items-center justify-between px-3 py-1.5 {{ $rsState === 'done' ? 'bg-green-50' : ($rsState === 'active' ? 'bg-blue-50' : 'bg-gray-50') }}">
                        <span class="font-semibold text-sm {{ $rsState === 'locked' ? 'text-gray-400' : 'text-gray-800' }}">{{ $rs->step_name }}</span>
                        @if($rsState === 'done')
                            <span class="text-xs text-green-700 bg-green-100 px-2 py-0.5 rounded-full">Approved</span>
                        @elseif($rsState === 'active')
                            <span class="text-xs text-blue-700 bg-blue-100 px-2 py-0.5 rounded-full">Menunggu Approval</span>
                        @else
                            <span class="text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">Terkunci</span>
                        @endif
                    </div>
                    @if($rs->approved_at)
                        <div class="px-3 py-1.5 text-xs text-gray-500">
                            Disetujui: {{ \Carbon\Carbon::parse($rs->approved_at)->format('d/m/Y H:i') }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- Delete button --}}
    @if($canPurchasing && $ps !== 'done')
        <div class="flex justify-start">
            <form method="POST" action="{{ route('purchasing.items.delete', $item) }}"
                  onsubmit="return confirm('Hapus purchasing item ini? Semua data benchmarking akan terhapus.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-lg text-sm transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Hapus Item Ini
                </button>
            </form>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const wrapper = document.getElementById('vendors-wrapper');
    const qty = {{ (int) $item->quantity }};

    const parseRupiah = v => parseInt((String(v)).replace(/[^0-9]/g, '') || '0', 10);
    const formatRupiah = n => (parseInt(n, 10) || 0).toLocaleString('id-ID');

    function bindRow(row) {
        const nameInput = row.querySelector('.supplier-name');
        const idInput   = row.querySelector('.supplier-id');
        const box       = row.querySelector('.supplier-suggest');
        const unitInput = row.querySelector('input[name$="[unit_price]"]');
        const totInput  = row.querySelector('input[name$="[total_price]"]');
        if (!nameInput || !idInput || !box) return;

        let timer;
        nameInput.addEventListener('input', function () {
            idInput.value = '';
            const q = this.value.trim();
            clearTimeout(timer);
            if (q.length < 2) { box.classList.add('hidden'); return; }
            timer = setTimeout(async () => {
                const url = new URL("{{ route('api.suppliers.suggest') }}", window.location.origin);
                url.searchParams.set('search', q);
                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await res.json();
                const list = (data.suppliers || []).slice(0, 10);
                box.innerHTML = list.length
                    ? list.map(s => `<div class="px-3 py-2 hover:bg-gray-50 cursor-pointer" data-id="${s.id}" data-name="${s.name}">${s.name}</div>`).join('')
                    : '<div class="px-3 py-2 text-sm text-gray-500">Tidak ada hasil</div>';
                box.classList.remove('hidden');
            }, 220);
        });
        box.addEventListener('click', e => {
            const t = e.target.closest('[data-id]');
            if (!t) return;
            idInput.value   = t.dataset.id;
            nameInput.value = t.dataset.name;
            box.classList.add('hidden');
        });
        nameInput.addEventListener('blur', () => setTimeout(() => box.classList.add('hidden'), 200));

        if (unitInput && totInput) {
            const refresh = () => {
                const u = parseRupiah(unitInput.value);
                unitInput.value = formatRupiah(u);
                if (!totInput.dataset.manual) totInput.value = formatRupiah(u * Math.max(1, qty));
            };
            unitInput.addEventListener('input', refresh);
            unitInput.addEventListener('blur', refresh);
            totInput.addEventListener('input', () => { totInput.value = formatRupiah(parseRupiah(totInput.value)); totInput.dataset.manual = '1'; });
        }
    }

    if (wrapper) {
        wrapper.querySelectorAll('.vendor-row').forEach(bindRow);
        const benchForm = document.getElementById('benchmarking-form');
        if (benchForm) {
            benchForm.addEventListener('submit', async function (ev) {
                ev.preventDefault();
                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const rows  = Array.from(wrapper.querySelectorAll('.vendor-row'));
                const toResolve = rows
                    .map(r => ({ idInput: r.querySelector('.supplier-id'), nameInput: r.querySelector('.supplier-name') }))
                    .filter(x => x.nameInput && !x.idInput.value && x.nameInput.value.trim().length >= 2);
                await Promise.all(toResolve.map(async ({ idInput, nameInput }) => {
                    try {
                        const res = await fetch("{{ route('api.suppliers.resolve') }}", {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
                            body: JSON.stringify({ name: nameInput.value.trim() }),
                        });
                        const d = await res.json();
                        if (d?.supplier?.id) { idInput.value = String(d.supplier.id); nameInput.value = d.supplier.name; }
                    } catch (e) { /* ignore */ }
                }));
                const ids = rows.map(r => r.querySelector('.supplier-id')?.value).filter(Boolean);
                if (ids.length !== new Set(ids).size) { alert('Terdapat vendor duplikat. Silakan pilih vendor berbeda.'); return; }
                rows.forEach(r => {
                    const sid = r.querySelector('.supplier-id');
                    if (!sid?.value) { r.querySelectorAll('input').forEach(i => { i.disabled = true; }); return; }
                    const u = r.querySelector('input[name$="[unit_price]"]');
                    const t = r.querySelector('input[name$="[total_price]"]');
                    if (u) u.value = String(parseRupiah(u.value));
                    if (t) t.value = String(parseRupiah(t.value));
                });
                benchForm.submit();
            });
        }
    }

    const prefForm = document.getElementById('preferred-form');
    if (prefForm) {
        const pName = prefForm.querySelector('.preferred-supplier-name');
        const pId   = prefForm.querySelector('.preferred-supplier-id');
        const pBox  = prefForm.querySelector('.preferred-supplier-suggest');
        const serverVendors = @json($item->vendors->map(fn($v) => ['id' => $v->supplier_id, 'name' => optional($v->supplier)->name])->values());

        if (pName && pId && pBox) {
            pName.addEventListener('input', function () {
                const q = this.value.toLowerCase().trim();
                pId.value = '';
                const list = serverVendors.filter(v => (v.name || '').toLowerCase().includes(q)).slice(0, 10);
                pBox.innerHTML = list.length
                    ? list.map(v => `<div class="px-3 py-2 hover:bg-gray-50 cursor-pointer" data-id="${v.id}" data-name="${v.name}">${v.name}</div>`).join('')
                    : '<div class="px-3 py-2 text-sm text-gray-400">Tidak ada hasil</div>';
                pBox.classList.remove('hidden');
            });
            pBox.addEventListener('click', e => {
                const t = e.target.closest('[data-id]'); if (!t) return;
                pId.value   = t.dataset.id;
                pName.value = t.dataset.name;
                pBox.classList.add('hidden');
            });
            pName.addEventListener('blur', () => setTimeout(() => pBox.classList.add('hidden'), 200));
        }

        const pUnit  = prefForm.querySelector('.currency-pref-unit');
        const pTotal = prefForm.querySelector('.currency-pref-total');
        if (pUnit)  pUnit.addEventListener('blur',  () => { pUnit.value  = formatRupiah(parseRupiah(pUnit.value));  });
        if (pTotal) pTotal.addEventListener('blur', () => { pTotal.value = formatRupiah(parseRupiah(pTotal.value)); });
        prefForm.addEventListener('submit', function () {
            if (pUnit)  pUnit.value  = String(parseRupiah(pUnit.value));
            if (pTotal) pTotal.value = String(parseRupiah(pTotal.value));
        });
    }
});
</script>
@endpush
