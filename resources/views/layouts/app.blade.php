<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Supply Chain')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/x-icon" href="images/logo.png">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div x-data="{
            sidebarOpen: false,
            sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === '1',
            toggleSidebar() {
                this.sidebarCollapsed = !this.sidebarCollapsed;
                localStorage.setItem('sidebarCollapsed', this.sidebarCollapsed ? '1' : '0');
                // Dispatch custom event for responsive table components
                document.dispatchEvent(new CustomEvent('sidebar-toggled'));
            }
        }"
        x-init="$watch('sidebarCollapsed', v => {
            localStorage.setItem('sidebarCollapsed', v ? '1' : '0');
            // Dispatch custom event for responsive table components
            document.dispatchEvent(new CustomEvent('sidebar-toggled'));
        })"
        class="min-h-screen flex">
        <!-- Sidebar -->
        <div :class="[
                sidebarOpen ? 'translate-x-0' : '-translate-x-full',
                sidebarCollapsed ? 'w-20' : 'w-64'
            ]"
            class="fixed inset-y-0 left-0 z-50 bg-green-700 shadow-lg transform transition-all duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0">
            
            <!-- Logo/Brand -->
            <div class="flex items-center justify-between h-20 px-4 border-b border-green-600">
                <div class="flex items-center space-x-3 overflow-hidden">
                    <div class="bg-white rounded-xl border border-green-200 shadow-sm p-2 flex-shrink-0">
                        <img src="{{ asset('images/logo.png') }}" alt="Logo" class="h-8 w-auto object-contain">
                    </div>
                    <h1 x-show="!sidebarCollapsed" class="text-xl font-bold text-white tracking-wide truncate">Supply Chain</h1>
                </div>
                
            </div>

            <!-- Sidebar Navigation -->
            <nav class="px-4 py-6">
                <div class="mb-6">
                    <h3 x-show="!sidebarCollapsed" class="text-xs font-semibold text-green-200 uppercase tracking-wider mb-3">MENU UTAMA</h3>
                </div>
                
                <ul class="space-y-2">
                    <li>
                        <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-3 text-white rounded-lg hover:bg-green-800 transition-colors {{ request()->routeIs('dashboard') ? 'bg-green-800' : '' }}" :class="sidebarCollapsed ? 'justify-center' : ''" title="Dashboard">
                            <i class="fas fa-tachometer-alt w-5" :class="sidebarCollapsed ? '' : 'mr-3'"></i>
                            <span x-show="!sidebarCollapsed">Dashboard</span>
                        </a>
                    </li>
                    

                    @if(auth()->user()->hasPermission('manage_users'))
                    <li>
                        <a href="{{ route('users.index') }}" class="flex items-center px-4 py-3 text-white rounded-lg hover:bg-green-800 transition-colors {{ request()->routeIs('users.*') ? 'bg-green-800' : '' }}" :class="sidebarCollapsed ? 'justify-center' : ''" title="Users">
                            <i class="fas fa-users w-5" :class="sidebarCollapsed ? '' : 'mr-3'"></i>
                            <span x-show="!sidebarCollapsed">Users</span>
                        </a>
                    </li>
                    @endif

                    @if(auth()->user()->hasPermission('manage_roles'))
                    <li>
                        <a href="{{ route('roles.index') }}" class="flex items-center px-4 py-3 text-white rounded-lg hover:bg-green-800 transition-colors {{ request()->routeIs('roles.*') ? 'bg-green-800' : '' }}" :class="sidebarCollapsed ? 'justify-center' : ''" title="Roles">
                            <i class="fas fa-user-shield w-5" :class="sidebarCollapsed ? '' : 'mr-3'"></i>
                            <span x-show="!sidebarCollapsed">Roles</span>
                        </a>
                    </li>
                    @endif

                    @if(auth()->user()->hasPermission('manage_permissions'))
                    <li>
                        <a href="{{ route('permissions.index') }}" class="flex items-center px-4 py-3 text-white rounded-lg hover:bg-green-800 transition-colors {{ request()->routeIs('permissions.*') ? 'bg-green-800' : '' }}" :class="sidebarCollapsed ? 'justify-center' : ''" title="Permissions">
                            <i class="fas fa-key w-5" :class="sidebarCollapsed ? '' : 'mr-3'"></i>
                            <span x-show="!sidebarCollapsed">Permissions</span>
                        </a>
                    </li>
                    @endif

                    @if(auth()->user()->hasPermission('manage_departments'))
                    <li>
                        <a href="{{ route('departments.index') }}" class="flex items-center px-4 py-3 text-white rounded-lg hover:bg-green-800 transition-colors {{ request()->routeIs('departments.*') ? 'bg-green-800' : '' }}" :class="sidebarCollapsed ? 'justify-center' : ''" title="Departments">
                            <i class="fas fa-building w-5" :class="sidebarCollapsed ? '' : 'mr-3'"></i>
                            <span x-show="!sidebarCollapsed">Departments</span>
                        </a>
                    </li>
                    @endif

                    @if(auth()->user()->hasPermission('manage_workflows'))
                    <li>
                        <a href="{{ route('approval-workflows.index') }}" class="flex items-center px-4 py-3 text-white rounded-lg hover:bg-green-800 transition-colors {{ request()->routeIs('approval-workflows.*') ? 'bg-green-800' : '' }}" :class="sidebarCollapsed ? 'justify-center' : ''" title="Workflows">
                            <i class="fas fa-sitemap w-5" :class="sidebarCollapsed ? '' : 'mr-3'"></i>
                            <span x-show="!sidebarCollapsed">Workflows</span>
                        </a>
                    </li>
                    @endif

                    @if(auth()->user()->hasPermission('view_all_approvals'))
                    <li>
                        <a href="{{ route('approval-requests.index') }}" class="flex items-center px-4 py-3 text-white rounded-lg hover:bg-green-800 transition-colors {{ request()->routeIs('approval-requests.index') ? 'bg-green-800' : '' }}" :class="sidebarCollapsed ? 'justify-center' : ''" title="All Approval Requests">
                            <i class="fas fa-clipboard-check w-5" :class="sidebarCollapsed ? '' : 'mr-3'"></i>
                            <span x-show="!sidebarCollapsed">All Approval Requests</span>
                        </a>
                    </li>
                    @endif

                @if(auth()->user()->hasPermission('manage_items'))
                <li>
                    <a href="{{ route('master-items.index') }}" class="flex items-center px-4 py-3 text-white rounded-lg hover:bg-green-800 transition-colors {{ request()->routeIs('master-items.*') ? 'bg-green-800' : '' }}" :class="sidebarCollapsed ? 'justify-center' : ''" title="Master Barang">
                        <i class="fas fa-box w-5" :class="sidebarCollapsed ? '' : 'mr-3'"></i>
                        <span x-show="!sidebarCollapsed">Master Barang</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('item-categories.index') }}" class="flex items-center px-4 py-3 text-white rounded-lg hover:bg-green-800 transition-colors {{ request()->routeIs('item-categories.*') ? 'bg-green-800' : '' }}" :class="sidebarCollapsed ? 'justify-center' : ''" title="Kategori Barang">
                        <i class="fas fa-tags w-5" :class="sidebarCollapsed ? '' : 'mr-3'"></i>
                        <span x-show="!sidebarCollapsed">Kategori Barang</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('item-types.index') }}" class="flex items-center px-4 py-3 text-white rounded-lg hover:bg-green-800 transition-colors {{ request()->routeIs('item-types.*') ? 'bg-green-800' : '' }}" :class="sidebarCollapsed ? 'justify-center' : ''" title="Tipe Barang">
                        <i class="fas fa-cube w-5" :class="sidebarCollapsed ? '' : 'mr-3'"></i>
                        <span x-show="!sidebarCollapsed">Tipe Barang</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('units.index') }}" class="flex items-center px-4 py-3 text-white rounded-lg hover:bg-green-800 transition-colors {{ request()->routeIs('units.*') ? 'bg-green-800' : '' }}" :class="sidebarCollapsed ? 'justify-center' : ''" title="Satuan Barang">
                        <i class="fas fa-weight w-5" :class="sidebarCollapsed ? '' : 'mr-3'"></i>
                        <span x-show="!sidebarCollapsed">Satuan Barang</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('commodities.index') }}" class="flex items-center px-4 py-3 text-white rounded-lg hover:bg-green-800 transition-colors {{ request()->routeIs('commodities.*') ? 'bg-green-800' : '' }}" :class="sidebarCollapsed ? 'justify-center' : ''" title="Komoditas">
                        <i class="fas fa-industry w-5" :class="sidebarCollapsed ? '' : 'mr-3'"></i>
                        <span x-show="!sidebarCollapsed">Komoditas</span>
                    </a>
                </li>
                @endif

                </ul>

                <!-- Approval Section -->
                @if(auth()->user()->hasPermission('view_my_approvals') || auth()->user()->hasPermission('view_pending_approvals'))
                <div class="mt-6">
                    <h3 class="px-4 py-2 text-xs font-semibold text-green-200 uppercase tracking-wider" x-show="!sidebarCollapsed">Approval</h3>
                    <ul class="mt-2 space-y-1">
                        @if(auth()->user()->hasPermission('view_my_approvals'))
                        <li>
                            <a href="{{ route('approval-requests.my-requests') }}" class="flex items-center px-4 py-3 text-white rounded-lg hover:bg-green-800 transition-colors {{ request()->routeIs('approval-requests.my-requests') ? 'bg-green-800' : '' }}" :class="sidebarCollapsed ? 'justify-center' : ''" title="My Requests">
                                <i class="fas fa-file-alt w-5" :class="sidebarCollapsed ? '' : 'mr-3'"></i>
                                <span x-show="!sidebarCollapsed">My Requests</span>
                            </a>
                        </li>
                        @endif
                        @if(auth()->user()->hasPermission('view_pending_approvals'))
                        <li>
                            <a href="{{ route('approval-requests.pending-approvals') }}" class="flex items-center px-4 py-3 text-white rounded-lg hover:bg-green-800 transition-colors {{ request()->routeIs('approval-requests.pending-approvals') ? 'bg-green-800' : '' }}" :class="sidebarCollapsed ? 'justify-center' : ''" title="Pending Approvals">
                                <i class="fas fa-clock w-5" :class="sidebarCollapsed ? '' : 'mr-3'"></i>
                                <span x-show="!sidebarCollapsed">Pending Approvals</span>
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>
                @endif

                <!-- User Profile Section -->
                <div class="mt-8 pt-6 border-t border-green-600">
                    <div class="flex items-center px-4 py-3 text-white" :class="sidebarCollapsed ? 'justify-center' : ''">
                        <div class="w-8 h-8 bg-green-600 rounded-full flex items-center justify-center" :class="sidebarCollapsed ? '' : 'mr-3'">
                            <i class="fas fa-user text-sm"></i>
                        </div>
                        <div x-show="!sidebarCollapsed" class="flex-1 overflow-hidden">
                            <div class="text-sm font-medium truncate">{{ auth()->user()->name }}</div>
                            <div class="text-xs text-green-200 truncate">{{ auth()->user()->role->display_name ?? 'User' }}</div>
                        </div>
                        <i x-show="!sidebarCollapsed" class="fas fa-chevron-down text-xs text-green-200"></i>
                    </div>
                </div>
            </nav>
        </div>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col lg:ml-0">
            <!-- Top Navigation Bar -->
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="flex items-center justify-between h-16 px-6">
                    <div class="flex items-center">
                        <button @click="window.innerWidth >= 1024 ? toggleSidebar() : sidebarOpen = !sidebarOpen" class="mr-4 p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition-colors" :title="window.innerWidth >= 1024 ? (sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar') : (sidebarOpen ? 'Close menu' : 'Open menu')">
                            <!-- Mobile icon -->
                            <i class="fas" :class="sidebarOpen ? 'fa-xmark' : 'fa-bars'" class="lg:hidden"></i>
                            <!-- Desktop icon -->
                            <i class="fas hidden lg:inline" :class="sidebarCollapsed ? 'fa-angles-right' : 'fa-angles-left'"></i>
                        </button>
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800">@yield('title', 'Dashboard')</h2>
                            <p class="text-sm text-gray-500">Supply Chain Management System</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <div class="text-sm text-gray-600">
                            {{ now()->format('d M Y, H:i') }}
                        </div>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="p-2 text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-md transition-colors" onclick="return confirm('Apakah Anda yakin ingin keluar?')">
                                <i class="fas fa-sign-out-alt"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 p-6 bg-gray-50">
                @if(session('success'))
                    <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle mr-2"></i>
                            {{ session('success') }}
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            {{ session('error') }}
                        </div>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>

        <!-- Mobile Overlay -->
        <div x-show="sidebarOpen" @click="sidebarOpen = false" 
             class="fixed inset-0 z-40 bg-black bg-opacity-50 lg:hidden"></div>
    </div>
</body>
</html>
