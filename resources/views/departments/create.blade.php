@extends('layouts.app')

@section('title', 'Tambah Department')

@section('content')
<div class="w-full mx-auto max-w-4xl">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Tambah Department</h2>
                <a href="{{ route('departments.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Kembali
                </a>
            </div>
        </div>

        <div class="p-6">
            <form action="{{ route('departments.store') }}" method="POST">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Nama Department -->
                    <div class="md:col-span-2">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                            Nama Department <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-500 @enderror"
                               placeholder="Masukkan nama department">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Kode Department -->
                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700 mb-2">
                            Kode Department <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="code" name="code" value="{{ old('code') }}" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('code') border-red-500 @enderror"
                               placeholder="IT, HR, FIN, dll" maxlength="10">
                        @error('code')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Parent Department -->
                    <div>
                        <label for="parent_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Parent Department
                        </label>
                        <select id="parent_id" name="parent_id" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('parent_id') border-red-500 @enderror">
                            <option value="">Pilih Parent Department (Opsional)</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ old('parent_id') == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }} ({{ $dept->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('parent_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Level -->
                    <div>
                        <label for="level" class="block text-sm font-medium text-gray-700 mb-2">
                            Level <span class="text-red-500">*</span>
                        </label>
                        <select id="level" name="level" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('level') border-red-500 @enderror">
                            <option value="">Pilih Level</option>
                            <option value="1" {{ old('level') == '1' ? 'selected' : '' }}>Level 1 - Unit</option>
                            <option value="2" {{ old('level') == '2' ? 'selected' : '' }}>Level 2 - Management</option>
                            <option value="3" {{ old('level') == '3' ? 'selected' : '' }}>Level 3 - Direktur</option>
                        </select>
                        @error('level')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Approval Level -->
                    <div>
                        <label for="approval_level" class="block text-sm font-medium text-gray-700 mb-2">
                            Approval Level <span class="text-red-500">*</span>
                        </label>
                        <select id="approval_level" name="approval_level" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('approval_level') border-red-500 @enderror">
                            <option value="">Pilih Approval Level</option>
                            <option value="1" {{ old('approval_level') == '1' ? 'selected' : '' }}>Level 1</option>
                            <option value="2" {{ old('approval_level') == '2' ? 'selected' : '' }}>Level 2</option>
                            <option value="3" {{ old('approval_level') == '3' ? 'selected' : '' }}>Level 3</option>
                            <option value="4" {{ old('approval_level') == '4' ? 'selected' : '' }}>Level 4</option>
                            <option value="5" {{ old('approval_level') == '5' ? 'selected' : '' }}>Level 5</option>
                        </select>
                        @error('approval_level')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Manager -->
                    <div>
                        <label for="manager_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Manager
                        </label>
                        <select id="manager_id" name="manager_id" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('manager_id') border-red-500 @enderror">
                            <option value="">Pilih Manager (Opsional)</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('manager_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }} ({{ $user->role->display_name ?? 'No Role' }})
                                </option>
                            @endforeach
                        </select>
                        @error('manager_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700">Department Aktif</span>
                        </label>
                    </div>

                    <!-- Deskripsi -->
                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            Deskripsi
                        </label>
                        <textarea id="description" name="description" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('description') border-red-500 @enderror"
                                  placeholder="Masukkan deskripsi department">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Section Anggota Departemen -->
                <div class="mt-8 border-t pt-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Anggota Departemen</h3>
                        <button type="button" id="addMemberBtn" 
                                class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-sm">
                            <i class="fas fa-plus mr-1"></i> Tambah Anggota
                        </button>
                    </div>
                    
                    <div id="membersContainer" class="space-y-3">
                        <!-- Dynamic members will be added here -->
                    </div>
                    
                    <div id="noMembersMessage" class="text-center py-8 text-gray-500 bg-gray-50 rounded-lg">
                        <i class="fas fa-users text-4xl mb-2"></i>
                        <p>Belum ada anggota departemen</p>
                        <p class="text-sm">Klik "Tambah Anggota" untuk menambahkan anggota</p>
                    </div>
                </div>

                <div class="flex justify-end space-x-4 mt-8">
                    <a href="{{ route('departments.index') }}" 
                       class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded">
                        Batal
                    </a>
                    <button type="submit" 
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                        Simpan Department
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let memberCount = 0;
const membersContainer = document.getElementById('membersContainer');
const noMembersMessage = document.getElementById('noMembersMessage');
const addMemberBtn = document.getElementById('addMemberBtn');

// Add member functionality
addMemberBtn.addEventListener('click', function() {
    addMemberRow();
});

function addMemberRow() {
    const memberDiv = document.createElement('div');
    memberDiv.className = 'member-item bg-white border border-gray-200 rounded-lg p-4 shadow-sm';
    memberDiv.innerHTML = `
        <div class="flex items-center space-x-4">
            <div class="flex-1">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pilih User</label>
                        <select name="members[${memberCount}][user_id]" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Pilih User</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jabatan</label>
                        <input type="text" name="members[${memberCount}][position]" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Contoh: Staff, Supervisor, dll">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="flex items-center">
                        <input type="checkbox" name="members[${memberCount}][is_primary]" value="1"
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <span class="ml-2 text-sm text-gray-700">Departemen Utama</span>
                    </label>
                </div>
            </div>
            <div class="flex-shrink-0">
                <button type="button" class="remove-member-btn bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-3 rounded text-sm">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    
    membersContainer.appendChild(memberDiv);
    memberCount++;
    
    // Hide no members message
    noMembersMessage.style.display = 'none';
    
    // Add remove functionality
    const removeBtn = memberDiv.querySelector('.remove-member-btn');
    removeBtn.addEventListener('click', function() {
        memberDiv.remove();
        
        // Show no members message if no members left
        if (membersContainer.children.length === 0) {
            noMembersMessage.style.display = 'block';
        }
    });
}

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const members = document.querySelectorAll('.member-item');
    const selectedUsers = new Set();
    let hasDuplicate = false;
    
    members.forEach(member => {
        const userId = member.querySelector('select[name*="[user_id]"]').value;
        if (userId) {
            if (selectedUsers.has(userId)) {
                hasDuplicate = true;
                member.querySelector('select[name*="[user_id]"]').classList.add('border-red-500');
            } else {
                selectedUsers.add(userId);
                member.querySelector('select[name*="[user_id]"]').classList.remove('border-red-500');
            }
        }
    });
    
    if (hasDuplicate) {
        e.preventDefault();
        alert('Tidak boleh memilih user yang sama untuk beberapa anggota!');
    }
});
</script>
@endsection
