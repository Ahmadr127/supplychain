@extends('layouts.app')

@section('title', 'Purchasing Item Detail')

@section('content')
@php
    $canPurchasing = auth()->user()->hasPermission('manage_purchasing') || auth()->user()->hasPermission('process_purchasing_item');
    $canVendor     = auth()->user()->hasPermission('manage_vendor');

    $ps = $item->status ?? 'unprocessed';

    // Sequential gating checks
    $step1Done = !empty($item->approvalRequest->received_at);
    $step2Done = $item->vendors->isNotEmpty();
    $step3Done = !empty($item->preferred_vendor_id);
    $step4Done = !empty($item->po_number);
    $step5Done = !empty($item->invoice_number);
    $step6Done = $ps === 'done';

    // Step states: 'done' | 'active' | 'locked'
    $s1State = $step1Done ? 'done' : 'active';
    $s2State = $step1Done ? ($step2Done ? 'done' : 'active') : 'locked';
    $s3State = $step2Done ? ($step3Done ? 'done' : 'active') : 'locked';
    $s4State = $step3Done ? ($step4Done ? 'done' : 'active') : 'locked';
    $s5State = $step4Done ? ($step5Done ? 'done' : 'active') : 'locked';
    $s6State = $step5Done ? ($step6Done ? 'done' : 'active') : 'locked';

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
         PROGRESS STEPPER
    ═══════════════════════════════════════════════════ --}}
    @php
        $steps = [
            ['label' => 'Tgl Dokumen', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'state' => $s1State],
            ['label' => 'Benchmarking', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', 'state' => $s2State],
            ['label' => 'Preferred Vendor', 'icon' => 'M5 13l4 4L19 7', 'state' => $s3State],
            ['label' => 'PO', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'state' => $s4State],
            ['label' => 'Invoice', 'icon' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z', 'state' => $s5State],
            ['label' => 'Selesai', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'state' => $s6State],
        ];
    @endphp
    <div class="bg-white border border-gray-200 rounded-lg p-2">
        <div class="flex items-center justify-between relative">
            {{-- connector line --}}
            <div class="absolute left-0 right-0 top-5 h-0.5 bg-gray-200 z-0" style="margin: 0 2.5rem;"></div>
            @foreach($steps as $i => $step)
                @php
                    $dotColor = match($step['state']) {
                        'done'   => 'bg-green-500 text-white border-green-500',
                        'active' => 'bg-blue-600 text-white border-blue-600',
                        default  => 'bg-white text-gray-400 border-gray-300',
                    };
                    $labelColor = match($step['state']) {
                        'done'   => 'text-green-700 font-semibold',
                        'active' => 'text-blue-700 font-semibold',
                        default  => 'text-gray-400',
                    };
                @endphp
                <div class="flex flex-col items-center z-10 flex-1">
                    <div class="w-10 h-10 rounded-full border-2 flex items-center justify-center {{ $dotColor }} shadow-sm">
                        @if($step['state'] === 'done')
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        @elseif($step['state'] === 'locked')
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H9m3-6V7a3 3 0 00-6 0v4m6 0H6a2 2 0 00-2 2v5a2 2 0 002 2h12a2 2 0 002-2v-5a2 2 0 00-2-2H9z"/></svg>
                        @else
                            <span class="text-sm font-bold">{{ $i + 1 }}</span>
                        @endif
                    </div>
                    <span class="mt-1.5 text-xs text-center {{ $labelColor }} leading-tight">{{ $step['label'] }}</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════
         STEP 1: TANGGAL DOKUMEN DITERIMA
    ═══════════════════════════════════════════════════ --}}
    <div class="bg-white border rounded-lg overflow-hidden {{ $s1State === 'done' ? 'border-green-200' : 'border-blue-300 ring-1 ring-blue-100' }}">
        <div class="flex items-center justify-between px-3 py-1.5 border-b {{ $s1State === 'done' ? 'bg-green-50 border-green-100' : 'bg-blue-50 border-blue-100' }}">
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-full {{ $s1State === 'done' ? 'bg-green-500' : 'bg-blue-600' }} text-white flex items-center justify-center text-xs font-bold">{{ $s1State === 'done' ? '✓' : '1' }}</span>
                <span class="font-semibold text-gray-800">Tanggal Dokumen Diterima</span>
            </div>
            @if($step1Done)
                <span class="text-sm text-green-700 font-medium">{{ \Carbon\Carbon::parse($item->approvalRequest->received_at)->format('d/m/Y') }}</span>
            @endif
        </div>
        <div class="p-3">
            @if($canPurchasing)
                <form method="POST" action="{{ route('approval-requests.set-received-date', $item->approvalRequest) }}" class="flex items-end gap-3 flex-wrap">
                    @csrf
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Tanggal Diterima</label>
                        <input type="date" name="received_at"
                               value="{{ $item->approvalRequest->received_at ? \Carbon\Carbon::parse($item->approvalRequest->received_at)->format('Y-m-d') : '' }}"
                               class="h-9 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required />
                    </div>
                    <button type="submit" class="h-9 px-4 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">
                        Simpan Tanggal
                    </button>
                </form>
                @if(!$step1Done)
                    <p class="mt-2 text-xs text-amber-700 bg-amber-50 border border-amber-200 px-3 py-1.5 rounded-lg">⚠ Tanggal dokumen wajib diisi sebelum melanjutkan ke tahap berikutnya.</p>
                @endif
            @else
                <p class="text-sm text-gray-600">Tanggal: <strong>{{ $item->approvalRequest->received_at ? \Carbon\Carbon::parse($item->approvalRequest->received_at)->format('d/m/Y') : '-' }}</strong></p>
            @endif
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════
         STEP 2: BENCHMARKING VENDOR
    ═══════════════════════════════════════════════════ --}}
    <div class="bg-white border rounded-lg overflow-hidden {{ $s2State === 'done' ? 'border-green-200' : ($s2State === 'active' ? 'border-blue-300 ring-1 ring-blue-100' : 'border-gray-200') }}">
        <div class="flex items-center justify-between px-3 py-1.5 border-b {{ $s2State === 'done' ? 'bg-green-50 border-green-100' : ($s2State === 'active' ? 'bg-blue-50 border-blue-100' : 'bg-gray-50 border-gray-100') }}">
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-full {{ $s2State === 'done' ? 'bg-green-500' : ($s2State === 'active' ? 'bg-blue-600' : 'bg-gray-300') }} text-white flex items-center justify-center text-xs font-bold">
                    {{ $s2State === 'done' ? '✓' : '2' }}
                </span>
                <span class="font-semibold {{ $s2State === 'locked' ? 'text-gray-400' : 'text-gray-800' }}">Benchmarking Vendor (SPH)</span>
            </div>
            @if($step2Done)
                <span class="text-xs text-green-700 bg-green-100 px-2 py-0.5 rounded-full">{{ $item->vendors->count() }} vendor</span>
            @endif
        </div>
        <div class="p-3">
            @if($s2State === 'locked')
                <div class="flex items-center gap-2 text-sm text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H9m3-6V7a3 3 0 00-6 0v4m9 0H6a2 2 0 00-2 2v5a2 2 0 002 2h12a2 2 0 002-2v-5a2 2 0 00-2-2h-3z"/></svg>
                    Selesaikan Step 1 terlebih dahulu untuk membuka tahap ini.
                </div>
            @elseif($canPurchasing)
                <form method="POST" action="{{ route('purchasing.items.benchmarking', $item) }}" class="space-y-3" id="benchmarking-form">
                    @csrf
                    <div class="text-xs text-gray-500 mb-2">Data Vendor (min 1, disarankan 3) &bull; Qty: {{ (int) $item->quantity }}</div>
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
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Catatan Benchmarking (opsional)</label>
                        <textarea name="benchmark_notes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
                                  placeholder="Tulis analisis hasil benchmarking...">{{ old('benchmark_notes', $item->benchmark_notes) }}</textarea>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Simpan Benchmarking
                    </button>
                </form>
            @else
                {{-- Read-only for non-purchasing --}}
                @if($item->vendors->isNotEmpty())
                    <div class="space-y-1">
                        @foreach($item->vendors as $v)
                            <div class="flex items-center justify-between text-sm bg-gray-50 rounded-lg px-3 py-2">
                                <span class="font-medium text-gray-800">{{ $v->supplier->name ?? '-' }}</span>
                                <div class="flex gap-4 text-gray-600 text-xs">
                                    <span>Rp {{ number_format((float)$v->unit_price, 0, ',', '.') }}/unit</span>
                                    <span>Total: Rp {{ number_format((float)$v->total_price, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-400">Belum ada data benchmarking.</p>
                @endif
            @endif
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════
         STEP 3: PREFERRED VENDOR (manage_vendor → Manager Keuangan)
    ═══════════════════════════════════════════════════ --}}
    <div class="bg-white border rounded-lg overflow-hidden {{ $s3State === 'done' ? 'border-green-200' : ($s3State === 'active' ? 'border-purple-300 ring-1 ring-purple-100' : 'border-gray-200') }}">
        <div class="flex items-center justify-between px-3 py-1.5 border-b {{ $s3State === 'done' ? 'bg-green-50 border-green-100' : ($s3State === 'active' ? 'bg-purple-50 border-purple-100' : 'bg-gray-50 border-gray-100') }}">
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-full {{ $s3State === 'done' ? 'bg-green-500' : ($s3State === 'active' ? 'bg-purple-600' : 'bg-gray-300') }} text-white flex items-center justify-center text-xs font-bold">
                    {{ $s3State === 'done' ? '✓' : '3' }}
                </span>
                <span class="font-semibold {{ $s3State === 'locked' ? 'text-gray-400' : 'text-gray-800' }}">Preferred Vendor</span>
                <span class="text-xs px-2 py-0.5 rounded-full {{ $s3State === 'locked' ? 'bg-gray-100 text-gray-400' : 'bg-purple-100 text-purple-700' }}">Manager Keuangan</span>
            </div>
            @if($step3Done)
                <span class="text-sm text-green-700 font-medium">{{ $item->preferredVendor->name ?? '-' }}</span>
            @endif
        </div>
        <div class="p-3">
            @if($s3State === 'locked')
                <div class="flex items-center gap-2 text-sm text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H9m3-6V7a3 3 0 00-6 0v4m9 0H6a2 2 0 00-2 2v5a2 2 0 002 2h12a2 2 0 002-2v-5a2 2 0 00-2-2h-3z"/></svg>
                    Selesaikan Step 2 (Benchmarking) terlebih dahulu.
                </div>
            @elseif($canVendor)
                <form method="POST" action="{{ route('purchasing.items.preferred', $item) }}" class="space-y-3" id="preferred-form">
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
                    <button type="submit" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition-colors">
                        Simpan Preferred Vendor
                    </button>
                </form>
            @else
                {{-- Read-only --}}
                @if($step3Done)
                    <div class="flex items-center justify-between text-sm bg-purple-50 rounded-lg px-3 py-2 border border-purple-100">
                        <span class="font-medium text-gray-800">{{ $item->preferredVendor->name ?? '-' }}</span>
                        <div class="flex gap-4 text-gray-600 text-xs">
                            <span>Rp {{ number_format((float)$item->preferred_unit_price, 0, ',', '.') }}/unit</span>
                            <span>Total: Rp {{ number_format((float)$item->preferred_total_price, 0, ',', '.') }}</span>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-gray-400">Menunggu Manager Keuangan untuk memilih preferred vendor.</p>
                @endif
            @endif
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════
         STEP 4: PO NUMBER
    ═══════════════════════════════════════════════════ --}}
    <div class="bg-white border rounded-lg overflow-hidden {{ $s4State === 'done' ? 'border-green-200' : ($s4State === 'active' ? 'border-blue-300 ring-1 ring-blue-100' : 'border-gray-200') }}">
        <div class="flex items-center justify-between px-3 py-1.5 border-b {{ $s4State === 'done' ? 'bg-green-50 border-green-100' : ($s4State === 'active' ? 'bg-blue-50 border-blue-100' : 'bg-gray-50 border-gray-100') }}">
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-full {{ $s4State === 'done' ? 'bg-green-500' : ($s4State === 'active' ? 'bg-blue-600' : 'bg-gray-300') }} text-white flex items-center justify-center text-xs font-bold">
                    {{ $s4State === 'done' ? '✓' : '4' }}
                </span>
                <span class="font-semibold {{ $s4State === 'locked' ? 'text-gray-400' : 'text-gray-800' }}">Purchase Order (PO)</span>
            </div>
            @if($step4Done)
                <span class="text-sm text-green-700 font-medium font-mono">{{ $item->po_number }}</span>
            @endif
        </div>
        <div class="p-3">
            @if($s4State === 'locked')
                <div class="flex items-center gap-2 text-sm text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H9m3-6V7a3 3 0 00-6 0v4m9 0H6a2 2 0 00-2 2v5a2 2 0 002 2h12a2 2 0 002-2v-5a2 2 0 00-2-2h-3z"/></svg>
                    Selesaikan Step 3 (Preferred Vendor) terlebih dahulu.
                </div>
            @elseif($canPurchasing)
                <form method="POST" action="{{ route('purchasing.items.po', $item) }}" class="flex items-end gap-3">
                    @csrf
                    <div class="flex-1">
                        <label class="block text-xs text-gray-600 mb-1">Nomor PO</label>
                        <input type="text" name="po_number" value="{{ $item->po_number }}"
                               class="h-9 w-full px-3 border border-gray-300 rounded-lg text-sm font-mono" placeholder="Contoh: PO-2026-001" />
                    </div>
                    <button type="submit" class="h-9 px-4 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors whitespace-nowrap">
                        Simpan PO
                    </button>
                </form>
            @else
                <p class="text-sm text-gray-600">PO: <strong class="font-mono">{{ $item->po_number ?? '-' }}</strong></p>
            @endif
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════
         STEP 5: INVOICE + GRN DATE
    ═══════════════════════════════════════════════════ --}}
    <div class="bg-white border rounded-lg overflow-hidden {{ $s5State === 'done' ? 'border-green-200' : ($s5State === 'active' ? 'border-blue-300 ring-1 ring-blue-100' : 'border-gray-200') }}">
        <div class="flex items-center justify-between px-3 py-1.5 border-b {{ $s5State === 'done' ? 'bg-green-50 border-green-100' : ($s5State === 'active' ? 'bg-blue-50 border-blue-100' : 'bg-gray-50 border-gray-100') }}">
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-full {{ $s5State === 'done' ? 'bg-green-500' : ($s5State === 'active' ? 'bg-blue-600' : 'bg-gray-300') }} text-white flex items-center justify-center text-xs font-bold">
                    {{ $s5State === 'done' ? '✓' : '5' }}
                </span>
                <span class="font-semibold {{ $s5State === 'locked' ? 'text-gray-400' : 'text-gray-800' }}">Invoice & GRN (Penerimaan Barang)</span>
            </div>
        </div>
        <div class="p-3">
            @if($s5State === 'locked')
                <div class="flex items-center gap-2 text-sm text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H9m3-6V7a3 3 0 00-6 0v4m9 0H6a2 2 0 00-2 2v5a2 2 0 002 2h12a2 2 0 002-2v-5a2 2 0 00-2-2h-3z"/></svg>
                    Selesaikan Step 4 (PO) terlebih dahulu.
                </div>
            @elseif($canPurchasing)
                <form method="POST" action="{{ route('purchasing.items.grn', $item) }}" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Nomor Invoice</label>
                            <input type="text" name="invoice_number" value="{{ $item->invoice_number }}"
                                   class="w-full h-9 px-3 border border-gray-300 rounded-lg text-sm font-mono" placeholder="INV-..." required />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Tanggal GRN (Penerimaan)</label>
                            <input type="date" name="grn_date" value="{{ $item->grn_date ? $item->grn_date->format('Y-m-d') : '' }}"
                                   class="w-full h-9 px-3 border border-gray-300 rounded-lg text-sm" required />
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="h-9 px-6 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">Simpan Data GRN & Invoice</button>
                    </div>
                </form>
            @else
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <p>Invoice: <strong class="font-mono">{{ $item->invoice_number ?? '-' }}</strong></p>
                    <p>GRN: <strong>{{ $item->grn_date ? $item->grn_date->format('d/m/Y') : '-' }}</strong></p>
                </div>
            @endif
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════
         STEP 6: MARK AS DONE
    ═══════════════════════════════════════════════════ --}}
    <div class="bg-white border rounded-lg overflow-hidden {{ $s6State === 'done' ? 'border-green-300 bg-green-50' : ($s6State === 'active' ? 'border-green-300 ring-1 ring-green-100' : 'border-gray-200') }}">
        <div class="flex items-center justify-between px-3 py-1.5 border-b {{ $s6State === 'done' ? 'bg-green-100 border-green-200' : ($s6State === 'active' ? 'bg-green-50 border-green-100' : 'bg-gray-50 border-gray-100') }}">
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-full {{ $s6State === 'done' ? 'bg-green-600' : ($s6State === 'active' ? 'bg-green-500' : 'bg-gray-300') }} text-white flex items-center justify-center text-xs font-bold">
                    {{ $s6State === 'done' ? '✓' : '6' }}
                </span>
                <span class="font-semibold {{ $s6State === 'locked' ? 'text-gray-400' : 'text-gray-800' }}">
                    {{ $s6State === 'done' ? '🎉 Proses Selesai!' : 'Mark as DONE' }}
                </span>
            </div>
        </div>
        <div class="p-3">
            @if($s6State === 'done')
                <div class="text-sm text-green-700">
                    <p>Proses purchasing telah diselesaikan.</p>
                    @if($item->done_notes) <p class="mt-1 text-gray-600">Catatan: {{ $item->done_notes }}</p> @endif
                </div>
            @elseif($s6State === 'locked')
                <div class="flex items-center gap-2 text-sm text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H9m3-6V7a3 3 0 00-6 0v4m9 0H6a2 2 0 00-2 2v5a2 2 0 002 2h12a2 2 0 002-2v-5a2 2 0 00-2-2h-3z"/></svg>
                    Selesaikan Step 5 (Invoice) terlebih dahulu.
                </div>
            @elseif($canPurchasing)
                <form method="POST" action="{{ route('purchasing.items.done', $item) }}" onsubmit="return confirm('Tandai item ini sebagai DONE? Pastikan semua data sudah benar.')" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Catatan Penutupan (opsional)</label>
                        <textarea name="done_notes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500"
                                  placeholder="Tulis catatan untuk penutupan...">{{ old('done_notes', $item->done_notes) }}</textarea>
                    </div>
                    <button type="submit" class="px-5 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-semibold transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Tandai Selesai (DONE)
                    </button>
                </form>
            @else
                <p class="text-sm text-gray-400">Menunggu tim purchasing menyelesaikan proses.</p>
            @endif
        </div>
    </div>

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
