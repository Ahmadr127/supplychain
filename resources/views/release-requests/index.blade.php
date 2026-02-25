@extends('layouts.app')

@section('title', 'Release Pending')

@section('content')
<div>
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Release Pending</h2>
            <p class="text-gray-500 text-sm mt-1">Daftar item yang menunggu proses release</p>
        </div>
    </div>

    {{-- Status Counts --}}
    @if(isset($statusCounts))
    <div class="flex flex-wrap gap-2 mb-4">
        <x-approval-status-badge status="in_purchasing" :count="$statusCounts['in_purchasing']" variant="solid" />
        <x-approval-status-badge status="in_release" :count="$statusCounts['in_release']" variant="solid" />
        <x-approval-status-badge status="done" :count="$statusCounts['done']" variant="solid" />
    </div>
    @endif

    {{-- Filter --}}
    <div class="bg-white rounded-xl border border-gray-200 p-3 mb-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-gray-700 mb-1">Cari</label>
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input type="text" name="search" value="{{ request('search') }}"
                        class="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                        placeholder="Nama item atau request number...">
                </div>
            </div>
            <div class="w-44">
                <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="">Semua</option>
                    <option value="in_release" {{ request('status') === 'in_release' ? 'selected' : '' }}>Pending Release</option>
                    <option value="in_purchasing" {{ request('status') === 'in_purchasing' ? 'selected' : '' }}>In Purchasing</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors">
                <i class="fas fa-filter mr-1"></i>Filter
            </button>
            @if(request('search') || request('status'))
            <a href="{{ route('release-requests.index') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm rounded-lg transition-colors">
                <i class="fas fa-times mr-1"></i>Reset
            </a>
            @endif
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
                    <tr>
                        <th class="px-4 py-3 text-left border-b border-gray-200">No Request</th>
                        <th class="px-4 py-3 text-left border-b border-gray-200">Item</th>
                        <th class="px-4 py-3 text-left border-b border-gray-200">Pemohon</th>
                        <th class="px-4 py-3 text-center border-b border-gray-200">Qty</th>
                        <th class="px-4 py-3 text-left border-b border-gray-200">Tipe</th>
                        <th class="px-4 py-3 text-left border-b border-gray-200">Vendor</th>
                        <th class="px-4 py-3 text-right border-b border-gray-200">Total Harga</th>
                        <th class="px-4 py-3 text-left border-b border-gray-200">No PO</th>
                        <th class="px-4 py-3 text-center border-b border-gray-200">Release Steps</th>
                        <th class="px-4 py-3 text-center border-b border-gray-200">Status</th>
                        <th class="px-4 py-3 text-center border-b border-gray-200">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($releaseItems as $item)
                    @php
                        $masterItem = $item->masterItem;
                        $req = $item->approvalRequest;
                        $purchasingItem = \App\Models\PurchasingItem::where('approval_request_id', $req->id)
                            ->where('master_item_id', $item->master_item_id)
                            ->with(['preferredVendor'])
                            ->first();

                        $releaseSteps = $item->getReleasePhaseSteps();
                        $currentReleaseStep = $releaseSteps
                            ->filter(fn($s) => in_array($s->status, ['pending', 'pending_purchase']))
                            ->sortBy('step_number')
                            ->first();
                        $canRelease = $currentReleaseStep && $currentReleaseStep->status === 'pending' &&
                            $currentReleaseStep->canApprove(auth()->id());
                    @endphp
                    <tr class="hover:bg-gray-50 {{ $canRelease ? 'bg-purple-50' : '' }}">
                        {{-- No Request --}}
                        <td class="px-4 py-3 font-mono text-xs text-gray-600 whitespace-nowrap">
                            {{ $req->request_number ?? '-' }}
                        </td>

                        {{-- Item --}}
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-800 truncate max-w-[200px]" title="{{ $masterItem->name }}">
                                {{ $masterItem->name }}
                            </p>
                        </td>

                        {{-- Pemohon --}}
                        <td class="px-4 py-3 text-xs text-gray-600 whitespace-nowrap">
                            {{ $req->requester->name ?? '-' }}
                        </td>

                        {{-- Qty --}}
                        <td class="px-4 py-3 text-center text-sm text-gray-700">
                            {{ $item->quantity }} {{ $masterItem->unit->name ?? '' }}
                        </td>

                        {{-- Tipe --}}
                        <td class="px-4 py-3 text-xs text-gray-600">
                            {{ $masterItem->itemType->name ?? '-' }}
                        </td>

                        {{-- Vendor --}}
                        <td class="px-4 py-3 text-xs">
                            @if($purchasingItem?->preferredVendor)
                                <span class="text-blue-600 font-medium">{{ $purchasingItem->preferredVendor->name }}</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>

                        {{-- Total Harga --}}
                        <td class="px-4 py-3 text-right text-xs">
                            @if($purchasingItem?->preferred_total_price)
                                <span class="text-green-600 font-semibold">Rp {{ number_format($purchasingItem->preferred_total_price, 0, ',', '.') }}</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>

                        {{-- No PO --}}
                        <td class="px-4 py-3 text-xs text-gray-600">
                            {{ $purchasingItem?->po_number ?? '—' }}
                        </td>

                        {{-- Release Steps --}}
                        <td class="px-4 py-3">
                            <div class="flex flex-col gap-1 items-start">
                                @foreach($releaseSteps as $step)
                                <div class="flex items-center gap-1.5 text-xs whitespace-nowrap">
                                    @if($step->status === 'approved')
                                        <span class="w-3.5 h-3.5 rounded-full bg-green-500 flex items-center justify-center flex-shrink-0">
                                            <i class="fas fa-check text-white text-[7px]"></i>
                                        </span>
                                        <span class="text-green-700">{{ $step->step_name }}</span>
                                    @elseif($step->status === 'pending')
                                        <span class="w-3.5 h-3.5 rounded-full bg-purple-500 animate-pulse flex-shrink-0"></span>
                                        <span class="text-purple-700 font-medium">{{ $step->step_name }}</span>
                                    @elseif($step->status === 'pending_purchase')
                                        <span class="w-3.5 h-3.5 rounded-full bg-gray-300 flex-shrink-0"></span>
                                        <span class="text-gray-500">{{ $step->step_name }}</span>
                                    @elseif($step->status === 'rejected')
                                        <span class="w-3.5 h-3.5 rounded-full bg-red-500 flex items-center justify-center flex-shrink-0">
                                            <i class="fas fa-times text-white text-[7px]"></i>
                                        </span>
                                        <span class="text-red-700">{{ $step->step_name }}</span>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                        </td>

                        {{-- Status --}}
                        <td class="px-4 py-3 text-center">
                            <x-approval-status-badge :status="$item->status" />
                            @if($canRelease)
                                <div class="mt-1">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-purple-100 text-purple-800 animate-pulse">
                                        <i class="fas fa-bell mr-0.5"></i>Giliran Anda
                                    </span>
                                </div>
                            @endif
                        </td>

                        {{-- Aksi --}}
                        <td class="px-4 py-3 text-center">
                            @php
                                $url = route('approval-requests.show', ['approvalRequest' => $req->id, 'item_id' => $item->id]);
                            @endphp
                            @if($canRelease)
                                <a href="{{ $url }}" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg text-white bg-purple-600 hover:bg-purple-700 transition-colors">
                                    <i class="fas fa-paper-plane mr-1"></i>Release
                                </a>
                            @else
                                <a href="{{ $url }}" class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg text-blue-600 bg-blue-50 hover:bg-blue-100 transition-colors">
                                    <i class="fas fa-eye mr-1"></i>Detail
                                </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="px-4 py-16 text-center">
                            <i class="fas fa-paper-plane text-5xl text-gray-200 mb-4"></i>
                            <p class="text-gray-500">Tidak ada item release pending.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($releaseItems->hasPages())
        <div class="px-4 py-3 border-t border-gray-100">
            {{ $releaseItems->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
