@extends('layouts.app')

@section('title', 'Detail Master Barang')

@section('content')
<div class="w-full mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <!-- Header Section -->
    <div class="bg-white shadow-sm rounded-lg mb-4">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center space-x-4">
                    <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
                        <i class="fas fa-box text-blue-600 text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ $masterItem->name }}</h1>
                        <p class="text-sm text-gray-500">{{ $masterItem->code }}</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button onclick="openEditModalFromShow('{{ $masterItem->id }}', {{ json_encode($masterItem->toArray()) }})" 
                            class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg text-sm transition-colors duration-200">
                        <i class="fas fa-edit mr-1"></i> Edit
                    </button>
                    <a href="{{ route('master-items.index') }}" 
                       class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg text-sm transition-colors duration-200">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <!-- Left Column - Basic Info & Classification -->
        <div class="xl:col-span-2 space-y-6">
            <!-- Basic Information Card -->
            <div class="bg-white shadow-sm rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Informasi Dasar</h3>
                </div>
                <div class="p-6">
                    @if($masterItem->description)
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-500 mb-1">Deskripsi</label>
                            <p class="text-gray-900">{{ $masterItem->description }}</p>
                        </div>
                    @endif
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Tipe Barang</label>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $masterItem->itemType->name == 'Medis' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                                {{ $masterItem->itemType->name }}
                            </span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Kategori</label>
                            <p class="text-gray-900">{{ $masterItem->itemCategory->name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Komoditas</label>
                            <p class="text-gray-900">{{ $masterItem->commodity->name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Satuan</label>
                            <p class="text-gray-900">{{ $masterItem->unit->name }} ({{ $masterItem->unit->code }})</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pricing Information Card -->
            <div class="bg-white shadow-sm rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Informasi Harga</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <label class="block text-sm font-medium text-gray-500 mb-2">HNA</label>
                            <div class="text-2xl font-bold text-gray-900">Rp {{ number_format($masterItem->hna, 0, ',', '.') }}</div>
                        </div>
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <label class="block text-sm font-medium text-gray-500 mb-2">PPN ({{ $masterItem->ppn_percentage }}%)</label>
                            <div class="text-2xl font-bold text-gray-900">Rp {{ number_format($masterItem->ppn_amount, 0, ',', '.') }}</div>
                        </div>
                        <div class="text-center p-4 bg-blue-50 rounded-lg">
                            <label class="block text-sm font-medium text-blue-600 mb-2">Total Harga</label>
                            <div class="text-2xl font-bold text-blue-600">Rp {{ number_format($masterItem->total_price, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Stock & Status -->
        <div class="space-y-6">
            <!-- Stock Information Card -->
            <div class="bg-white shadow-sm rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Stok</h3>
                </div>
                <div class="p-6">
                    <div class="text-center">
                        <div class="text-4xl font-bold {{ $masterItem->stock > 0 ? 'text-green-600' : 'text-red-600' }} mb-2">
                            {{ $masterItem->stock }}
                        </div>
                        <div class="text-sm text-gray-600">{{ $masterItem->unit->name }} ({{ $masterItem->unit->code }})</div>
                        <div class="mt-3">
                            <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full {{ $masterItem->stock > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $masterItem->stock > 0 ? 'Tersedia' : 'Habis' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Card -->
            <div class="bg-white shadow-sm rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Status</h3>
                </div>
                <div class="p-6">
                    <div class="text-center">
                        <div class="mb-4">
                            <span class="inline-flex px-4 py-2 text-sm font-semibold rounded-full {{ $masterItem->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $masterItem->is_active ? 'Aktif' : 'Tidak Aktif' }}
                            </span>
                        </div>
                        <div class="text-sm text-gray-500">
                            {{ $masterItem->is_active ? 'Barang dapat digunakan' : 'Barang tidak dapat digunakan' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Card -->
            <div class="bg-white shadow-sm rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Aksi Cepat</h3>
                </div>
                <div class="p-6 space-y-3">
                    <button onclick="openEditModalFromShow('{{ $masterItem->id }}', {{ json_encode($masterItem->toArray()) }})" 
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg text-sm transition-colors duration-200">
                        <i class="fas fa-edit mr-2"></i> Edit Barang
                    </button>
                    <a href="{{ route('master-items.index') }}" 
                       class="block w-full bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg text-sm text-center transition-colors duration-200">
                        <i class="fas fa-arrow-left mr-2"></i> Kembali ke Daftar
                    </a>
                    <form action="{{ route('master-items.destroy', $masterItem) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus barang ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg text-sm transition-colors duration-200">
                            <i class="fas fa-trash mr-2"></i> Hapus Barang
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Modal Form -->
@include('components.modals.form-master-items')

<script>
function openEditModalFromShow(itemId, itemData) {
    // Convert the item data to the format expected by the modal
    const formattedData = {
        name: itemData.name,
        code: itemData.code,
        item_type_id: itemData.item_type_id,
        item_category_id: itemData.item_category_id,
        commodity_id: itemData.commodity_id,
        unit_id: itemData.unit_id,
        stock: itemData.stock,
        hna: itemData.hna,
        ppn_percentage: itemData.ppn_percentage,
        is_active: itemData.is_active,
        description: itemData.description
    };
    
    openEditModal(itemId, formattedData);
}
</script>
@endsection
