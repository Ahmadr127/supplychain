@extends('layouts.app')

@section('title', 'Mapping Kolom')

@section('content')
<div class="max-w-5xl mx-auto">

    {{-- Page Header --}}
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Mapping Kolom</h2>
            <p class="text-gray-500 text-sm mt-1">Cocokkan kolom di file Excel dengan kolom di database.</p>
        </div>
        <a href="{{ route('import.index') }}" class="text-sm text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left mr-1"></i>Mulai Ulang
        </a>
    </div>

    {{-- Step Indicator --}}
    <div class="flex items-center mb-8">
        @foreach(['Upload', 'Mapping', 'Preview', 'Done'] as $i => $step)
            <div class="flex items-center {{ $i > 0 ? 'flex-1' : '' }}">
                @if($i > 0)<div class="flex-1 h-0.5 {{ $i <= 1 ? 'bg-green-400' : 'bg-gray-200' }} mx-2"></div>@endif
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold
                        {{ $i <= 1 ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-500' }}">
                        @if($i < 1)<i class="fas fa-check text-xs"></i>@else{{ $i + 1 }}@endif
                    </div>
                    <span class="text-sm {{ $i === 1 ? 'font-semibold text-green-700' : ($i < 1 ? 'text-green-600' : 'text-gray-400') }}">{{ $step }}</span>
                </div>
            </div>
        @endforeach
    </div>

    <form action="{{ route('import.save-mapping') }}" method="POST">
        @csrf
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-5">
            <div class="flex flex-wrap gap-4 mb-6">
                {{-- Target Model Selector --}}
                <div class="flex-1 min-w-48">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Target Model Data <span class="text-red-500">*</span></label>
                    <div class="flex gap-2">
                        <select name="target_model" id="target_model" required
                            class="w-full text-sm rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 bg-white"
                            onchange="document.getElementById('switchModelBtn').click()">
                            <option value="">— Pilih Model —</option>
                            @foreach($allowedModels as $label => $fqcn)
                                <option value="{{ $fqcn }}" {{ $selectedModelFQCN === $fqcn ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" name="switch_model_only" value="1" id="switchModelBtn" class="hidden">Ganti Model</button>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Pilih data apa yang mau diimport. Kolom bawah akan otomatis menyesuaikan.</p>
                </div>

                {{-- Import Mode --}}
                <div class="flex-1 min-w-64">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Mode Import <span class="text-red-500">*</span></label>
                    <div class="flex gap-2 flex-wrap">
                        @foreach(['add' => ['Tambah', 'fa-plus', 'green'], 'replace' => ['Ganti', 'fa-sync', 'orange'], 'upsert' => ['Update/Tambah', 'fa-layer-group', 'blue']] as $val => [$label, $icon, $color])
                            <label class="flex items-center gap-1.5 px-3 py-2 rounded-lg border-2 cursor-pointer transition-all
                                {{ old('import_mode', $importMode) === $val ? "border-{$color}-500 bg-{$color}-50" : 'border-gray-200 bg-white hover:border-gray-300' }}">
                                <input type="radio" name="import_mode" value="{{ $val }}"
                                       {{ old('import_mode', $importMode) === $val ? 'checked' : '' }}
                                       class="hidden" onchange="this.closest('form').querySelectorAll('label').forEach(l=>l.classList.remove('border-green-500','bg-green-50','border-orange-500','bg-orange-50','border-blue-500','bg-blue-50')); this.closest('label').classList.add('border-{{ $color }}-500','bg-{{ $color }}-50')">
                                <i class="fas {{ $icon }} text-{{ $color }}-600 text-xs"></i>
                                <span class="text-xs font-medium text-gray-700">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="text-xs text-gray-400 mt-1">
                        <b>Tambah</b>: insert baru. <b>Ganti</b>: hapus lama + insert. <b>Update/Tambah</b>: update jika ada, insert jika belum.
                    </p>
                </div>
            </div>

            {{-- Unique Keys (for replace/upsert) --}}
            <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <p class="text-xs font-semibold text-yellow-800 mb-2">
                    <i class="fas fa-key mr-1"></i>Unique Key (untuk mode Ganti / Update+Tambah)
                </p>
                <p class="text-xs text-yellow-700 mb-2">Pilih kolom DB yang digunakan sebagai kunci untuk mencocokkan data yang sudah ada.</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($dbColumns as $col)
                        <label class="flex items-center gap-1.5 px-2 py-1 rounded border border-yellow-300 bg-white cursor-pointer hover:bg-yellow-50">
                            <input type="checkbox" name="unique_keys[]" value="{{ $col }}"
                                   {{ in_array($col, old('unique_keys', $uniqueKeys)) ? 'checked' : '' }}
                                   class="text-yellow-600">
                            <span class="text-xs font-mono text-gray-700">{{ $col }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Column Mapping Table --}}
            <h4 class="text-sm font-semibold text-gray-700 mb-3">Pemetaan Kolom</h4>
            <div class="overflow-x-auto allow-horizontal-scroll">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
                            <th class="text-left px-4 py-2 border border-gray-200 w-1/2">Kolom Excel</th>
                            <th class="text-left px-4 py-2 border border-gray-200 w-1/2">Kolom Database</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($excelHeaders as $excelCol)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 border border-gray-200">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-table text-green-400 text-xs"></i>
                                        <span class="font-mono text-gray-700">{{ $excelCol }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-2 border border-gray-200">
                                    <select name="column_map[{{ $excelCol }}]"
                                        class="w-full text-sm rounded border border-gray-300 px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-green-500 bg-white">
                                        <option value="">— Abaikan kolom ini —</option>
                                        @foreach($dbColumns as $dbCol)
                                            <option value="{{ $dbCol }}"
                                                {{ (isset($savedMap[$excelCol]) && $savedMap[$excelCol] === $dbCol) ? 'selected' : '' }}>
                                                {{ $dbCol }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex justify-between">
            <a href="{{ route('import.index') }}"
               class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <i class="fas fa-arrow-left mr-1"></i>Kembali
            </a>
            <button type="submit"
                class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg transition-colors">
                Preview Data <i class="fas fa-arrow-right ml-1"></i>
            </button>
        </div>
    </form>
</div>
@endsection
