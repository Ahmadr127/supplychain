@extends('layouts.app')

@section('title', 'Kelola Approval Requests')

@section('content')
<x-responsive-table 
    title="Kelola Approval Requests"
    :pagination="$items"
    :emptyState="$items->count() === 0"
    emptyMessage="Belum ada approval request"
    emptyIcon="fas fa-clipboard-check"
    :emptyActionRoute="route('approval-requests.create')"
    emptyActionLabel="Buat Request Pertama"
    :filtersBorder="false">
    
    <x-slot name="filters">
        <div class="space-y-2">
            <!-- Main Filter Bar with Action Buttons -->
            <div class="flex flex-col lg:flex-row gap-2">
                <!-- Search and Filter Section -->
                <form method="GET" class="flex flex-1 gap-2 items-center">
                    <div class="flex-1 max-w-md">
                        <div class="relative">
                            <input type="text" name="search" value="{{ request('search') }}" 
                                   placeholder="Cari request..."
                                   class="w-full h-9 pl-9 pr-3 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <svg class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <select name="status" class="h-9 px-3 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Status</option>
                        <option value="on progress" {{ request('status') === 'on progress' ? 'selected' : '' }}>On Progress</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                    
                    <!-- Advanced Filters (collapsible) -->
                    <div x-data="{ showAdvanced: false }" class="flex items-center gap-2">
                        <button type="button" @click="showAdvanced = !showAdvanced" 
                                class="h-9 px-3 text-sm text-gray-600 hover:text-gray-900 border border-gray-300 rounded-md hover:bg-gray-50">
                            <i class="fas fa-filter mr-1"></i>
                            <span x-text="showAdvanced ? 'Sembunyikan' : 'Filter Lanjutan'"></span>
                        </button>
                        
                        <div x-show="showAdvanced" x-transition class="flex gap-2">
                            <select name="workflow_id" class="h-9 px-3 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Semua Workflow</option>
                                @foreach($workflows as $workflow)
                                    <option value="{{ $workflow->id }}" {{ request('workflow_id') == $workflow->id ? 'selected' : '' }}>
                                        {{ $workflow->name }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="date" name="date_from" value="{{ request('date_from') }}" 
                                   class="h-9 px-3 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <input type="date" name="date_to" value="{{ request('date_to') }}" 
                                   class="h-9 px-3 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    
                    <button type="submit" class="h-9 px-4 text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors">
                        <i class="fas fa-search mr-1"></i>
                        Filter
                    </button>
                </form>
                
                <!-- Action Buttons -->
                <div class="flex gap-2 flex-shrink-0">
                    <a href="{{ route('approval-requests.my-requests') }}" 
                       class="h-9 px-3 inline-flex items-center text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors">
                        <i class="fas fa-list mr-1.5"></i>
                        <span class="hidden sm:inline">My Requests</span>
                    </a>
                    <a href="{{ route('approval-requests.pending-approvals') }}" 
                       class="h-9 px-3 inline-flex items-center text-sm font-medium bg-yellow-600 hover:bg-yellow-700 text-white rounded-md transition-colors">
                        <i class="fas fa-clock mr-1.5"></i>
                        <span class="hidden sm:inline">Pending</span>
                    </a>
                    <a href="{{ route('approval-requests.create') }}" 
                       class="h-9 px-3 inline-flex items-center text-sm font-medium bg-green-600 hover:bg-green-700 text-white rounded-md transition-colors">
                        <i class="fas fa-plus mr-1.5"></i>
                        <span class="hidden sm:inline">Buat Request</span>
                    </a>
                </div>
            </div>
            
            <!-- Info Status -->
            <x-info-status class="py-1" variant="status" size="sm" />
        </div>
    </x-slot>
    <div class="overflow-x-auto">
        <table class="responsive-table min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="w-16 text-left">No</th>
                    <th class="w-24 text-left">Tanggal</th>
                    <th class="w-1/4 text-left">Request & Item</th>
                    <th class="w-48 text-left">Unit Peruntukan</th>
                    <th class="w-32 text-left">Pengaju</th>
                    <th class="w-1/3 text-left">Progress</th>
                    <th class="w-20 text-left">Status</th>
                    <th class="w-40 text-left">Status Purchasing</th>
                    <th class="w-20 text-left">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($items as $index => $row)
                <tr class="hover:bg-gray-50 transition-colors duration-150">
                    <td class="w-16">{{ $items->firstItem() + $index }}</td>
                    <td class="w-24">
                        <div>{{ $row->request->created_at->format('d/m/Y') }}</div>
                        <div class="text-xs">{{ $row->request->created_at->format('H:i') }}</div>
                    </td>
                    <td class="w-1/4">
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-gray-900 truncate">
                                <span class="inline-block bg-gray-100 text-gray-800 text-xs px-1 py-0.5 rounded mr-1">
                                    {{ $row->request->request_number }}
                                </span>
                            </div>
                            <div class="text-xs text-gray-900 min-w-0">
                                <span class="inline-block bg-gray-100 border border-gray-200 text-gray-800 px-1 py-0.5 rounded">{{ $row->item->name }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="w-48 align-top">
                        @php
                            $deptId = $row->itemData->allocation_department_id ?? null;
                            $deptNames = collect([$deptId])->filter()->map(fn($id) => $departmentsMap[$id] ?? null)->filter()->values();
                        @endphp
                        <span class="text-sm text-gray-900">{{ $deptNames->count() ? $deptNames->implode(', ') : '-' }}</span>
                    </td>
                    <td class="w-32">
                        <div class="flex items-center min-w-0">
                            <div class="flex-shrink-0 h-5 w-5">
                                <div class="h-5 w-5 rounded-full bg-gray-300 flex items-center justify-center">
                                    <span class="text-gray-600 text-xs font-medium">
                                        {{ substr($row->request->requester->name, 0, 2) }}
                                    </span>
                                </div>
                            </div>
                            <div class="ml-1 min-w-0 flex-1">
                                <div class="text-sm font-medium text-gray-900 truncate">{{ $row->request->requester->name }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="w-1/3">
                        <x-approval-progress-steps :request="$row->request" :step-data="$row->itemData" :show-metadata="true" />
                    </td>
                    <td class="w-20">
                        <x-approval-status-badge :status="$row->itemData->status" />
                    </td>
                    <td class="w-40">
                        <x-purchasing-status-badge :status="$row->request->purchasing_status" :request-id="$row->request->id" />
                    </td>
                    <td class="w-20">
                        <div class="flex space-x-1">
                            <a href="{{ route('approval-requests.show', ['approvalRequest' => $row->request->id, 'item_id' => $row->itemData->id]) }}" 
                               class="text-blue-600 hover:text-blue-900 transition-colors duration-150" title="Lihat">👁</a>
                            @if($row->itemData->status == 'pending' && $row->request->requester_id == auth()->id())
                                <a href="{{ route('approval-requests.edit', $row->request) }}" 
                                   class="text-indigo-600 hover:text-indigo-900 transition-colors duration-150" title="Edit">✏️</a>
                            @endif
                            @if($row->request->requester_id == auth()->id())
                                <button onclick="deleteRequest({{ $row->request->id }})" 
                                        class="text-red-600 hover:text-red-900 transition-colors duration-150" title="Hapus">🗑️</button>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-responsive-table>

<!-- Hidden Delete Form -->
<form id="deleteForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<script src="{{ asset('js/approval-requests-common.js') }}"></script>
<script>
window.deleteRequest = function(requestId) {
    if (confirm('Apakah Anda yakin ingin menghapus request ini? Tindakan ini tidak dapat dibatalkan.')) {
        const form = document.getElementById('deleteForm');
        form.action = `/approval-requests/${requestId}`;
        form.submit();
    }
}
</script>

@endsection

