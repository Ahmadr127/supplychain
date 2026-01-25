{{-- Release Step Card Component --}}
{{-- Used in release phase to show purchasing info and release approval --}}

@props([
    'step',
    'item',
    'purchasingItem' => null,
    'approvalRequest',
    'canApprove' => false,
])

@php
    // Get purchasing item if not provided
    if (!$purchasingItem) {
        $purchasingItem = \App\Models\PurchasingItem::where('approval_request_id', $approvalRequest->id)
            ->where('master_item_id', $item->master_item_id)
            ->first();
    }
    
    $vendor = $purchasingItem?->preferredVendor;
    $isCurrentStep = $step->status === 'pending';
    $isPendingPurchase = $step->status === 'pending_purchase';
@endphp

<div class="release-step-card bg-purple-50 border border-purple-200 rounded-lg p-3 {{ $isCurrentStep ? 'ring-2 ring-purple-400' : '' }}">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold
                {{ $step->status === 'approved' ? 'bg-green-500 text-white' : '' }}
                {{ $step->status === 'pending' ? 'bg-purple-500 text-white' : '' }}
                {{ $step->status === 'pending_purchase' ? 'bg-gray-400 text-white' : '' }}
                {{ $step->status === 'rejected' ? 'bg-red-500 text-white' : '' }}
            ">
                @if($step->status === 'approved')
                    <i class="fas fa-check"></i>
                @elseif($step->status === 'rejected')
                    <i class="fas fa-times"></i>
                @elseif($step->status === 'pending_purchase')
                    <i class="fas fa-pause"></i>
                @else
                    {{ $step->step_number }}
                @endif
            </span>
            <div>
                <h4 class="text-sm font-semibold text-gray-900">{{ $step->step_name }}</h4>
                @if($step->scope_process)
                    <p class="text-[10px] text-gray-600">{{ $step->scope_process }}</p>
                @endif
            </div>
        </div>
        
        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium
            {{ $step->status === 'approved' ? 'bg-green-100 text-green-800' : '' }}
            {{ $step->status === 'pending' ? 'bg-purple-100 text-purple-800' : '' }}
            {{ $step->status === 'pending_purchase' ? 'bg-gray-100 text-gray-600' : '' }}
            {{ $step->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}
        ">
            @if($step->status === 'pending_purchase')
                <i class="fas fa-clock mr-1"></i>Waiting Purchasing
            @else
                {{ ucfirst(str_replace('_', ' ', $step->status)) }}
            @endif
        </span>
    </div>

    {{-- Purchasing Info Summary --}}
    @if($purchasingItem)
    <div class="bg-white rounded-md p-2 mb-3 border border-purple-100">
        <h5 class="text-xs font-semibold text-gray-700 mb-2">
            <i class="fas fa-shopping-cart mr-1 text-purple-500"></i>Informasi Purchasing
        </h5>
        <div class="grid grid-cols-2 gap-2 text-xs">
            @if($vendor)
            <div>
                <span class="text-gray-600">Vendor:</span>
                <span class="font-medium text-gray-900 ml-1">{{ $vendor->name }}</span>
            </div>
            @endif
            
            @if($purchasingItem->preferred_total_price)
            <div>
                <span class="text-gray-600">Total:</span>
                <span class="font-semibold text-green-700 ml-1">
                    Rp {{ number_format($purchasingItem->preferred_total_price, 0, ',', '.') }}
                </span>
            </div>
            @endif
            
            @if($purchasingItem->po_number)
            <div>
                <span class="text-gray-600">No. PO:</span>
                <span class="font-medium text-gray-900 ml-1">{{ $purchasingItem->po_number }}</span>
            </div>
            @endif
            
            <div>
                <span class="text-gray-600">Status:</span>
                <span class="font-medium text-indigo-700 ml-1">{{ ucfirst($purchasingItem->status) }}</span>
            </div>
        </div>
    </div>
    @endif

    {{-- Approved Info --}}
    @if($step->status === 'approved' && $step->approver)
    <div class="text-xs text-gray-600 mb-2">
        <i class="fas fa-user-check text-green-500 mr-1"></i>
        Approved by <span class="font-medium">{{ $step->approver->name }}</span>
        @if($step->approved_at)
            <span class="text-gray-400">• {{ $step->approved_at->format('d/m/Y H:i') }}</span>
        @endif
    </div>
    @if($step->comments)
    <div class="text-xs text-gray-700 bg-gray-50 rounded p-2">
        <i class="fas fa-comment mr-1 text-gray-400"></i>{{ $step->comments }}
    </div>
    @endif
    @endif

    {{-- Rejected Info --}}
    @if($step->status === 'rejected')
    <div class="text-xs text-red-600 mb-2">
        <i class="fas fa-user-times mr-1"></i>
        Rejected by <span class="font-medium">{{ $step->approver->name ?? 'Unknown' }}</span>
    </div>
    @if($step->rejected_reason)
    <div class="text-xs text-red-700 bg-red-50 rounded p-2">
        <strong>Alasan:</strong> {{ $step->rejected_reason }}
    </div>
    @endif
    @endif

    {{-- Pending Purchase Info --}}
    @if($isPendingPurchase)
    <div class="text-xs text-gray-500 italic">
        <i class="fas fa-info-circle mr-1"></i>
        Step ini akan aktif setelah vendor dipilih oleh Purchasing.
    </div>
    @endif

    {{-- Approval Form (for current pending step) --}}
    @if($isCurrentStep && $canApprove)
    <div class="mt-3 pt-3 border-t border-purple-200">
        <form action="{{ route('approval-items.approve', ['approvalRequest' => $approvalRequest->id, 'item' => $item->id]) }}" method="POST">
            @csrf
            
            <div class="mb-3">
                <label class="block text-xs font-medium text-gray-700 mb-1">Komentar (Opsional)</label>
                <textarea 
                    name="comments" 
                    rows="2" 
                    class="w-full px-2 py-1 text-xs border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                    placeholder="Tambahkan catatan release..."
                ></textarea>
            </div>
            
            <div class="flex gap-2">
                <button type="submit" 
                    class="flex-1 bg-purple-600 hover:bg-purple-700 text-white text-xs font-medium py-2 px-3 rounded-md transition-colors">
                    <i class="fas fa-paper-plane mr-1"></i>Release
                </button>
                <button type="button" 
                    onclick="document.getElementById('reject-modal-{{ $step->id }}').classList.remove('hidden')"
                    class="flex-1 bg-red-100 hover:bg-red-200 text-red-700 text-xs font-medium py-2 px-3 rounded-md transition-colors">
                    <i class="fas fa-times mr-1"></i>Reject
                </button>
            </div>
        </form>
    </div>

    {{-- Reject Modal --}}
    <div id="reject-modal-{{ $step->id }}" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-4 max-w-md w-full mx-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">Reject Release</h3>
            <form action="{{ route('approval-items.reject', ['approvalRequest' => $approvalRequest->id, 'item' => $item->id]) }}" method="POST">
                @csrf
                
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Alasan Reject *</label>
                    <textarea 
                        name="rejected_reason" 
                        required
                        rows="3" 
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-red-500 focus:border-red-500"
                        placeholder="Jelaskan alasan reject..."
                    ></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Komentar Tambahan</label>
                    <textarea 
                        name="comments" 
                        rows="2" 
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-gray-500 focus:border-gray-500"
                        placeholder="Catatan tambahan..."
                    ></textarea>
                </div>
                
                <div class="flex gap-2">
                    <button type="button" 
                        onclick="document.getElementById('reject-modal-{{ $step->id }}').classList.add('hidden')"
                        class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm font-medium py-2 px-4 rounded-md">
                        Batal
                    </button>
                    <button type="submit" 
                        class="flex-1 bg-red-600 hover:bg-red-700 text-white text-sm font-medium py-2 px-4 rounded-md">
                        Reject Release
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
