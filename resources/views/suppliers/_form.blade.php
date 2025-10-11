@php($edit = isset($supplier))
<div class="grid grid-cols-1 md:grid-cols-2 gap-3">
    <div>
        <label class="block text-sm font-medium mb-1">Nama <span class="text-red-600">*</span></label>
        <input name="name" value="{{ old('name', $supplier->name ?? '') }}" class="w-full px-3 py-2 border rounded" required>
        @error('name')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Kode <span class="text-red-600">*</span></label>
        <input name="code" value="{{ old('code', $supplier->code ?? '') }}" class="w-full px-3 py-2 border rounded" required>
        @error('code')<div class="text-red-600 text-sm">{{ $message }}</div>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Email</label>
        <input type="email" name="email" value="{{ old('email', $supplier->email ?? '') }}" class="w-full px-3 py-2 border rounded">
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Telepon</label>
        <input name="phone" value="{{ old('phone', $supplier->phone ?? '') }}" class="w-full px-3 py-2 border rounded">
    </div>
    <div class="md:col-span-2">
        <label class="block text-sm font-medium mb-1">Alamat</label>
        <textarea name="address" rows="2" class="w-full px-3 py-2 border rounded">{{ old('address', $supplier->address ?? '') }}</textarea>
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Aktif</label>
        <select name="is_active" class="w-full px-3 py-2 border rounded">
            <option value="1" @selected(old('is_active', ($supplier->is_active ?? true))==true)>Aktif</option>
            <option value="0" @selected(old('is_active', ($supplier->is_active ?? true))==false)>Nonaktif</option>
        </select>
    </div>
    <div class="md:col-span-2">
        <label class="block text-sm font-medium mb-1">Catatan</label>
        <textarea name="notes" rows="2" class="w-full px-3 py-2 border rounded">{{ old('notes', $supplier->notes ?? '') }}</textarea>
    </div>
</div>
