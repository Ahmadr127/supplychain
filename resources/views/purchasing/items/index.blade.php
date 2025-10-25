@extends('layouts.app')

@section('title', 'Purchasing Items')

@section('content')
<div class="space-y-3">
    <div class="bg-white p-3 rounded-lg border border-gray-200">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-7 gap-2 items-end">
            <div class="md:col-span-3">
                <label class="block text-xs font-medium text-gray-600 mb-0.5">Pencarian</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari No Input / Nama Item" class="w-full h-8 px-2 py-1 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-0.5">Status</label>
                <select name="status" class="w-full h-8 px-2 py-1 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Semua</option>
                    @foreach(['unprocessed','benchmarking','comparing','selected','po_issued','grn_received','done'] as $st)
                        <option value="{{ $st }}" {{ request('status')===$st ? 'selected' : '' }}>{{ strtoupper($st) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-0.5">Tampilkan</label>
                <select name="per_page" class="w-full h-8 px-2 py-1 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500" onchange="this.form.submit()">
                    <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                    <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                </select>
            </div>
            <div class="md:col-span-2 flex gap-2">
                <button class="h-8 px-3 bg-indigo-600 text-white rounded-md text-xs">Filter</button>
                <a href="{{ route('purchasing.items.index') }}" class="h-8 px-3 border border-gray-300 rounded-md text-xs flex items-center">Reset</a>
            </div>
        </form>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <div class="overflow-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-700">
                    <tr>
                        <th class="px-3 py-2 text-left">No Input</th>
                        <th class="px-3 py-2 text-left">Item</th>
                        <th class="px-3 py-2 text-left">Qty</th>
                        <th class="px-3 py-2 text-left">Status</th>
                        <th class="px-3 py-2 text-left">Preferred Vendor</th>
                        <th class="px-3 py-2 text-left">PO</th>
                        <th class="px-3 py-2 text-left">GRN Date</th>
                        <th class="px-3 py-2 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $pi)
                        <tr class="border-t">
                            <td class="px-3 py-2 text-sm text-gray-900">{{ $pi->approvalRequest->request_number ?? '-' }}</td>
                            <td class="px-3 py-2 text-sm text-gray-900">{{ $pi->masterItem->name ?? '-' }}</td>
                            <td class="px-3 py-2 text-sm text-gray-900">{{ $pi->quantity }}</td>
                            <td class="px-3 py-2 text-sm">
                                @php
                                    $ps = $pi->status ?? 'unprocessed';
                                    $psText = match($ps){
                                        'unprocessed' => 'Belum diproses',
                                        'benchmarking' => 'Pemilihan vendor',
                                        'selected' => 'Uji coba/Proses PR sistem',
                                        'po_issued' => 'Proses di vendor',
                                        'grn_received' => 'Barang sudah diterima',
                                        'done' => 'Selesai',
                                        default => strtoupper($ps),
                                    };
                                    $psColor = match($ps){
                                        'unprocessed' => 'bg-gray-100 text-gray-700',
                                        'benchmarking' => 'bg-yellow-100 text-yellow-800',
                                        'selected' => 'bg-blue-100 text-blue-800',
                                        'po_issued' => 'bg-indigo-100 text-indigo-800',
                                        'grn_received' => 'bg-teal-100 text-teal-800',
                                        'done' => 'bg-green-100 text-green-800',
                                        default => 'bg-gray-100 text-gray-700',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $psColor }}">{{ $psText }}</span>
                            </td>
                            <td class="px-3 py-2 text-sm text-gray-900">{{ $pi->preferredVendor->name ?? '-' }}</td>
                            <td class="px-3 py-2 text-sm text-gray-900">{{ $pi->po_number ?? '-' }}</td>
                            <td class="px-3 py-2 text-sm text-gray-900">{{ $pi->grn_date ? $pi->grn_date->format('Y-m-d') : '-' }}</td>
                            <td class="px-3 py-2 text-sm flex items-center gap-3">
                                @if(auth()->user()->hasPermission('manage_vendor') || auth()->user()->hasPermission('manage_purchasing'))
                                    <a href="{{ route('purchasing.items.vendor', $pi) }}" class="text-blue-600 hover:underline">Vendor</a>
                                @endif
                                <a href="{{ route('purchasing.items.show', $pi) }}" class="text-indigo-600 hover:underline">Buka</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-3 py-4 text-center text-sm text-gray-500">Belum ada data</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!-- Pagination Section -->
        <div class="px-4 py-3 border-t border-gray-200 bg-white">
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                <!-- Show entries info -->
                <div class="text-sm text-gray-700">
                    <span class="font-medium">Menampilkan</span>
                    <span class="font-semibold text-gray-900">{{ $items->firstItem() ?? 0 }}</span>
                    <span class="font-medium">sampai</span>
                    <span class="font-semibold text-gray-900">{{ $items->lastItem() ?? 0 }}</span>
                    <span class="font-medium">dari</span>
                    <span class="font-semibold text-gray-900">{{ $items->total() }}</span>
                    <span class="font-medium">data</span>
                </div>
                
                <!-- Custom Pagination -->
                <div class="flex items-center space-x-1">
                    @if ($items->onFirstPage())
                        <span class="px-3 py-2 text-sm text-gray-400 bg-gray-100 border border-gray-300 rounded-l-md cursor-not-allowed">
                            <i class="fas fa-chevron-left"></i>
                        </span>
                    @else
                        <a href="{{ $items->previousPageUrl() }}" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-l-md hover:bg-gray-50 hover:text-gray-900 transition-colors duration-150">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    @endif

                    @php
                        $currentPage = $items->currentPage();
                        $lastPage = $items->lastPage();
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($lastPage, $currentPage + 2);
                        
                        // Adjust range if we're near the beginning or end
                        if ($currentPage <= 3) {
                            $endPage = min(5, $lastPage);
                        }
                        if ($currentPage >= $lastPage - 2) {
                            $startPage = max(1, $lastPage - 4);
                        }
                    @endphp

                    @if ($startPage > 1)
                        <a href="{{ $items->url(1) }}" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 hover:text-gray-900 transition-colors duration-150">
                            1
                        </a>
                        @if ($startPage > 2)
                            <span class="px-2 py-2 text-sm text-gray-500">...</span>
                        @endif
                    @endif

                    @for ($page = $startPage; $page <= $endPage; $page++)
                        @if ($page == $currentPage)
                            <span class="px-3 py-2 text-sm font-semibold text-white bg-blue-600 border border-blue-600">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $items->url($page) }}" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 hover:text-gray-900 transition-colors duration-150">
                                {{ $page }}
                            </a>
                        @endif
                    @endfor

                    @if ($endPage < $lastPage)
                        @if ($endPage < $lastPage - 1)
                            <span class="px-2 py-2 text-sm text-gray-500">...</span>
                        @endif
                        <a href="{{ $items->url($lastPage) }}" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 hover:text-gray-900 transition-colors duration-150">
                            {{ $lastPage }}
                        </a>
                    @endif

                    @if ($items->hasMorePages())
                        <a href="{{ $items->nextPageUrl() }}" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-r-md hover:bg-gray-50 hover:text-gray-900 transition-colors duration-150">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    @else
                        <span class="px-3 py-2 text-sm text-gray-400 bg-gray-100 border border-gray-300 rounded-r-md cursor-not-allowed">
                            <i class="fas fa-chevron-right"></i>
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
