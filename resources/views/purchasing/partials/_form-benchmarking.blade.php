{{-- Partial: Benchmarking Vendor Form --}}
{{-- Variables available: $item, $pStep --}}
<form method="POST" action="{{ route('purchasing.items.receive-doc-benchmark', $item) }}" class="space-y-3" id="benchmarking-form">
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
            @php $v = optional($item->vendors->values()->get($i)); @endphp
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
    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">Simpan</button>
</form>
