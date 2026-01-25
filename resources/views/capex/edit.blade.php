@extends('layouts.app')

@section('title', 'Edit CapEx ID')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-2xl">
    {{-- Header --}}
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('capex.index') }}" class="text-gray-600 hover:text-gray-800">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit CapEx ID</h1>
            <p class="text-sm text-gray-600">{{ $capex->code }}</p>
        </div>
    </div>

    {{-- Budget Summary --}}
    @php
        $remaining = $capex->getRemainingBudget();
        $used = $capex->budget_amount - $remaining;
        $utilization = $capex->budget_amount > 0 ? round($used / $capex->budget_amount * 100, 1) : 0;
    @endphp
    <div class="bg-blue-50 rounded-lg border border-blue-200 p-4 mb-6">
        <div class="grid grid-cols-3 gap-4 text-center">
            <div>
                <div class="text-xs text-blue-600 font-medium">Total Budget</div>
                <div class="text-lg font-bold text-blue-900">Rp {{ number_format($capex->budget_amount, 0, ',', '.') }}</div>
            </div>
            <div>
                <div class="text-xs text-green-600 font-medium">Sisa Budget</div>
                <div class="text-lg font-bold text-green-700">Rp {{ number_format($remaining, 0, ',', '.') }}</div>
            </div>
            <div>
                <div class="text-xs text-purple-600 font-medium">Utilisasi</div>
                <div class="text-lg font-bold text-purple-900">{{ $utilization }}%</div>
            </div>
        </div>
        <div class="mt-3">
            <div class="w-full bg-blue-200 rounded-full h-2">
                <div class="{{ $utilization > 90 ? 'bg-red-500' : ($utilization > 70 ? 'bg-yellow-500' : 'bg-blue-600') }} h-2 rounded-full" 
                    style="width: {{ $utilization }}%"></div>
            </div>
        </div>
    </div>

    {{-- Form --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <form action="{{ route('capex.update', $capex) }}" method="POST">
            @csrf
            @method('PUT')
            
            {{-- Kode CapEx --}}
            <div class="mb-4">
                <label for="code" class="block text-sm font-medium text-gray-700 mb-1">
                    Kode CapEx <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                    id="code" 
                    name="code" 
                    value="{{ old('code', $capex->code) }}"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('code') border-red-500 @enderror"
                    placeholder="Contoh: CAPEX-2026-001">
                @error('code')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
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
                    placeholder="Deskripsi penggunaan CapEx ID...">{{ old('description', $capex->description) }}</textarea>
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
                        @for($y = date('Y') + 1; $y >= date('Y') - 5; $y--)
                            <option value="{{ $y }}" {{ old('fiscal_year', $capex->fiscal_year) == $y ? 'selected' : '' }}>{{ $y }}</option>
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
                        <option value="1" {{ old('is_active', $capex->is_active) == 1 ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ old('is_active', $capex->is_active) == 0 ? 'selected' : '' }}>Non-Aktif</option>
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
                        value="{{ old('budget_amount', number_format($capex->budget_amount, 0, '', '.')) }}"
                        required
                        class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('budget_amount') border-red-500 @enderror"
                        placeholder="1.000.000.000"
                        oninput="formatCurrency(this)">
                </div>
                @error('budget_amount')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
                @if($used > 0)
                    <p class="mt-1 text-xs text-yellow-600">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        Sudah ada Rp {{ number_format($used, 0, ',', '.') }} teralokasi. Budget baru minimal harus sama dengan nilai teralokasi.
                    </p>
                @endif
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
                        <option value="{{ $dept->id }}" {{ old('department_id', $capex->department_id) == $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Actions --}}
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                <a href="{{ route('capex.index') }}" 
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors">
                    Batal
                </a>
                <button type="submit" 
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors">
                    <i class="fas fa-save mr-1"></i>Update
                </button>
            </div>
        </form>
    </div>

    {{-- Allocation History --}}
    @if($capex->allocations->count() > 0)
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mt-6">
        <div class="px-4 py-3 border-b border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900">Riwayat Alokasi</h3>
        </div>
        <div class="divide-y divide-gray-200">
            @foreach($capex->allocations->take(10) as $allocation)
            <div class="px-4 py-3 flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-gray-900">
                        {{ $allocation->approvalRequest->request_number ?? 'N/A' }}
                    </div>
                    <div class="text-xs text-gray-500">
                        {{ $allocation->created_at->format('d/m/Y H:i') }}
                    </div>
                </div>
                <div class="text-sm font-semibold text-red-600">
                    - Rp {{ number_format($allocation->allocated_amount, 0, ',', '.') }}
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>

<script>
function formatCurrency(input) {
    let value = input.value.replace(/\D/g, '');
    value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    input.value = value;
}
</script>
@endsection
