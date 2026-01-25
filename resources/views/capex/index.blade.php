@extends('layouts.app')

@section('title', 'Master CapEx ID')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Master CapEx ID</h1>
            <p class="text-sm text-gray-600">Kelola ID Number CapEx untuk alokasi budget</p>
        </div>
        <a href="{{ route('capex.create') }}" 
            class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg text-sm transition-colors">
            <i class="fas fa-plus mr-2"></i>Tambah CapEx ID
        </a>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-gray-700 mb-1">Cari</label>
                <input type="text" 
                    name="search" 
                    value="{{ request('search') }}"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                    placeholder="Kode atau deskripsi...">
            </div>
            <div class="w-32">
                <label class="block text-xs font-medium text-gray-700 mb-1">Tahun</label>
                <select name="year" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua</option>
                    @for($y = date('Y') + 1; $y >= date('Y') - 5; $y--)
                        <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div class="w-32">
                <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Aktif</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Non-Aktif</option>
                </select>
            </div>
            <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-md text-sm">
                <i class="fas fa-filter mr-1"></i>Filter
            </button>
        </form>
    </div>

    {{-- Stats Summary --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        @php
            $currentYear = date('Y');
            $thisYearCapex = $capexIds->where('fiscal_year', $currentYear);
            $totalBudget = $thisYearCapex->sum('budget_amount');
            $totalAllocated = $thisYearCapex->sum(function($c) { return $c->budget_amount - $c->getRemainingBudget(); });
            $totalRemaining = $totalBudget - $totalAllocated;
            $utilizationPercent = $totalBudget > 0 ? round($totalAllocated / $totalBudget * 100, 1) : 0;
        @endphp
        
        <div class="bg-blue-50 rounded-lg p-4 border border-blue-100">
            <div class="text-xs text-blue-600 font-medium">Total CapEx ID</div>
            <div class="text-2xl font-bold text-blue-900">{{ $capexIds->count() }}</div>
        </div>
        <div class="bg-green-50 rounded-lg p-4 border border-green-100">
            <div class="text-xs text-green-600 font-medium">Total Budget {{ $currentYear }}</div>
            <div class="text-lg font-bold text-green-900">Rp {{ number_format($totalBudget, 0, ',', '.') }}</div>
        </div>
        <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-100">
            <div class="text-xs text-yellow-600 font-medium">Teralokasi</div>
            <div class="text-lg font-bold text-yellow-900">Rp {{ number_format($totalAllocated, 0, ',', '.') }}</div>
        </div>
        <div class="bg-purple-50 rounded-lg p-4 border border-purple-100">
            <div class="text-xs text-purple-600 font-medium">Utilisasi</div>
            <div class="text-2xl font-bold text-purple-900">{{ $utilizationPercent }}%</div>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Kode</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Deskripsi</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Tahun</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Budget</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Sisa</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Utilisasi</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($capexIds as $capex)
                @php
                    $remaining = $capex->getRemainingBudget();
                    $used = $capex->budget_amount - $remaining;
                    $utilization = $capex->budget_amount > 0 ? round($used / $capex->budget_amount * 100, 1) : 0;
                    $barColor = $utilization > 90 ? 'bg-red-500' : ($utilization > 70 ? 'bg-yellow-500' : 'bg-green-500');
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <span class="font-mono text-sm font-semibold text-blue-600">{{ $capex->code }}</span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-900">{{ Str::limit($capex->description, 50) }}</td>
                    <td class="px-4 py-3 text-center text-sm text-gray-600">{{ $capex->fiscal_year }}</td>
                    <td class="px-4 py-3 text-right text-sm font-medium text-gray-900">
                        Rp {{ number_format($capex->budget_amount, 0, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 text-right text-sm font-medium {{ $remaining > 0 ? 'text-green-600' : 'text-red-600' }}">
                        Rp {{ number_format($remaining, 0, ',', '.') }}
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 bg-gray-200 rounded-full h-2">
                                <div class="{{ $barColor }} h-2 rounded-full" style="width: {{ $utilization }}%"></div>
                            </div>
                            <span class="text-xs font-medium text-gray-600 w-12 text-right">{{ $utilization }}%</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($capex->is_active)
                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                Aktif
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-600">
                                Non-Aktif
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('capex.edit', $capex) }}" 
                                class="text-blue-600 hover:text-blue-800" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('capex.destroy', $capex) }}" method="POST" class="inline" 
                                onsubmit="return confirm('Yakin hapus CapEx ID ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-3xl mb-2"></i>
                        <p>Belum ada data CapEx ID</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($capexIds->hasPages())
    <div class="mt-4">
        {{ $capexIds->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
