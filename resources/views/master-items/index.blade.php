@extends('layouts.app')

@section('title', 'Master Barang')

@section('content')
<x-responsive-table 
    title="Master Barang"
    :createRoute="route('master-items.create')"
    createLabel="Tambah Barang"
    :pagination="$masterItems"
    :emptyState="$masterItems->count() === 0"
    emptyMessage="Belum ada master barang"
    emptyIcon="fas fa-box-open"
    :emptyActionRoute="route('master-items.create')"
    emptyActionLabel="Tambah Barang Pertama">
    
    <x-slot name="filters">
        <form method="GET" action="{{ route('master-items.index') }}" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-48">
                <input type="text" name="search" value="{{ request('search') }}" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                       placeholder="Nama, kode...">
            </div>
            
            <div class="w-32">
                <select name="item_type_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    <option value="">Semua Tipe</option>
                    @foreach($itemTypes as $type)
                        <option value="{{ $type->id }}" {{ request('item_type_id') == $type->id ? 'selected' : '' }}>
                            {{ $type->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="w-32">
                <select name="item_category_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    <option value="">Semua Kategori</option>
                    @foreach($itemCategories as $category)
                        <option value="{{ $category->id }}" {{ request('item_category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="w-32">
                <select name="commodity_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    <option value="">Semua Komoditas</option>
                    @foreach($commodities as $commodity)
                        <option value="{{ $commodity->id }}" {{ request('commodity_id') == $commodity->id ? 'selected' : '' }}>
                            {{ $commodity->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="w-32">
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    <option value="">Semua Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
                </select>
            </div>
            
            <div class="flex space-x-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded text-sm">
                    <i class="fas fa-search mr-1"></i> Filter
                </button>
                <a href="{{ route('master-items.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded text-sm">
                    <i class="fas fa-times mr-1"></i> Reset
                </a>
            </div>
        </form>
    </x-slot>

    <!-- Master Data Management Links -->
    <div class="p-6 bg-white border-b border-gray-200">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="{{ route('item-types.index') }}" class="bg-green-100 hover:bg-green-200 p-4 rounded-lg text-center transition-colors duration-200">
                <i class="fas fa-tags text-green-600 text-2xl mb-2"></i>
                <h3 class="font-semibold text-green-800">Tipe Barang</h3>
                <p class="text-sm text-green-600">Medis/Non Medis</p>
            </a>
            <a href="{{ route('item-categories.index') }}" class="bg-blue-100 hover:bg-blue-200 p-4 rounded-lg text-center transition-colors duration-200">
                <i class="fas fa-list text-blue-600 text-2xl mb-2"></i>
                <h3 class="font-semibold text-blue-800">Kategori</h3>
                <p class="text-sm text-blue-600">ATK, Rumah Tangga, dll</p>
            </a>
            <a href="{{ route('commodities.index') }}" class="bg-purple-100 hover:bg-purple-200 p-4 rounded-lg text-center transition-colors duration-200">
                <i class="fas fa-cube text-purple-600 text-2xl mb-2"></i>
                <h3 class="font-semibold text-purple-800">Komoditas</h3>
                <p class="text-sm text-purple-600">Farmasi, Kesehatan, dll</p>
            </a>
            <a href="{{ route('units.index') }}" class="bg-orange-100 hover:bg-orange-200 p-4 rounded-lg text-center transition-colors duration-200">
                <i class="fas fa-ruler text-orange-600 text-2xl mb-2"></i>
                <h3 class="font-semibold text-orange-800">Satuan</h3>
                <p class="text-sm text-orange-600">Pcs, Box, Liter, dll</p>
            </a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="responsive-table min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="w-1/4 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Barang</th>
                    <th class="w-1/12 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe</th>
                    <th class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                    <th class="w-1/12 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stok</th>
                    <th class="w-1/12 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">HNA</th>
                    <th class="w-1/12 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PPN</th>
                    <th class="w-1/12 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                    <th class="w-1/12 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="w-1/12 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($masterItems as $item)
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="w-1/4 px-6 py-4">
                            <div class="flex items-center min-w-0">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                        <i class="fas fa-box text-blue-600"></i>
                                    </div>
                                </div>
                                <div class="ml-4 min-w-0 flex-1">
                                    <div class="text-sm font-medium text-gray-900 truncate">{{ $item->name }}</div>
                                    <div class="text-sm text-gray-500 truncate">{{ $item->code }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="w-1/12 px-6 py-4">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $item->itemType->name == 'Medis' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                                {{ $item->itemType->name }}
                            </span>
                        </td>
                        <td class="w-1/6 px-6 py-4">
                            <div class="text-sm text-gray-900 truncate">{{ $item->itemCategory->name }}</div>
                        </td>
                        <td class="w-1/12 px-6 py-4">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $item->stock > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $item->stock }} {{ $item->unit->code }}
                            </span>
                        </td>
                        <td class="w-1/12 px-6 py-4">
                            <div class="text-sm text-gray-900 truncate">Rp {{ number_format($item->hna, 0, ',', '.') }}</div>
                        </td>
                        <td class="w-1/12 px-6 py-4">
                            <div class="min-w-0">
                                <div class="text-sm text-gray-900 truncate">{{ $item->ppn_percentage }}%</div>
                                <div class="text-xs text-gray-500 truncate">Rp {{ number_format($item->ppn_amount, 0, ',', '.') }}</div>
                            </div>
                        </td>
                        <td class="w-1/12 px-6 py-4">
                            <div class="text-sm font-semibold text-gray-900 truncate">Rp {{ number_format($item->total_price, 0, ',', '.') }}</div>
                        </td>
                        <td class="w-1/12 px-6 py-4">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $item->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $item->is_active ? 'Aktif' : 'Tidak Aktif' }}
                            </span>
                        </td>
                        <td class="w-1/12 px-6 py-4 text-sm font-medium">
                            <div class="flex space-x-2">
                                <a href="{{ route('master-items.show', $item) }}" class="text-blue-600 hover:text-blue-900 transition-colors duration-150">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('master-items.edit', $item) }}" class="text-indigo-600 hover:text-indigo-900 transition-colors duration-150">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('master-items.destroy', $item) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus barang ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 transition-colors duration-150">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-responsive-table>
@endsection
