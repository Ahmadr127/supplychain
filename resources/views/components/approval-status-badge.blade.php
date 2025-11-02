@props(['status', 'requestStatus' => null])

@php
    // Transform logic: pending step in active request = on progress
    $actualStatus = $status;
    if ($status === 'pending' && $requestStatus && in_array($requestStatus, ['pending', 'on progress'])) {
        $actualStatus = 'on progress';
    }
    
    $displayStatus = $actualStatus;
    $statusColor = 'bg-gray-500 text-white';
    
    if ($actualStatus == 'on progress') {
        $statusColor = 'bg-blue-500 text-white';
        $displayStatus = 'On Progress';
    } elseif ($actualStatus == 'pending') {
        $statusColor = 'bg-yellow-500 text-white';
        $displayStatus = 'Pending';
    } elseif ($actualStatus == 'approved') {
        $statusColor = 'bg-green-600 text-white';
        $displayStatus = 'Approved';
    } elseif ($actualStatus == 'rejected') {
        $statusColor = 'bg-red-600 text-white';
        $displayStatus = 'Rejected';
    } elseif ($actualStatus == 'cancelled') {
        $statusColor = 'bg-gray-500 text-white';
        $displayStatus = 'Cancelled';
    }
@endphp

<span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium {{ $statusColor }}">
    {{ $displayStatus }}
</span>
