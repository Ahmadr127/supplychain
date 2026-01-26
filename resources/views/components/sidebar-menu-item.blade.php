{{-- Sidebar Menu Item Component --}}
@props([
    'route',           // Route name
    'icon',            // FontAwesome icon class (e.g., 'fa-home')
    'label',           // Menu label text
    'title' => null,   // Tooltip title
    'routeMatch' => null, // Custom route matching (default: exact match)
    'badge' => null,   // Optional badge text
    'badgeColor' => 'bg-red-500', // Badge background color
])

@php
    $title = $title ?? $label;
    $routeMatch = $routeMatch ?? $route;
    
    // Determine if this menu item is active
    $isActive = is_array($routeMatch) 
        ? request()->routeIs(...$routeMatch)
        : request()->routeIs($routeMatch);
@endphp

<li>
    <a href="{{ route($route) }}" 
       class="flex items-center px-4 py-3 text-white rounded-lg hover:bg-green-800 transition-colors {{ $isActive ? 'bg-green-800' : '' }}" 
       :class="sidebarCollapsed ? 'justify-center' : ''" 
       title="{{ $title }}">
        <i class="fas {{ $icon }} w-5" :class="sidebarCollapsed ? '' : 'mr-3'"></i>
        <span x-show="!sidebarCollapsed" class="flex-1">{{ $label }}</span>
        @if($badge)
            <span x-show="!sidebarCollapsed" class="ml-2 px-2 py-0.5 text-xs font-bold text-white {{ $badgeColor }} rounded-full">
                {{ $badge }}
            </span>
        @endif
    </a>
</li>
