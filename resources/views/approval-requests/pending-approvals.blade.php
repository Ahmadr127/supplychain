@extends('layouts.app')

@section('title', 'My Approvals')

@section('content')
<x-responsive-table 
    title="My Approvals"
    :pagination="$pendingApprovals"
    :emptyState="$pendingApprovals->count() === 0"
    emptyMessage="Tidak ada approvals"
    emptyIcon="fas fa-check-circle"
    :emptyActionRoute="route('approval-requests.index')"
    emptyActionLabel="Lihat Semua Requests">
    
    <x-slot name="filters">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-48">
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Cari nomor request, judul, atau requester..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
            </div>
            <div class="min-w-32">
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    <option value="">Semua Status</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                </select>
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded text-sm">
                Search
            </button>
        </form>
    </x-slot>

    <!-- Action Buttons -->
    <div class="p-6 bg-white border-b border-gray-200">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('approval-requests.my-requests') }}" 
               class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition-colors duration-200">
                My Requests
            </a>
            <a href="{{ route('approval-requests.index') }}" 
               class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded transition-colors duration-200">
                All Requests
            </a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="responsive-table min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="w-16 px-2 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                    <th class="w-24 px-2 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                    <th class="w-1/4 px-2 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Request</th>
                    <th class="w-32 px-2 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Petugas</th>
                    <th class="w-1/3 px-2 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
                    <th class="w-20 px-2 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="w-20 px-2 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($pendingApprovals as $index => $step)
                <tr class="hover:bg-gray-50 transition-colors duration-150">
                    <td class="w-16 px-2 py-1 text-sm text-gray-900">{{ $pendingApprovals->firstItem() + $index }}</td>
                    <td class="w-24 px-2 py-1 text-sm text-gray-500">
                        <div>{{ $step->request->created_at->format('d/m/Y') }}</div>
                        <div class="text-xs">{{ $step->request->created_at->format('H:i') }}</div>
                    </td>
                    <td class="w-1/4 px-2 py-1">
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-gray-900 truncate">
                                <span class="inline-block bg-gray-100 text-gray-800 text-xs px-1 py-0.5 rounded mr-1">
                                    {{ $step->request->request_number }}
                                </span>
                            </div>
                            <div class="text-sm text-gray-900 truncate">{{ $step->request->title }}</div>
                        </div>
                    </td>
                    <td class="w-32 px-2 py-1">
                        <div class="flex items-center min-w-0">
                            <div class="flex-shrink-0 h-5 w-5">
                                <div class="h-5 w-5 rounded-full bg-gray-300 flex items-center justify-center">
                                    <span class="text-gray-600 text-xs font-medium">
                                        {{ substr($step->request->requester->name, 0, 2) }}
                                    </span>
                                </div>
                            </div>
                            <div class="ml-1 min-w-0 flex-1">
                                <div class="text-sm font-medium text-gray-900 truncate">{{ $step->request->requester->name }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="w-1/3 px-2 py-1">
                        <div class="min-w-0">
                            <div class="flex flex-nowrap gap-1 overflow-x-auto">
                                @foreach($step->request->workflow->steps as $workflowStep)
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
                                            if ($workflowStep->step_number >= $step->request->current_step) {
                                                $stepColor = 'bg-red-600 text-white';
                                                $stepStatusText = 'Rejected';
                                            } else {
                                                $stepColor = 'bg-green-600 text-white';
                                                $stepStatusText = 'Approved';
                                            }
                                        } else {
                                            // For on progress and pending requests
                                            if ($workflowStep->step_number < $step->request->current_step) {
                                                $stepStatus = 'completed';
                                                $stepColor = 'bg-green-600 text-white';
                                                $stepStatusText = 'Approved';
                                            } elseif ($workflowStep->step_number == $step->request->current_step) {
                                                $stepStatus = 'current';
                                                $stepColor = 'bg-blue-600 text-white';
                                                $stepStatusText = 'On Progress';
                                            }
                                        }
                                    @endphp
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium whitespace-nowrap flex-shrink-0 {{ $stepColor }} cursor-pointer hover:opacity-80 transition-opacity" 
                                          onclick="showStepStatus('{{ $workflowStep->step_name }}', '{{ $stepStatusText }}', '{{ $workflowStep->step_number }}', '{{ $step->request->id }}')"
                                          title="Klik untuk melihat detail status">
                                        {{ $workflowStep->step_name }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </td>
                    <td class="w-20 px-2 py-1">
                        @php
                            $stepStatus = $step->status;
                            $requestStatus = $step->request->status;
                            
                            // Determine the display status
                            if ($requestStatus === 'approved') {
                                $statusColor = 'bg-green-600 text-white';
                                $statusText = 'Approved';
                            } elseif ($requestStatus === 'rejected') {
                                $statusColor = 'bg-red-600 text-white';
                                $statusText = 'Rejected';
                            } elseif ($stepStatus === 'approved') {
                                $statusColor = 'bg-blue-600 text-white';
                                $statusText = 'Approved';
                            } elseif ($stepStatus === 'rejected') {
                                $statusColor = 'bg-red-600 text-white';
                                $statusText = 'Rejected';
                            } else {
                                $statusColor = 'bg-yellow-500 text-white';
                                $statusText = 'Pending';
                            }
                        @endphp
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium {{ $statusColor }}">
                            {{ $statusText }}
                        </span>
                    </td>
                    <td class="w-20 px-2 py-1 text-sm font-medium">
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
    
    // Populate content
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
