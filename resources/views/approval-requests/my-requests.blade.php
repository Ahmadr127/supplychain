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
                       placeholder="Cari nomor request atau judul..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
            </div>
            <div class="w-32">
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    <option value="">Semua Status</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
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
                            <div class="text-sm text-gray-900 truncate">{{ $request->title }}</div>
                        </div>
                    </td>
                    <td class="w-1/2 px-2 py-1">
                        <div class="min-w-0">
                            <div class="flex flex-nowrap gap-1 overflow-x-auto">
                                @foreach($request->workflow->steps as $step)
                                    @php
                                        $stepStatus = 'pending';
                                        $stepColor = 'bg-gray-100 text-gray-600';
                                        
                                        if ($request->status == 'approved') {
                                            // If request is fully approved, all steps should be green
                                            $stepStatus = 'completed';
                                            $stepColor = 'bg-green-600 text-white';
                                        } elseif ($request->status == 'rejected') {
                                            // If request is rejected, steps at or after current step should be red
                                            if ($step->step_number >= $request->current_step) {
                                                $stepColor = 'bg-red-600 text-white';
                                            } else {
                                                $stepColor = 'bg-green-600 text-white';
                                            }
                                        } else {
                                            // For pending requests
                                            if ($step->step_number < $request->current_step) {
                                                $stepStatus = 'completed';
                                                $stepColor = 'bg-green-600 text-white';
                                            } elseif ($step->step_number == $request->current_step) {
                                                $stepStatus = 'current';
                                                $stepColor = 'bg-blue-600 text-white';
                                            }
                                        }
                                    @endphp
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium whitespace-nowrap flex-shrink-0 {{ $stepColor }}">
                                        {{ $step->step_name }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </td>
                    <td class="w-20 px-2 py-1">
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium
                            {{ $request->status == 'pending' ? 'bg-yellow-500 text-white' : 
                               ($request->status == 'approved' ? 'bg-green-600 text-white' : 
                               ($request->status == 'rejected' ? 'bg-red-600 text-white' : 'bg-gray-500 text-white')) }}">
                            {{ ucfirst($request->status) }}
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
</script>
@endsection
