@extends('layouts.app')

@section('title', 'Tambah Master Barang')

@section('content')
<div class="w-full mx-auto max-w-6xl">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Tambah Master Barang</h2>
                <a href="{{ route('master-items.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali
                </a>
            </div>
        </div>

        <div class="p-6">
            <form action="{{ route('master-items.store') }}" method="POST" id="masterItemForm">
                @csrf
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Left Column - Basic Information -->
                    <div class="space-y-6">
                        <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Informasi Dasar</h3>
                        
                        <!-- Nama Barang -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                Nama Barang <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-500 @enderror"
                                   placeholder="Masukkan nama barang">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Kode Barang -->
                        <div>
                            <label for="code" class="block text-sm font-medium text-gray-700 mb-2">
                                Kode Barang <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="code" name="code" value="{{ old('code') }}" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('code') border-red-500 @enderror"
                                   placeholder="Contoh: PAR500, AMX500" maxlength="50">
                            @error('code')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>


                        <!-- Deskripsi -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                Deskripsi
                            </label>
                            <textarea id="description" name="description" rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('description') border-red-500 @enderror"
                                      placeholder="Deskripsi barang">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>

                    <!-- Right Column - Classification & Pricing -->
                    <div class="space-y-6">
                        <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Klasifikasi & Harga</h3>
                        
                        <!-- Tipe Barang -->
                        <div>
                            <label for="item_type_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Tipe Barang <span class="text-red-500">*</span>
                            </label>
                            <select id="item_type_id" name="item_type_id" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('item_type_id') border-red-500 @enderror">
                                <option value="">Pilih Tipe Barang</option>
                                @foreach($itemTypes as $type)
                                    <option value="{{ $type->id }}" {{ old('item_type_id') == $type->id ? 'selected' : '' }}>
                                        {{ $type->name }} - {{ $type->description }}
                                    </option>
                                @endforeach
                            </select>
                            @error('item_type_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Kategori Barang -->
                        <div>
                            <label for="item_category_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Kategori Barang <span class="text-red-500">*</span>
                            </label>
                            <select id="item_category_id" name="item_category_id" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('item_category_id') border-red-500 @enderror">
                                <option value="">Pilih Kategori</option>
                                @foreach($itemCategories as $category)
                                    <option value="{{ $category->id }}" {{ old('item_category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }} - {{ $category->description }}
                                    </option>
                                @endforeach
                            </select>
                            @error('item_category_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Komoditas -->
                        <div>
                            <label for="commodity_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Komoditas <span class="text-red-500">*</span>
                            </label>
                            <select id="commodity_id" name="commodity_id" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('commodity_id') border-red-500 @enderror">
                                <option value="">Pilih Komoditas</option>
                                @foreach($commodities as $commodity)
                                    <option value="{{ $commodity->id }}" {{ old('commodity_id') == $commodity->id ? 'selected' : '' }}>
                                        {{ $commodity->name }} - {{ $commodity->description }}
                                    </option>
                                @endforeach
                            </select>
                            @error('commodity_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Satuan -->
                        <div>
                            <label for="unit_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Satuan <span class="text-red-500">*</span>
                            </label>
                            <select id="unit_id" name="unit_id" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('unit_id') border-red-500 @enderror">
                                <option value="">Pilih Satuan</option>
                                @foreach($units as $unit)
                                    <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>
                                        {{ $unit->name }} ({{ $unit->code }}) - {{ $unit->description }}
                                    </option>
                                @endforeach
                            </select>
                            @error('unit_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- HNA -->
                        <div>
                            <label for="hna" class="block text-sm font-medium text-gray-700 mb-2">
                                HNA (Harga Netto Apotek) <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">Rp</span>
                                </div>
                                <input type="number" id="hna" name="hna" value="{{ old('hna') }}" required step="0.01" min="0"
                                       class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('hna') border-red-500 @enderror"
                                       placeholder="0.00" oninput="calculateTotal()">
                            </div>
                            @error('hna')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- PPN Percentage -->
                        <div>
                            <label for="ppn_percentage" class="block text-sm font-medium text-gray-700 mb-2">
                                PPN (%) <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="number" id="ppn_percentage" name="ppn_percentage" value="{{ old('ppn_percentage', 0) }}" required step="0.01" min="0" max="100"
                                       class="w-full pr-8 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('ppn_percentage') border-red-500 @enderror"
                                       placeholder="0.00" oninput="calculateTotal()">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">%</span>
                                </div>
                            </div>
                            @error('ppn_percentage')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- PPN Amount (Read Only) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                PPN (Rp)
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">Rp</span>
                                </div>
                                <input type="text" id="ppn_amount_display" readonly
                                       class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-600"
                                       placeholder="0.00">
                            </div>
                        </div>

                        <!-- Total Price (Read Only) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Total Harga (HNA + PPN)
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">Rp</span>
                                </div>
                                <input type="text" id="total_price_display" readonly
                                       class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-600 font-semibold"
                                       placeholder="0.00">
                            </div>
                        </div>

                        <!-- Stock -->
                        <div>
                            <label for="stock" class="block text-sm font-medium text-gray-700 mb-2">
                                Stok
                            </label>
                            <input type="number" id="stock" name="stock" value="0" min="0" readonly
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-600 cursor-not-allowed"
                                   placeholder="0">
                            <p class="mt-1 text-xs text-gray-500">Stok akan diatur secara otomatis ke 0</p>
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700">Barang Aktif</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-4 mt-8">
                    <a href="{{ route('master-items.index') }}" 
                       class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded">
                        Batal
                    </a>
                    <button type="submit" 
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                        <i class="fas fa-save mr-1"></i> Simpan Barang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function calculateTotal() {
    const hna = parseFloat(document.getElementById('hna').value) || 0;
    const ppnPercentage = parseFloat(document.getElementById('ppn_percentage').value) || 0;
    
    const ppnAmount = (hna * ppnPercentage) / 100;
    const totalPrice = hna + ppnAmount;
    
    document.getElementById('ppn_amount_display').value = ppnAmount.toLocaleString('id-ID', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    
    document.getElementById('total_price_display').value = totalPrice.toLocaleString('id-ID', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Calculate on page load
document.addEventListener('DOMContentLoaded', function() {
    calculateTotal();
});

// Auto-uppercase for code
document.getElementById('code').addEventListener('input', function() {
    this.value = this.value.toUpperCase();
});
</script>
@endsection
