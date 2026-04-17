{{-- Form Partial for Users --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    {{-- Nama Lengkap --}}
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
        <input type="text" name="name" id="name" value="{{ old('name', $user->name ?? '') }}" required
               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm">
        @error('name')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    {{-- NIK --}}
    <div>
        <label for="nik" class="block text-sm font-medium text-gray-700">NIK</label>
        <input type="text" name="nik" id="nik" value="{{ old('nik', $user->nik ?? '') }}"
               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm">
        @error('nik')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    {{-- Username --}}
    <div>
        <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
        <input type="text" name="username" id="username" value="{{ old('username', $user->username ?? '') }}" required
               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm">
        @error('username')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    {{-- Email --}}
    <div>
        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
        <input type="email" name="email" id="email" value="{{ old('email', $user->email ?? '') }}" required
               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm">
        @error('email')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    {{-- Password --}}
    <div>
        <label for="password" class="block text-sm font-medium text-gray-700">
            {{ isset($user) ? 'Password Baru (Opsional)' : 'Password' }}
        </label>
        <input type="password" name="password" id="password" {{ isset($user) ? '' : 'required' }}
               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm"
               placeholder="{{ isset($user) ? 'Kosongkan jika tidak ingin mengubah password' : '' }}">
        @error('password')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    {{-- Confirm Password --}}
    <div>
        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
            {{ isset($user) ? 'Konfirmasi Password Baru' : 'Konfirmasi Password' }}
        </label>
        <input type="password" name="password_confirmation" id="password_confirmation" {{ isset($user) ? '' : 'required' }}
               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500 sm:text-sm"
               placeholder="{{ isset($user) ? 'Konfirmasi password baru' : '' }}">
    </div>

    {{-- Role --}}
    <div class="md:col-span-2">
        <label for="role_id" class="block text-sm font-medium text-gray-700 mb-2">Role</label>
        @php
            $roleOptions = $roles->map(fn($r) => ['id' => $r->id, 'label' => $r->display_name . ' - ' . $r->description]);
        @endphp
        <x-searchable-select 
            name="role_id" 
            :options="$roleOptions" 
            :selected="old('role_id', $user->role_id ?? '')"
            placeholder="Pilih Role"
            width="w-full" />
        @error('role_id')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    {{-- Submit Button --}}
    <div class="md:col-span-2 flex justify-end">
        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
            {{ isset($user) ? 'Update User' : 'Simpan User' }}
        </button>
    </div>
</div>
