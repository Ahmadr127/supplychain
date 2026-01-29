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

        /* Custom Scrollbar for Sidebar */
        .sidebar-scroll::-webkit-scrollbar {
            width: 6px;
        }
        
        .sidebar-scroll::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }
        
        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            transition: background 0.3s ease;
        }
        
        .sidebar-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        /* For Firefox */
        .sidebar-scroll {
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.2) rgba(255, 255, 255, 0.05);
        }
    </style>
</head>
<body class="bg-gray-100 overflow-x-hidden h-screen">
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
        class="h-full flex overflow-x-hidden">
        <!-- Sidebar -->
        <div :class="[
                sidebarOpen ? 'translate-x-0' : '-translate-x-full',
                sidebarCollapsed ? 'w-20' : 'w-64'
            ]"
            class="fixed inset-y-0 left-0 z-50 bg-green-700 shadow-lg transform transition-all duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0 flex flex-col h-screen">
            
            <!-- Logo/Brand -->
            <div class="flex items-center justify-between h-20 px-4 border-b border-green-600 flex-shrink-0">
                <div class="flex items-center space-x-3 overflow-hidden">
                    <div class="bg-white rounded-xl border border-green-200 shadow-sm p-2 flex-shrink-0">
                        <img src="{{ asset('images/logo.png') }}" alt="Logo" class="h-8 w-auto object-contain">
                    </div>
                    <h1 x-show="!sidebarCollapsed" class="text-xl font-bold text-white tracking-wide truncate">Supply Chain</h1>
                </div>
                
            </div>

            <!-- Sidebar Navigation -->
            <nav class="flex-1 overflow-y-auto sidebar-scroll px-4 py-6"
                 x-init="$el.scrollTop = localStorage.getItem('sidebarScroll') || 0"
                 @scroll.debounce.100ms="localStorage.setItem('sidebarScroll', $el.scrollTop)">
                <div class="mb-6">
                    <h3 x-show="!sidebarCollapsed" class="text-xs font-semibold text-green-200 uppercase tracking-wider mb-3">MENU UTAMA</h3>
                </div>
                
                <ul class="space-y-2">
                    {{-- Dashboard - Always visible at top --}}
                    @if(auth()->user()->hasPermission('view_dashboard'))
                        <x-sidebar-menu-item 
                            route="dashboard" 
                            icon="fa-tachometer-alt" 
                            label="Dashboard" 
                        />
                    @endif

                    {{-- System Dropdown --}}
                    @if(auth()->user()->hasPermission('manage_users') || auth()->user()->hasPermission('manage_roles') || auth()->user()->hasPermission('manage_permissions') || auth()->user()->hasPermission('manage_departments'))
                        <x-sidebar-dropdown-menu 
                            title="System" 
                            icon="fa-cogs" 
                            routePrefix="users.*,roles.*,permissions.*,departments.*"
                            defaultOpen="false">
                            @if(auth()->user()->hasPermission('manage_users'))
                                <x-sidebar-menu-item 
                                    route="users.index" 
                                    icon="fa-users" 
                                    label="Users" 
                                    routeMatch="users.*"
                                />
                            @endif
                            @if(auth()->user()->hasPermission('manage_roles'))
                                <x-sidebar-menu-item 
                                    route="roles.index" 
                                    icon="fa-user-shield" 
                                    label="Roles" 
                                    routeMatch="roles.*"
                                />
                            @endif
                            @if(auth()->user()->hasPermission('manage_permissions'))
                                <x-sidebar-menu-item 
                                    route="permissions.index" 
                                    icon="fa-key" 
                                    label="Permissions" 
                                    routeMatch="permissions.*"
                                />
                            @endif
                            @if(auth()->user()->hasPermission('manage_departments'))
                                <x-sidebar-menu-item 
                                    route="departments.index" 
                                    icon="fa-building" 
                                    label="Departments" 
                                    routeMatch="departments.*"
                                />
                            @endif
                        </x-sidebar-dropdown-menu>
                    @endif

                    {{-- Workflow Dropdown --}}
                    @if(auth()->user()->hasPermission('manage_workflows') || auth()->user()->hasPermission('view_all_approvals') || auth()->user()->hasPermission('manage_submission_types'))
                        <x-sidebar-dropdown-menu 
                            title="Workflow" 
                            icon="fa-project-diagram" 
                            routePrefix="approval-workflows.*,approval-requests.index,submission-types.*"
                            defaultOpen="false">
                            @if(auth()->user()->hasPermission('manage_workflows'))
                                <x-sidebar-menu-item 
                                    route="approval-workflows.index" 
                                    icon="fa-sitemap" 
                                    label="Workflows" 
                                    routeMatch="approval-workflows.*"
                                />
                            @endif
                            @if(auth()->user()->hasPermission('view_all_approvals'))
                                <x-sidebar-menu-item 
                                    route="approval-requests.index" 
                                    icon="fa-clipboard-check" 
                                    label="All Approval Requests" 
                                    routeMatch="approval-requests.index"
                                />
                            @endif
                            @if(auth()->user()->hasPermission('manage_submission_types'))
                                <x-sidebar-menu-item 
                                    route="submission-types.index" 
                                    icon="fa-list-alt" 
                                    label="Jenis Pengajuan" 
                                    routeMatch="submission-types.*"
                                />
                            @endif
                        </x-sidebar-dropdown-menu>
                    @endif

                    {{-- Master Data Dropdown --}}
                    @if(auth()->user()->hasPermission('manage_items'))
                        <x-sidebar-dropdown-menu 
                            title="Master Data" 
                            icon="fa-database" 
                            permission="manage_items"
                            routePrefix="master-items.*,item-categories.*,item-types.*,units.*,commodities.*"
                            defaultOpen="false">
                            <x-sidebar-menu-item 
                                route="master-items.index" 
                                icon="fa-box" 
                                label="Master Barang" 
                                routeMatch="master-items.*"
                            />
                            <x-sidebar-menu-item 
                                route="item-categories.index" 
                                icon="fa-tags" 
                                label="Kategori Barang" 
                                routeMatch="item-categories.*"
                            />
                            <x-sidebar-menu-item 
                                route="item-types.index" 
                                icon="fa-cube" 
                                label="Tipe Barang" 
                                routeMatch="item-types.*"
                            />
                            <x-sidebar-menu-item 
                                route="units.index" 
                                icon="fa-weight" 
                                label="Satuan Barang" 
                                routeMatch="units.*"
                            />
                            <x-sidebar-menu-item 
                                route="commodities.index" 
                                icon="fa-industry" 
                                label="Komoditas" 
                                routeMatch="commodities.*"
                            />
                        </x-sidebar-dropdown-menu>
                    @endif

                    {{-- Config Dropdown (Suppliers & Settings) --}}
                    @if(auth()->user()->hasPermission('manage_suppliers') || auth()->user()->hasPermission('manage_settings'))
                        <x-sidebar-dropdown-menu 
                            title="Config" 
                            icon="fa-tools" 
                            routePrefix="suppliers.*,settings.*"
                            defaultOpen="false">
                            @if(auth()->user()->hasPermission('manage_suppliers'))
                                <x-sidebar-menu-item 
                                    route="suppliers.index" 
                                    icon="fa-truck" 
                                    label="Suppliers" 
                                    routeMatch="suppliers.*"
                                />
                            @endif
                            @if(auth()->user()->hasPermission('manage_settings'))
                                <x-sidebar-menu-item 
                                    route="settings.index" 
                                    icon="fa-cog" 
                                    label="Pengaturan" 
                                    routeMatch="settings.*"
                                />
                            @endif
                        </x-sidebar-dropdown-menu>
                    @endif


                </ul>

                {{-- My Approvals Dropdown --}}
                @if(auth()->user()->hasPermission('view_my_approvals') || auth()->user()->hasPermission('approval'))
                    <x-sidebar-dropdown-menu 
                        title="My Approvals" 
                        icon="fa-tasks" 
                        routePrefix="approval-requests.my-requests,approval-requests.pending-approvals"
                        defaultOpen="false">
                        @if(auth()->user()->hasPermission('view_my_approvals'))
                            <x-sidebar-menu-item 
                                route="approval-requests.my-requests" 
                                icon="fa-file-alt" 
                                label="My Requests" 
                            />
                        @endif
                        @if(auth()->user()->hasPermission('approval'))
                            <x-sidebar-menu-item 
                                route="approval-requests.pending-approvals" 
                                icon="fa-check-circle" 
                                label="Pending Approvals" 
                            />
                        @endif
                    </x-sidebar-dropdown-menu>
                @endif

                    {{-- Release Dropdown --}}
                    @if(auth()->user()->hasPermission('manage_purchasing') || auth()->user()->hasPermission('view_release_requests') || auth()->user()->hasPermission('view_pending_release'))
                        <x-sidebar-dropdown-menu 
                            title="Release" 
                            icon="fa-paper-plane" 
                            routePrefix="release-requests.*"
                            defaultOpen="false">
                            @if(auth()->user()->hasPermission('view_release_requests'))
                                <x-sidebar-menu-item 
                                    route="release-requests.index" 
                                    icon="fa-list" 
                                    label="Release Requests" 
                                />
                            @endif
                            @if(auth()->user()->hasPermission('view_pending_release'))
                                <x-sidebar-menu-item 
                                    route="release-requests.my-pending" 
                                    icon="fa-clock" 
                                    label="Pending Release" 
                                />
                            @endif
                        </x-sidebar-dropdown-menu>
                    @endif

                    {{-- Purchasing Dropdown --}}
                    @if(auth()->user()->hasPermission('manage_purchasing') || auth()->user()->hasPermission('view_process_purchasing'))
                        <x-sidebar-dropdown-menu 
                            title="Purchasing" 
                            icon="fa-shopping-cart" 
                            routePrefix="reports.approval-requests*"
                            defaultOpen="false">
                            @if(auth()->user()->hasPermission('view_process_purchasing'))
                                <x-sidebar-menu-item 
                                    route="reports.approval-requests" 
                                    icon="fa-shopping-bag" 
                                    label="Process Purchasing" 
                                    routeMatch="reports.approval-requests*"
                                />
                            @endif
                        </x-sidebar-dropdown-menu>
                    @endif

                {{-- CapEx Dropdown --}}
                @if(auth()->user()->hasPermission('manage_capex'))
                    <x-sidebar-dropdown-menu 
                        title="CapEx" 
                        icon="fa-wallet" 
                        routePrefix="capex.*"
                        defaultOpen="false">
                        <x-sidebar-menu-item 
                            route="capex.index" 
                            icon="fa-list-ol" 
                            label="CapEx ID Numbers" 
                            routeMatch="capex.*"
                        />
                    </x-sidebar-dropdown-menu>
                @endif

            </nav>
        </div>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col lg:ml-0 overflow-x-hidden max-w-full h-full">
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
            <main class="flex-1 p-3 sm:p-4 lg:p-5 bg-gray-50 overflow-y-auto overflow-x-hidden max-w-full">
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
