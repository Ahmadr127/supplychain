@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Welcome Section -->
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Selamat Datang, {{ $user->name }}!</h2>
            <p class="text-gray-600">Dashboard Library RS - Sistem Manajemen Perpustakaan</p>
        </div>
    </div>

    <!-- Quick Actions Card -->
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Aksi Cepat</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @if($user->hasPermission('manage_users'))
                <a href="{{ route('users.index') }}" class="flex items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors group">
                    <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center mr-4 group-hover:bg-blue-700 transition-colors">
                        <i class="fas fa-users text-white"></i>
                    </div>
                    <div>
                        <div class="text-blue-900 font-medium">Kelola Pengguna</div>
                        <div class="text-sm text-blue-600">Manajemen data pengguna</div>
                    </div>
                </a>
                @endif
                
                @if($user->hasPermission('manage_roles'))
                <a href="{{ route('roles.index') }}" class="flex items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors group">
                    <div class="w-10 h-10 bg-green-600 rounded-lg flex items-center justify-center mr-4 group-hover:bg-green-700 transition-colors">
                        <i class="fas fa-user-tag text-white"></i>
                    </div>
                    <div>
                        <div class="text-green-900 font-medium">Kelola Role</div>
                        <div class="text-sm text-green-600">Manajemen role pengguna</div>
                    </div>
                </a>
                @endif
                
                @if($user->hasPermission('manage_permissions'))
                <a href="{{ route('permissions.index') }}" class="flex items-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors group">
                    <div class="w-10 h-10 bg-purple-600 rounded-lg flex items-center justify-center mr-4 group-hover:bg-purple-700 transition-colors">
                        <i class="fas fa-key text-white"></i>
                    </div>
                    <div>
                        <div class="text-purple-900 font-medium">Kelola Permission</div>
                        <div class="text-sm text-purple-600">Manajemen izin akses</div>
                    </div>
                </a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
