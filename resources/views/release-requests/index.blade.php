@extends('layouts.app')

@section('title', 'Release Pending')

@section('content')
<div class="container mx-auto">
    <x-responsive-table :pagination="$releaseItems">
        <x-slot name="filters">
            {{-- Status Counts --}}
            @if(isset($statusCounts))
            <div class="flex flex-wrap gap-2 mb-4">
                <x-approval-status-badge status="in_purchasing" :count="$statusCounts['in_purchasing']" variant="solid" />
                <x-approval-status-badge status="in_release" :count="$statusCounts['in_release']" variant="solid" />
                <x-approval-status-badge status="done" :count="$statusCounts['done']" variant="solid" />
            </div>
            @endif

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
        </x-slot>

        @php
            $columns = [
                [
                    'label' => 'Item Details',
                    'render' => function($item) {
                        $masterItem = $item->masterItem;
                        $request = $item->approvalRequest;
                        return '
                            <div class="flex flex-col">
                                <span class="text-sm font-medium text-gray-900">'.e($masterItem->name).'</span>
                                <span class="text-xs text-gray-500">'.e($request->request_number).'</span>
                                <span class="text-xs text-gray-500">'.e($request->requester->name ?? 'Unknown').'</span>
                                <div class="mt-1 text-xs">
                                    <span class="font-medium">'.e($item->quantity).' '.e($masterItem->unit->name ?? '').'</span>
                                    <span class="text-gray-400 mx-1">•</span>
                                    <span>'.e($masterItem->itemType->name ?? '-').'</span>
                                </div>
                            </div>
                        ';
                    }
                ],
                [
                    'label' => 'Purchasing Info',
                    'render' => function($item) {
                        $request = $item->approvalRequest;
                        $purchasingItem = \App\Models\PurchasingItem::where('approval_request_id', $request->id)
                            ->where('master_item_id', $item->master_item_id)
                            ->first();
                        
                        if (!$purchasingItem) {
                            return '<span class="text-xs text-gray-500 italic">No purchasing data</span>';
                        }

                        $vendor = $purchasingItem->preferredVendor;
                        $html = '<div class="text-xs space-y-1">';
                        if ($vendor) {
                            $html .= '<div class="font-medium text-blue-600">'.e($vendor->name).'</div>';
                        }
                        if ($purchasingItem->preferred_total_price) {
                            $html .= '<div class="text-green-600 font-semibold">Rp '.number_format($purchasingItem->preferred_total_price, 0, ',', '.').'</div>';
                        }
                        if ($purchasingItem->po_number) {
                            $html .= '<div class="text-gray-600">PO: '.e($purchasingItem->po_number).'</div>';
                        }
                        $html .= '<div class="text-indigo-600 capitalize">'.ucfirst($purchasingItem->status).'</div>';
                        $html .= '</div>';
                        return $html;
                    }
                ],
                [
                    'label' => 'Release Steps',
                    'render' => function($item) {
                        $steps = $item->getReleasePhaseSteps();
                        $html = '<div class="space-y-1">';
                        foreach ($steps as $step) {
                            $html .= '<div class="flex items-center gap-2 text-xs">';
                            if ($step->status === 'approved') {
                                $html .= '<span class="w-4 h-4 rounded-full bg-green-500 flex items-center justify-center flex-shrink-0"><i class="fas fa-check text-white text-[8px]"></i></span>';
                                $html .= '<span class="text-green-700">'.e($step->step_name).'</span>';
                            } elseif ($step->status === 'pending') {
                                $html .= '<span class="w-4 h-4 rounded-full bg-purple-500 animate-pulse flex-shrink-0"></span>';
                                $html .= '<span class="text-purple-700 font-medium">'.e($step->step_name).'</span>';
                            } elseif ($step->status === 'pending_purchase') {
                                $html .= '<span class="w-4 h-4 rounded-full bg-gray-300 flex-shrink-0"></span>';
                                $html .= '<span class="text-gray-500">'.e($step->step_name).'</span>';
                            } elseif ($step->status === 'rejected') {
                                $html .= '<span class="w-4 h-4 rounded-full bg-red-500 flex items-center justify-center flex-shrink-0"><i class="fas fa-times text-white text-[8px]"></i></span>';
                                $html .= '<span class="text-red-700">'.e($step->step_name).'</span>';
                            }
                            $html .= '</div>';
                        }
                        $html .= '</div>';
                        return $html;
                    }
                ],
                [
                    'label' => 'Status',
                    'render' => function($item) {
                        $html = view('components.approval-status-badge', ['status' => $item->status])->render();
                        
                        $currentReleaseStep = $item->getReleasePhaseSteps()
                            ->filter(fn($s) => in_array($s->status, ['pending', 'pending_purchase']))
                            ->sortBy('step_number')
                            ->first();
                        $canRelease = $currentReleaseStep && $currentReleaseStep->status === 'pending' && 
                            $currentReleaseStep->canApprove(auth()->id());

                        if ($canRelease) {
                            $html .= '<div class="mt-2"><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 animate-pulse"><i class="fas fa-bell mr-1"></i>Your Turn</span></div>';
                        }
                        return $html;
                    }
                ],
                [
                    'label' => 'Action',
                    'render' => function($item) {
                        $request = $item->approvalRequest;
                        $currentReleaseStep = $item->getReleasePhaseSteps()
                            ->filter(fn($s) => in_array($s->status, ['pending', 'pending_purchase']))
                            ->sortBy('step_number')
                            ->first();
                        $canRelease = $currentReleaseStep && $currentReleaseStep->status === 'pending' && 
                            $currentReleaseStep->canApprove(auth()->id());
                        
                        $url = route('approval-requests.show', ['approvalRequest' => $request->id, 'item_id' => $item->id]);
                        
                        if ($canRelease) {
                            return '<a href="'.$url.'" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 transition-colors"><i class="fas fa-paper-plane mr-1"></i>Release</a>';
                        } else {
                            return '<a href="'.$url.'" class="text-blue-600 hover:text-blue-900 text-xs"><i class="fas fa-eye mr-1"></i>Detail</a>';
                        }
                    }
                ]
            ];

            $rowClass = function($item) {
                $currentReleaseStep = $item->getReleasePhaseSteps()
                    ->filter(fn($s) => in_array($s->status, ['pending', 'pending_purchase']))
                    ->sortBy('step_number')
                    ->first();
                $canRelease = $currentReleaseStep && $currentReleaseStep->status === 'pending' && 
                    $currentReleaseStep->canApprove(auth()->id());
                return $canRelease ? 'bg-purple-50' : '';
            };
        @endphp

        <x-data-table 
            :columns="$columns" 
            :data="$releaseItems" 
            :actions="false"
            :row-class="$rowClass"
        />
    </x-responsive-table>
</div>
@endsection
