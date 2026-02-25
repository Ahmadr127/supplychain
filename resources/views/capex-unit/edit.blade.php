@extends('layouts.app')
@section('title', 'Edit Item CapEx')
@section('content')
<div>
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Edit Item CapEx</h2>
        <p class="text-gray-500 text-sm mt-1">
            {{ $item->capex->department->name ?? '-' }} — Tahun {{ $item->capex->fiscal_year }}
            <span class="ml-2 font-mono text-xs bg-gray-100 px-2 py-0.5 rounded">{{ $item->capex_id_number }}</span>
        </p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form action="{{ route('unit.capex.update', $item) }}" method="POST">
            @csrf @method('PATCH')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Barang/Jasa/Program Kerja <span class="text-red-500">*</span></label>
                    <input type="text" name="item_name" value="{{ old('item_name', $item->item_name) }}" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 @error('item_name') border-red-400 @enderror">
                    @error('item_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                    <select name="capex_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 bg-white">
                        <option value="">— Pilih —</option>
                        <option value="New" {{ old('capex_type', $item->capex_type) === 'New' ? 'selected' : '' }}>New (Baru)</option>
                        <option value="Replacement" {{ old('capex_type', $item->capex_type) === 'Replacement' ? 'selected' : '' }}>Replacement (Penggantian)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Skala Prioritas</label>
                    <select name="priority_scale" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 bg-white">
                        <option value="">— Pilih —</option>
                        @foreach([1,2,3] as $p)
                            <option value="{{ $p }}" {{ old('priority_scale', $item->priority_scale) == $p ? 'selected' : '' }}>
                                {{ $p }} — Prioritas {{ $p === 1 ? 'Tinggi' : ($p === 2 ? 'Sedang' : 'Rendah') }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bulan Pengadaan</label>
                    <select name="month" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 bg-white">
                        <option value="">— Pilih —</option>
                        @foreach(['January','February','March','April','May','June','July','August','September','October','November','December'] as $m)
                            <option value="{{ $m }}" {{ old('month', $item->month) === $m ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kode Instalasi</label>
                    <input type="text" name="category" value="{{ old('category', $item->category) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount/Tahun</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-gray-400 text-sm">Rp</span>
                        <input type="text" name="amount_per_year"
                            value="{{ old('amount_per_year', $item->amount_per_year ? number_format($item->amount_per_year, 0, ',', '.') : '') }}"
                            class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                            oninput="formatRupiah(this)">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nilai CapEx <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-gray-400 text-sm">Rp</span>
                        <input type="text" name="budget_amount" required
                            value="{{ old('budget_amount', number_format($item->budget_amount, 0, ',', '.')) }}"
                            class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 @error('budget_amount') border-red-400 @enderror"
                            oninput="formatRupiah(this)">
                    </div>
                    @error('budget_amount')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    @if($item->used_amount > 0)
                        <p class="text-xs text-orange-500 mt-1">Sudah terpakai: Rp {{ number_format($item->used_amount, 0, ',', '.') }}</p>
                    @endif
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">PIC</label>
                    <input type="text" name="pic" value="{{ old('pic', $item->pic) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
                    <textarea name="description" rows="2"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500">{{ old('description', $item->description) }}</textarea>
                </div>
            </div>

            <div class="flex justify-between mt-6 pt-4 border-t border-gray-100">
                <a href="{{ route('unit.capex.index', ['year' => $item->capex->fiscal_year]) }}"
                   class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg">
                    <i class="fas fa-save mr-1"></i>Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@push('scripts')
<script>
function formatRupiah(input) {
    let raw = input.value.replace(/\D/g, '');
    input.value = raw.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}
</script>
@endpush
@endsection
