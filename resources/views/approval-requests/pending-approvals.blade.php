@extends('layouts.app')

@section('title', 'All My Approvals')

@section('content')
<!-- Debug info -->
<!-- @if(config('app.debug'))
<div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
    <strong>Debug Info:</strong><br>
    Pending Approvals Count: {{ $pendingApprovals->count() }}<br>
    Is Empty: {{ $pendingApprovals->isEmpty() ? 'Yes' : 'No' }}<br>
    Total: {{ $pendingApprovals->total() }}<br>
    Current Page: {{ $pendingApprovals->currentPage() }}<br>
    Per Page: {{ $pendingApprovals->perPage() }}
</div>
@endif -->

<x-responsive-table 
    title="All My Approvals"
    :pagination="$pendingApprovals"
    :emptyState="$pendingApprovals->isEmpty()"
    emptyMessage="Tidak ada approvals yang tersedia"
    emptyIcon="fas fa-check-circle"
    :emptyActionRoute="route('approval-requests.index')"
    emptyActionLabel="Lihat Semua Requests">
    
    <x-slot name="filters">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-48">
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Cari nomor request, jenis pengajuan, deskripsi, atau requester..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
            </div>
            <div class="min-w-32">
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    <option value="">Semua Status</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="on progress" {{ request('status') === 'on progress' ? 'selected' : '' }}>On Progress</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
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
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="responsive-table min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="w-16 px-2 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                    <th class="w-24 px-2 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                    <th class="w-1/4 px-2 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Request</th>
                    <th class="w-48 px-2 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Peruntukan</th>
                    <th class="w-32 px-2 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Petugas</th>
                    <th class="w-40 px-2 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status Purchasing</th>
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
                            <div class="text-sm text-gray-900 truncate">{{ $step->request->submissionType->name ?? '-' }}</div>
                        </div>
                    </td>
                    <td class="w-48 px-2 py-1 align-top">
                        @php
                            // Ensure departments map available locally
                            $__deptMap = $departmentsMap ?? \App\Models\Department::pluck('name','id');
                            $deptIds = collect($step->request->masterItems ?? [])->pluck('pivot.allocation_department_id')->filter()->unique()->values();
                            $deptNames = $deptIds->map(fn($id) => $__deptMap[$id] ?? null)->filter()->values();
                        @endphp
                        <span class="text-sm text-gray-900">{{ $deptNames->count() ? $deptNames->implode(', ') : '-' }}</span>
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
                    <td class="w-40 px-2 py-1">
                        @php
                            $ps = $step->request->purchasing_status ?? 'unprocessed';
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
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium {{ $psColor }} cursor-pointer" onclick="openPurchasingStatusModal('{{ $ps }}','{{ $psText }}','{{ $step->request->id }}')">{{ $psText }}</span>
                    </td>
                    <td class="w-20 px-2 py-1">
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

@endsection
