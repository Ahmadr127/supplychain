{{-- Reusable Form Component for Departments --}}
<form action="{{ $action }}" method="POST" id="departmentForm">
    @csrf
    @if(isset($department))
        @method('PUT')
    @endif

    {{-- Basic Information Section --}}
    <div class="bg-white rounded-none shadow-none p-6 mb-0 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Informasi Dasar</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Nama Department --}}
            <div class="md:col-span-2">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                    Nama Department <span class="text-red-500">*</span>
                </label>
                <input type="text" id="name" name="name" 
                       value="{{ old('name', $department->name ?? '') }}" 
                       required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-500 @enderror"
                       placeholder="Masukkan nama department">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Kode Department --}}
            <div>
                <label for="code" class="block text-sm font-medium text-gray-700 mb-2">
                    Kode Department <span class="text-red-500">*</span>
                </label>
                <input type="text" id="code" name="code" 
                       value="{{ old('code', $department->code ?? '') }}" 
                       required maxlength="10"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('code') border-red-500 @enderror"
                       placeholder="IT, HR, FIN, dll">
                @error('code')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Parent Department --}}
            <div>
                <label for="parent_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Parent Department
                </label>
                <select id="parent_id" name="parent_id" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('parent_id') border-red-500 @enderror">
                    <option value="">Pilih Parent Department (Opsional)</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" 
                                {{ old('parent_id', $department->parent_id ?? '') == $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }} ({{ $dept->code }})
                        </option>
                    @endforeach
                </select>
                @error('parent_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Level Department --}}
            <div>
                <label for="level" class="block text-sm font-medium text-gray-700 mb-2">
                    Level Department <span class="text-red-500">*</span>
                </label>
                <select id="level" name="level" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('level') border-red-500 @enderror">
                    <option value="">Pilih Level Department</option>
                    <option value="1" {{ old('level', $department->level ?? '') == '1' ? 'selected' : '' }}>Level 1 - Unit/Departemen Bawahan</option>
                    <option value="2" {{ old('level', $department->level ?? '') == '2' ? 'selected' : '' }}>Level 2 - Management/Direktur</option>
                    <option value="3" {{ old('level', $department->level ?? '') == '3' ? 'selected' : '' }}>Level 3 - Direksi/Board</option>
                </select>
                @error('level')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">Level 2 dan 3 dapat melihat kolom Progress di halaman approval</p>
            </div>

            {{-- Manager --}}
            <div>
                <label for="manager_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Manager
                </label>
                <select id="manager_id" name="manager_id" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('manager_id') border-red-500 @enderror">
                    <option value="">Pilih Manager (Opsional)</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" 
                                {{ old('manager_id', $department->manager_id ?? '') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }} ({{ $user->role->display_name ?? 'No Role' }})
                        </option>
                    @endforeach
                </select>
                @error('manager_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Status --}}
            <div class="flex items-center">
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" value="1"
                           {{ old('is_active', $department->is_active ?? true) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <span class="ml-2 text-sm text-gray-700">Department Aktif</span>
                </label>
            </div>

            {{-- Deskripsi --}}
            <div class="md:col-span-2">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                    Deskripsi
                </label>
                <textarea id="description" name="description" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('description') border-red-500 @enderror"
                          placeholder="Masukkan deskripsi department">{{ old('description', $department->description ?? '') }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Members Section --}}
    <div class="bg-white rounded-none shadow-none p-6 mb-0 border-b border-gray-200">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Anggota Departemen</h3>
                <p class="text-sm text-gray-600 mt-1">Kelola anggota departemen</p>
            </div>
            <button type="button" id="addMemberBtn"
                    class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded flex items-center">
                <i class="fas fa-plus mr-2"></i>
                Tambah Anggota
            </button>
        </div>

        <div id="membersContainer" class="space-y-4">
            {{-- Members will be added here --}}
        </div>

        <div id="noMembersMessage" class="text-center py-8 text-gray-500">
            <i class="fas fa-users text-4xl mb-4"></i>
            <p>Belum ada anggota departemen</p>
            <p class="text-sm">Klik "Tambah Anggota" untuk menambahkan anggota</p>
        </div>
    </div>

    {{-- Form Actions --}}
    <div class="bg-white rounded-none shadow-none p-6 flex justify-end space-x-4 border-t border-gray-200">
        <a href="{{ route('departments.index') }}"
           class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded">
            Batal
        </a>
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
            {{ isset($department) ? 'Update Department' : 'Simpan Department' }}
        </button>
    </div>
</form>

<script>
    let memberCount = 0;
    let existingMembers = [];
    const membersContainer = document.getElementById('membersContainer');
    const noMembersMessage = document.getElementById('noMembersMessage');
    const addMemberBtn = document.getElementById('addMemberBtn');

    // User data from PHP
    const usersData = @json($users);

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        @if(isset($department) && $department->users)
            loadExistingMembers();
        @endif
    });

    // Load existing members
    function loadExistingMembers() {
        const members = @json(isset($department) ? $department->users : []);
        members.forEach((user, index) => {
            addMemberToDOM(user, index);
            memberCount++;
        });
        updateMembersDisplay();
    }

    // Add member button click
    addMemberBtn.addEventListener('click', function() {
        addMemberRow();
    });

    // Add new member row
    function addMemberRow() {
        addMemberToDOM(null, memberCount);
        memberCount++;
        updateMembersDisplay();
    }

    // Add member to DOM
    function addMemberToDOM(memberData, index) {
        const memberDiv = document.createElement('div');
        memberDiv.className = 'border-b border-gray-200 p-6 member-item bg-white';
        memberDiv.setAttribute('data-member-index', index);

        const userId = memberData?.id || '';
        const position = memberData?.pivot?.position || '';
        const isPrimary = memberData?.pivot?.is_primary || false;

        let userOptions = '<option value="">-- Pilih User --</option>';
        usersData.forEach(user => {
            const selected = userId == user.id ? 'selected' : '';
            userOptions += `<option value="${user.id}" ${selected}>${user.name} (${user.email})</option>`;
        });

        memberDiv.innerHTML = `
            <div class="flex justify-between items-center mb-4 pb-4 border-b border-gray-200">
                <h4 class="text-md font-medium text-gray-900">Anggota ${index + 1}</h4>
                <button type="button" class="remove-member-btn text-red-600 hover:text-red-800 flex items-center">
                    <i class="fas fa-trash mr-1"></i> Hapus
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Pilih User</label>
                    <select name="members[${index}][user_id]" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        ${userOptions}
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Jabatan</label>
                    <input type="text" name="members[${index}][position]" required
                           value="${position}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Contoh: Staff, Supervisor, dll">
                </div>

                <div class="md:col-span-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="members[${index}][is_primary]" value="1"
                               ${isPrimary ? 'checked' : ''}
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <span class="ml-2 text-sm text-gray-700">Departemen Utama</span>
                    </label>
                </div>
            </div>
        `;

        membersContainer.appendChild(memberDiv);

        // Add remove functionality
        const removeBtn = memberDiv.querySelector('.remove-member-btn');
        removeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            memberDiv.remove();
            updateMembersDisplay();
        });
    }

    // Update members display
    function updateMembersDisplay() {
        const memberItems = document.querySelectorAll('.member-item');
        
        if (memberItems.length === 0) {
            noMembersMessage.classList.remove('hidden');
        } else {
            noMembersMessage.classList.add('hidden');
            
            // Update member numbers
            memberItems.forEach((item, index) => {
                const header = item.querySelector('h4');
                if (header) {
                    header.textContent = `Anggota ${index + 1}`;
                }
            });
        }
    }

    // Form validation
    document.getElementById('departmentForm').addEventListener('submit', function(e) {
        const members = document.querySelectorAll('.member-item');
        const selectedUsers = new Set();
        let hasDuplicate = false;

        members.forEach(member => {
            const userSelect = member.querySelector('select[name*="[user_id]"]');
            const userId = userSelect.value;

            if (userId) {
                if (selectedUsers.has(userId)) {
                    hasDuplicate = true;
                    userSelect.classList.add('border-red-500');
                } else {
                    selectedUsers.add(userId);
                    userSelect.classList.remove('border-red-500');
                }
            }
        });

        if (hasDuplicate) {
            e.preventDefault();
            alert('Tidak boleh memilih user yang sama untuk beberapa anggota!');
            return false;
        }
    });
</script>
