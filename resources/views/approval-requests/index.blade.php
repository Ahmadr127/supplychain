@extends('layouts.app')

@section('title', 'Kelola Approval Requests')

@section('content')
<x-responsive-table 
    title="Kelola Approval Requests"
    :pagination="$requests"
    :emptyState="$requests->count() === 0"
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
                    <th class="w-1/4 text-left">Request</th>
                    <th class="w-48 text-left">Unit Peruntukan</th>
                    <th class="w-32 text-left">Pengaju</th>
                    <th class="w-1/3 text-left">Progress</th>
                    <th class="w-20 text-left">Status</th>
                    <th class="w-40 text-left">Status Purchasing</th>
                    <th class="w-20 text-left">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($requests as $index => $request)
                <tr class="hover:bg-gray-50 transition-colors duration-150">
                    <td class="w-16">{{ $requests->firstItem() + $index }}</td>
                    <td class="w-24">
                        <div>{{ $request->created_at->format('d/m/Y') }}</div>
                        <div class="text-xs">{{ $request->created_at->format('H:i') }}</div>
                    </td>
                    <td class="w-1/4">
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-gray-900 truncate">
                                <span class="inline-block bg-gray-100 text-gray-800 text-xs px-1 py-0.5 rounded mr-1">
                                    {{ $request->request_number }}
                                </span>
                            </div>
                            @php
                                $itemNames = collect($request->masterItems ?? [])->pluck('name')->filter()->values();
                            @endphp
                            <div class="text-xs text-gray-900 min-w-0">
                                @if($itemNames->isEmpty())
                                    <span class="text-gray-500">-</span>
                                @else
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($itemNames->take(3) as $nm)
                                            <span class="inline-block bg-gray-100 border border-gray-200 text-gray-800 px-1 py-0.5 rounded">{{ $nm }}</span>
                                        @endforeach
                                        @if($itemNames->count() > 3)
                                            <span class="text-gray-500">+{{ $itemNames->count() - 3 }} lainnya</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="w-48 align-top">
                        @php
                            $deptIds = collect($request->masterItems)->pluck('pivot.allocation_department_id')->filter()->unique()->values();
                            $deptNames = $deptIds->map(fn($id) => $departmentsMap[$id] ?? null)->filter()->values();
                        @endphp
                        <span class="text-sm text-gray-900">{{ $deptNames->count() ? $deptNames->implode(', ') : '-' }}</span>
                    </td>
                    <td class="w-32">
                        <div class="flex items-center min-w-0">
                            <div class="flex-shrink-0 h-5 w-5">
                                <div class="h-5 w-5 rounded-full bg-gray-300 flex items-center justify-center">
                                    <span class="text-gray-600 text-xs font-medium">
                                        {{ substr($request->requester->name, 0, 2) }}
                                    </span>
                                </div>
                            </div>
                            <div class="ml-1 min-w-0 flex-1">
                                <div class="text-sm font-medium text-gray-900 truncate">{{ $request->requester->name }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="w-1/3">
                        <div class="min-w-0">
                            <div class="flex flex-nowrap gap-1 overflow-x-auto">
                                @foreach($request->workflow->steps as $step)
                                    @php
                                        $stepStatus = 'pending';
                                        $stepColor = 'bg-gray-100 text-gray-600';
                                        $stepStatusText = 'Pending';
                                        
                                        if ($request->status == 'approved') {
                                            // If request is fully approved, all steps should be green
                                            $stepStatus = 'completed';
                                            $stepColor = 'bg-green-600 text-white';
                                            $stepStatusText = 'Approved';
                                        } elseif ($request->status == 'rejected') {
                                            // If request is rejected, steps at or after current step should be red
                                            if ($step->step_number >= $request->current_step) {
                                                $stepColor = 'bg-red-600 text-white';
                                                $stepStatusText = 'Rejected';
                                            } else {
                                                $stepColor = 'bg-green-600 text-white';
                                                $stepStatusText = 'Approved';
                                            }
                                        } else {
                                            // For on progress and pending requests
                                            if ($step->step_number < $request->current_step) {
                                                $stepStatus = 'completed';
                                                $stepColor = 'bg-green-600 text-white';
                                                $stepStatusText = 'Approved';
                                            } elseif ($step->step_number == $request->current_step) {
                                                $stepStatus = 'current';
                                                $stepColor = 'bg-blue-600 text-white';
                                                $stepStatusText = 'On Progress';
                                            }
                                        }
                                    @endphp
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium whitespace-nowrap flex-shrink-0 {{ $stepColor }} cursor-pointer hover:opacity-80 transition-opacity" 
                                          onclick="showStepStatus('{{ $step->step_name }}', '{{ $stepStatusText }}', '{{ $step->step_number }}', '{{ $request->id }}')"
                                          title="Klik untuk melihat detail status">
                                        {{ $step->step_name }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </td>
                    <td class="w-20">
                        @php
                            $displayStatus = $request->status;
                            $statusColor = 'bg-gray-500 text-white';
                            
                            if ($request->status == 'on progress') {
                                $statusColor = 'bg-blue-500 text-white';
                                $displayStatus = 'On Progress';
                            } elseif ($request->status == 'pending') {
                                $statusColor = 'bg-yellow-500 text-white';
                                $displayStatus = 'Pending';
                            } elseif ($request->status == 'approved') {
                                $statusColor = 'bg-green-600 text-white';
                                $displayStatus = 'Approved';
                            } elseif ($request->status == 'rejected') {
                                $statusColor = 'bg-red-600 text-white';
                                $displayStatus = 'Rejected';
                            } elseif ($request->status == 'cancelled') {
                                $statusColor = 'bg-gray-500 text-white';
                                $displayStatus = 'Cancelled';
                            }
                        @endphp
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium {{ $statusColor }}">
                            {{ $displayStatus }}
                        </span>
                    </td>
                    <td class="w-40">
                        @php
                            $ps = $request->purchasing_status ?? 'unprocessed';
                            $psText = match($ps){
                                'unprocessed' => 'Belum diproses',
                                'benchmarking' => 'Pemilihan vendor',
                                'selected' => 'Uji coba/Proses PR sistem',
                                'po_issued' => 'Proses di vendor',
                                'grn_received' => 'Barang sudah diterima',
                                'done' => 'Selesai',
                                default => strtoupper($ps),
                            };
                            // Colors per request: benchmarking=red, selected=yellow, po_issued=orange, grn_received=green (white text)
                            $psColor = match($ps){
                                'benchmarking' => 'bg-red-600 text-white',
                                'selected' => 'bg-yellow-400 text-black',
                                'po_issued' => 'bg-orange-500 text-white',
                                'grn_received' => 'bg-green-600 text-white',
                                'unprocessed' => 'bg-gray-200 text-gray-800',
                                'done' => 'bg-green-700 text-white',
                                default => 'bg-gray-200 text-gray-800',
                            };
                        @endphp
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium {{ $psColor }} cursor-pointer" onclick="openPurchasingStatusModal('{{ $ps }}','{{ $psText }}','{{ $request->id }}')">{{ $psText }}</span>
                    </td>
                    <td class="w-20">
                        <div class="flex space-x-1">
                            <a href="{{ route('approval-requests.show', $request) }}" 
                               class="text-blue-600 hover:text-blue-900 transition-colors duration-150" title="Lihat">👁</a>
                            @if($request->status == 'pending' && $request->requester_id == auth()->id())
                                <a href="{{ route('approval-requests.edit', $request) }}" 
                                   class="text-indigo-600 hover:text-indigo-900 transition-colors duration-150" title="Edit">✏️</a>
                            @endif
                            @if($request->requester_id == auth()->id())
                                <button onclick="deleteRequest({{ $request->id }})" 
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

<script>
window.deleteRequest = function(requestId) {
    if (confirm('Apakah Anda yakin ingin menghapus request ini? Tindakan ini tidak dapat dibatalkan.')) {
        const form = document.getElementById('deleteForm');
        form.action = `/approval-requests/${requestId}`;
        form.submit();
    }
}

window.showStepStatus = function(stepName, status, stepNumber, requestId) {
    // Create modal if it doesn't exist
    let modal = document.getElementById('stepStatusModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'stepStatusModal';
        modal.className = 'fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden';
        modal.innerHTML = `
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-lg font-medium text-gray-900">Detail Status Step</h3>
                        <button onclick="closeStepStatusModal()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div id="stepStatusContent">
                        <!-- Content will be populated here -->
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    // Fetch step details via AJAX with robust JSON handling
    fetch(`/api/approval-steps/${requestId}/${stepNumber}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(async (response) => {
            const contentType = response.headers.get('content-type') || '';
            if (!response.ok) {
                const text = await response.text().catch(() => '');
                throw new Error(`Gagal memuat (${response.status}). ${text?.slice(0, 200)}`);
            }
            if (contentType.includes('application/json')) {
                return response.json();
            }
            const text = await response.text().catch(() => '');
            throw new Error('Respon bukan JSON: ' + text?.slice(0, 200));
        })
        .then(data => {
            const content = document.getElementById('stepStatusContent');
            content.innerHTML = `
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nama Step</label>
                        <p class="text-sm text-gray-900">${stepName}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(status)}">
                            ${status}
                        </span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Step Number</label>
                        <p class="text-sm text-gray-900">${stepNumber}</p>
                    </div>
                    ${data.approved_at ? `
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Waktu Aksi</label>
                        <p class="text-sm text-gray-900">${new Date(data.approved_at).toLocaleString('id-ID')}</p>
                    </div>
                    ` : ''}
                    ${data.approved_by_name ? `
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Diapprove Oleh</label>
                        <p class="text-sm text-gray-900">${data.approved_by_name}</p>
                    </div>
                    ` : ''}
                    ${data.comments ? `
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Komentar</label>
                        <p class="text-sm text-gray-900">${data.comments}</p>
                    </div>
                    ` : ''}
                    <div class="pt-3">
                        <button onclick="closeStepStatusModal()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded text-sm">
                            Tutup
                        </button>
                    </div>
                </div>
            `;
        })
        .catch(error => {
            console.error('Error fetching step details:', error);
            const content = document.getElementById('stepStatusContent');
            content.innerHTML = `
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nama Step</label>
                        <p class="text-sm text-gray-900">${stepName}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(status)}">
                            ${status}
                        </span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Step Number</label>
                        <p class="text-sm text-gray-900">${stepNumber}</p>
                    </div>
                    <div class="text-xs text-red-600">${String(error)}</div>
                    <div class="pt-3">
                        <button onclick="closeStepStatusModal()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded text-sm">
                            Tutup
                        </button>
                    </div>
                </div>
            `;
        });
    
    // Show modal
    modal.classList.remove('hidden');
}

window.closeStepStatusModal = function() {
    const modal = document.getElementById('stepStatusModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

window.getStatusColor = function(status) {
    switch(status.toLowerCase()) {
        case 'approved':
            return 'bg-green-100 text-green-800';
        case 'rejected':
            return 'bg-red-100 text-red-800';
        case 'on progress':
            return 'bg-blue-100 text-blue-800';
        case 'pending':
        default:
            return 'bg-yellow-100 text-yellow-800';
    }
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    const modal = document.getElementById('stepStatusModal');
    if (modal && !modal.classList.contains('hidden')) {
        if (e.target === modal) {
            closeStepStatusModal();
        }
    }
});

</script>

@endsection

@push('scripts')
<script>
    async function openPurchasingStatusModal(code, label, requestId) {
        const body = document.getElementById('ps-modal-body');
        body.innerHTML = '<div class="p-3 text-sm text-gray-600">Memuat...</div>';
        document.getElementById('ps-modal').classList.remove('hidden');

        try {
            const url = `{{ url('api/purchasing/status') }}/${requestId}`;
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await res.json();
            const changedAt = data.changed_at ? new Date(data.changed_at).toLocaleString('id-ID') : '-';
            const changedBy = data.changed_by_name || 'Tidak tersedia';
            const notes = (data.status_code === 'done' && data.done_notes) ? data.done_notes : null;

            body.innerHTML = `
                <div class="space-y-2 text-sm">
                    <div><span class="font-semibold">Request ID:</span> ${requestId}</div>
                    <div><span class="font-semibold">Status Purchasing:</span> ${data.status_label || label}</div>
                    <div><span class="font-semibold">Waktu aksi:</span> ${changedAt}</div>
                    <div><span class="font-semibold">Diubah oleh:</span> ${changedBy}</div>
                    ${notes ? `<div><span class=\"font-semibold\">Catatan DONE:</span> ${notes}</div>` : ''}
                    <div class="mt-2 text-gray-600">Status ini adalah status gabungan dari item-item pembelian pada request ini.</div>
                </div>`;
        } catch (e) {
            body.innerHTML = '<div class="p-3 text-sm text-red-600">Gagal memuat detail status.</div>';
        }
    }
    function closePurchasingStatusModal(){
        document.getElementById('ps-modal').classList.add('hidden');
    }
</script>
@endpush

@push('modals')
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
@endpush
