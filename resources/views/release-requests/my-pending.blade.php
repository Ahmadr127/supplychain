@extends('layouts.app')

@section('title', 'My Pending Releases')

@section('content')
<div x-data="{
    ...tableFilter({
        search: '{{ request('search') }}',
    })
}">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Pending Release Saya</h2>
            <p class="text-gray-500 text-sm mt-1">Item yang perlu saya proses di tahap release</p>
        </div>
    </div>

    {{-- Status Counts --}}
    @if(isset($statusCounts))
    <div class="flex flex-wrap gap-2 mb-4">
        <x-approval-status-badge status="pending" :count="$statusCounts['pending']" variant="solid" />
        <x-approval-status-badge status="approved" :count="$statusCounts['approved']" variant="solid" />
        <x-approval-status-badge status="rejected" :count="$statusCounts['rejected']" variant="solid" />
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
                        placeholder="Request number, nama item...">
                </div>
            </div>
            <button type="submit" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors">
                <i class="fas fa-filter mr-1"></i>Filter
            </button>
            @if(request('search'))
            <a href="{{ route('release-requests.my-pending') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm rounded-lg transition-colors">
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
                        <th class="px-4 py-3 text-center border-b border-gray-200 w-10">No.</th>
                        <th class="px-4 py-3 text-left border-b border-gray-200">No Request</th>
                        <th class="px-4 py-3 text-left border-b border-gray-200">Item</th>
                        <th class="px-4 py-3 text-center border-b border-gray-200">Qty</th>
                        <th class="px-4 py-3 text-left border-b border-gray-200">Tipe</th>
                        <th class="px-4 py-3 text-left border-b border-gray-200">Jenis Pengadaan</th>
                        <th class="px-4 py-3 text-left border-b border-gray-200">User Approver</th>
                        <th class="px-4 py-3 text-center border-b border-gray-200">Status</th>
                        <th class="px-4 py-3 text-center border-b border-gray-200">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($pendingReleaseSteps as $step)
                    @php
                        $masterItem  = $step->masterItem;
                        $request     = $step->approvalRequest;
                        $item        = $request ? $request->items->where('master_item_id', $step->master_item_id)->first() : null;
                        $canApprove  = $step->status === 'pending' && $step->canApprove(auth()->id());
                    @endphp
                    <tr class="hover:bg-gray-50 {{ $canApprove ? 'bg-purple-50' : '' }}">
                        {{-- No --}}
                        <td class="px-4 py-3 text-center text-xs text-gray-500">{{ $loop->iteration }}</td>

                        {{-- No Request --}}
                        <td class="px-4 py-3 font-mono text-xs text-gray-600 whitespace-nowrap">
                            {{ $request->request_number ?? '-' }}
                        </td>

                        {{-- Item --}}
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-800 truncate max-w-[200px]" title="{{ $masterItem->name ?? '' }}">
                                {{ $masterItem->name ?? 'Unknown Item' }}
                            </p>
                        </td>

                        {{-- Qty --}}
                        <td class="px-4 py-3 text-center text-sm text-gray-700">
                            @if($item)
                                {{ $item->quantity }} {{ $masterItem->unit->name ?? '' }}
                            @else
                                —
                            @endif
                        </td>

                        {{-- Tipe --}}
                        <td class="px-4 py-3 text-xs text-gray-600">
                            {{ $masterItem->itemType->name ?? '-' }}
                        </td>

                        {{-- Jenis Pengadaan --}}
                        <td class="px-4 py-3 text-xs text-gray-600">
                            {{ $request->procurementType->name ?? '-' }}
                        </td>

                        {{-- User Approver --}}
                        <td class="px-4 py-3 text-xs text-gray-700">
                            {{ $step->approver->name ?? '—' }}
                        </td>

                        {{-- Status --}}
                        <td class="px-4 py-3 text-center">
                            @if($step->status === 'pending')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 animate-pulse">
                                    <i class="fas fa-bell mr-1"></i>Ready
                                </span>
                            @elseif($step->status === 'pending_purchase')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                    <i class="fas fa-shopping-cart mr-1"></i>Waiting Purchase
                                </span>
                            @else
                                <span class="text-xs text-gray-500">{{ ucfirst($step->status) }}</span>
                            @endif
                        </td>

                        {{-- Aksi --}}
                        <td class="px-4 py-3 text-center">
                            @if($canApprove && $request && $item)
                                <a href="{{ route('approval-requests.show', ['approvalRequest' => $request->id, 'item_id' => $item->id]) }}"
                                   class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg text-white bg-purple-600 hover:bg-purple-700 transition-colors">
                                    <i class="fas fa-paper-plane mr-1"></i>Proses
                                </a>
                            @elseif($step->status === 'pending_purchase')
                                <span class="text-xs text-gray-400 italic">Menunggu Purchasing</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-16 text-center">
                            <i class="fas fa-check-circle text-5xl text-gray-200 mb-4"></i>
                            <p class="text-gray-500">Tidak ada item pending release untuk Anda.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($pendingReleaseSteps, 'hasPages') && $pendingReleaseSteps->hasPages())
        <div class="px-4 py-3 border-t border-gray-100">
            {{ $pendingReleaseSteps->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
