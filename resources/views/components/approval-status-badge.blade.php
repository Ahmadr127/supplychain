@props(['status'])

@php
    $displayStatus = $status;
    $statusColor = 'bg-gray-500 text-white';
    
    if ($status == 'on progress') {
        $statusColor = 'bg-blue-500 text-white';
        $displayStatus = 'On Progress';
    } elseif ($status == 'pending') {
        $statusColor = 'bg-yellow-500 text-white';
        $displayStatus = 'Pending';
    } elseif ($status == 'approved') {
        $statusColor = 'bg-green-600 text-white';
        $displayStatus = 'Approved';
    } elseif ($status == 'rejected') {
        $statusColor = 'bg-red-600 text-white';
        $displayStatus = 'Rejected';
    } elseif ($status == 'cancelled') {
        $statusColor = 'bg-gray-500 text-white';
        $displayStatus = 'Cancelled';
    }
@endphp

<span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium {{ $statusColor }}">
    {{ $displayStatus }}
</span>
