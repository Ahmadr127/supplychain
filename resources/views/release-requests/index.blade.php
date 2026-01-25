@extends('layouts.app')

@section('title', 'Release Pending')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Release Pending</h1>
            <p class="text-sm text-gray-600">Item yang menunggu release setelah proses purchasing</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('approval-requests.pending-approvals') }}" 
                class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg text-sm transition-colors">
                <i class="fas fa-check-circle mr-2"></i>Approval Pending
            </a>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        @php
            $pendingCount = $releaseItems->where('status', 'in_release')->count();
            $waitingPurchase = $releaseItems->filter(function($item) {
                return $item->getReleasePhaseSteps()->where('status', 'pending_purchase')->count() > 0;
            })->count();
            $completedToday = $releaseItems->where('status', 'approved')
                ->filter(fn($i) => $i->approved_at && $i->approved_at->isToday())->count();
        @endphp
        
        <div class="bg-purple-50 rounded-lg p-4 border border-purple-100">
            <div class="text-xs text-purple-600 font-medium">Pending Release</div>
            <div class="text-2xl font-bold text-purple-900">{{ $pendingCount }}</div>
        </div>
        <div class="bg-indigo-50 rounded-lg p-4 border border-indigo-100">
            <div class="text-xs text-indigo-600 font-medium">Waiting Purchasing</div>
            <div class="text-2xl font-bold text-indigo-900">{{ $waitingPurchase }}</div>
        </div>
        <div class="bg-green-50 rounded-lg p-4 border border-green-100">
            <div class="text-xs text-green-600 font-medium">Released Today</div>
            <div class="text-2xl font-bold text-green-900">{{ $completedToday }}</div>
        </div>
        <div class="bg-blue-50 rounded-lg p-4 border border-blue-100">
            <div class="text-xs text-blue-600 font-medium">Total Items</div>
            <div class="text-2xl font-bold text-blue-900">{{ $releaseItems->count() }}</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-gray-700 mb-1">Cari</label>
                <input type="text" 
                    name="search" 
                    value="{{ request('search') }}"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500"
                    placeholder="Nama item atau request number...">
            </div>
            <div class="w-40">
                <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500">
                    <option value="">Semua</option>
                    <option value="in_release" {{ request('status') === 'in_release' ? 'selected' : '' }}>Pending Release</option>
                    <option value="in_purchasing" {{ request('status') === 'in_purchasing' ? 'selected' : '' }}>In Purchasing</option>
                </select>
            </div>
            <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 rounded-md text-sm">
                <i class="fas fa-filter mr-1"></i>Filter
            </button>
        </form>
    </div>

    {{-- Items List --}}
    <div class="space-y-4">
        @forelse($releaseItems as $item)
        @php
            $masterItem = $item->masterItem;
            $request = $item->approvalRequest;
            $purchasingItem = \App\Models\PurchasingItem::where('approval_request_id', $request->id)
                ->where('master_item_id', $item->master_item_id)
                ->first();
            $vendor = $purchasingItem?->preferredVendor;
            $currentReleaseStep = $item->getReleasePhaseSteps()
                ->filter(fn($s) => in_array($s->status, ['pending', 'pending_purchase']))
                ->sortBy('step_number')
                ->first();
            $canRelease = $currentReleaseStep && $currentReleaseStep->status === 'pending' && 
                $currentReleaseStep->canApprove(auth()->id());
        @endphp
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden {{ $canRelease ? 'ring-2 ring-purple-400' : '' }}">
            {{-- Header --}}
            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900">{{ $masterItem->name }}</h4>
                        <p class="text-xs text-gray-600">
                            {{ $request->request_number }} • 
                            {{ $request->requester->name ?? 'Unknown' }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <x-approval-status-badge :status="$item->status" />
                    @if($canRelease)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 animate-pulse">
                            <i class="fas fa-bell mr-1"></i>Your Turn
                        </span>
                    @endif
                </div>
            </div>

            <div class="p-4">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    {{-- Item Info --}}
                    <div class="space-y-2">
                        <h5 class="text-xs font-semibold text-gray-700 uppercase">Detail Item</h5>
                        <div class="text-xs space-y-1">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Quantity:</span>
                                <span class="font-medium">{{ $item->quantity }} {{ $masterItem->unit->name ?? '' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Tipe:</span>
                                <span class="font-medium">{{ $masterItem->itemType->name ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Kategori:</span>
                                <span class="font-medium">{{ $masterItem->itemCategory->name ?? '-' }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Purchasing Info --}}
                    <div class="space-y-2">
                        <h5 class="text-xs font-semibold text-gray-700 uppercase">Purchasing</h5>
                        @if($purchasingItem)
                        <div class="text-xs space-y-1">
                            @if($vendor)
                            <div class="flex justify-between">
                                <span class="text-gray-600">Vendor:</span>
                                <span class="font-medium text-blue-600">{{ $vendor->name }}</span>
                            </div>
                            @endif
                            @if($purchasingItem->preferred_total_price)
                            <div class="flex justify-between">
                                <span class="text-gray-600">Total Harga:</span>
                                <span class="font-semibold text-green-600">
                                    Rp {{ number_format($purchasingItem->preferred_total_price, 0, ',', '.') }}
                                </span>
                            </div>
                            @endif
                            @if($purchasingItem->po_number)
                            <div class="flex justify-between">
                                <span class="text-gray-600">No. PO:</span>
                                <span class="font-medium">{{ $purchasingItem->po_number }}</span>
                            </div>
                            @endif
                            <div class="flex justify-between">
                                <span class="text-gray-600">Status:</span>
                                <span class="font-medium text-indigo-600">{{ ucfirst($purchasingItem->status) }}</span>
                            </div>
                        </div>
                        @else
                        <p class="text-xs text-gray-500 italic">Belum ada data purchasing</p>
                        @endif
                    </div>

                    {{-- Release Steps --}}
                    <div class="space-y-2">
                        <h5 class="text-xs font-semibold text-gray-700 uppercase">Release Steps</h5>
                        <div class="space-y-1">
                            @foreach($item->getReleasePhaseSteps() as $step)
                            <div class="flex items-center gap-2 text-xs">
                                @if($step->status === 'approved')
                                    <span class="w-4 h-4 rounded-full bg-green-500 flex items-center justify-center">
                                        <i class="fas fa-check text-white text-[8px]"></i>
                                    </span>
                                    <span class="text-green-700">{{ $step->step_name }}</span>
                                @elseif($step->status === 'pending')
                                    <span class="w-4 h-4 rounded-full bg-purple-500 animate-pulse"></span>
                                    <span class="text-purple-700 font-medium">{{ $step->step_name }}</span>
                                @elseif($step->status === 'pending_purchase')
                                    <span class="w-4 h-4 rounded-full bg-gray-300"></span>
                                    <span class="text-gray-500">{{ $step->step_name }}</span>
                                @elseif($step->status === 'rejected')
                                    <span class="w-4 h-4 rounded-full bg-red-500 flex items-center justify-center">
                                        <i class="fas fa-times text-white text-[8px]"></i>
                                    </span>
                                    <span class="text-red-700">{{ $step->step_name }}</span>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                @if($canRelease)
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="text-xs text-gray-600">
                            <i class="fas fa-info-circle mr-1"></i>
                            Current Step: <span class="font-semibold text-purple-700">{{ $currentReleaseStep->step_name }}</span>
                        </div>
                        <div class="flex gap-2">
                            <a href="{{ route('approval-requests.show', ['approvalRequest' => $request->id, 'item_id' => $item->id]) }}"
                                class="bg-purple-600 hover:bg-purple-700 text-white text-xs font-medium py-2 px-4 rounded-md transition-colors">
                                <i class="fas fa-paper-plane mr-1"></i>Release Item
                            </a>
                        </div>
                    </div>
                </div>
                @else
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <a href="{{ route('approval-requests.show', ['approvalRequest' => $request->id, 'item_id' => $item->id]) }}"
                        class="text-xs text-blue-600 hover:text-blue-800">
                        <i class="fas fa-eye mr-1"></i>Lihat Detail
                    </a>
                </div>
                @endif
            </div>
        </div>
        @empty
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
            <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
            <p class="text-gray-500">Tidak ada item yang menunggu release</p>
        </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($releaseItems->hasPages())
    <div class="mt-6">
        {{ $releaseItems->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
