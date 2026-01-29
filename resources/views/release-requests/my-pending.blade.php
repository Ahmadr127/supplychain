@extends('layouts.app')

@section('title', 'My Pending Releases')

@section('content')
<div class="container mx-auto " x-data="{
    ...tableFilter({
        search: '{{ request('search') }}',
    })
}">

    <x-responsive-table :pagination="$pendingReleaseSteps">
        <x-slot name="filters">
            {{-- Status Counts --}}
            {{-- Status Counts --}}
            @if(isset($statusCounts))
            <div class="flex flex-wrap gap-2 mb-4">
                <x-approval-status-badge status="pending" :count="$statusCounts['pending']" variant="solid" />
                <x-approval-status-badge status="approved" :count="$statusCounts['approved']" variant="solid" />
                <x-approval-status-badge status="rejected" :count="$statusCounts['rejected']" variant="solid" />
            </div>
            @endif

            <x-table-filter 
                search-placeholder="Cari request number, item..."
                :show-date-range="false"
            />
        </x-slot>

        @php
            $columns = [
                [
                    'label' => 'Step Info',
                    'render' => function($step) {
                        return '
                            <div class="flex flex-col">
                                <span class="text-sm font-medium text-gray-900">'.e($step->step_name).'</span>
                                <span class="text-xs text-gray-500">Step '.e($step->step_number).'</span>
                                <span class="text-xs text-purple-600 font-medium mt-1">Release Phase</span>
                            </div>
                        ';
                    }
                ],
                [
                    'label' => 'Item Details',
                    'render' => function($step) {
                        $masterItem = $step->masterItem;
                        $request = $step->approvalRequest;
                        $item = $request ? $request->items->where('master_item_id', $step->master_item_id)->first() : null;
                        
                        $html = '<div class="flex flex-col">';
                        $html .= '<span class="text-sm font-medium text-gray-900">'.e($masterItem->name ?? 'Unknown Item').'</span>';
                        $html .= '<span class="text-xs text-gray-500">'.e($request->request_number ?? 'N/A').'</span>';
                        if ($item) {
                            $html .= '<div class="mt-1 text-xs">';
                            $html .= '<span class="font-medium">'.e($item->quantity).' '.e($masterItem->unit->name ?? '').'</span>';
                            $html .= '<span class="text-gray-400 mx-1">•</span>';
                            $html .= '<span>'.e($masterItem->itemType->name ?? '-').'</span>';
                            $html .= '</div>';
                        }
                        $html .= '</div>';
                        return $html;
                    }
                ],
                [
                    'label' => 'Approver',
                    'render' => function($step) {
                        $html = '<div class="text-xs space-y-1">';
                        if ($step->approver) {
                            $html .= '<div><span class="text-gray-500">User:</span> <span class="font-medium">'.e($step->approver->name).'</span></div>';
                        }
                        if ($step->approverRole) {
                            $html .= '<div><span class="text-gray-500">Role:</span> <span class="font-medium">'.e($step->approverRole->name).'</span></div>';
                        }
                        $html .= '</div>';
                        return $html;
                    }
                ],
                [
                    'label' => 'Status',
                    'render' => function($step) {
                        if ($step->status === 'pending') {
                            return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 animate-pulse"><i class="fas fa-bell mr-1"></i>Ready</span>';
                        } elseif ($step->status === 'pending_purchase') {
                            return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800"><i class="fas fa-shopping-cart mr-1"></i>Waiting Purchase</span>';
                        }
                        return '';
                    }
                ],
                [
                    'label' => 'Action',
                    'render' => function($step) {
                        $request = $step->approvalRequest;
                        $item = $request ? $request->items->where('master_item_id', $step->master_item_id)->first() : null;
                        $canApprove = $step->status === 'pending' && $step->canApprove(auth()->id());
                        
                        if ($canApprove && $request && $item) {
                            $url = route('approval-requests.show', ['approvalRequest' => $request->id, 'item_id' => $item->id]);
                            return '<a href="'.$url.'" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 transition-colors"><i class="fas fa-paper-plane mr-1"></i>Proses</a>';
                        } elseif ($step->status === 'pending_purchase') {
                            return '<span class="text-xs text-gray-500 italic">Menunggu Purchasing</span>';
                        }
                        return '';
                    }
                ]
            ];

            $rowClass = function($step) {
                $canApprove = $step->status === 'pending' && $step->canApprove(auth()->id());
                return $canApprove ? 'bg-purple-50' : '';
            };
        @endphp

        <x-data-table 
            :columns="$columns" 
            :data="$pendingReleaseSteps" 
            :actions="false"
            :row-class="$rowClass"
        />
    </x-responsive-table>
</div>
@endsection
