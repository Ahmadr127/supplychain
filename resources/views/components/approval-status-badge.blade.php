@props(['status', 'requestStatus' => null, 'count' => null, 'variant' => 'solid'])

@php
    // Use requestStatus if provided, otherwise use status
    $actualStatus = $requestStatus ?? $status;
    
    $displayStatus = $actualStatus;
    
    // Default solid colors
    $colors = [
        'bg' => 'bg-gray-500',
        'text' => 'text-white',
        'border' => 'border-transparent',
    ];

    // Subtle colors (light background)
    $subtleColors = [
        'bg' => 'bg-gray-100',
        'text' => 'text-gray-800',
        'border' => 'border-gray-200',
    ];
    
    if ($actualStatus == 'on progress' || $actualStatus == 'pending_purchase') {
        $displayStatus = 'On Progress';
        $colors = ['bg' => 'bg-blue-500', 'text' => 'text-white', 'border' => 'border-transparent'];
        $subtleColors = ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'border' => 'border-blue-200'];
    } elseif ($actualStatus == 'pending') {
        $displayStatus = 'Pending';
        $colors = ['bg' => 'bg-yellow-500', 'text' => 'text-white', 'border' => 'border-transparent'];
        $subtleColors = ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'border' => 'border-yellow-200'];
    } elseif ($actualStatus == 'in_purchasing') {
        $displayStatus = 'In Purchasing';
        $colors = ['bg' => 'bg-indigo-500', 'text' => 'text-white', 'border' => 'border-transparent'];
        $subtleColors = ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-800', 'border' => 'border-indigo-200'];
    } elseif ($actualStatus == 'in_release') {
        $displayStatus = 'Awaiting Release';
        $colors = ['bg' => 'bg-purple-500', 'text' => 'text-white', 'border' => 'border-transparent'];
        $subtleColors = ['bg' => 'bg-purple-100', 'text' => 'text-purple-800', 'border' => 'border-purple-200'];
    } elseif ($actualStatus == 'approved' || $actualStatus == 'done') {
        $displayStatus = $actualStatus == 'done' ? 'Done' : 'Approved';
        $colors = ['bg' => 'bg-green-600', 'text' => 'text-white', 'border' => 'border-transparent'];
        $subtleColors = ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'border' => 'border-green-200'];
    } elseif ($actualStatus == 'rejected') {
        $displayStatus = 'Rejected';
        $colors = ['bg' => 'bg-red-600', 'text' => 'text-white', 'border' => 'border-transparent'];
        $subtleColors = ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'border' => 'border-red-200'];
    } elseif ($actualStatus == 'cancelled') {
        $displayStatus = 'Cancelled';
        $colors = ['bg' => 'bg-gray-500', 'text' => 'text-white', 'border' => 'border-transparent'];
        $subtleColors = ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'border' => 'border-gray-200'];
    }

    // Select color scheme based on variant
    $scheme = $variant === 'subtle' ? $subtleColors : $colors;
    $classes = "{$scheme['bg']} {$scheme['text']}";
    if ($variant === 'subtle') {
        $classes .= " border {$scheme['border']}";
    }
    
    // Shape classes
    $shape = $variant === 'subtle' ? 'rounded-full px-2.5' : 'rounded px-1.5';
@endphp

<span class="inline-flex items-center {{ $shape }} py-0.5 text-xs font-medium {{ $classes }}">
    {{ $displayStatus }}
    @if($count !== null)
        <span class="ml-1 font-bold">: {{ $count }}</span>
    @endif
</span>

