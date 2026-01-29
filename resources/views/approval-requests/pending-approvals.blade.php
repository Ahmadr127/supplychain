@extends('layouts.app')

@section('title', 'All My Approvals')

@section('content')

@php
    // Check if current user is director level
    $isDirectorLevel = auth()->user()->isDirectorLevel();
    $currentUser = auth()->user();
    
    // Check if user should see progress column at all
    $showProgressColumn = $isDirectorLevel;
    
    // If not director, check if user is manager and has any requests from their department
    if (!$showProgressColumn) {
        foreach ($pendingItems as $item) {
            if ($currentUser->isManagerOfRequester($item->request->requester)) {
                $showProgressColumn = true;
                break;
            }
        }
    }
@endphp

<x-responsive-table 
    title="All My Approvals"
    :pagination="$pendingItems"
    :emptyState="$pendingItems->isEmpty()"
    emptyMessage="Tidak ada approvals yang tersedia"
    emptyIcon="fas fa-check-circle"
    :emptyActionRoute="route('approval-requests.index')"
    emptyActionLabel="Lihat Semua Requests">
    
    <x-slot name="filters">
        <div class="space-y-3">


            {{-- Phase Tabs --}}


            {{-- Main Filter Bar with Action Buttons --}}
            <div class="flex flex-col lg:flex-row gap-2">
                <!-- Search and Filter Section -->
                <form method="GET" class="flex flex-1 gap-2 items-center">
                    <div class="flex-1 max-w-md">
                        <div class="relative">
                            <input type="text" name="search" value="{{ request('search') }}" 
                                   placeholder="Cari approval..."
                                   class="w-full h-9 pl-9 pr-3 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <svg class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <select name="status" class="h-9 px-3 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="all" {{ !request('status') || request('status') === 'all' ? 'selected' : '' }}>Semua Status</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="on progress" {{ request('status') === 'on progress' ? 'selected' : '' }}>On Progress</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                    <button type="submit" class="h-9 px-4 text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors">
                        <i class="fas fa-search mr-1"></i>
                        Search
                    </button>
                </form>
                
                <!-- Action Buttons -->
                <div class="flex gap-2 flex-shrink-0">
                    <a href="{{ route('approval-requests.my-requests') }}" 
                       class="h-9 px-3 inline-flex items-center text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors">
                        <i class="fas fa-list mr-1.5"></i>
                        <span class="hidden sm:inline">My Requests</span>
                    </a>
                </div>
            </div>
            
            <!-- Status Legend with Counts -->
            <div class="flex flex-wrap gap-2 py-1">
                @if(isset($statusCounts))
                    <x-approval-status-badge status="on progress" :count="$statusCounts['on_progress'] ?? 0" variant="solid" />
                    <x-approval-status-badge status="pending" :count="$statusCounts['pending'] ?? 0" variant="solid" />
                    <x-approval-status-badge status="approved" :count="$statusCounts['approved'] ?? 0" variant="solid" />
                    <x-approval-status-badge status="rejected" :count="$statusCounts['rejected'] ?? 0" variant="solid" />
                    <x-approval-status-badge status="cancelled" :count="$statusCounts['cancelled'] ?? 0" variant="solid" />
                @else
                    <x-info-status class="py-1" variant="status" size="sm" />
                @endif
            </div>
        </div>
    </x-slot>
    <div class="overflow-x-auto">
        <table class="responsive-table min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="w-16 text-left">No</th>
                    <th class="w-24 text-left">Tanggal</th>
                    <th class="w-1/5 text-left">Request & Item</th>
                    <th class="w-48 text-left">Unit Peruntukan</th>
                    <th class="w-32 text-left">Pengaju</th>
                    @if($showProgressColumn)
                        <th class="w-1/2 text-left">Progress</th>
                    @endif
                    <th class="w-20 text-left">Status</th>
                    <th class="w-40 text-left">Status Purchasing</th>
                    <th class="w-20 text-left">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($pendingItems as $index => $row)
                <tr class="hover:bg-gray-50 transition-colors duration-150">
                    <td class="w-16">{{ $pendingItems->firstItem() + $index }}</td>
                    <td class="w-24">
                        <div>{{ $row->request->created_at->format('d/m/Y') }}</div>
                        <div class="text-xs">{{ $row->request->created_at->format('H:i') }}</div>
                    </td>
                    <td class="w-1/5">
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
                            // Ensure departments map available locally
                            $__deptMap = $departmentsMap ?? \App\Models\Department::pluck('name','id');
                            // Use itemData from new per-item system (no longer pivot)
                            $deptId = $row->itemData->allocation_department_id ?? null;
                            $deptNames = collect([$deptId])->filter()->map(fn($id) => $__deptMap[$id] ?? null)->filter()->values();
                        @endphp
                        <span class="text-sm text-gray-900">{{ $deptNames->count() ? $deptNames->implode(', ') : '-' }}</span>
                    </td>
                    <td class="w-32">
                        <div class="text-sm font-medium text-gray-900">{{ $row->request->requester->name }}</div>
                    </td>
                    @if($showProgressColumn)
                        @php
                            // Show progress if user is director OR manager of requester's department
                            $showProgress = $isDirectorLevel || $currentUser->isManagerOfRequester($row->request->requester);
                        @endphp
                        @if($showProgress)
                            <td class="w-1/2">
                                <x-approval-progress-steps :request="$row->request" :step-data="$row->itemData" :show-metadata="true" />
                            </td>
                        @else
                            <td class="w-1/2">
                                <span class="text-xs text-gray-400 italic">-</span>
                            </td>
                        @endif
                    @endif
                    <td class="w-20">
                        @php
                            // Use user's step status as requested
                            $displayStatus = $row->step->status;
                        @endphp
                        <x-approval-status-badge :status="$displayStatus" />
                    </td>
                    <td class="w-40">
                        <x-purchasing-status-badge :item="$row->itemData" :request="$row->request" />
                    </td>
                    <td class="w-20">
                        <div class="flex space-x-1">
                            <a href="{{ route('approval-requests.show', ['approvalRequest' => $row->request->id, 'item_id' => $row->itemData->id]) }}" 
                               class="text-blue-600 hover:text-blue-900 transition-colors duration-150" title="Review">👁</a>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-responsive-table>

<!-- Purchasing Status Modal -->
<div id="ps-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-black bg-opacity-40" onclick="closePurchasingStatusModal()"></div>
    <div class="relative bg-white rounded-lg shadow-lg w-full max-w-md mx-3">
        <div class="px-4 py-3 border-b flex items-center justify-between">
            <h3 class="text-sm font-semibold">Detail Status Purchasing</h3>
            <button class="text-gray-500 hover:text-gray-700" onclick="closePurchasingStatusModal()">&times;</button>
        </div>
        <div id="ps-modal-body" class="px-4 py-3"></div>
        <div class="px-4 py-3 border-t flex justify-end">
            <button class="px-3 py-1.5 text-sm rounded bg-gray-800 text-white" onclick="closePurchasingStatusModal()">Tutup</button>
        </div>
    </div>
</div>

<script src="{{ asset('js/approval-requests-common.js') }}"></script>

@endsection
