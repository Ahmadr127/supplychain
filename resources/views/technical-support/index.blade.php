@extends('layouts.app')

@section('title', 'Antrean Technical Support')

@section('content')
<div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <div class="p-6 border-b border-gray-200">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Antrean Technical Support</h2>
                <p class="text-sm text-gray-500 mt-1">Daftar item yang membutuhkan input spesifikasi dari Technical Support</p>
            </div>
            
            <form method="GET" action="{{ route('technical-support.index') }}" class="w-full sm:w-auto flex flex-col sm:flex-row gap-3">
                <select name="status" class="w-full sm:w-40 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm" onchange="this.form.submit()">
                    <option value="pending" {{ request('status', 'pending') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="done" {{ request('status') == 'done' ? 'selected' : '' }}>Selesai (Done)</option>
                </select>
                
                <div class="relative w-full sm:w-64">
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Cari item / No Request..." 
                           class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-600">
            <thead class="bg-gray-50 text-gray-700 uppercase">
                <tr>
                    <th class="px-6 py-4 font-medium">No. Request</th>
                    <th class="px-6 py-4 font-medium">Item & Kategori</th>
                    <th class="px-6 py-4 font-medium">Requester</th>
                    <th class="px-6 py-4 font-medium">Tgl Request</th>
                    <th class="px-6 py-4 font-medium">Status TS</th>
                    <th class="px-6 py-4 font-medium text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($items as $item)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="font-medium text-gray-900">{{ $item->approvalRequest->request_number }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900">{{ $item->masterItem->name ?? 'Unknown Item' }}</div>
                            <div class="text-xs text-gray-500 mt-1 flex gap-1">
                                <span class="bg-gray-100 px-2 py-0.5 rounded border border-gray-200" title="Kategori Barang">{{ $item->masterItem->itemCategory->name ?? '-' }}</span>
                                <span class="bg-blue-50 text-blue-700 px-2 py-0.5 rounded border border-blue-200" title="Kategori TS">{{ $item->tsCategory->name ?? '-' }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ $item->approvalRequest->requester->name ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ $item->created_at->format('d M Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($item->ts_status === 'pending')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    <i class="fas fa-clock mr-1.5"></i> Pending
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-check mr-1.5"></i> Selesai
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <a href="{{ route('technical-support.show', $item->id) }}" 
                               class="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-600 rounded hover:bg-blue-100 transition-colors">
                                @if($item->ts_status === 'pending')
                                    <i class="fas fa-edit mr-1.5"></i> Isi Spek
                                @else
                                    <i class="fas fa-eye mr-1.5"></i> Lihat
                                @endif
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-clipboard-list text-3xl mb-3 text-gray-300"></i>
                            <p>Tidak ada antrean Technical Support.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($items->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $items->links() }}
        </div>
    @endif
</div>
@endsection
