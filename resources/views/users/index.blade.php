@extends('layouts.app')

@section('title', 'Kelola Users')

@section('content')
<div class="w-full mx-auto">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Kelola Users</h2>
                <div class="flex space-x-3">
                    <a href="{{ route('users.import') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded inline-flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        Import Excel
                    </a>
                    <a href="{{ route('users.create') }}" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                        Tambah User
                    </a>
                </div>
            </div>
        </div>

        <!-- Table Filter Component -->
        <div x-data="tableFilter({}, window.rolesData)">
            <x-table-filter 
                search-placeholder="Cari nama, NIK, username, atau email..."
                :show-role-filter="true"
                :roles="$roles"
            />
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Nama/NIK
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Username
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Email
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Department
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Role
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Tanggal Dibuat
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($users as $user)
                    <tr>
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                        <span class="text-sm font-medium text-gray-700">
                                            {{ strtoupper(substr($user->name, 0, 2)) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-4 min-w-0">
                                    <div class="text-sm font-medium text-gray-900 truncate">{{ $user->name }}</div>
                                    <div class="text-xs text-gray-500 truncate">NIK: {{ $user->nik ?: '-' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900 truncate">{{ $user->username }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900 truncate">{{ $user->email }}</div>
                        </td>
                        <td class="px-6 py-4">
                            @if($user->departments && $user->departments->count() > 0)
                                <div class="flex flex-col gap-1">
                                    @foreach($user->departments as $dept)
                                        <div class="text-sm text-gray-900">
                                            {{ $dept->name }}
                                            @if($dept->pivot && $dept->pivot->is_primary)
                                                <span class="text-xs text-green-600 font-medium ml-1">(Utama)</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-gray-400 text-sm">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($user->role)
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                    @if($user->role->name === 'admin') bg-red-100 text-red-800
                                    @elseif($user->role->name === 'librarian') bg-blue-100 text-blue-800
                                    @else bg-green-100 text-green-800 @endif">
                                    {{ $user->role->display_name }}
                                </span>
                            @else
                                <span class="text-gray-500">Tidak ada role</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ $user->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 text-sm font-medium">
                            <a href="{{ route('users.edit', $user) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                            @if($user->id !== auth()->id())
                                <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900" 
                                            onclick="return confirm('Yakin ingin menghapus user ini?')">
                                        Hapus
                                    </button>
                                </form>
                            @else
                                <span class="text-gray-400">(Akun Anda)</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
        <div class="px-6 py-3 border-t border-gray-200">
            {{ $users->links() }}
        </div>
        @endif
    </div>
</div>

<script>
// Store roles data globally before Alpine initializes
window.rolesData = @json($roles ?? []);

document.addEventListener('alpine:init', () => {
    Alpine.data('tableFilter', (initialFilters = {}, rolesData = []) => ({
        filters: {
            search: initialFilters.search || '',
            dateFrom: initialFilters.dateFrom || '',
            dateTo: initialFilters.dateTo || '',
            roleId: initialFilters.roleId || '',
            ...initialFilters
        },
        roles: rolesData,
        roleOpen: false,
        roleSearch: '',

        init() {
            // Initialize filters from URL params
            this.filters = {
                search: new URLSearchParams(window.location.search).get('search') || '',
                dateFrom: new URLSearchParams(window.location.search).get('date_from') || '',
                dateTo: new URLSearchParams(window.location.search).get('date_to') || '',
                roleId: new URLSearchParams(window.location.search).get('role_id') || '',
                ...initialFilters
            };
        },

        applyFilters() {
            const params = new URLSearchParams();
            
            if (this.filters.search) params.set('search', this.filters.search);
            if (this.filters.dateFrom) params.set('date_from', this.filters.dateFrom);
            if (this.filters.dateTo) params.set('date_to', this.filters.dateTo);
            if (this.filters.roleId) params.set('role_id', this.filters.roleId);
            
            const currentPage = new URLSearchParams(window.location.search).get('page');
            if (currentPage) params.set('page', currentPage);
            
            const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            window.location.href = newUrl;
        },

        clearFilters() {
            this.filters = {
                search: '',
                dateFrom: '',
                dateTo: '',
                roleId: '',
                ...initialFilters
            };
            this.roleSearch = '';
            
            const newUrl = window.location.pathname;
            window.location.href = newUrl;
        },

        removeFilter(key) {
            this.filters[key] = '';
            this.applyFilters();
        },

        hasActiveFilters() {
            return Object.values(this.filters).some(value => value !== '' && value !== null);
        },

        getActiveFilters() {
            const active = {};
            Object.entries(this.filters).forEach(([key, value]) => {
                if (value !== '' && value !== null) {
                    active[key] = value;
                }
            });
            return active;
        },

        getFilterLabel(key, value) {
            const labels = {
                search: `Pencarian: "${value}"`,
                dateFrom: `Dari: ${this.formatDate(value)}`,
                dateTo: `Sampai: ${this.formatDate(value)}`,
                roleId: `Role: ${this.getRoleLabel(value)}`
            };
            return labels[key] || `${key}: ${value}`;
        },

        getRoleLabel(roleId) {
            const role = this.roles.find(r => r.id == roleId);
            return role?.display_name || 'Unknown';
        },

        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('id-ID', {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });
        },

        filteredRoles() {
            if (!this.roleSearch) return this.roles;
            return this.roles.filter(role => {
                const displayName = role?.display_name || '';
                return displayName.toLowerCase().includes(this.roleSearch.toLowerCase());
            });
        },

        selectRole(roleId) {
            this.filters.roleId = roleId;
            this.roleOpen = false;
            this.applyFilters();
        },

        clearRole() {
            this.filters.roleId = '';
            this.roleSearch = '';
            this.applyFilters();
        }
    }));
});
</script>
@endsection
