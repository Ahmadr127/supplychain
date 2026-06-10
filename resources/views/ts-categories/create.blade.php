@extends('layouts.app')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Tambah Kategori Technical Support
    </h2>
@endsection

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-visible shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200 rounded-lg">
                
                <form action="{{ route('ts-categories.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700">Nama Kategori <span class="text-red-500">*</span></label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required
                               class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4">
                        <label for="description" class="block text-sm font-medium text-gray-700">Deskripsi</label>
                        <textarea name="description" id="description" rows="3"
                                  class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">{{ old('description') }}</textarea>
                        @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div x-data="{ approverType: '{{ old('ts_approver_type', '') }}' }">
                        <div class="mb-4">
                            <label for="ts_approver_type" class="block text-sm font-medium text-gray-700">Tipe Penanggung Jawab <span class="text-red-500">*</span></label>
                            <select name="ts_approver_type" id="ts_approver_type" x-model="approverType" required
                                    class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="">-- Pilih Tipe --</option>
                                <option value="user">User Spesifik</option>
                                <option value="role">Role</option>
                                <option value="department_manager">Manager Departemen (Otomatis dari requester)</option>
                            </select>
                            @error('ts_approver_type') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <!-- Specific User Select -->
                        <div x-show="approverType === 'user'" class="mb-4" style="display: none;">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Pilih User <span class="text-red-500">*</span></label>
                            <div x-data="{
                                open: false,
                                search: '',
                                selectedId: '{{ old('ts_approver_id') }}',
                                selectedLabel: '-- Pilih User --',
                                options: @js($users->map(fn($u) => ['id' => $u->id, 'label' => $u->name . ' (' . ($u->role->display_name ?? 'No Role') . ')'])),
                                init() {
                                    const found = this.options.find(o => o.id == this.selectedId);
                                    if(found) this.selectedLabel = found.label;
                                },
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
                                <input type="hidden" name="ts_approver_id" :value="selectedId">
                                <button type="button" @click="open = !open" 
                                    class="w-full flex items-center justify-between px-3 py-2 text-sm border border-gray-300 rounded-md bg-white hover:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                                    <span class="truncate text-gray-700" x-text="selectedLabel"></span>
                                    <i class="fas fa-chevron-down text-gray-400 text-xs ml-2 transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                                </button>
                                <div x-show="open" style="display:none;" class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg">
                                    <div class="p-2 border-b border-gray-100">
                                        <div class="relative">
                                            <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                                            <input type="text" x-model="search" placeholder="Cari User..." x-ref="searchInput"
                                                @keydown.escape="open = false" x-effect="if(open) $nextTick(() => $refs.searchInput.focus())"
                                                class="w-full pl-7 pr-3 py-1.5 text-sm border border-gray-200 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                                        </div>
                                    </div>
                                    <ul class="max-h-56 overflow-y-auto py-1">
                                        <li @click="select('', '-- Pilih User --')" class="px-3 py-2 text-sm cursor-pointer hover:bg-blue-50 flex items-center gap-2" :class="selectedId === '' ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-700'">
                                            <i class="fas fa-check text-blue-600 text-xs w-3" x-show="selectedId === ''"></i>
                                            <span :class="selectedId === '' ? '' : 'ml-5'">-- Pilih User --</span>
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
                            @error('ts_approver_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <!-- Role Select -->
                        <div x-show="approverType === 'role'" class="mb-4" style="display: none;">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Role <span class="text-red-500">*</span></label>
                            <div x-data="{
                                open: false,
                                search: '',
                                selectedId: '{{ old('ts_approver_role_id') }}',
                                selectedLabel: '-- Pilih Role --',
                                options: @js($roles->map(fn($r) => ['id' => $r->id, 'label' => $r->display_name])),
                                init() {
                                    const found = this.options.find(o => o.id == this.selectedId);
                                    if(found) this.selectedLabel = found.label;
                                },
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
                                <input type="hidden" name="ts_approver_role_id" :value="selectedId">
                                <button type="button" @click="open = !open" 
                                    class="w-full flex items-center justify-between px-3 py-2 text-sm border border-gray-300 rounded-md bg-white hover:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                                    <span class="truncate text-gray-700" x-text="selectedLabel"></span>
                                    <i class="fas fa-chevron-down text-gray-400 text-xs ml-2 transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                                </button>
                                <div x-show="open" style="display:none;" class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg">
                                    <div class="p-2 border-b border-gray-100">
                                        <div class="relative">
                                            <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                                            <input type="text" x-model="search" placeholder="Cari Role..." x-ref="searchInput"
                                                @keydown.escape="open = false" x-effect="if(open) $nextTick(() => $refs.searchInput.focus())"
                                                class="w-full pl-7 pr-3 py-1.5 text-sm border border-gray-200 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                                        </div>
                                    </div>
                                    <ul class="max-h-56 overflow-y-auto py-1">
                                        <li @click="select('', '-- Pilih Role --')" class="px-3 py-2 text-sm cursor-pointer hover:bg-blue-50 flex items-center gap-2" :class="selectedId === '' ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-700'">
                                            <i class="fas fa-check text-blue-600 text-xs w-3" x-show="selectedId === ''"></i>
                                            <span :class="selectedId === '' ? '' : 'ml-5'">-- Pilih Role --</span>
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
                            @error('ts_approver_role_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="mb-6">
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input id="is_active" name="is_active" type="checkbox" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                                       class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="is_active" class="font-medium text-gray-700">Aktif</label>
                                <p class="text-gray-500">Kategori ini bisa dipilih saat pembuatan request baru.</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2">
                        <a href="{{ route('ts-categories.index') }}" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 font-bold py-2 px-4 rounded text-sm transition-colors">
                            Batal
                        </a>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm transition-colors">
                            Simpan
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>
@endsection
