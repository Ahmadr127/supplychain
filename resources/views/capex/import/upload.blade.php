@extends('layouts.app')
@section('title', 'Import CapEx — Upload File')
@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Import CapEx
            @if($context === 'unit' && $capex)
                — {{ $capex->department->name }}
            @else
                — Semua Unit
            @endif
        </h2>
        <p class="text-gray-500 text-sm mt-1">Upload file Excel (.xlsx / .xls / .csv) berisi data CapEx.</p>
    </div>

    {{-- Step Indicator --}}
    <div class="flex items-center mb-8">
        @foreach(['Upload', 'Mapping', 'Preview', 'Selesai'] as $i => $step)
            <div class="flex items-center {{ $i > 0 ? 'flex-1' : '' }}">
                @if($i > 0)<div class="flex-1 h-0.5 bg-gray-200 mx-2"></div>@endif
                <div class="flex items-center space-x-1">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold
                        {{ $i === 0 ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-500' }}">{{ $i + 1 }}</div>
                    <span class="text-xs {{ $i === 0 ? 'font-semibold text-green-700' : 'text-gray-400' }}">{{ $step }}</span>
                </div>
            </div>
        @endforeach
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">

        @if($context === 'all')
        <form action="{{ route('capex.import.upload-all') }}" method="POST" enctype="multipart/form-data">
        @else
        <form action="{{ route('capex.import.upload', $capex) }}" method="POST" enctype="multipart/form-data">
        @endif
            @csrf

            {{-- Fiscal Year (only for all-unit context) --}}
            @if($context === 'all')
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Tahun Anggaran <span class="text-red-500">*</span></label>
                <input type="number" name="fiscal_year" value="{{ date('Y') }}" min="2020" max="2100"
                    class="w-40 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                @error('fiscal_year')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            @else
            <div class="mb-4 bg-blue-50 rounded-lg p-3">
                <p class="text-xs text-blue-700"><i class="fas fa-info-circle mr-1"></i>
                    Import untuk unit <b>{{ $capex->department->name ?? '-' }}</b> — Tahun Anggaran <b>{{ $capex->fiscal_year }}</b>
                </p>
            </div>
            @endif

            {{-- File Upload --}}
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">File Excel <span class="text-red-500">*</span></label>
                <div id="dropzone" class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center cursor-pointer hover:border-green-400 hover:bg-green-50 transition-all">
                    <i class="fas fa-file-excel text-4xl text-green-500 mb-3"></i>
                    <p class="text-sm text-gray-600">Seret file ke sini atau <span class="text-green-600 font-medium">klik untuk memilih</span></p>
                    <p class="text-xs text-gray-400 mt-1">.xlsx, .xls, .csv — maksimal 20 MB</p>
                    <p id="filename" class="text-sm font-medium text-gray-700 mt-2 hidden"></p>
                    <input type="file" name="file" id="fileInput" accept=".xlsx,.xls,.csv,.ods" class="hidden" required>
                </div>
                @error('file')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Info --}}
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-6">
                <p class="text-xs text-yellow-700">
                    <i class="fas fa-lightbulb mr-1"></i>
                    Pastikan kolom <b>ID CapEx</b> ada di file Excel. ID ini akan digunakan langsung sebagai identifikasi unik item CapEx.
                    Format: <code class="bg-yellow-100 px-1 rounded">7/CapEx/2026/I-RI</code>
                </p>
            </div>

            <button type="submit" class="w-full py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition-colors">
                <i class="fas fa-arrow-right mr-2"></i>Lanjut ke Mapping Kolom
            </button>
        </form>
    </div>
</div>
@push('scripts')
<script>
const dropzone = document.getElementById('dropzone');
const fileInput = document.getElementById('fileInput');
const filename  = document.getElementById('filename');
dropzone.addEventListener('click', () => fileInput.click());
dropzone.addEventListener('dragover', (e) => { e.preventDefault(); dropzone.classList.add('border-green-500','bg-green-50'); });
dropzone.addEventListener('dragleave', () => dropzone.classList.remove('border-green-500','bg-green-50'));
dropzone.addEventListener('drop', (e) => {
    e.preventDefault();
    if (e.dataTransfer.files.length) { fileInput.files = e.dataTransfer.files; showFilename(); }
});
fileInput.addEventListener('change', showFilename);
function showFilename() {
    if (fileInput.files[0]) { filename.textContent = '✓ ' + fileInput.files[0].name; filename.classList.remove('hidden'); }
}
</script>
@endpush
@endsection
