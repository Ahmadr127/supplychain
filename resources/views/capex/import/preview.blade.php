@extends('layouts.app')
@section('title', 'Import CapEx — Preview')
@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Preview Import CapEx</h2>
            <p class="text-gray-500 text-sm mt-1">25 baris per halaman. Periksa data sebelum melanjutkan.</p>
        </div>
        <a href="{{ route('capex.import.mapping') }}" class="text-sm text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left mr-1"></i>Ubah Mapping
        </a>
    </div>

    {{-- Steps --}}
    <div class="flex items-center mb-8">
        @foreach(['Upload', 'Mapping', 'Preview', 'Selesai'] as $i => $step)
            <div class="flex items-center {{ $i > 0 ? 'flex-1' : '' }}">
                @if($i > 0)<div class="flex-1 h-0.5 {{ $i <= 2 ? 'bg-green-400' : 'bg-gray-200' }} mx-2"></div>@endif
                <div class="flex items-center space-x-1">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold
                        {{ $i <= 2 ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-500' }}">
                        @if($i < 2)<i class="fas fa-check text-xs"></i>@else{{ $i + 1 }}@endif
                    </div>
                    <span class="text-xs {{ $i === 2 ? 'font-semibold text-green-700' : ($i < 2 ? 'text-green-600' : 'text-gray-400') }}">{{ $step }}</span>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Summary Cards --}}
    @php
        $validCount   = collect($previewRows)->where('valid', true)->count();
        $invalidCount = collect($previewRows)->where('valid', false)->count();
        $existsCount  = collect($previewRows)->where('exists', true)->count();
        $newCount     = collect($previewRows)->where('exists', false)->where('valid', true)->count();
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-gray-800">{{ $totalRows }}</p>
            <p class="text-xs text-gray-400">Total Baris di File</p>
        </div>
        <div class="bg-green-50 rounded-xl border border-green-200 p-4 text-center">
            <p class="text-2xl font-bold text-green-700">{{ $newCount }}</p>
            <p class="text-xs text-green-600">Baru (Insert)</p>
        </div>
        <div class="bg-yellow-50 rounded-xl border border-yellow-200 p-4 text-center">
            <p class="text-2xl font-bold text-yellow-700">{{ $existsCount }}</p>
            <p class="text-xs text-yellow-600">Sudah Ada di DB</p>
        </div>
        <div class="bg-red-50 rounded-xl border border-red-200 p-4 text-center">
            <p class="text-2xl font-bold text-red-700">{{ $invalidCount }}</p>
            <p class="text-xs text-red-500">Error Validasi</p>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-5 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-gray-50 text-gray-500 uppercase tracking-wide text-xs">
                    <tr>
                        <th class="px-3 py-3 text-left border-b border-gray-200 w-10">Row</th>
                        <th class="px-3 py-3 text-left border-b border-gray-200 w-28">Status</th>
                        <th class="px-3 py-3 text-left border-b border-gray-200">ID CapEx</th>
                        <th class="px-3 py-3 text-left border-b border-gray-200">Nama Item</th>
                        <th class="px-3 py-3 text-left border-b border-gray-200">Tipe</th>
                        <th class="px-3 py-3 text-left border-b border-gray-200">Prioritas</th>
                        <th class="px-3 py-3 text-left border-b border-gray-200">Bulan</th>
                        <th class="px-3 py-3 text-right border-b border-gray-200">Nilai CapEx</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($previewRows as $item)
                        @php
                            $isError = !$item['valid'];
                            $isDupe  = $item['exists'] ?? false;
                            $rowClass = $isError ? 'bg-red-50' : ($isDupe ? 'bg-yellow-50' : 'hover:bg-green-50');
                            $m = $item['mapped'];
                        @endphp
                        <tr class="{{ $rowClass }}">
                            <td class="px-3 py-2 text-gray-400 font-mono">{{ $item['row'] }}</td>
                            <td class="px-3 py-2">
                                @if($isError)
                                    <span class="inline-flex items-center gap-1 text-xs text-red-700 bg-red-100 px-2 py-0.5 rounded-full">
                                        <i class="fas fa-xmark"></i> Error
                                    </span>
                                @elseif($isDupe)
                                    @if($mode === 'add')
                                        <span class="inline-flex items-center gap-1 text-xs text-orange-700 bg-orange-100 px-2 py-0.5 rounded-full">
                                            <i class="fas fa-exclamation"></i> Duplikat
                                        </span>
                                    @elseif($mode === 'replace')
                                        <span class="inline-flex items-center gap-1 text-xs text-blue-700 bg-blue-100 px-2 py-0.5 rounded-full">
                                            <i class="fas fa-sync"></i> Akan Diganti
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-xs text-indigo-700 bg-indigo-100 px-2 py-0.5 rounded-full">
                                            <i class="fas fa-pen"></i> Akan Diupdate
                                        </span>
                                    @endif
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs text-green-700 bg-green-100 px-2 py-0.5 rounded-full">
                                        <i class="fas fa-plus"></i> Baru
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-2 font-mono text-gray-700">{{ $m['capex_id_number'] ?? '—' }}</td>
                            <td class="px-3 py-2 text-gray-700 max-w-xs truncate" title="{{ $m['item_name'] ?? '' }}">{{ $m['item_name'] ?? '—' }}</td>
                            <td class="px-3 py-2 text-gray-500">{{ $m['capex_type'] ?? '—' }}</td>
                            <td class="px-3 py-2 text-center">
                                @if(isset($m['priority_scale']))
                                    <span class="inline-block px-2 py-0.5 text-xs rounded-full {{ $m['priority_scale'] == 1 ? 'bg-red-100 text-red-700' : ($m['priority_scale'] == 2 ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-600') }}">
                                        P{{ $m['priority_scale'] }}
                                    </span>
                                @else —@endif
                            </td>
                            <td class="px-3 py-2 text-gray-500">{{ $m['month'] ?? '—' }}</td>
                            <td class="px-3 py-2 text-right text-gray-700">
                                {{ isset($m['budget_amount']) ? 'Rp ' . number_format((float)$m['budget_amount'], 0, ',', '.') : '—' }}
                            </td>
                        </tr>
                        @if($isError && !empty($item['errors']))
                        <tr class="bg-red-50">
                            <td colspan="8" class="px-3 py-1.5">
                                <p class="text-xs text-red-600"><i class="fas fa-exclamation-circle mr-1"></i>
                                    {{ collect($item['errors'])->flatten()->implode(', ') }}
                                </p>
                            </td>
                        </tr>
                        @endif
                    @empty
                        <tr><td colspan="8" class="text-center py-8 text-gray-400">Tidak ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @php $totalPages = $perPage > 0 ? (int) ceil($totalRows / $perPage) : 1; @endphp
        @if($totalPages > 1)
        <div class="flex items-center justify-between px-4 py-3 border-t border-gray-100">
            <p class="text-xs text-gray-500">Baris {{ ($page-1)*$perPage+1 }}–{{ min($page*$perPage, $totalRows) }} dari {{ $totalRows }}</p>
            <div class="flex gap-1">
                @if($page > 1)
                    <a href="{{ route('capex.import.preview', ['page' => $page-1]) }}" class="px-3 py-1 text-xs rounded border border-gray-200 hover:bg-gray-50">«</a>
                @endif
                @for($p = max(1,$page-2); $p <= min($totalPages,$page+2); $p++)
                    <a href="{{ route('capex.import.preview', ['page' => $p]) }}"
                       class="px-3 py-1 text-xs rounded border {{ $p === $page ? 'border-green-500 bg-green-50 text-green-700 font-bold' : 'border-gray-200 hover:bg-gray-50' }}">{{ $p }}</a>
                @endfor
                @if($page < $totalPages)
                    <a href="{{ route('capex.import.preview', ['page' => $page+1]) }}" class="px-3 py-1 text-xs rounded border border-gray-200 hover:bg-gray-50">»</a>
                @endif
            </div>
        </div>
        @endif
    </div>

    {{-- Mode info --}}
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-5">
        <p class="text-sm text-blue-800">
            <i class="fas fa-info-circle mr-1"></i>
            Mode: <b class="uppercase">{{ $mode }}</b>
            @if($context === 'unit' && $capex)
                &mdash; Unit: <b>{{ $capex->department->name ?? '-' }}</b>
            @else
                &mdash; Semua Unit (kolom Kode Unit harus sudah dimapping)
            @endif
        </p>
    </div>

    <div class="flex justify-between">
        <a href="{{ route('capex.import.mapping') }}" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-1"></i>Kembali
        </a>
        <form action="{{ route('capex.import.run') }}" method="POST" onsubmit="return confirm('Mulai import CapEx?')">
            @csrf
            <button type="submit" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg transition-colors">
                <i class="fas fa-file-import mr-1"></i>Mulai Import
            </button>
        </form>
    </div>
</div>
@endsection
