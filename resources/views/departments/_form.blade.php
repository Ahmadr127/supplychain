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
                @php
                    $deptOptions = $departments->map(fn($d) => ['id' => $d->id, 'label' => $d->name . ' (' . $d->code . ')']);
                @endphp
                <x-searchable-select 
                    name="parent_id" 
                    :options="$deptOptions" 
                    :selected="old('parent_id', $department->parent_id ?? '')"
                    placeholder="Pilih Parent Department (Opsional)"
                    width="w-full" />
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
                @php
                    $userOptions = $users->map(fn($u) => ['id' => $u->id, 'label' => $u->name . ' (' . ($u->role->display_name ?? 'No Role') . ')']);
                @endphp
                <x-searchable-select 
                    name="manager_id" 
                    :options="$userOptions" 
                    :selected="old('manager_id', $department->manager_id ?? '')"
                    placeholder="Pilih Manager (Opsional)"
                    width="w-full" />
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

    // User data from PHP transformed for searchable select
    const usersOptions = @json($users->map(fn($u) => ['id' => $u->id, 'label' => $u->name . ' (' . ($u->role->display_name ?? 'No Role') . ')']));
    const oldMembersData = @json(old('members', []));

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        // Prioritize old input so selected values are preserved after validation errors.
        if (oldMembersData && Object.keys(oldMembersData).length > 0) {
            loadOldMembers();
            return;
        }

        @if(isset($department) && $department->users)
        loadExistingMembers();
        @endif
    });

    // Load members from old() input after validation failure
    function loadOldMembers() {
        const members = Array.isArray(oldMembersData)
            ? oldMembersData
            : Object.values(oldMembersData);

        members.forEach((member, index) => {
            addMemberToDOM(member, index);
            memberCount++;
        });

        updateMembersDisplay();
    }

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

    // Helper to render searchable select in JS
    function renderSearchableSelect(name, options, selectedId, placeholder = 'Pilih...') {
        const found = options.find(o => o.id == selectedId);
        const selectedLabel = found ? found.label : placeholder;
        const optionsJson = JSON.stringify(options).replace(/"/g, '&quot;');
        
        return `
            <div x-data="{
                open: false,
                search: '',
                selectedId: '${selectedId}',
                selectedLabel: '${selectedLabel}',
                options: ${optionsJson},
                get filtered() {
                    if (!this.search) return this.options;
                    const q = this.search.toLowerCase();
                    return this.options.filter(o => o.label.toLowerCase().includes(q));
                },
                select(id, label) {
                    this.selectedId = id;
                    this.selectedLabel = label;
                    this.open = false;
                    this.search = '';
                }
            }" @click.outside="open = false" class="relative">
                <input type="hidden" name="${name}" :value="selectedId">
                <button type="button" @click="open = !open" 
                    class="w-full flex items-center justify-between px-3 py-2 text-sm border border-gray-300 rounded-md bg-white hover:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                    <span class="truncate text-gray-700" x-text="selectedLabel"></span>
                    <i class="fas fa-chevron-down text-gray-400 text-xs ml-2 transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                </button>
                <div x-show="open" style="display:none;" class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg">
                    <div class="p-2 border-b border-gray-100">
                        <div class="relative">
                            <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                            <input type="text" x-model="search" placeholder="Cari..." x-ref="searchInput"
                                @keydown.escape="open = false" x-effect="if(open) $nextTick(() => $refs.searchInput.focus())"
                                class="w-full pl-7 pr-3 py-1.5 text-sm border border-gray-200 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                        </div>
                    </div>
                    <ul class="max-h-56 overflow-y-auto py-1">
                        <li @click="select('', '${placeholder}')" class="px-3 py-2 text-sm cursor-pointer hover:bg-blue-50 flex items-center gap-2" :class="selectedId === '' ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-700'">
                            <i class="fas fa-check text-blue-600 text-xs w-3" x-show="selectedId === ''"></i>
                            <span :class="selectedId === '' ? '' : 'ml-5'">${placeholder}</span>
                        </li>
                        <template x-for="opt in filtered" :key="opt.id">
                            <li @click="select(opt.id, opt.label)" class="px-3 py-2 text-sm cursor-pointer hover:bg-blue-50 flex items-center gap-2" :class="selectedId == opt.id ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-700'">
                                <i class="fas fa-check text-blue-600 text-xs w-3" x-show="selectedId == opt.id"></i>
                                <span :class="selectedId == opt.id ? '' : 'ml-5'" x-text="opt.label"></span>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>
        `;
    }

    // Add member to DOM
    function addMemberToDOM(memberData, index) {
        const memberDiv = document.createElement('div');
        memberDiv.className = 'border-b border-gray-200 p-6 member-item bg-white';
        memberDiv.setAttribute('data-member-index', index);

        const userId = memberData?.user_id ?? memberData?.id ?? '';
        const position = memberData?.position ?? memberData?.pivot?.position ?? '';
        const isPrimary =
            (memberData?.is_primary ?? memberData?.pivot?.is_primary ?? false) == true ||
            (memberData?.is_primary ?? memberData?.pivot?.is_primary ?? false) == 1 ||
            (memberData?.is_primary ?? memberData?.pivot?.is_primary ?? false) === '1';

        const searchableSelect = renderSearchableSelect(`members[${index}][user_id]`, usersOptions, userId, '-- Pilih User --');

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
                    ${searchableSelect}
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
