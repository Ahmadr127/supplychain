@extends('layouts.app')

@section('title', 'Buat CapEx Baru')

@section('content')
<div class="max-w-2xl">
    {{-- Header --}}
        <div class="flex items-center gap-2 text-sm text-gray-600 mb-4">
            <a href="{{ route('capex.index') }}" class="hover:text-blue-600">Master CapEx</a>
            <span>/</span>
            <span>Buat Baru</span>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h1 class="text-xl font-bold text-gray-900">Buat CapEx Baru</h1>
                <p class="text-sm text-gray-600">Buat header anggaran CapEx untuk departemen dan tahun tertentu.</p>
            </div>
            
            <form action="{{ route('capex.store') }}" method="POST" class="p-6">
                @csrf
                
                <div class="space-y-6">
                    {{-- Department --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Departemen</label>
                        <select name="department_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Pilih Departemen</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }} ({{ $dept->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('department_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Fiscal Year --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tahun Anggaran</label>
                        <input type="number" name="fiscal_year" required min="2020" max="2100" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                            value="{{ old('fiscal_year', date('Y')) }}">
                        @error('fiscal_year')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan (Opsional)</label>
                        <textarea name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" placeholder="Contoh: Anggaran CapEx Tahunan">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-8 flex justify-end gap-3">
                    <a href="{{ route('capex.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 font-medium">Batal</a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-medium">
                        Simpan & Lanjut ke Item
                    </button>
                </div>
            </form>
        </div>
</div>
@endsection
