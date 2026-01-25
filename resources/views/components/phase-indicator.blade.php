{{-- Phase Indicator Component --}}
{{-- Shows the current phase in 3-phase workflow: Approval → Purchasing → Release --}}

@props(['item', 'purchasingItem' => null])

@php
    // Determine current phase based on item status
    $status = $item->status ?? 'pending';
    
    // Phase states: 'done', 'current', 'pending', 'disabled'
    $approvalPhase = 'current';
    $purchasingPhase = 'pending';
    $releasePhase = 'pending';
    
    // Check if item has release phase steps
    $hasReleasePhase = $item->hasReleasePhase();
    
    switch ($status) {
        case 'pending':
        case 'on progress':
            $approvalPhase = 'current';
            $purchasingPhase = 'pending';
            $releasePhase = $hasReleasePhase ? 'pending' : 'disabled';
            break;
            
        case 'in_purchasing':
            $approvalPhase = 'done';
            $purchasingPhase = 'current';
            $releasePhase = $hasReleasePhase ? 'pending' : 'disabled';
            break;
            
        case 'in_release':
            $approvalPhase = 'done';
            $purchasingPhase = 'done';
            $releasePhase = 'current';
            break;
            
        case 'approved':
            $approvalPhase = 'done';
            $purchasingPhase = 'done';
            $releasePhase = $hasReleasePhase ? 'done' : 'disabled';
            break;
            
        case 'rejected':
        case 'cancelled':
            // Keep current phase as failed
            break;
    }
    
    // Get purchasing item info
    if (!$purchasingItem && method_exists($item, 'purchasingItem')) {
        $purchasingItem = $item->purchasingItem();
    }
    
    $purchasingStatus = $purchasingItem->status ?? null;
@endphp

<div class="flex items-center gap-2 text-xs">
    {{-- Approval Phase --}}
    <div class="flex items-center gap-1">
        @if($approvalPhase === 'done')
            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-green-500 text-white">
                <i class="fas fa-check text-[10px]"></i>
            </span>
            <span class="text-green-700 font-medium">Approval</span>
        @elseif($approvalPhase === 'current')
            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-blue-500 text-white animate-pulse">
                <i class="fas fa-clock text-[10px]"></i>
            </span>
            <span class="text-blue-700 font-semibold">Approval</span>
        @else
            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-gray-300 text-gray-500">
                <i class="fas fa-circle text-[8px]"></i>
            </span>
            <span class="text-gray-500">Approval</span>
        @endif
    </div>

    {{-- Arrow 1 --}}
    <i class="fas fa-chevron-right text-gray-400"></i>

    {{-- Purchasing Phase --}}
    <div class="flex items-center gap-1">
        @if($purchasingPhase === 'done')
            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-green-500 text-white">
                <i class="fas fa-check text-[10px]"></i>
            </span>
            <span class="text-green-700 font-medium">Purchasing</span>
        @elseif($purchasingPhase === 'current')
            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-indigo-500 text-white animate-pulse">
                <i class="fas fa-shopping-cart text-[10px]"></i>
            </span>
            <span class="text-indigo-700 font-semibold">Purchasing</span>
            @if($purchasingStatus)
                <span class="text-[10px] text-indigo-600">({{ ucfirst($purchasingStatus) }})</span>
            @endif
        @else
            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-gray-300 text-gray-500">
                <i class="fas fa-circle text-[8px]"></i>
            </span>
            <span class="text-gray-500">Purchasing</span>
        @endif
    </div>

    {{-- Arrow 2 (only if release phase exists) --}}
    @if($releasePhase !== 'disabled')
        <i class="fas fa-chevron-right text-gray-400"></i>

        {{-- Release Phase --}}
        <div class="flex items-center gap-1">
            @if($releasePhase === 'done')
                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-green-500 text-white">
                    <i class="fas fa-check text-[10px]"></i>
                </span>
                <span class="text-green-700 font-medium">Release</span>
            @elseif($releasePhase === 'current')
                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-purple-500 text-white animate-pulse">
                    <i class="fas fa-paper-plane text-[10px]"></i>
                </span>
                <span class="text-purple-700 font-semibold">Release</span>
            @else
                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-gray-300 text-gray-500">
                    <i class="fas fa-circle text-[8px]"></i>
                </span>
                <span class="text-gray-500">Release</span>
            @endif
        </div>
    @endif
</div>
