@extends('layouts.app')

@section('title', 'My Requests')

@section('content')
<x-responsive-table 
    title="My Requests"
    :pagination="$requests"
    :emptyState="$requests->count() === 0"
    emptyMessage="Belum ada request"
    emptyIcon="fas fa-file-alt"
    :emptyActionRoute="route('approval-requests.create')"
    emptyActionLabel="Buat Request Pertama">
    
    <x-slot name="filters">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-48">
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Cari nomor request, jenis pengajuan, atau deskripsi..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
            </div>
            <div class="w-32">
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    <option value="">Semua Status</option>
                    <option value="on progress" {{ request('status') === 'on progress' ? 'selected' : '' }}>On Progress</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded text-sm">
                Filter
            </button>
        </form>
    </x-slot>

    <!-- Action Buttons -->
    <div class="p-6 bg-white border-b border-gray-200">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('approval-requests.pending-approvals') }}" 
               class="bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded transition-colors duration-200">
                Pending Approvals
            </a>
            <a href="{{ route('approval-requests.create') }}" 
               class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded transition-colors duration-200">
                Buat Request
            </a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="responsive-table min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="w-16 px-2 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                    <th class="w-24 px-2 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                    <th class="w-1/3 px-2 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Request</th>
                    <th class="w-48 px-2 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Peruntukan</th>
                    <th class="w-1/2 px-2 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
                    <th class="w-20 px-2 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="w-20 px-2 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($requests as $index => $request)
                <tr class="hover:bg-gray-50 transition-colors duration-150">
                    <td class="w-16 px-2 py-1 text-sm text-gray-900">{{ $requests->firstItem() + $index }}</td>
                    <td class="w-24 px-2 py-1 text-sm text-gray-500">
                        <div>{{ $request->created_at->format('d/m/Y') }}</div>
                        <div class="text-xs">{{ $request->created_at->format('H:i') }}</div>
                    </td>
                    <td class="w-1/3 px-2 py-1">
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-gray-900 truncate">
                                <span class="inline-block bg-gray-100 text-gray-800 text-xs px-1 py-0.5 rounded mr-1">
                                    {{ $request->request_number }}
                                </span>
                            </div>
                            <div class="text-sm text-gray-900 truncate">{{ $request->submissionType->name ?? '-' }}</div>
                        </div>
                    </td>
                    <td class="w-48 px-2 py-1 align-top">
                        @php
                            $deptIds = collect($request->masterItems)->pluck('pivot.allocation_department_id')->filter()->unique()->values();
                            $deptNames = $deptIds->map(fn($id) => $departmentsMap[$id] ?? null)->filter()->values();
                        @endphp
                        <span class="text-sm text-gray-900">{{ $deptNames->count() ? $deptNames->implode(', ') : '-' }}</span>
                    </td>
                    <td class="w-1/2 px-2 py-1">
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
                    <td class="w-20 px-2 py-1">
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
                    <td class="w-20 px-2 py-1 text-sm font-medium">
                        <div class="flex space-x-1">
                            <a href="{{ route('approval-requests.show', $request) }}" 
                               class="text-blue-600 hover:text-blue-900 transition-colors duration-150" title="Lihat">👁</a>
                            @if($request->status == 'pending')
                                <a href="{{ route('approval-requests.edit', $request) }}" 
                                   class="text-indigo-600 hover:text-indigo-900 transition-colors duration-150" title="Edit">✏️</a>
                            @endif
                            @if($request->status == 'pending')
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
function deleteRequest(requestId) {
    if (confirm('Apakah Anda yakin ingin menghapus request ini? Tindakan ini tidak dapat dibatalkan.')) {
        const form = document.getElementById('deleteForm');
        form.action = `/approval-requests/${requestId}`;
        form.submit();
    }
}

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
</script>
@endsection
