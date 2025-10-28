@php
    // Expecting $item to be passed in
    $disableBenchmarking = $disableBenchmarking ?? false;
    $disablePreferred = $disablePreferred ?? false;
@endphp

@if(auth()->user()->hasPermission('manage_vendor'))
    <!-- Benchmarking Vendors -->
    <div class="bg-white border border-gray-200 rounded-lg">
        <div class="px-3 py-2 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900">Vendor Benchmarking</h3>
            <div class="text-xs text-gray-500 flex items-center gap-2">Qty:
                <input type="text" class="h-6 w-16 text-center border border-gray-200 rounded bg-gray-50" value="{{ (int) $item->quantity }}" disabled>
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

    <!-- Preferred Vendor -->
    <div class="bg-white border border-gray-200 rounded-lg">
        <div class="px-3 py-2 border-b border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900">Preferred Vendor</h3>
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
