{{-- Partial: Input PO Form --}}
{{-- Variables available: $item --}}
<form method="POST" action="{{ route('purchasing.items.po', $item) }}" class="flex items-end gap-3">
    @csrf
    <div class="flex-1">
        <label class="block text-xs text-gray-600 mb-1">Nomor PO</label>
        <input type="text" name="po_number" value="{{ $item->po_number }}"
               class="h-9 w-full px-3 border border-gray-300 rounded-lg text-sm font-mono" placeholder="Contoh: PO-2026-001" />
    </div>
    <button type="submit" class="h-9 px-4 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium whitespace-nowrap">Simpan</button>
</form>
