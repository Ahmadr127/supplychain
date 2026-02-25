@extends('layouts.app')

@section('title', 'Preview Import')

@section('content')
<div class="max-w-6xl mx-auto">

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Preview Import</h2>
            <p class="text-gray-500 text-sm mt-1">10 baris pertama ditampilkan. Periksa sebelum melanjutkan.</p>
        </div>
        <a href="{{ route('import.mapping') }}" class="text-sm text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left mr-1"></i>Ubah Mapping
        </a>
    </div>

    {{-- Step Indicator --}}
    <div class="flex items-center mb-8">
        @foreach(['Upload', 'Mapping', 'Preview', 'Done'] as $i => $step)
            <div class="flex items-center {{ $i > 0 ? 'flex-1' : '' }}">
                @if($i > 0)<div class="flex-1 h-0.5 {{ $i <= 2 ? 'bg-green-400' : 'bg-gray-200' }} mx-2"></div>@endif
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold
                        {{ $i <= 2 ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-500' }}">
                        @if($i < 2)<i class="fas fa-check text-xs"></i>@else{{ $i + 1 }}@endif
                    </div>
                    <span class="text-sm {{ $i === 2 ? 'font-semibold text-green-700' : ($i < 2 ? 'text-green-600' : 'text-gray-400') }}">{{ $step }}</span>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Summary Bar --}}
    @php
        $validCount    = collect($previewRows)->where('valid', true)->count();
        $invalidCount  = collect($previewRows)->where('valid', false)->count();
        $existsCount   = collect($previewRows)->where('exists', true)->count();
        $newCount      = collect($previewRows)->where('exists', false)->where('valid', true)->count();
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-gray-800">{{ count($previewRows) }}</p>
            <p class="text-xs text-gray-400">Baris Dipreview</p>
        </div>
        <div class="bg-green-50 rounded-xl border border-green-200 p-4 text-center">
            <p class="text-2xl font-bold text-green-700">{{ $newCount }}</p>
            <p class="text-xs text-green-600">Baru (Insert)</p>
        </div>
        <div class="bg-yellow-50 rounded-xl border border-yellow-200 p-4 text-center">
            <p class="text-2xl font-bold text-yellow-700">{{ $existsCount }}</p>
            <p class="text-xs text-yellow-600">Sudah Ada (Duplikat)</p>
        </div>
        <div class="bg-red-50 rounded-xl border border-red-200 p-4 text-center">
            <p class="text-2xl font-bold text-red-700">{{ $invalidCount }}</p>
            <p class="text-xs text-red-500">Ada Error Validasi</p>
        </div>
    </div>

    {{-- Preview Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6 overflow-hidden">
        <div class="overflow-x-auto allow-horizontal-scroll">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
                    <tr>
                        <th class="px-4 py-3 text-left border-b border-gray-200 w-12">Row</th>
                        <th class="px-4 py-3 text-left border-b border-gray-200 w-28">Status</th>
                        @if(!empty($previewRows[0]['mapped']))
                            @foreach(array_keys($previewRows[0]['mapped']) as $col)
                                <th class="px-4 py-3 text-left border-b border-gray-200 font-mono">{{ $col }}</th>
                            @endforeach
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($previewRows as $item)
                        @php
                            $isError  = !$item['valid'];
                            $isDupe   = $item['exists'] ?? false;
                            $rowClass = $isError ? 'bg-red-50' : ($isDupe ? 'bg-yellow-50' : 'hover:bg-green-50');
                        @endphp
                        <tr class="{{ $rowClass }}">
                            <td class="px-4 py-3 text-gray-400 font-mono">{{ $item['row'] }}</td>
                            <td class="px-4 py-3">
                                @if($isError)
                                    <span class="inline-flex items-center gap-1 text-xs text-red-700 bg-red-100 px-2 py-0.5 rounded-full">
                                        <i class="fas fa-xmark text-xs"></i> Error
                                    </span>
                                @elseif($isDupe)
                                    @if($mode === 'add')
                                        <span class="inline-flex items-center gap-1 text-xs text-orange-700 bg-orange-100 px-2 py-0.5 rounded-full">
                                            <i class="fas fa-exclamation text-xs"></i> Duplikat
                                        </span>
                                    @elseif($mode === 'replace')
                                        <span class="inline-flex items-center gap-1 text-xs text-blue-700 bg-blue-100 px-2 py-0.5 rounded-full">
                                            <i class="fas fa-sync text-xs"></i> Akan Diganti
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-xs text-indigo-700 bg-indigo-100 px-2 py-0.5 rounded-full">
                                            <i class="fas fa-pen text-xs"></i> Akan Diupdate
                                        </span>
                                    @endif
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs text-green-700 bg-green-100 px-2 py-0.5 rounded-full">
                                        <i class="fas fa-plus text-xs"></i> Baru
                                    </span>
                                @endif
                            </td>
                            @foreach($item['mapped'] as $col => $val)
                                <td class="px-4 py-3 truncate max-w-xs
                                    {{ $isError && isset($item['errors'][$col]) ? 'text-red-600 font-medium' : 'text-gray-700' }}"
                                    title="{{ $val }}">
                                    {{ $val ?? '—' }}
                                    @if($isError && isset($item['errors'][$col]))
                                        <p class="text-xs text-red-500 mt-0.5">
                                            {{ implode(', ', $item['errors'][$col]) }}
                                        </p>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="20" class="text-center py-8 text-gray-400">Tidak ada data untuk dipreview.</td>
                        </tr>
                    @endforelse
                \u003c/tbody\u003e
            \u003c/table\u003e
        \u003c/div\u003e

        {{-- Pagination Footer --}}
        @php
            $totalPages = $perPage > 0 ? (int) ceil($totalRows / $perPage) : 1;
        @endphp
        @if($totalPages > 1)
        <div class="flex items-center justify-between px-5 py-3 border-t border-gray-100">
            <p class="text-xs text-gray-500">
                Menampilkan baris {{ ($page - 1) * $perPage + 1 }}–{{ min($page * $perPage, $totalRows) }} dari {{ $totalRows }} baris
            </p>
            <div class="flex items-center gap-1">
                @if($page > 1)
                    <a href="{{ route('import.preview', ['page' => $page - 1]) }}"
                       class="px-3 py-1 text-xs rounded border border-gray-200 text-gray-600 hover:bg-gray-50">« Sebelumnya</a>
                @endif
                @for($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++)
                    <a href="{{ route('import.preview', ['page' => $p]) }}"
                       class="px-3 py-1 text-xs rounded border {{ $p === $page ? 'border-green-500 bg-green-50 text-green-700 font-bold' : 'border-gray-200 text-gray-600 hover:bg-gray-50' }}">{{ $p }}</a>
                @endfor
                @if($page < $totalPages)
                    <a href="{{ route('import.preview', ['page' => $page + 1]) }}"
                       class="px-3 py-1 text-xs rounded border border-gray-200 text-gray-600 hover:bg-gray-50">Selanjutnya »</a>
                @endif
            </div>
        </div>
        @endif
    </div>

    {{-- Import Mode Info --}}
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
        <div class="flex items-center gap-3">
            <i class="fas fa-info-circle text-blue-500 text-xl"></i>
            <div>
                <p class="text-sm font-semibold text-blue-800">
                    Mode:
                    <span class="uppercase">{{ $mode }}</span>
                    @if(!empty($uniqueKeys))
                        &mdash; Unique key: <span class="font-mono">{{ implode(', ', $uniqueKeys) }}</span>
                    @endif
                </p>
                <p class="text-xs text-blue-600 mt-0.5">
                    @if($mode === 'add')
                        Semua baris akan di-insert. Baris duplikat (kuning) kemungkinan akan gagal karena constraint database.
                    @elseif($mode === 'replace')
                        Baris duplikat yang ada akan dihapus lalu dimasukkan ulang. Baris baru akan langsung diinsert.
                    @else
                        Baris duplikat akan diupdate. Baris baru akan diinsert.
                    @endif
                    Ini adalah preview 10 baris pertama.
                </p>
            </div>
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="flex justify-between">
        <a href="{{ route('import.mapping') }}"
           class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
            <i class="fas fa-arrow-left mr-1"></i>Kembali
        </a>

        <form action="{{ route('import.run') }}" method="POST" onsubmit="return confirmImport()">
            @csrf
            <button type="submit"
                class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg transition-colors flex items-center gap-2">
                <i class="fas fa-file-import"></i>
                Mulai Import
            </button>
        </form>
    </div>
</div>

@push('scripts')
<script>
function confirmImport() {
    return confirm('Anda yakin ingin memulai import? Pastikan data sudah diperiksa.');
}
</script>
@endpush
@endsection

