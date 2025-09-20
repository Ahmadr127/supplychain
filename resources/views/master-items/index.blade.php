@extends('layouts.app')

@section('title', 'Master Barang')

@section('content')
<div class="w-full mx-auto">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Master Barang</h2>
                <div class="flex space-x-2">
                    <a href="{{ route('master-items.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        <i class="fas fa-plus mr-1"></i> Tambah Barang
                    </a>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="bg-gray-50 p-4 rounded-lg mb-6">
                <form method="GET" action="{{ route('master-items.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cari</label>
                        <input type="text" name="search" value="{{ request('search') }}" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Nama, kode...">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Barang</label>
                        <select name="item_type_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Semua Tipe</option>
                            @foreach($itemTypes as $type)
                                <option value="{{ $type->id }}" {{ request('item_type_id') == $type->id ? 'selected' : '' }}>
                                    {{ $type->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                        <select name="item_category_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Semua Kategori</option>
                            @foreach($itemCategories as $category)
                                <option value="{{ $category->id }}" {{ request('item_category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Komoditas</label>
                        <select name="commodity_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Semua Komoditas</option>
                            @foreach($commodities as $commodity)
                                <option value="{{ $commodity->id }}" {{ request('commodity_id') == $commodity->id ? 'selected' : '' }}>
                                    {{ $commodity->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Semua Status</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
                        </select>
                    </div>
                    
                    <div class="md:col-span-5 flex justify-end space-x-2">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            <i class="fas fa-search mr-1"></i> Filter
                        </button>
                        <a href="{{ route('master-items.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            <i class="fas fa-times mr-1"></i> Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Master Data Management Links -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <a href="{{ route('item-types.index') }}" class="bg-green-100 hover:bg-green-200 p-4 rounded-lg text-center">
                    <i class="fas fa-tags text-green-600 text-2xl mb-2"></i>
                    <h3 class="font-semibold text-green-800">Tipe Barang</h3>
                    <p class="text-sm text-green-600">Medis/Non Medis</p>
                </a>
                <a href="{{ route('item-categories.index') }}" class="bg-blue-100 hover:bg-blue-200 p-4 rounded-lg text-center">
                    <i class="fas fa-list text-blue-600 text-2xl mb-2"></i>
                    <h3 class="font-semibold text-blue-800">Kategori</h3>
                    <p class="text-sm text-blue-600">ATK, Rumah Tangga, dll</p>
                </a>
                <a href="{{ route('commodities.index') }}" class="bg-purple-100 hover:bg-purple-200 p-4 rounded-lg text-center">
                    <i class="fas fa-cube text-purple-600 text-2xl mb-2"></i>
                    <h3 class="font-semibold text-purple-800">Komoditas</h3>
                    <p class="text-sm text-purple-600">Farmasi, Kesehatan, dll</p>
                </a>
                <a href="{{ route('units.index') }}" class="bg-orange-100 hover:bg-orange-200 p-4 rounded-lg text-center">
                    <i class="fas fa-ruler text-orange-600 text-2xl mb-2"></i>
                    <h3 class="font-semibold text-orange-800">Satuan</h3>
                    <p class="text-sm text-orange-600">Pcs, Box, Liter, dll</p>
                </a>
            </div>
        </div>

        <div class="p-6">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif

            @if($masterItems->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Barang</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stok</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">HNA</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PPN</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($masterItems as $item)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                    <i class="fas fa-box text-blue-600"></i>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">{{ $item->name }}</div>
                                                <div class="text-sm text-gray-500">{{ $item->code }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $item->itemType->name == 'Medis' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                                            {{ $item->itemType->name }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $item->itemCategory->name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $item->stock > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $item->stock }} {{ $item->unit->code }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        Rp {{ number_format($item->hna, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $item->ppn_percentage }}%<br>
                                        <span class="text-xs text-gray-500">Rp {{ number_format($item->ppn_amount, 0, ',', '.') }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                        Rp {{ number_format($item->total_price, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $item->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $item->is_active ? 'Aktif' : 'Tidak Aktif' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <a href="{{ route('master-items.show', $item) }}" class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('master-items.edit', $item) }}" class="text-indigo-600 hover:text-indigo-900">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('master-items.destroy', $item) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus barang ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900">
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

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $masterItems->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <i class="fas fa-box-open text-6xl text-gray-400 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Tidak ada barang</h3>
                    <p class="text-gray-500 mb-4">Belum ada master barang yang tersedia.</p>
                    <a href="{{ route('master-items.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        <i class="fas fa-plus mr-1"></i> Tambah Barang Pertama
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
