@extends('layouts.app')

@section('title', 'Detail Department')

@section('content')
<div class="w-full">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-4 bg-white border-b border-gray-200">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">{{ $department->name }}</h2>
                    <p class="text-sm text-gray-600">{{ $department->code }} - {{ $department->description }}</p>
                </div>
                <div class="flex space-x-2">
                    <a href="{{ route('departments.edit', $department) }}" 
                       class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-3 rounded text-sm">
                        Edit
                    </a>
                    <a href="{{ route('departments.index') }}" 
                       class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-3 rounded text-sm">
                        Kembali
                    </a>
                </div>
            </div>
        </div>

        <div class="p-4">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Department Info -->
                <div class="lg:col-span-2">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">Informasi Department</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Kode Department</label>
                                <p class="mt-1 text-sm text-gray-900">
                                    <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">
                                        {{ $department->code }}
                                    </span>
                                </p>
                            </div>
                            
                            
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Status</label>
                                <p class="mt-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $department->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $department->is_active ? 'Aktif' : 'Tidak Aktif' }}
                                    </span>
                                </p>
                            </div>
                            
                            @if($department->parent)
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Parent Department</label>
                                <p class="mt-1 text-sm text-gray-900">
                                    <a href="{{ route('departments.show', $department->parent) }}" 
                                       class="text-blue-600 hover:text-blue-800">
                                        {{ $department->parent->name }} ({{ $department->parent->code }})
                                    </a>
                                </p>
                            </div>
                            @endif
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Dibuat</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $department->created_at->format('d M Y H:i') }}</p>
                            </div>
                        </div>
                        
                        @if($department->description)
                        <div class="mt-3">
                            <label class="block text-sm font-medium text-gray-700">Deskripsi</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $department->description }}</p>
                        </div>
                        @endif
                    </div>

                    <!-- Manager Info -->
                    @if($department->manager)
                    <div class="bg-blue-50 rounded-lg p-4 mt-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">Manager Department</h3>
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center">
                                    <span class="text-white font-medium text-sm">
                                        {{ substr($department->manager->name, 0, 2) }}
                                    </span>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-900">{{ $department->manager->name }}</p>
                                <p class="text-sm text-gray-500">{{ $department->manager->role->display_name ?? 'No Role' }}</p>
                                <p class="text-sm text-gray-500">{{ $department->manager->email }}</p>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Sidebar -->
                <div class="space-y-4">
                    <!-- Sub Departments -->
                    @if($department->children->count() > 0)
                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">Sub Departments</h3>
                        <div class="space-y-2">
                            @foreach($department->children as $child)
                            <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $child->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $child->code }}</p>
                                </div>
                                <a href="{{ route('departments.show', $child) }}" 
                                   class="text-blue-600 hover:text-blue-800 text-sm">Lihat</a>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Department Stats -->
                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">Statistik</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Total Users</span>
                                <span class="text-sm font-medium text-gray-900">{{ $department->users->count() }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Managers</span>
                                <span class="text-sm font-medium text-gray-900">{{ $department->managers->count() }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Sub Departments</span>
                                <span class="text-sm font-medium text-gray-900">{{ $department->children->count() }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users List -->
            @if($department->users->count() > 0)
            <div class="mt-6">
                <div class="bg-white border border-gray-200 rounded-lg">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Users di Department Ini</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        User
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Position
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Role
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($department->users as $user)
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-8 w-8">
                                                <div class="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center">
                                                    <span class="text-gray-600 text-xs font-medium">
                                                        {{ substr($user->name, 0, 2) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                                <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $user->pivot->position }}</div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $user->role->display_name ?? 'No Role' }}</div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex space-x-1">
                                            @if($user->pivot->is_primary)
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    Primary
                                                </span>
                                            @endif
                                            @if($user->pivot->is_manager)
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Manager
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                                        <form action="{{ route('departments.remove-user', [$department, $user]) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900" 
                                                    onclick="return confirm('Yakin ingin menghapus user dari department ini?')">
                                                Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
