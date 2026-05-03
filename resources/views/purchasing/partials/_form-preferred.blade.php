{{-- Partial: Pilih Preferred Vendor Form --}}
{{-- Variables available: $item, $canVendor --}}
@if($canVendor)
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
        <button type="submit" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium">Simpan</button>
    </form>
@else
    <p class="text-sm text-gray-400">Hanya pengguna dengan izin <em>manage_vendor</em> yang dapat memilih vendor.</p>
@endif
