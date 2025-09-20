@extends('layouts.app')

@section('title', 'Kelola Departments')

@section('content')
<div class="w-full mx-auto" x-data="{
    ...tableFilter({
        search: '{{ request('search') }}',
        level: '{{ request('level') }}',
        status: '{{ request('status') }}'
    })
}">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Kelola Departments</h2>
                <a href="{{ route('departments.create') }}" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    Tambah Department
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="p-6 border-b border-gray-200 bg-gray-50">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Cari nama, kode, atau deskripsi..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Level</label>
                    <select name="level" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Level</option>
                        <option value="1" {{ request('level') == '1' ? 'selected' : '' }}>Level 1 - Unit</option>
                        <option value="2" {{ request('level') == '2' ? 'selected' : '' }}>Level 2 - Management</option>
                        <option value="3" {{ request('level') == '3' ? 'selected' : '' }}>Level 3 - Direktur</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Kode & Nama
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Level
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Manager
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Users
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($departments as $department)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">
                                        <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded mr-2">
                                            {{ $department->code }}
                                        </span>
                                        {{ $department->name }}
                                    </div>
                                    @if($department->description)
                                        <div class="text-sm text-gray-500">{{ Str::limit($department->description, 50) }}</div>
                                    @endif
                                    @if($department->parent)
                                        <div class="text-xs text-gray-400">
                                            Parent: {{ $department->parent->name }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $department->level == 1 ? 'bg-green-100 text-green-800' : 
                                   ($department->level == 2 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                Level {{ $department->level }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($department->manager)
                                <div class="text-sm text-gray-900">{{ $department->manager->name }}</div>
                                <div class="text-sm text-gray-500">{{ $department->manager->role->display_name ?? 'No Role' }}</div>
                            @else
                                <span class="text-sm text-gray-400">Belum ada manager</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $department->users->count() }} users</div>
                            @if($department->users->count() > 0)
                                <div class="text-xs text-gray-500">
                                    {{ $department->users->take(2)->pluck('name')->join(', ') }}
                                    @if($department->users->count() > 2)
                                        +{{ $department->users->count() - 2 }} lainnya
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $department->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $department->is_active ? 'Aktif' : 'Tidak Aktif' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <a href="{{ route('departments.show', $department) }}" 
                                   class="text-blue-600 hover:text-blue-900">Lihat</a>
                                <a href="{{ route('departments.edit', $department) }}" 
                                   class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                <form action="{{ route('departments.destroy', $department) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900" 
                                            onclick="return confirm('Yakin ingin menghapus department ini?')">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($departments->hasPages())
        <div class="px-6 py-3 border-t border-gray-200">
            {{ $departments->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
