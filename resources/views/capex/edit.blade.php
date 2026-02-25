@extends('layouts.app')

@section('title', 'Edit CapEx')

@section('content')
<div class="max-w-2xl">
    {{-- Header --}}
        <div class="flex items-center gap-2 text-sm text-gray-600 mb-4">
            <a href="{{ route('capex.index') }}" class="hover:text-blue-600">Master CapEx</a>
            <span>/</span>
            <a href="{{ route('capex.show', $capex) }}" class="hover:text-blue-600">Detail</a>
            <span>/</span>
            <span>Edit</span>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h1 class="text-xl font-bold text-gray-900">Edit CapEx Header</h1>
                <p class="text-sm text-gray-600">{{ $capex->department->name }} - {{ $capex->fiscal_year }}</p>
            </div>
            
            <form action="{{ route('capex.update', $capex) }}" method="POST" class="p-6">
                @csrf
                @method('PUT')
                
                <div class="space-y-6">
                    {{-- Read Only Info --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Departemen</label>
                            <div class="text-gray-900 font-medium">{{ $capex->department->name }}</div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tahun Anggaran</label>
                            <input type="number" name="fiscal_year" required min="2020" max="2100" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 {{ $capex->items()->exists() ? 'bg-gray-100 cursor-not-allowed' : '' }}"
                                value="{{ old('fiscal_year', $capex->fiscal_year) }}"
                                {{ $capex->items()->exists() ? 'readonly' : '' }}>
                            @if($capex->items()->exists())
                                <p class="text-xs text-gray-500 mt-1">Tahun tidak dapat diubah karena sudah ada item.</p>
                            @endif
                            @error('fiscal_year')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Status --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <option value="draft" {{ old('status', $capex->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="active" {{ old('status', $capex->status) == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="closed" {{ old('status', $capex->status) == 'closed' ? 'selected' : '' }}>Closed</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">
                            * Draft: Belum bisa digunakan untuk approval request.<br>
                            * Active: Bisa digunakan.<br>
                            * Closed: Tidak bisa digunakan lagi.
                        </p>
                        @error('status')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                        <textarea name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">{{ old('notes', $capex->notes) }}</textarea>
                        @error('notes')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-8 flex justify-end gap-3">
                    <a href="{{ route('capex.show', $capex) }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 font-medium">Batal</a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-medium">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
</div>
@endsection
