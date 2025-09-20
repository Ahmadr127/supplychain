@extends('layouts.app')

@section('title', 'Detail Master Barang')

@section('content')
<div class="w-full mx-auto max-w-4xl">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Detail Master Barang</h2>
                <div class="flex space-x-2">
                    <a href="{{ route('master-items.edit', $masterItem) }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        <i class="fas fa-edit mr-1"></i> Edit
                    </a>
                    <a href="{{ route('master-items.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Left Column - Basic Information -->
                <div class="space-y-6">
                    <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Informasi Dasar</h3>
                    
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="flex items-center mb-4">
                            <div class="h-16 w-16 rounded-full bg-blue-100 flex items-center justify-center mr-4">
                                <i class="fas fa-box text-blue-600 text-2xl"></i>
                            </div>
                            <div>
                                <h4 class="text-xl font-semibold text-gray-900">{{ $masterItem->name }}</h4>
                                <p class="text-sm text-gray-500">{{ $masterItem->code }}</p>
                            </div>
                        </div>
                        
                        @if($masterItem->description)
                            <p class="text-gray-700 mb-4">{{ $masterItem->description }}</p>
                        @endif
                    </div>
                </div>

                <!-- Right Column - Classification & Pricing -->
                <div class="space-y-6">
                    <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Klasifikasi & Harga</h3>
                    
                    <div class="bg-gray-50 p-4 rounded-lg space-y-4">
                        <!-- Classification -->
                        <div class="grid grid-cols-1 gap-4">
                            <div class="flex justify-between">
                                <span class="font-medium text-gray-500">Tipe Barang:</span>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $masterItem->itemType->name == 'Medis' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                                    {{ $masterItem->itemType->name }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-medium text-gray-500">Kategori:</span>
                                <span class="text-gray-900">{{ $masterItem->itemCategory->name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-medium text-gray-500">Komoditas:</span>
                                <span class="text-gray-900">{{ $masterItem->commodity->name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-medium text-gray-500">Satuan:</span>
                                <span class="text-gray-900">{{ $masterItem->unit->name }} ({{ $masterItem->unit->code }})</span>
                            </div>
                        </div>

                        <!-- Pricing -->
                        <div class="border-t pt-4">
                            <h4 class="font-semibold text-gray-900 mb-3">Informasi Harga</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="font-medium text-gray-500">HNA:</span>
                                    <span class="text-gray-900">Rp {{ number_format($masterItem->hna, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="font-medium text-gray-500">PPN ({{ $masterItem->ppn_percentage }}%):</span>
                                    <span class="text-gray-900">Rp {{ number_format($masterItem->ppn_amount, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between border-t pt-2">
                                    <span class="font-semibold text-gray-700">Total Harga:</span>
                                    <span class="font-bold text-lg text-blue-600">Rp {{ number_format($masterItem->total_price, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Stock -->
                        <div class="border-t pt-4">
                            <h4 class="font-semibold text-gray-900 mb-3">Stok</h4>
                            <div class="text-center p-4 bg-blue-50 rounded-lg">
                                <div class="text-3xl font-bold text-blue-600">{{ $masterItem->stock }}</div>
                                <div class="text-sm text-blue-700">{{ $masterItem->unit->name }} ({{ $masterItem->unit->code }})</div>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="border-t pt-4">
                            <div class="flex justify-between items-center">
                                <span class="font-medium text-gray-500">Status:</span>
                                <span class="px-3 py-1 text-sm font-semibold rounded-full {{ $masterItem->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $masterItem->is_active ? 'Aktif' : 'Tidak Aktif' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end space-x-4 mt-8 pt-6 border-t">
                <a href="{{ route('master-items.index') }}" 
                   class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar
                </a>
                <a href="{{ route('master-items.edit', $masterItem) }}" 
                   class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                    <i class="fas fa-edit mr-1"></i> Edit Barang
                </a>
                <form action="{{ route('master-items.destroy', $masterItem) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus barang ini?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-6 rounded">
                        <i class="fas fa-trash mr-1"></i> Hapus Barang
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
