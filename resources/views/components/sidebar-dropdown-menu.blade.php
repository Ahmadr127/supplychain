{{-- Collapsible Sidebar Menu Component with Dropdown --}}
@props([
    'title',           // Menu title
    'icon',            // FontAwesome icon
    'permission' => null, // Permission required
    'routePrefix' => null, // Route prefix for active detection
    'defaultOpen' => false, // Open by default
])

@php
    // Check if any child route is active
    $isActive = false;
    if ($routePrefix) {
        $patterns = is_array($routePrefix) ? $routePrefix : explode(',', $routePrefix);
        foreach ($patterns as $pattern) {
            if (request()->routeIs(trim($pattern))) {
                $isActive = true;
                break;
            }
        }
    }
    
    // Generate unique ID for this menu
    $menuId = 'menu_' . str_replace([' ', '&'], '_', strtolower($title));
@endphp

@if(!$permission || auth()->user()->hasPermission($permission))
<li x-data="{ 
    open: false,
    init() {
        // Check localStorage for this specific menu
        const stored = localStorage.getItem('{{ $menuId }}');
        if (stored !== null) {
            this.open = stored === '1';
        } else {
            // Default: open if active or defaultOpen
            this.open = {{ $defaultOpen || $isActive ? 'true' : 'false' }};
        }
    },
    toggle() {
        this.open = !this.open;
        localStorage.setItem('{{ $menuId }}', this.open ? '1' : '0');
    }
}">
    {{-- Main menu button - NO active background color --}}
    <button 
        @click="toggle()" 
        class="w-full flex items-center justify-between px-4 py-3 text-white rounded-lg hover:bg-green-800 transition-colors"
        :class="sidebarCollapsed ? 'justify-center' : ''"
        type="button">
        <div class="flex items-center">
            <i class="fas {{ $icon }} w-5" :class="sidebarCollapsed ? '' : 'mr-3'"></i>
            <span x-show="!sidebarCollapsed">{{ $title }}</span>
        </div>
        <i x-show="!sidebarCollapsed" class="fas fa-chevron-down text-xs transition-transform duration-200" :class="{ 'rotate-180': open }"></i>
    </button>
    
    {{-- Submenu items --}}
    <ul 
        x-show="open && !sidebarCollapsed" 
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        class="mt-1 ml-4 space-y-1 border-l-2 border-green-600 pl-2">
        {{ $slot }}
    </ul>
</li>
@endif
