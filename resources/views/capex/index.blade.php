@extends('layouts.app')

@section('title', 'Master CapEx')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Master CapEx</h1>
            <p class="text-sm text-gray-600">Kelola Anggaran CapEx per Departemen</p>
        </div>
        <a href="{{ route('capex.create') }}" 
            class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg text-sm transition-colors">
            <i class="fas fa-plus mr-2"></i>Buat CapEx Baru
        </a>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div class="w-64">
                <label class="block text-xs font-medium text-gray-700 mb-1">Departemen</label>
                <select name="department_id" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Departemen</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }} ({{ $dept->code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="w-32">
                <label class="block text-xs font-medium text-gray-700 mb-1">Tahun</label>
                <select name="year" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua</option>
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-32">
                <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Closed</option>
                </select>
            </div>
            <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-md text-sm">
                <i class="fas fa-filter mr-1"></i>Filter
            </button>
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Departemen</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Tahun</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Jml Item</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Total Budget</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Terpakai</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Utilisasi</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($capexes as $capex)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-900">{{ $capex->department->name }}</div>
                        <div class="text-xs text-gray-500">{{ $capex->department->code }}</div>
                    </td>
                    <td class="px-4 py-3 text-center text-sm text-gray-600">{{ $capex->fiscal_year }}</td>
                    <td class="px-4 py-3 text-center text-sm text-gray-600">{{ $capex->items_count }}</td>
                    <td class="px-4 py-3 text-right text-sm font-medium text-gray-900">
                        Rp {{ number_format($capex->total_budget, 0, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 text-right text-sm font-medium text-gray-600">
                        Rp {{ number_format($capex->total_used, 0, ',', '.') }}
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $capex->utilization_percent }}%"></div>
                            </div>
                            <span class="text-xs font-medium text-gray-600 w-10 text-right">{{ $capex->utilization_percent }}%</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center">
                        @php
                            $statusColors = [
                                'active' => 'bg-green-100 text-green-800',
                                'draft' => 'bg-gray-100 text-gray-800',
                                'closed' => 'bg-red-100 text-red-800',
                            ];
                        @endphp
                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full {{ $statusColors[$capex->status] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ ucfirst($capex->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('capex.show', $capex) }}" 
                                class="text-blue-600 hover:text-blue-800" title="Lihat Detail & Item">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('capex.edit', $capex) }}" 
                                class="text-yellow-600 hover:text-yellow-800" title="Edit Header">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-folder-open text-3xl mb-2"></i>
                        <p>Belum ada data CapEx</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($capexes->hasPages())
    <div class="mt-4">
        {{ $capexes->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
