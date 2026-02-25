@extends('layouts.app')
@section('title', 'CapEx Unit Saya')
@section('content')
<div>

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">CapEx Unit Saya</h2>
            @if($capex)
                <p class="text-gray-500 text-sm mt-1">
                    {{ $capex->department->name }} — Tahun Anggaran {{ $year }}
                </p>
            @else
                <p class="text-gray-500 text-sm mt-1">Belum ada data CapEx untuk tahun ini.</p>
            @endif
        </div>
        <div class="flex items-center gap-2">
            {{-- Year selector --}}
            <form method="GET" class="flex items-center gap-2">
                <select name="year" onchange="this.form.submit()"
                    class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-white">
                    @foreach($availableYears as $y)
                        <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                    @if(!$availableYears->contains(date('Y')))
                        <option value="{{ date('Y') }}" {{ date('Y') == $year ? 'selected' : '' }}>{{ date('Y') }}</option>
                    @endif
                </select>
            </form>

            @if($capex)
            <a href="{{ route('capex.import.upload-unit', $capex) }}"
               class="px-4 py-2 text-sm bg-blue-50 text-blue-700 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors">
                <i class="fas fa-file-import mr-1"></i>Import Excel
            </a>
            @endif
            <a href="{{ route('unit.capex.create', ['year' => $year]) }}"
               class="px-4 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                <i class="fas fa-plus mr-1"></i>Tambah Item
            </a>
        </div>
    </div>

    {{-- Session alerts --}}
    @if(session('success'))
        <div class="mb-4 bg-green-50 border border-green-200 rounded-xl p-4 flex items-center gap-3">
            <i class="fas fa-check-circle text-green-500"></i>
            <p class="text-sm text-green-700">{{ session('success') }}</p>
        </div>
    @endif
    @if($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 rounded-xl p-4">
            @foreach($errors->all() as $err)
                <p class="text-sm text-red-600"><i class="fas fa-exclamation-circle mr-1"></i>{{ $err }}</p>
            @endforeach
        </div>
    @endif

    {{-- Budget Summary (if capex exists) --}}
    @if($capex)
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-xl font-bold text-gray-800">{{ $items->total() }}</p>
            <p class="text-xs text-gray-400 mt-1">Total Item</p>
        </div>
        <div class="bg-blue-50 rounded-xl border border-blue-200 p-4 text-center">
            <p class="text-xl font-bold text-blue-700">Rp {{ number_format($capex->total_budget, 0, ',', '.') }}</p>
            <p class="text-xs text-blue-600 mt-1">Total Anggaran</p>
        </div>
        <div class="bg-green-50 rounded-xl border border-green-200 p-4 text-center">
            <p class="text-xl font-bold text-green-700">Rp {{ number_format($capex->total_used, 0, ',', '.') }}</p>
            <p class="text-xs text-green-600 mt-1">Terpakai</p>
        </div>
        <div class="bg-gray-50 rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-xl font-bold text-gray-700">Rp {{ number_format($capex->remaining_budget, 0, ',', '.') }}</p>
            <p class="text-xs text-gray-400 mt-1">Sisa Anggaran</p>
        </div>
    </div>
    @endif

    {{-- Items Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        {{-- Search --}}
        <div class="p-3 border-b border-gray-100">
            <form method="GET" class="flex items-center gap-2">
                <input type="hidden" name="year" value="{{ $year }}">
                <div class="relative flex-1 max-w-sm">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari ID, nama item, PIC..."
                        class="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                <button type="submit" class="px-3 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700">
                    <i class="fas fa-search"></i>
                </button>
                @if(request('search'))
                <a href="{{ route('unit.capex.index', ['year' => $year]) }}" class="px-3 py-2 bg-gray-200 text-gray-700 text-sm rounded-lg hover:bg-gray-300">
                    <i class="fas fa-times"></i>
                </a>
                @endif
            </form>
        </div>
        @if($items->count())
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
                    <tr>
                        <th class="px-4 py-3 text-center border-b border-gray-200 w-10">No.</th>
                        <th class="px-4 py-3 text-left border-b border-gray-200">ID CapEx</th>
                        <th class="px-4 py-3 text-left border-b border-gray-200">Nama Item</th>
                        <th class="px-4 py-3 text-center border-b border-gray-200">Tipe</th>
                        <th class="px-4 py-3 text-center border-b border-gray-200">Prioritas</th>
                        <th class="px-4 py-3 text-left border-b border-gray-200">Bulan</th>
                        <th class="px-4 py-3 text-right border-b border-gray-200">Amount/Thn</th>
                        <th class="px-4 py-3 text-right border-b border-gray-200">Nilai CapEx</th>
                        <th class="px-4 py-3 text-left border-b border-gray-200">PIC</th>
                        <th class="px-4 py-3 text-center border-b border-gray-200">Status</th>
                        <th class="px-4 py-3 text-center border-b border-gray-200">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100" id="unitCapexTableBody">
                    @foreach($items as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-center text-xs text-gray-500">{{ $loop->iteration + ($items->currentPage() - 1) * $items->perPage() }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $item->capex_id_number }}</td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-800 truncate max-w-xs" title="{{ $item->item_name }}">{{ $item->item_name }}</p>
                            @if($item->description)<p class="text-xs text-gray-400 truncate max-w-xs">{{ $item->description }}</p>@endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($item->capex_type)
                                <span class="inline-block px-2 py-0.5 text-xs rounded-full
                                    {{ $item->capex_type === 'New' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700' }}">
                                    {{ $item->capex_type }}
                                </span>
                            @else—@endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($item->priority_scale)
                                <span class="inline-block px-2 py-0.5 text-xs rounded-full font-bold
                                    {{ $item->priority_scale === 1 ? 'bg-red-100 text-red-700' : ($item->priority_scale === 2 ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-600') }}">
                                    P{{ $item->priority_scale }}
                                </span>
                            @else—@endif
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $item->month ?? '—' }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">
                            {{ $item->amount_per_year ? 'Rp ' . number_format($item->amount_per_year, 0, ',', '.') : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right font-medium text-gray-800">
                            Rp {{ number_format($item->budget_amount, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ $item->pic ?? '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-block px-2 py-0.5 text-xs rounded-full
                                {{ $item->status === 'available' ? 'bg-green-100 text-green-700' :
                                   ($item->status === 'partially_used' ? 'bg-blue-100 text-blue-700' :
                                   ($item->status === 'exhausted' ? 'bg-red-100 text-red-700' :
                                   'bg-gray-100 text-gray-500')) }}">
                                {{ ucfirst(str_replace('_', ' ', $item->status)) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <a href="{{ route('unit.capex.edit', $item) }}"
                                   class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                    <i class="fas fa-pen text-xs"></i>
                                </a>
                                @if($item->used_amount <= 0)
                                <form method="POST" action="{{ route('unit.capex.destroy', $item) }}"
                                      onsubmit="return confirm('Hapus item ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                                        <i class="fas fa-trash text-xs"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-100">
            {{ $items->appends(['year' => $year])->links() }}
        </div>
        @else
        <div class="text-center py-16">
            <i class="fas fa-file-invoice-dollar text-5xl text-gray-200 mb-4"></i>
            <p class="text-gray-500">Belum ada item CapEx untuk tahun {{ $year }}.</p>
            <a href="{{ route('unit.capex.create', ['year' => $year]) }}"
               class="mt-4 inline-block px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700">
                <i class="fas fa-plus mr-1"></i>Tambah Item Pertama
            </a>
        </div>
        @endif
    </div>
</div>
@push('scripts')
<script>
    // search dipindah ke server-side
</script>
@endpush
@endsection
