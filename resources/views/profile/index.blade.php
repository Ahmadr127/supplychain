@extends('layouts.app')

@section('title', 'Profil Saya')

@section('content')
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Profil Pengguna</h2>

            <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Nama Lengkap</h4>
                        <p class="mt-1 text-lg text-gray-900">{{ $user->name }}</p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">NIK</h4>
                        <p class="mt-1 text-lg text-gray-900">{{ $user->nik ?: '-' }}</p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Username</h4>
                        <p class="mt-1 text-lg text-gray-900">{{ $user->username }}</p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Email</h4>
                        <p class="mt-1 text-lg text-gray-900">{{ $user->email }}</p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Role</h4>
                        <p class="mt-1 text-lg text-gray-900">{{ $user->role ? $user->role->display_name : '-' }}</p>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Departemen Utama</h4>
                        @php
                            $primaryDept = $user->departments()->wherePivot('is_primary', true)->first();
                        @endphp
                        <p class="mt-1 text-lg text-gray-900">{{ $primaryDept ? $primaryDept->name : '-' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
