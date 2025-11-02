<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Supply Chain')</title>
   @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" type="image/x-icon" href="{{ asset('images/logo.png') }}">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Global overflow prevention */
        html, body {
            overflow-x: hidden;
            max-width: 100%;
        }
        
        /* Ensure all containers respect boundaries */
        * {
            max-width: 100%;
        }
        
        /* Allow specific elements to have wider content with scroll */
        .allow-horizontal-scroll {
            max-width: none;
        }
    </style>
</head>
<body class="bg-gray-100 overflow-x-hidden">
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
        class="min-h-screen flex overflow-x-hidden">
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
                    @if(auth()->user()->hasPermission('view_dashboard'))
                    <li>
                        <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-3 text-white rounded-lg hover:bg-green-800 transition-colors {{ request()->routeIs('dashboard') ? 'bg-green-800' : '' }}" :class="sidebarCollapsed ? 'justify-center' : ''" title="Dashboard">
                            <i class="fas fa-tachometer-alt w-5" :class="sidebarCollapsed ? '' : 'mr-3'"></i>
                            <span x-show="!sidebarCollapsed">Dashboard</span>
                        </a>
                    </li>
                    @endif
                    

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

                @if(auth()->user()->hasPermission('manage_submission_types'))
                <li>
                    <a href="{{ route('submission-types.index') }}" class="flex items-center px-4 py-3 text-white rounded-lg hover:bg-green-800 transition-colors {{ request()->routeIs('submission-types.*') ? 'bg-green-800' : '' }}" :class="sidebarCollapsed ? 'justify-center' : ''" title="Jenis Pengajuan">
                        <i class="fas fa-list-alt w-5" :class="sidebarCollapsed ? '' : 'mr-3'"></i>
                        <span x-show="!sidebarCollapsed">Jenis Pengajuan</span>
                    </a>
                </li>
                @endif

                @if(auth()->user()->hasPermission('manage_suppliers'))
                <li>
                    <a href="{{ route('suppliers.index') }}" class="flex items-center px-4 py-3 text-white rounded-lg hover:bg-green-800 transition-colors {{ request()->routeIs('suppliers.*') ? 'bg-green-800' : '' }}" :class="sidebarCollapsed ? 'justify-center' : ''" title="Supplier">
                        <i class="fas fa-truck w-5" :class="sidebarCollapsed ? '' : 'mr-3'"></i>
                        <span x-show="!sidebarCollapsed">Suppliers</span>
                    </a>
                </li>
                @endif
                
                @if(auth()->user()->hasPermission('manage_settings'))
                <li>
                    <a href="{{ route('settings.index') }}" class="flex items-center px-4 py-3 text-white rounded-lg hover:bg-green-800 transition-colors {{ request()->routeIs('settings.*') ? 'bg-green-800' : '' }}" :class="sidebarCollapsed ? 'justify-center' : ''" title="Pengaturan">
                        <i class="fas fa-cog w-5" :class="sidebarCollapsed ? '' : 'mr-3'"></i>
                        <span x-show="!sidebarCollapsed">Pengaturan</span>
                    </a>
                </li>
                @endif

                @if(auth()->user()->hasPermission('view_reports'))
                <li>
                    <a href="{{ route('reports.approval-requests') }}" class="flex items-center px-4 py-3 text-white rounded-lg hover:bg-green-800 transition-colors {{ request()->routeIs('reports.approval-requests') ? 'bg-green-800' : '' }}" :class="sidebarCollapsed ? 'justify-center' : ''" title="Laporan Pengajuan">
                        <i class="fas fa-chart-bar w-5" :class="sidebarCollapsed ? '' : 'mr-3'"></i>
                        <span x-show="!sidebarCollapsed">Laporan Pengajuan</span>
                    </a>
                </li>
                @endif

                </ul>

                <!-- Approval Section -->
                @if(auth()->user()->hasPermission('view_my_approvals') || auth()->user()->hasPermission('approval'))
                <div class="mt-6">
                    <h3 class="px-4 py-2 text-xs font-semibold text-green-200 uppercase tracking-wider" x-show="!sidebarCollapsed">Approval</h3>
                    <ul class="mt-2 space-y-1">
                        @if(auth()->user()->hasPermission('view_my_approvals'))
                        <li>
                            <a href="{{ route('approval-requests.my-requests') }}" class="js-my-requests flex items-center px-4 py-3 text-white rounded-lg hover:bg-green-800 transition-colors {{ request()->routeIs('approval-requests.my-requests') ? 'bg-green-800' : '' }}" :class="sidebarCollapsed ? 'justify-center' : ''" title="My Requests">
                                <i class="fas fa-file-alt w-5" :class="sidebarCollapsed ? '' : 'mr-3'"></i>
                                <span x-show="!sidebarCollapsed">My Requests</span>
                            </a>
                        </li>
                        @endif
                        @if(auth()->user()->hasPermission('approval'))
                        <li>
                            <a href="{{ route('approval-requests.pending-approvals') }}" class="flex items-center px-4 py-3 text-white rounded-lg hover:bg-green-800 transition-colors {{ request()->routeIs('approval-requests.pending-approvals') ? 'bg-green-800' : '' }}" :class="sidebarCollapsed ? 'justify-center' : ''" title="Approval">
                                <i class="fas fa-check-circle w-5" :class="sidebarCollapsed ? '' : 'mr-3'"></i>
                                <span x-show="!sidebarCollapsed">Approval</span>
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>
                @endif

            </nav>
        </div>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col lg:ml-0 overflow-x-hidden max-w-full">
            <!-- Top Navigation Bar -->
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="flex items-center justify-between h-16 px-6">
                    <div class="flex items-center space-x-4">
                        <button @click="window.innerWidth >= 1024 ? toggleSidebar() : sidebarOpen = !sidebarOpen" 
                                class="p-2 rounded-lg text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50" 
                                :title="window.innerWidth >= 1024 ? (sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar') : (sidebarOpen ? 'Close menu' : 'Open menu')">
                            <!-- Mobile icon -->
                            <i class="fas text-lg" :class="sidebarOpen ? 'fa-xmark' : 'fa-bars'" class="lg:hidden"></i>
                            <!-- Desktop icon -->
                            <i class="fas text-lg hidden lg:inline" :class="sidebarCollapsed ? 'fa-angles-right' : 'fa-angles-left'"></i>
                        </button>
                        
                        <div class="hidden sm:block">
                            <h2 class="text-xl font-semibold text-gray-800">@yield('title', 'Dashboard')</h2>
                            <p class="text-sm text-gray-500">Supply Chain Management System</p>
                        </div>
                        
                        <!-- Mobile Title -->
                        <div class="sm:hidden">
                            <h2 class="text-lg font-semibold text-gray-800">@yield('title', 'Dashboard')</h2>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-3">
                        <!-- User Dropdown -->
                        <div class="relative" x-data="{ open: false }" @click.away="open = false">
                            <button @click="open = !open" class="flex items-center space-x-2 px-3 py-2 rounded-lg hover:bg-gray-50 transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50">
                                <div class="w-8 h-8 bg-green-600 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-sm text-white"></i>
                                </div>
                                <div class="text-left">
                                    <div class="text-sm font-medium text-gray-700">{{ auth()->user()->name }}</div>
                                    <div class="text-xs text-gray-500">
                                        @php
                                            $primaryDepartment = auth()->user()->departments()->wherePivot('is_primary', true)->first();
                                            $role = auth()->user()->role;
                                        @endphp
                                        {{ $primaryDepartment ? $primaryDepartment->name : 'No Department' }}
                                        @if($role)
                                            • {{ $role->display_name }}
                                        @endif
                                    </div>
                                </div>
                                <i class="fas fa-chevron-down text-xs text-gray-400 transition-transform" :class="{ 'rotate-180': open }"></i>
                            </button>
                            
                            <!-- Dropdown Menu -->
                            <div x-show="open" 
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                                
                                <!-- Menu Items -->
                                <div class="py-1">
                                    @if(auth()->user()->hasPermission('view_dashboard'))
                                    <a href="{{ route('dashboard') }}" class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                        <i class="fas fa-home w-4 h-4 mr-2 text-gray-400"></i>
                                        Dashboard
                                    </a>
                                    @endif
                                    
                                    @if(auth()->user()->hasPermission('view_my_approvals'))
                                    <a href="{{ route('approval-requests.my-requests') }}" class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                        <i class="fas fa-file-alt w-4 h-4 mr-2 text-gray-400"></i>
                                        My Requests
                                    </a>
                                    @endif
                                    
                                    @if(auth()->user()->hasPermission('approval'))
                                    <a href="{{ route('approval-requests.pending-approvals') }}" class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                        <i class="fas fa-check-circle w-4 h-4 mr-2 text-gray-400"></i>
                                        Approval
                                    </a>
                                    @endif
                                    
                                    <div class="border-t border-gray-100 my-1"></div>
                                    
                                    <form method="POST" action="{{ route('logout') }}" class="block">
                                        @csrf
                                        <button type="submit" 
                                                class="flex items-center w-full px-3 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors"
                                                onclick="return confirm('Apakah Anda yakin ingin keluar?')">
                                            <i class="fas fa-sign-out-alt w-4 h-4 mr-2"></i>
                                            Logout
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 p-3 sm:p-4 lg:p-5 bg-gray-50 overflow-x-hidden max-w-full">
                {{-- Toasts handled globally via JS --}}

                @yield('content')
            </main>
        </div>

        <!-- Mobile Overlay -->
        <div x-show="sidebarOpen" @click="sidebarOpen = false" 
             class="fixed inset-0 z-40 bg-black bg-opacity-50 lg:hidden">        </div>
    </div>
    <script src="{{ asset('js/toast.js') }}"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function(){
        @if(session('success'))
          window.Toast && Toast.success(@json(session('success')));
        @endif
        @if(session('error'))
          window.Toast && Toast.error(@json(session('error')));
        @endif
        @if($errors->any())
          @foreach($errors->all() as $err)
            window.Toast && Toast.error(@json($err), { duration: 6000 });
          @endforeach
        @endif
      });
    </script>
    @stack('scripts')
</body>
</html>
