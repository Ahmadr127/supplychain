@extends('layouts.app')
@section('title', 'Import CapEx — Mapping Kolom')
@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Mapping Kolom CapEx</h2>
        <p class="text-gray-500 text-sm mt-1">Cocokkan kolom di file Excel dengan field sistem CapEx.</p>
    </div>

    {{-- Step --}}
    <div class="flex items-center mb-8">
        @foreach(['Upload', 'Mapping', 'Preview', 'Selesai'] as $i => $step)
            <div class="flex items-center {{ $i > 0 ? 'flex-1' : '' }}">
                @if($i > 0)<div class="flex-1 h-0.5 {{ $i <= 1 ? 'bg-green-400' : 'bg-gray-200' }} mx-2"></div>@endif
                <div class="flex items-center space-x-1">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold
                        {{ $i <= 1 ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-500' }}">
                        @if($i < 1)<i class="fas fa-check text-xs"></i>@else{{ $i + 1 }}@endif
                    </div>
                    <span class="text-xs {{ $i === 1 ? 'font-semibold text-green-700' : ($i < 1 ? 'text-green-600' : 'text-gray-400') }}">{{ $step }}</span>
                </div>
            </div>
        @endforeach
    </div>

    <form action="{{ route('capex.import.save-mapping') }}" method="POST">
        @csrf

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-5">

            {{-- Import Mode --}}
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Mode Import <span class="text-red-500">*</span></label>
                <div class="flex gap-3 flex-wrap">
                    @foreach(['add' => ['Tambah Baru', 'fa-plus', 'green'], 'replace' => ['Ganti', 'fa-sync', 'orange'], 'upsert' => ['Update/Tambah', 'fa-layer-group', 'blue']] as $val => [$label, $icon, $color])
                        <label class="flex items-center gap-2 px-4 py-2 rounded-lg border-2 cursor-pointer transition-all
                            {{ $mode === $val ? "border-{$color}-500 bg-{$color}-50" : 'border-gray-200 bg-white' }}">
                            <input type="radio" name="mode" value="{{ $val }}" {{ $mode === $val ? 'checked' : '' }} class="hidden"
                                   onchange="document.querySelectorAll('.mode-label').forEach(l=>l.className=l.className.replace(/border-\w+-500 bg-\w+-50/,'border-gray-200 bg-white')); this.closest('label').classList.add('border-{{ $color }}-500','bg-{{ $color }}-50')">
                            <i class="fas {{ $icon }} text-{{ $color }}-600 text-xs"></i>
                            <span class="text-sm font-medium text-gray-700">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                <p class="text-xs text-gray-400 mt-2">
                    <b>Tambah</b>: insert baru (error jika ID duplikat). <b>Ganti</b>: hapus lama + insert. <b>Update/Tambah</b>: update jika ada, insert jika belum (direkomendasikan untuk re-upload).
                </p>
            </div>

            {{-- Column Mapping --}}
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Mapping Kolom</h3>
                <p class="text-xs text-gray-400 mb-4">
                    Kolom bertanda <span class="text-red-500 font-bold">*</span> wajib dimapping. Kolom Excel yang tidak diperlukan bisa dibiarkan kosong.
                </p>

                <div class="grid grid-cols-1 gap-3">
                    @foreach($systemFields as $key => $label)
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg border border-gray-100">
                            <div class="w-56 flex-shrink-0">
                                <p class="text-sm font-medium text-gray-700">{{ $label }}</p>
                                <p class="text-xs text-gray-400 font-mono">{{ $key }}</p>
                            </div>
                            <i class="fas fa-arrow-right text-gray-400 text-xs flex-shrink-0"></i>
                            <select name="column_map[{{ $key }}]" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 bg-white">
                                <option value="">— Tidak dimapping —</option>
                                @foreach($excelHeaders as $header)
                                    <option value="{{ $header }}"
                                        {{ (isset($savedMap[$key]) && $savedMap[$key] === $header) ? 'selected' : '' }}
                                        {{ (empty($savedMap) && strtolower($header) === strtolower(preg_replace('/[^a-z0-9]/i', '', $label))) ? 'selected' : '' }}>
                                        {{ $header }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Context info --}}
        @if($context === 'unit' && $capex)
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-5">
            <p class="text-sm text-blue-800">
                <i class="fas fa-building mr-1"></i>
                Import untuk unit <b>{{ $capex->department->name ?? '-' }}</b> — Tahun <b>{{ $capex->fiscal_year }}</b>.
                Kolom "Kode Unit" <em>tidak perlu</em> dimapping karena unit sudah diketahui.
            </p>
        </div>
        @endif

        <div class="flex justify-between">
            <a href="javascript:history.back()" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                <i class="fas fa-arrow-left mr-1"></i>Kembali
            </a>
            <button type="submit" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg transition-colors">
                Preview Data <i class="fas fa-arrow-right ml-1"></i>
            </button>
        </div>
    </form>
</div>
@endsection
