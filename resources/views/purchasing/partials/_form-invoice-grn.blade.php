{{-- Partial: Invoice & GRN (Done) Form --}}
{{-- Variables available: $item, $pStep --}}
{{-- No release gate: GRN now comes BEFORE the release step in the corrected workflow. --}}
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
    <button type="submit" class="px-5 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-semibold">Simpan &amp; DONE</button>
</form>
