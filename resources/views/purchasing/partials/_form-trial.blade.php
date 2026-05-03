{{-- Partial: Trial Vendor Form --}}
{{-- Variables available: $item, $pStep --}}
<form method="POST" action="{{ route('purchasing.items.trial', $item) }}" class="space-y-3">
    @csrf
    @if($item->vendors->isEmpty())
        <p class="text-sm text-gray-400">Belum ada vendor benchmarking.</p>
    @else
        <div class="space-y-2">
            @foreach($item->vendors as $i => $v)
                @php $trial = $v->latestTrial; @endphp
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                    <div class="text-sm font-semibold text-gray-800">{{ $v->supplier->name ?? '-' }}</div>
                    <input type="hidden" name="trials[{{ $i }}][purchasing_item_vendor_id]" value="{{ $v->id }}" />
                    <label class="block text-xs text-gray-600 mt-2 mb-1">Catatan Trial</label>
                    <textarea name="trials[{{ $i }}][trial_notes]" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">{{ old("trials.$i.trial_notes", $trial?->trial_notes) }}</textarea>
                </div>
            @endforeach
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">Simpan</button>
    @endif
</form>
