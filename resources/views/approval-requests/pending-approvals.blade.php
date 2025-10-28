@extends('layouts.app')

@section('title', 'All My Approvals')

@section('content')

<x-responsive-table 
    title="All My Approvals"
    :pagination="$pendingApprovals"
    :emptyState="$pendingApprovals->isEmpty()"
    emptyMessage="Tidak ada approvals yang tersedia"
    emptyIcon="fas fa-check-circle"
    :emptyActionRoute="route('approval-requests.index')"
    emptyActionLabel="Lihat Semua Requests">
    
    <x-slot name="filters">
        <div class="space-y-2">
            <!-- Main Filter Bar with Action Buttons -->
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
                        <option value="">Semua Status</option>
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
            
        </div>
    </x-slot>
    <div class="overflow-x-auto">
        <table class="responsive-table min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="w-16 text-left">No</th>
                    <th class="w-24 text-left">Tanggal</th>
                    <th class="w-1/5 text-left">Request</th>
                    <th class="w-48 text-left">Unit Peruntukan</th>
                    <th class="w-32 text-left">Pengaju</th>
                    <th class="w-1/2 text-left">Progress</th>
                    <th class="w-20 text-left">Status</th>
                    <th class="w-40 text-left">Status Purchasing</th>
                    <th class="w-20 text-left">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($pendingApprovals as $index => $step)
                <tr class="hover:bg-gray-50 transition-colors duration-150">
                    <td class="w-16">{{ $pendingApprovals->firstItem() + $index }}</td>
                    <td class="w-24">
                        <div>{{ $step->request->created_at->format('d/m/Y') }}</div>
                        <div class="text-xs">{{ $step->request->created_at->format('H:i') }}</div>
                    </td>
                    <td class="w-1/5">
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-gray-900 truncate">
                                <span class="inline-block bg-gray-100 text-gray-800 text-xs px-1 py-0.5 rounded mr-1">
                                    {{ $step->request->request_number }}
                                </span>
                            </div>
                            @php
                                $itemNames = collect($step->request->masterItems ?? [])->pluck('name')->filter()->values();
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
                            // Ensure departments map available locally
                            $__deptMap = $departmentsMap ?? \App\Models\Department::pluck('name','id');
                            $deptIds = collect($step->request->masterItems ?? [])->pluck('pivot.allocation_department_id')->filter()->unique()->values();
                            $deptNames = $deptIds->map(fn($id) => $__deptMap[$id] ?? null)->filter()->values();
                        @endphp
                        <span class="text-sm text-gray-900">{{ $deptNames->count() ? $deptNames->implode(', ') : '-' }}</span>
                    </td>
                    <td class="w-32">
                        <div class="text-sm font-medium text-gray-900">{{ $step->request->requester->name }}</div>
                    </td>
                    <td class="w-1/2">
                        <div class="min-w-0">
                            <div class="flex flex-nowrap gap-1 overflow-x-auto">
                                @foreach($step->request->workflow->steps as $wfStep)
                                    @php
                                        $stepStatus = 'pending';
                                        $stepColor = 'bg-gray-100 text-gray-600';
                                        $stepStatusText = 'Pending';
                                        
                                        if ($step->request->status == 'approved') {
                                            // If request is fully approved, all steps should be green
                                            $stepStatus = 'completed';
                                            $stepColor = 'bg-green-600 text-white';
                                            $stepStatusText = 'Approved';
                                        } elseif ($step->request->status == 'rejected') {
                                            // If request is rejected, steps at or after current step should be red
                                            if ($wfStep->step_number >= $step->request->current_step) {
                                                $stepColor = 'bg-red-600 text-white';
                                                $stepStatusText = 'Rejected';
                                            } else {
                                                $stepColor = 'bg-green-600 text-white';
                                                $stepStatusText = 'Approved';
                                            }
                                        } else {
                                            // For on progress and pending requests
                                            if ($wfStep->step_number < $step->request->current_step) {
                                                $stepStatus = 'completed';
                                                $stepColor = 'bg-green-600 text-white';
                                                $stepStatusText = 'Approved';
                                            } elseif ($wfStep->step_number == $step->request->current_step) {
                                                $stepStatus = 'current';
                                                $stepColor = 'bg-blue-600 text-white';
                                                $stepStatusText = 'On Progress';
                                            }
                                        }
                                    @endphp
                                    <div class="flex flex-col flex-shrink-0">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium whitespace-nowrap {{ $stepColor }} cursor-pointer step-badge" 
                                              data-step-name="{{ $wfStep->step_name }}" 
                                              data-step-status="{{ $stepStatusText }}" 
                                              data-step-number="{{ $wfStep->step_number }}" 
                                              data-request-id="{{ $step->request->id }}">
                                            {{ $wfStep->step_name }}
                                        </span>
                                        <div class="mt-0.5 text-[11px] text-gray-600 step-meta" data-request-id="{{ $step->request->id }}" data-step-number="{{ $wfStep->step_number }}">
                                            <!-- info disisipkan via JS: status, oleh, pada -->
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </td>
                    <td class="w-20">
                        @php
                            $stepStatus = $step->status;
                            $requestStatus = $step->request->status;
                            
                            // Determine the display status - prioritize step status, then request status
                            if ($stepStatus === 'approved') {
                                $statusColor = 'bg-green-600 text-white';
                                $statusText = 'Approved';
                            } elseif ($stepStatus === 'rejected') {
                                $statusColor = 'bg-red-600 text-white';
                                $statusText = 'Rejected';
                            } elseif ($stepStatus === 'pending') {
                                // For pending steps, check if it's the current step
                                if ($step->step_number == $step->request->current_step) {
                                    $statusColor = 'bg-blue-500 text-white';
                                    $statusText = 'On Progress';
                                } else {
                                    $statusColor = 'bg-yellow-500 text-white';
                                    $statusText = 'Waiting';
                                }
                            } else {
                                // Fallback to request status for consistency
                                if ($requestStatus === 'approved') {
                                    $statusColor = 'bg-green-600 text-white';
                                    $statusText = 'Approved';
                                } elseif ($requestStatus === 'rejected') {
                                    $statusColor = 'bg-red-600 text-white';
                                    $statusText = 'Rejected';
                                } elseif ($requestStatus === 'on progress') {
                                    $statusColor = 'bg-blue-500 text-white';
                                    $statusText = 'On Progress';
                                } else {
                                    $statusColor = 'bg-yellow-500 text-white';
                                    $statusText = 'Waiting';
                                }
                            }
                        @endphp
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium {{ $statusColor }}">
                            {{ $statusText }}
                        </span>
                    </td>
                    <td class="w-40">
                        @php
                            $ps = $step->request->purchasing_status ?? 'unprocessed';
                            $psText = match($ps){
                                'unprocessed' => 'Belum diproses',
                                'benchmarking' => 'Pemilihan vendor',
                                'selected' => 'Proses PR & PO',
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
                            // Aggregate benchmarking notes per request for modal usage
                            $bmNotesLine = '';
                            if($ps === 'benchmarking'){
                                $bmNotesColl = collect($step->request->purchasingItems ?? [])->filter(fn($pi) => !empty($pi->benchmark_notes));
                                if($bmNotesColl->count()){
                                    $bmNotesLine = $bmNotesColl->map(function($pi){
                                        $name = $pi->masterItem->name ?? 'Item';
                                        $note = trim(preg_replace('/\s+/', ' ', (string)$pi->benchmark_notes));
                                        return $name.': '.$note;
                                    })->implode(' • ');
                                }
                            }
                        @endphp
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium {{ $psColor }} cursor-pointer" 
                              data-bmnotes="{{ e($bmNotesLine) }}"
                              onclick="openPurchasingStatusModal('{{ $ps }}','{{ $psText }}','{{ $step->request->id }}', this)">{{ $psText }}</span>
                    </td>
                    <td class="w-20">
                        <div class="flex space-x-1">
                            <a href="{{ route('approval-requests.show', $step->request) }}" 
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

<script>
function showStepStatus(stepName, status, stepNumber, requestId) {
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
    
    // Fetch step details via AJAX
    fetch(`/api/approval-steps/${requestId}/${stepNumber}`)
        .then(response => response.json())
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

function closeStepStatusModal() {
    const modal = document.getElementById('stepStatusModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

function getStatusColor(status) {
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

async function openPurchasingStatusModal(code, label, requestId, el) {
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
        const bmNotes = (data.status_code === 'benchmarking') ? (el?.getAttribute('data-bmnotes') || '') : '';

        body.innerHTML = `
            <div class="space-y-2 text-sm">
                <div><span class="font-semibold">Request ID:</span> ${requestId}</div>
                <div><span class="font-semibold">Status Purchasing:</span> ${data.status_label || label}</div>
                <div><span class="font-semibold">Waktu aksi:</span> ${changedAt}</div>
                <div><span class="font-semibold">Diubah oleh:</span> ${changedBy}</div>
                ${bmNotes ? `<div class=\"text-gray-800\">${bmNotes}</div>` : ''}
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

// Close purchasing status modal on outside click
document.addEventListener('click', function(e) {
    const modal = document.getElementById('ps-modal');
    if (modal && !modal.classList.contains('hidden')) {
        if (e.target === modal) {
            closePurchasingStatusModal();
        }
    }
});
</script>

<script>
// Populate inline step metadata (status, approved by, time) under each step badge
document.addEventListener('DOMContentLoaded', function() {
    // Add click event listeners to step badges
    const stepBadges = document.querySelectorAll('.step-badge');
    stepBadges.forEach(badge => {
        badge.addEventListener('click', function() {
            const stepName = this.getAttribute('data-step-name');
            const stepStatus = this.getAttribute('data-step-status');
            const stepNumber = this.getAttribute('data-step-number');
            const requestId = this.getAttribute('data-request-id');
            showStepStatus(stepName, stepStatus, stepNumber, requestId);
        });
    });

    // Populate metadata
    const metas = document.querySelectorAll('.step-meta');
    metas.forEach(async el => {
        const requestId = el.getAttribute('data-request-id');
        const stepNumber = el.getAttribute('data-step-number');
        try {
            const res = await fetch(`/api/approval-steps/${requestId}/${stepNumber}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) throw new Error('Failed');
            const data = await res.json();
            const approvedBy = data.approved_by_name || null;
            let approvedAt = data.approved_at ? new Date(data.approved_at) : null;
            // Format: d/m/Y (date only)
            const pad = n => String(n).padStart(2, '0');
            let approvedAtStr = null;
            if (approvedAt) {
                const d = pad(approvedAt.getDate());
                const m = pad(approvedAt.getMonth() + 1);
                const y = approvedAt.getFullYear();
                approvedAtStr = `${d}/${m}/${y}`;
            }
            if (approvedBy && approvedAtStr) {
                el.innerHTML = `<span>${approvedBy} • ${approvedAtStr}</span>`;
            } else if (approvedBy) {
                el.innerHTML = `<span>${approvedBy}</span>`;
            } else if (approvedAtStr) {
                el.innerHTML = `<span>${approvedAtStr}</span>`;
            } else {
                el.innerHTML = '';
            }
        } catch (e) {
            el.innerHTML = '';
        }
    });
});
</script>

@endsection
