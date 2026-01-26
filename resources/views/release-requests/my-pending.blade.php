@extends('layouts.app')

@section('title', 'My Pending Releases')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">My Pending Releases</h1>
            <p class="text-sm text-gray-600">Item yang menunggu persetujuan release dari Anda</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('release-requests.index') }}" 
                class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg text-sm transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Semua Release
            </a>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        @php
            $pendingCount = $pendingReleaseSteps->where('status', 'pending')->count();
            $waitingPurchase = $pendingReleaseSteps->where('status', 'pending_purchase')->count();
        @endphp
        
        <div class="bg-purple-50 rounded-lg p-4 border border-purple-100">
            <div class="text-xs text-purple-600 font-medium">Ready to Release</div>
            <div class="text-2xl font-bold text-purple-900">{{ $pendingCount }}</div>
        </div>
        <div class="bg-indigo-50 rounded-lg p-4 border border-indigo-100">
            <div class="text-xs text-indigo-600 font-medium">Waiting Purchasing</div>
            <div class="text-2xl font-bold text-indigo-900">{{ $waitingPurchase }}</div>
        </div>
        <div class="bg-blue-50 rounded-lg p-4 border border-blue-100">
            <div class="text-xs text-blue-600 font-medium">Total Items</div>
            <div class="text-2xl font-bold text-blue-900">{{ $pendingReleaseSteps->count() }}</div>
        </div>
    </div>

    {{-- Items List --}}
    <div class="space-y-4">
        @forelse($pendingReleaseSteps as $step)
        @php
            $masterItem = $step->masterItem;
            $request = $step->approvalRequest;
            $item = $request ? $request->items->where('master_item_id', $step->master_item_id)->first() : null;
            $canApprove = $step->status === 'pending' && $step->canApprove(auth()->id());
        @endphp
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden {{ $canApprove ? 'ring-2 ring-purple-400' : '' }}">
            {{-- Header --}}
            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center">
                        <i class="fas fa-rocket text-purple-600"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900">{{ $masterItem->name ?? 'Unknown Item' }}</h4>
                        <p class="text-xs text-gray-600">
                            {{ $request->request_number ?? 'N/A' }} • 
                            Step {{ $step->step_number }}: {{ $step->step_name }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if($step->status === 'pending')
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 animate-pulse">
                            <i class="fas fa-bell mr-1"></i>Ready
                        </span>
                    @elseif($step->status === 'pending_purchase')
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                            <i class="fas fa-shopping-cart mr-1"></i>Waiting Purchase
                        </span>
                    @endif
                </div>
            </div>

            <div class="p-4">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    {{-- Step Info --}}
                    <div class="space-y-2">
                        <h5 class="text-xs font-semibold text-gray-700 uppercase">Step Info</h5>
                        <div class="text-xs space-y-1">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Step Number:</span>
                                <span class="font-medium">{{ $step->step_number }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Step Name:</span>
                                <span class="font-medium">{{ $step->step_name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Phase:</span>
                                <span class="font-medium text-purple-600">Release</span>
                            </div>
                        </div>
                    </div>

                    {{-- Item Details --}}
                    <div class="space-y-2">
                        <h5 class="text-xs font-semibold text-gray-700 uppercase">Item Details</h5>
                        <div class="text-xs space-y-1">
                            @if($item)
                            <div class="flex justify-between">
                                <span class="text-gray-600">Quantity:</span>
                                <span class="font-medium">{{ $item->quantity }} {{ $masterItem->unit->name ?? '' }}</span>
                            </div>
                            @endif
                            <div class="flex justify-between">
                                <span class="text-gray-600">Type:</span>
                                <span class="font-medium">{{ $masterItem->itemType->name ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Category:</span>
                                <span class="font-medium">{{ $masterItem->itemCategory->name ?? '-' }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Approver Info --}}
                    <div class="space-y-2">
                        <h5 class="text-xs font-semibold text-gray-700 uppercase">Approver</h5>
                        <div class="text-xs space-y-1">
                            @if($step->approver)
                            <div class="flex justify-between">
                                <span class="text-gray-600">Specific User:</span>
                                <span class="font-medium">{{ $step->approver->name }}</span>
                            </div>
                            @endif
                            @if($step->approverRole)
                            <div class="flex justify-between">
                                <span class="text-gray-600">Role:</span>
                                <span class="font-medium">{{ $step->approverRole->name }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                @if($canApprove && $request && $item)
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="text-xs text-gray-600">
                            <i class="fas fa-info-circle mr-1"></i>
                            Release step ini untuk melanjutkan ke tahap berikutnya
                        </div>
                        <div class="flex gap-2">
                            <a href="{{ route('approval-requests.show', ['approvalRequest' => $request->id, 'item_id' => $item->id]) }}"
                                class="bg-purple-600 hover:bg-purple-700 text-white text-xs font-medium py-2 px-4 rounded-md transition-colors">
                                <i class="fas fa-paper-plane mr-1"></i>Proses Release
                            </a>
                        </div>
                    </div>
                </div>
                @elseif($step->status === 'pending_purchase')
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <div class="text-xs text-yellow-700 bg-yellow-50 rounded p-2">
                        <i class="fas fa-hourglass-half mr-1"></i>
                        Menunggu proses purchasing selesai sebelum dapat di-release
                    </div>
                </div>
                @endif
            </div>
        </div>
        @empty
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
            <i class="fas fa-check-circle text-4xl text-green-300 mb-3"></i>
            <p class="text-gray-500">Tidak ada item yang menunggu release dari Anda</p>
            <a href="{{ route('release-requests.index') }}" class="mt-3 inline-block text-blue-600 hover:text-blue-800 text-sm">
                Lihat semua release items
            </a>
        </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($pendingReleaseSteps->hasPages())
    <div class="mt-6">
        {{ $pendingReleaseSteps->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
