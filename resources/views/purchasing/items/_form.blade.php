@php
    // Expecting $item to be passed in
    $disableBenchmarking = $disableBenchmarking ?? false;
    $disablePreferred = $disablePreferred ?? false;
@endphp

@if(auth()->user()->hasPermission('manage_vendor'))
    <!-- Benchmarking Vendors -->
    <div class="bg-white border border-gray-200 shadow-sm rounded-lg">
        <div class="bg-emerald-600 border-b border-emerald-700 rounded-t-lg px-3 py-2 flex items-center justify-between text-white">
            <div class="flex items-center gap-2">
                <div class="bg-emerald-500 text-white rounded-full p-1 shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="text-sm font-semibold text-white">Vendor Benchmarking</h3>
            </div>
            <div class="text-xs text-white bg-emerald-800 bg-opacity-50 px-2 py-0.5 rounded-full flex items-center gap-1 font-medium shadow-inner">
                Qty: <span class="font-bold">{{ (int) $item->quantity }}</span>
            </div>
        </div>
        <div class="p-3">
            <form method="POST" action="{{ route('purchasing.items.benchmarking', $item) }}" class="space-y-2" id="benchmarking-form">
                @csrf
                <div class="text-xs text-gray-600">Tambah/Replace Benchmarking (min 1, disarankan 3)</div>
                <div id="vendors-wrapper" class="space-y-2 {{ $disableBenchmarking ? 'opacity-60 pointer-events-none' : '' }}">
                    @php
                        $existingVendors = ($item->vendors ?? collect())->take(3)->values();
                    @endphp
                    @for($i=0; $i<3; $i++)
                    @php $v = $existingVendors[$i] ?? null; @endphp
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                        <div class="relative">
                            <input type="hidden" name="vendors[{{ $i }}][supplier_id]" class="supplier-id" value="{{ $v?->supplier_id }}" @if($disableBenchmarking) disabled @endif />
                            <input type="text" class="supplier-name h-8 w-full px-2 border border-gray-300 rounded text-sm" placeholder="Cari supplier..." autocomplete="off" value="{{ $v?->supplier?->name }}" @if($disableBenchmarking) disabled @endif />
                            <div class="supplier-suggest absolute left-0 right-0 mt-0.5 bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-auto hidden z-50 text-sm"></div>
                        </div>
                        <input type="text" name="vendors[{{ $i }}][unit_price]" class="h-8 px-2 border border-gray-300 rounded text-sm" placeholder="Unit Price (Rp)" value="{{ is_null($v?->unit_price)? '' : intval($v->unit_price) }}" @if($disableBenchmarking) disabled @endif />
                        <input type="text" name="vendors[{{ $i }}][total_price]" class="h-8 px-2 border border-gray-300 rounded text-sm" placeholder="Total Price (Rp)" value="{{ is_null($v?->total_price)? '' : intval($v->total_price) }}" @if($disableBenchmarking) disabled @endif />
                        <input type="text" name="vendors[{{ $i }}][notes]" class="h-8 px-2 border border-gray-300 rounded text-sm" placeholder="Notes" value="{{ $v?->notes }}" @if($disableBenchmarking) disabled @endif />
                    </div>
                    @endfor
                </div>

                <!-- Catatan Benchmarking -->
                <div class="mt-2">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Catatan Benchmarking (opsional)</label>
                    <textarea name="benchmark_notes" rows="2" class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Tulis catatan atau analisis hasil benchmarking..." @if($disableBenchmarking) disabled @endif>{{ old('benchmark_notes', $item->benchmark_notes) }}</textarea>
                    @if($item->benchmark_notes)
                        <div class="text-xs text-gray-500 mt-1">Terakhir diperbarui: {{ optional($item->updated_at)->format('d/m/Y H:i') }}</div>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="px-3 py-1.5 bg-blue-600 text-white rounded text-sm hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed" id="btn-save-benchmarking" @if($disableBenchmarking) disabled @endif>
                        <span class="btn-text">Simpan Benchmarking</span>
                        <span class="btn-loading hidden">Memproses...</span>
                    </button>
                    <div class="text-xs text-gray-500">
                        <span class="text-blue-600">Tips:</span> Ketik nama vendor baru atau pilih dari saran yang muncul
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Benchmarking Notes removed: merged into main benchmarking form above -->

   

    {{-- 1. Trial Results Card --}}
    @php $hasTrials = isset($item->vendors) && $item->vendors->contains(fn($v) => !is_null($v->latestTrial)); @endphp
    @if($hasTrials)
        <div class="bg-white border border-gray-200 shadow-sm rounded-lg mt-3 overflow-hidden">
            <div class="bg-amber-600 border-b border-amber-700 px-3 py-2 flex items-center gap-2 text-white">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                <h3 class="text-sm font-semibold">Hasil Trial</h3>
            </div>
            <div class="p-3">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($item->vendors as $bv)
                        @if($bv->latestTrial)
                            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 shadow-sm relative overflow-hidden group hover:border-amber-300 transition-colors">
                                <div class="absolute -top-2 -right-2 p-1 opacity-10 group-hover:opacity-20 transition-opacity">
                                    <svg class="w-16 h-16 text-amber-500" fill="currentColor" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </div>
                                <div class="relative z-10">
                                    <div class="flex justify-between items-center mb-3 border-b border-amber-200 pb-2">
                                        <span class="font-bold text-amber-900 text-sm">{{ $bv->supplier->name }}</span>
                                        <div class="flex items-center gap-1 text-[9px] font-medium text-amber-700 bg-white border border-amber-200 px-2 py-0.5 rounded-full shadow-inner">
                                            <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            {{ $bv->latestTrial->created_at->format('d/m/Y H:i') }}
                                        </div>
                                    </div>
                                    <p class="text-amber-900 text-xs leading-relaxed italic">
                                        "{{ $bv->latestTrial->trial_notes }}"
                                    </p>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Preferred Vendor Decision Card -->
    <div class="bg-white border border-gray-200 shadow-sm rounded-lg mt-3">
        <div class="bg-purple-600 border-b border-purple-700 rounded-t-lg px-3 py-2 flex items-center gap-2 text-white">
            <h3 class="text-sm font-semibold text-white">Preferred Vendor Choice</h3>
            <span class="text-xs font-medium text-white bg-purple-800 bg-opacity-50 px-2 py-0.5 rounded-full shadow-inner">Manager Keuangan</span>
        </div>
        <div class="p-3">
            @if(($disableBenchmarking ?? false) && (($item->vendors?->count() ?? 0) === 0))
                <div class="mb-2 text-xs text-red-600">
                    Vendor benchmarking masih kosong hubungi purchasing/bagian terkait untuk mengisi
                </div>
            @endif
            <form method="POST" action="{{ route('purchasing.items.preferred', $item) }}" class="grid grid-cols-1 md:grid-cols-5 gap-2 items-end" id="preferred-form">
                @csrf
                <div class="md:col-span-2 relative">
                    <label class="block text-xs text-gray-600 mb-0.5">Vendor (benchmark)</label>
                    <input type="hidden" name="supplier_id" class="preferred-supplier-id" value="{{ $item->preferred_vendor_id }}" @if($disablePreferred) disabled @endif />
                    <input type="text" class="preferred-supplier-name h-8 w-full px-2 border border-gray-300 rounded text-sm" placeholder="Cari vendor dari hasil benchmarking..." autocomplete="off" value="{{ $item->preferredVendor->name ?? '' }}" @if($disablePreferred) disabled @endif />
                    <div class="preferred-supplier-suggest absolute left-0 right-0 mt-0.5 bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-auto hidden z-50 text-sm"></div>
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-0.5">Unit Price</label>
                    <input type="text" name="unit_price" value="{{ $item->preferred_unit_price ? intval($item->preferred_unit_price) : '' }}" class="w-full h-8 px-2 border border-gray-300 rounded text-sm currency-input" placeholder="Rp" @if($disablePreferred) disabled @endif />
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-0.5">Total Price</label>
                    <input type="text" name="total_price" value="{{ $item->preferred_total_price ? intval($item->preferred_total_price) : '' }}" class="w-full h-8 px-2 border border-gray-300 rounded text-sm currency-input" placeholder="Rp" @if($disablePreferred) disabled @endif />
                </div>
                <div>
                    <button class="px-3 py-1.5 bg-blue-600 text-white rounded text-sm" @if($disablePreferred) disabled @endif>Simpan Preferred</button>
                </div>
            </form>
        </div>
    </div>
@else
    <div class="bg-white border border-gray-200 rounded-lg p-3">
        <div class="text-sm text-gray-600">Preferred Vendor: <span class="font-medium text-gray-900">{{ $item->preferredVendor->name ?? '-' }}</span></div>
    </div>
@endif

{{-- Bottom Padding for Scrollability --}}
<div class="h-48"></div>
