@extends('layouts.app')

@section('title', 'Kelola Departments')

@section('content')
<x-responsive-table 
    title="Kelola Departments"
    :createRoute="route('departments.create')"
    createLabel="Tambah Department"
    :pagination="$departments"
    :emptyState="$departments->count() === 0"
    emptyMessage="Belum ada department"
    emptyIcon="fas fa-building"
    :emptyActionRoute="route('departments.create')"
    emptyActionLabel="Tambah Department Pertama">
    
    <x-slot name="filters">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-48">
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Cari nama, kode, atau deskripsi..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
            </div>
            <div class="w-32">
                <select name="level" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    <option value="">Semua Level</option>
                    <option value="1" {{ request('level') == '1' ? 'selected' : '' }}>Level 1</option>
                    <option value="2" {{ request('level') == '2' ? 'selected' : '' }}>Level 2</option>
                    <option value="3" {{ request('level') == '3' ? 'selected' : '' }}>Level 3</option>
                </select>
            </div>
            <div class="w-32">
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    <option value="">Semua Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
                </select>
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded text-sm">
                Filter
            </button>
        </form>
    </x-slot>

    <!-- Action Buttons -->
    <div class="p-3 bg-white border-b border-gray-200">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <i class="fas fa-building text-blue-600"></i>
                <span class="text-sm font-medium text-gray-700">Total: {{ $departments->total() }} departments</span>
            </div>
            <a href="{{ route('departments.create') }}" 
               class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-3 rounded-lg transition-colors duration-200 flex items-center space-x-2 text-sm">
                <i class="fas fa-plus"></i>
                <span>Tambah Department</span>
            </a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="responsive-table min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="w-1/3 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Kode & Nama
                    </th>
                    <th class="w-1/12 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Level
                    </th>
                    <th class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Manager
                    </th>
                    <th class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Users
                    </th>
                    <th class="w-1/12 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                    </th>
                    <th class="w-1/12 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Aksi
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($departments as $department)
                <tr class="hover:bg-gray-50 transition-colors duration-150">
                    <td class="w-1/3 px-6 py-4">
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-gray-900 truncate">
                                <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded mr-2">
                                    {{ $department->code }}
                                </span>
                                {{ $department->name }}
                            </div>
                            @if($department->description)
                                <div class="text-sm text-gray-500 truncate">{{ Str::limit($department->description, 50) }}</div>
                            @endif
                            @if($department->parent)
                                <div class="text-xs text-gray-400 truncate">
                                    Parent: {{ $department->parent->name }}
                                </div>
                            @endif
                        </div>
                    </td>
                    <td class="w-1/12 px-6 py-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            {{ $department->level == 1 ? 'bg-green-100 text-green-800' : 
                               ($department->level == 2 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                            Level {{ $department->level }}
                        </span>
                    </td>
                    <td class="w-1/6 px-6 py-4">
                        <div class="min-w-0">
                            @if($department->manager)
                                <div class="text-sm text-gray-900 truncate">{{ $department->manager->name }}</div>
                                <div class="text-sm text-gray-500 truncate">{{ $department->manager->role->display_name ?? 'No Role' }}</div>
                            @else
                                <span class="text-sm text-gray-400">Belum ada manager</span>
                            @endif
                        </div>
                    </td>
                    <td class="w-1/6 px-6 py-4">
                        <div class="min-w-0">
                            <div class="text-sm text-gray-900">{{ $department->users->count() }} users</div>
                            @if($department->users->count() > 0)
                                <div class="text-xs text-gray-500 truncate">
                                    {{ $department->users->take(2)->pluck('name')->join(', ') }}
                                    @if($department->users->count() > 2)
                                        +{{ $department->users->count() - 2 }} lainnya
                                    @endif
                                </div>
                            @endif
                        </div>
                    </td>
                    <td class="w-1/12 px-6 py-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            {{ $department->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $department->is_active ? 'Aktif' : 'Tidak Aktif' }}
                        </span>
                    </td>
                    <td class="w-1/12 px-6 py-4 text-sm font-medium">
                        <div class="flex space-x-2">
                            <a href="{{ route('departments.show', $department) }}" 
                               class="text-blue-600 hover:text-blue-900 transition-colors duration-150">Lihat</a>
                            <a href="{{ route('departments.edit', $department) }}" 
                               class="text-indigo-600 hover:text-indigo-900 transition-colors duration-150">Edit</a>
                            <form action="{{ route('departments.destroy', $department) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900 transition-colors duration-150" 
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
</x-responsive-table>
@endsection
