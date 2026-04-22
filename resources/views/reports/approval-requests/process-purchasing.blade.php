@extends('layouts.app')

@section('title', 'Purchasing Item Detail')

@section('content')
@php
    $canPurchasing = auth()->user()->hasPermission('manage_purchasing') || auth()->user()->hasPermission('process_purchasing_item');
    $canVendor     = auth()->user()->hasPermission('manage_vendor');

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

    $purchasingSteps = $purchasingSteps ?? collect();
@endphp

<div class="space-y-2 w-full">
    {{-- Errors --}}
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

    {{-- ═══════════════════════════════════════════════════
         DYNAMIC STEPS (driven by approval_item_steps)
    ═══════════════════════════════════════════════════ --}}
    @php
        $resolvedStates = [];
        $prevDone = true;

        foreach ($purchasingSteps as $st) {
            $status = (string) ($st->status ?? '');
            $isDone = $status === 'approved';
            $isActive = $prevDone && $status === 'pending';
            $state = $isDone ? 'done' : ($isActive ? 'active' : 'locked');
            $resolvedStates[$st->id] = $state;
            $prevDone = $isDone;
        }
    @endphp

    @foreach($purchasingSteps as $idx => $wfStep)
        @php
            $state = $resolvedStates[$wfStep->id] ?? 'locked';
            $doneBadge = $state === 'done';

            $headerColor = $state === 'done'
                ? 'bg-green-50 border-green-100'
                : ($state === 'active' ? 'bg-blue-50 border-blue-100' : 'bg-gray-50 border-gray-100');

            $borderColor = $state === 'done'
                ? 'border-green-200'
                : ($state === 'active' ? 'border-blue-300 ring-1 ring-blue-100' : 'border-gray-200');

            $dotColor = $state === 'done'
                ? 'bg-green-500'
                : ($state === 'active' ? 'bg-blue-600' : 'bg-gray-300');

            $required = (string) ($wfStep->required_action ?? '');
            $title = (string) ($wfStep->step_name ?? $required ?: 'Step');
        @endphp

        <div class="bg-white border rounded-lg overflow-hidden {{ $borderColor }}">
            <div class="flex items-center justify-between px-3 py-1.5 border-b {{ $headerColor }}">
                <div class="flex items-center gap-2">
                    <span class="w-6 h-6 rounded-full {{ $dotColor }} text-white flex items-center justify-center text-xs font-bold">
                        {{ $doneBadge ? '✓' : ($idx + 1) }}
                    </span>
                    <span class="font-semibold {{ $state === 'locked' ? 'text-gray-400' : 'text-gray-800' }}">{{ $title }}</span>
                </div>
                @if($doneBadge)
                    <span class="text-xs text-green-700 bg-green-100 px-2 py-0.5 rounded-full">Done</span>
                @endif
            </div>

            <div class="p-3">
                @if($state === 'locked')
                    <div class="flex items-center gap-2 text-sm text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H9m3-6V7a3 3 0 00-6 0v4m9 0H6a2 2 0 00-2 2v5a2 2 0 002 2h12a2 2 0 002-2v-5a2 2 0 00-2-2h-3z"/></svg>
                        Step ini masih terkunci. Selesaikan step sebelumnya terlebih dahulu.
                    </div>
                @elseif($required === 'purchasing_receive_doc_benchmark')
                    @if($canPurchasing)
                        <form method="POST" action="{{ route('purchasing.items.receive-doc-benchmark', $item) }}" class="space-y-3">
                            @csrf
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div class="md:col-span-1">
                                    <label class="block text-xs text-gray-600 mb-1">Tanggal Diterima</label>
                                    <input type="date" name="received_at"
                                           value="{{ $item->approvalRequest->received_at ? \Carbon\Carbon::parse($item->approvalRequest->received_at)->format('Y-m-d') : '' }}"
                                           class="h-9 w-full px-3 border border-gray-300 rounded-lg text-sm" required />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs text-gray-600 mb-1">Catatan Benchmarking (opsional)</label>
                                    <textarea name="benchmark_notes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">{{ old('benchmark_notes', $item->benchmark_notes) }}</textarea>
                                </div>
                            </div>

                            <div class="text-xs text-gray-500">Data Vendor (min 1, disarankan 3) • Qty: {{ (int) $item->quantity }}</div>
                            <div id="vendors-wrapper" class="space-y-2">
                                @for($i = 0; $i < 3; $i++)
                                    @php($v = optional($item->vendors->values()->get($i)))
                                    <div class="grid grid-cols-4 gap-2 items-center vendor-row">
                                        <div class="relative">
                                            <input type="hidden" name="vendors[{{ $i }}][supplier_id]" class="supplier-id" value="{{ $v->supplier_id ?? '' }}" />
                                            <input type="text" class="supplier-name h-9 w-full px-3 border border-gray-300 rounded-lg text-sm" placeholder="Cari supplier..." autocomplete="off"
                                                   value="{{ $v && $v->supplier ? $v->supplier->name : '' }}" />
                                            <div class="supplier-suggest absolute left-0 right-0 mt-0.5 bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-auto hidden z-50 text-sm"></div>
                                        </div>
                                        <input type="text" name="vendors[{{ $i }}][unit_price]" class="h-9 px-3 border border-gray-300 rounded-lg text-sm" placeholder="Harga Satuan (Rp)"
                                               value="{{ isset($v->unit_price) ? number_format((float)$v->unit_price, 0, ',', '.') : '' }}" />
                                        <input type="text" name="vendors[{{ $i }}][total_price]" class="h-9 px-3 border border-gray-300 rounded-lg text-sm" placeholder="Total (Rp)"
                                               value="{{ isset($v->total_price) ? number_format((float)$v->total_price, 0, ',', '.') : '' }}" />
                                        <input type="text" name="vendors[{{ $i }}][notes]" class="h-9 px-3 border border-gray-300 rounded-lg text-sm" placeholder="Catatan"
                                               value="{{ $v->notes ?? '' }}" />
                                    </div>
                                @endfor
                            </div>

                            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">
                                Simpan
                            </button>
                        </form>
                    @else
                        <p class="text-sm text-gray-600">Tanggal: <strong>{{ $item->approvalRequest->received_at ? \Carbon\Carbon::parse($item->approvalRequest->received_at)->format('d/m/Y') : '-' }}</strong></p>
                    @endif
                @elseif($required === 'purchasing_trial')
                    @if($canPurchasing)
                        <form method="POST" action="{{ route('purchasing.items.trial', $item) }}" class="space-y-3">
                            @csrf
                            @if($item->vendors->isEmpty())
                                <p class="text-sm text-gray-400">Belum ada vendor benchmarking.</p>
                            @else
                                <div class="space-y-2">
                                    @foreach($item->vendors as $i => $v)
                                        @php($trial = $v->latestTrial)
                                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                                            <div class="text-sm font-semibold text-gray-800">{{ $v->supplier->name ?? '-' }}</div>
                                            <input type="hidden" name="trials[{{ $i }}][purchasing_item_vendor_id]" value="{{ $v->id }}" />
                                            <label class="block text-xs text-gray-600 mt-2 mb-1">Catatan Trial</label>
                                            <textarea name="trials[{{ $i }}][trial_notes]" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">{{ old("trials.$i.trial_notes", $trial?->trial_notes) }}</textarea>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">
                                    Simpan
                                </button>
                            @endif
                        </form>
                    @else
                        <p class="text-sm text-gray-400">Menunggu tim purchasing.</p>
                    @endif
                @elseif($required === 'purchasing_preferred_vendor')
                    @if($canVendor)
                        <form method="POST" action="{{ route('purchasing.items.preferred', $item) }}" class="space-y-3">
                            @csrf
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div class="md:col-span-1 relative">
                                    <label class="block text-xs text-gray-600 mb-1">Pilih Vendor (dari Benchmarking)</label>
                                    <input type="hidden" name="supplier_id" class="preferred-supplier-id" value="{{ $item->preferred_vendor_id }}" />
                                    <input type="text" class="preferred-supplier-name h-9 w-full px-3 border border-gray-300 rounded-lg text-sm" placeholder="Cari vendor..."
                                           autocomplete="off" value="{{ $item->preferredVendor->name ?? '' }}" />
                                    <div class="preferred-supplier-suggest absolute left-0 right-0 mt-0.5 bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-auto hidden z-50 text-sm"></div>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Harga Satuan (Rp)</label>
                                    <input type="text" name="unit_price" value="{{ $item->preferred_unit_price ? number_format((float)$item->preferred_unit_price, 0, ',', '.') : '' }}"
                                           class="w-full h-9 px-3 border border-gray-300 rounded-lg text-sm currency-pref-unit" placeholder="Rp" />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Total (Rp)</label>
                                    <input type="text" name="total_price" value="{{ $item->preferred_total_price ? number_format((float)$item->preferred_total_price, 0, ',', '.') : '' }}"
                                           class="w-full h-9 px-3 border border-gray-300 rounded-lg text-sm currency-pref-total" placeholder="Rp" />
                                </div>
                            </div>
                            <button type="submit" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium">
                                Simpan
                            </button>
                        </form>
                    @else
                        <p class="text-sm text-gray-400">Menunggu Manager Keuangan memilih preferred vendor.</p>
                    @endif
                @elseif($required === 'purchasing_po')
                    @if($canPurchasing)
                        <form method="POST" action="{{ route('purchasing.items.po', $item) }}" class="flex items-end gap-3">
                            @csrf
                            <div class="flex-1">
                                <label class="block text-xs text-gray-600 mb-1">Nomor PO</label>
                                <input type="text" name="po_number" value="{{ $item->po_number }}"
                                       class="h-9 w-full px-3 border border-gray-300 rounded-lg text-sm font-mono" placeholder="Contoh: PO-2026-001" />
                            </div>
                            <button type="submit" class="h-9 px-4 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium whitespace-nowrap">
                                Simpan
                            </button>
                        </form>
                    @else
                        <p class="text-sm text-gray-600">PO: <strong class="font-mono">{{ $item->po_number ?? '-' }}</strong></p>
                    @endif
                @elseif($required === 'purchasing_invoice_grn_done')
                    @if($ps === 'done')
                        <div class="text-sm text-green-700">
                            <p>Proses purchasing telah diselesaikan.</p>
                            @if($item->done_notes) <p class="mt-2 text-gray-600 whitespace-pre-wrap">Catatan: {{ $item->done_notes }}</p> @endif
                        </div>
                    @elseif($canPurchasing)
                        <form method="POST" action="{{ route('purchasing.items.invoice-grn-done', $item) }}" onsubmit="return confirm('Simpan Invoice + GRN dan tandai DONE?')" class="space-y-4">
                            @csrf
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Nomor Invoice</label>
                                    <input type="text" name="invoice_number" value="{{ $item->invoice_number }}"
                                           class="w-full h-9 px-3 border border-gray-300 rounded-lg text-sm font-mono" placeholder="INV-..." required />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Tanggal GRN</label>
                                    <input type="date" name="grn_date" value="{{ $item->grn_date ? $item->grn_date->format('Y-m-d') : '' }}"
                                           class="w-full h-9 px-3 border border-gray-300 rounded-lg text-sm" required />
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Catatan Penutupan (opsional)</label>
                                <textarea name="done_notes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">{{ old('done_notes', $item->done_notes) }}</textarea>
                            </div>
                            <button type="submit" class="px-5 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-semibold">
                                Simpan & DONE
                            </button>
                        </form>
                    @else
                        <p class="text-sm text-gray-400">Menunggu tim purchasing.</p>
                    @endif
                @else
                    <p class="text-sm text-gray-500">Belum ada renderer untuk action: <span class="font-mono">{{ $required ?: '-' }}</span></p>
                @endif
            </div>
        </div>
    @endforeach

    {{-- Delete button (hanya purchasing + belum done) --}}
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

    // ── Helpers ──────────────────────────────────────────
    const parseRupiah = v => parseInt((String(v)).replace(/[^0-9]/g, '') || '0', 10);
    const formatRupiah = n => (parseInt(n, 10) || 0).toLocaleString('id-ID');

    // ── Bind each benchmarking row ────────────────────────
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

        // Currency
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

        // Benchmarking form submit
        const benchForm = document.getElementById('benchmarking-form');
        if (benchForm) {
            benchForm.addEventListener('submit', async function (ev) {
                ev.preventDefault();
                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const rows  = Array.from(wrapper.querySelectorAll('.vendor-row'));

                // Auto-resolve typed names without an ID
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

                // Duplicate check
                const ids = rows.map(r => r.querySelector('.supplier-id')?.value).filter(Boolean);
                if (ids.length !== new Set(ids).size) { alert('Terdapat vendor duplikat. Silakan pilih vendor berbeda.'); return; }

                // Disable empty rows, unformat currency
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

    // ── Preferred Vendor form ─────────────────────────────
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
                pId.value  = t.dataset.id;
                pName.value = t.dataset.name;
                pBox.classList.add('hidden');
                // Try auto-fill prices from benchmarking data
                const match = serverVendors.find(v => String(v.id) === String(t.dataset.id));
                if (match) {
                    const bRow = Array.from((wrapper?.querySelectorAll('.vendor-row') || [])).find(r => r.querySelector('.supplier-id')?.value === String(t.dataset.id));
                    const bUnit = bRow?.querySelector('input[name$="[unit_price]"]')?.value;
                    const bTotal = bRow?.querySelector('input[name$="[total_price]"]')?.value;
                    const pUnit = prefForm.querySelector('.currency-pref-unit');
                    const pTotal = prefForm.querySelector('.currency-pref-total');
                    if (pUnit && bUnit) pUnit.value = bUnit;
                    if (pTotal && bTotal) pTotal.value = bTotal;
                }
            });
            pName.addEventListener('blur', () => setTimeout(() => pBox.classList.add('hidden'), 200));
        }

        // Currency for preferred
        const pUnit  = prefForm.querySelector('.currency-pref-unit');
        const pTotal = prefForm.querySelector('.currency-pref-total');
        if (pUnit) { pUnit.addEventListener('blur', () => { pUnit.value = formatRupiah(parseRupiah(pUnit.value)); }); }
        if (pTotal) { pTotal.addEventListener('blur', () => { pTotal.value = formatRupiah(parseRupiah(pTotal.value)); }); }

        prefForm.addEventListener('submit', function () {
            if (pUnit)  pUnit.value  = String(parseRupiah(pUnit.value));
            if (pTotal) pTotal.value = String(parseRupiah(pTotal.value));
        });
    }
});
</script>
@endpush
