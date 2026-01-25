@extends('layouts.app')

@section('title', 'Tambah CapEx ID')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-2xl">
    {{-- Header --}}
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('capex.index') }}" class="text-gray-600 hover:text-gray-800">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Tambah CapEx ID</h1>
            <p class="text-sm text-gray-600">Buat ID Number CapEx baru untuk alokasi budget</p>
        </div>
    </div>

    {{-- Form --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <form action="{{ route('capex.store') }}" method="POST">
            @csrf
            
            {{-- Kode CapEx --}}
            <div class="mb-4">
                <label for="code" class="block text-sm font-medium text-gray-700 mb-1">
                    Kode CapEx <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                    id="code" 
                    name="code" 
                    value="{{ old('code') }}"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('code') border-red-500 @enderror"
                    placeholder="Contoh: CAPEX-2026-001">
                @error('code')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">Kode unik untuk identifikasi CapEx ID</p>
            </div>

            {{-- Deskripsi --}}
            <div class="mb-4">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                    Deskripsi <span class="text-red-500">*</span>
                </label>
                <textarea 
                    id="description" 
                    name="description" 
                    required
                    rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('description') border-red-500 @enderror"
                    placeholder="Deskripsi penggunaan CapEx ID...">{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                {{-- Tahun Fiskal --}}
                <div>
                    <label for="fiscal_year" class="block text-sm font-medium text-gray-700 mb-1">
                        Tahun Fiskal <span class="text-red-500">*</span>
                    </label>
                    <select 
                        id="fiscal_year" 
                        name="fiscal_year"
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('fiscal_year') border-red-500 @enderror">
                        @for($y = date('Y') + 1; $y >= date('Y') - 2; $y--)
                            <option value="{{ $y }}" {{ old('fiscal_year', date('Y')) == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                    @error('fiscal_year')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Status --}}
                <div>
                    <label for="is_active" class="block text-sm font-medium text-gray-700 mb-1">
                        Status
                    </label>
                    <select 
                        id="is_active" 
                        name="is_active"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="1" {{ old('is_active', 1) == 1 ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ old('is_active') == 0 ? 'selected' : '' }}>Non-Aktif</option>
                    </select>
                </div>
            </div>

            {{-- Budget Amount --}}
            <div class="mb-6">
                <label for="budget_amount" class="block text-sm font-medium text-gray-700 mb-1">
                    Jumlah Budget <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute left-3 top-2 text-gray-500">Rp</span>
                    <input type="text" 
                        id="budget_amount" 
                        name="budget_amount" 
                        value="{{ old('budget_amount') }}"
                        required
                        class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('budget_amount') border-red-500 @enderror"
                        placeholder="1.000.000.000"
                        oninput="formatCurrency(this)">
                </div>
                @error('budget_amount')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">Total budget yang dialokasikan untuk CapEx ID ini</p>
            </div>

            {{-- Department (Optional) --}}
            <div class="mb-6">
                <label for="department_id" class="block text-sm font-medium text-gray-700 mb-1">
                    Department (Opsional)
                </label>
                <select 
                    id="department_id" 
                    name="department_id"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">-- Semua Department --</option>
                    @foreach(\App\Models\Department::orderBy('name')->get() as $dept)
                        <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500">Jika dipilih, CapEx ID ini hanya bisa digunakan oleh department terkait</p>
            </div>

            {{-- Actions --}}
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                <a href="{{ route('capex.index') }}" 
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors">
                    Batal
                </a>
                <button type="submit" 
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors">
                    <i class="fas fa-save mr-1"></i>Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function formatCurrency(input) {
    // Remove non-digits
    let value = input.value.replace(/\D/g, '');
    // Format with dots as thousand separator
    value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    input.value = value;
}
</script>
@endsection
