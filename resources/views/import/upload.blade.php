@extends('layouts.app')

@section('title', 'Import Data')

@section('content')
<div class="max-w-5xl mx-auto">

    {{-- Page Header --}}
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Import Data</h2>
        <p class="text-gray-500 text-sm mt-1">Upload file Excel / CSV untuk mengimport data ke sistem.</p>
    </div>

    {{-- Step Indicator --}}
    <div class="flex items-center mb-8">
        @foreach(['Upload', 'Mapping', 'Preview', 'Done'] as $i => $step)
            <div class="flex items-center {{ $i > 0 ? 'flex-1' : '' }}">
                @if($i > 0)<div class="flex-1 h-0.5 bg-gray-200 mx-2"></div>@endif
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold
                        {{ $i === 0 ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-500' }}">
                        {{ $i + 1 }}
                    </div>
                    <span class="text-sm {{ $i === 0 ? 'font-semibold text-green-700' : 'text-gray-400' }}">{{ $step }}</span>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Upload Form --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">
                    <i class="fas fa-upload text-green-600 mr-2"></i>Upload File
                </h3>

                <form action="{{ route('import.upload') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                    @csrf



                    {{-- File Drop Zone --}}
                    <div class="mb-5" x-data="{ dragging: false, fileName: '' }">
                        <label class="block text-sm font-medium text-gray-700 mb-1">File Excel / CSV <span class="text-red-500">*</span></label>
                        <div
                            @dragover.prevent="dragging = true"
                            @dragleave.prevent="dragging = false"
                            @drop.prevent="
                                dragging = false;
                                const f = $event.dataTransfer.files[0];
                                if(f){ fileName = f.name; $refs.fileInput.files = $event.dataTransfer.files; }
                            "
                            :class="dragging ? 'border-green-500 bg-green-50' : 'border-gray-300 bg-gray-50'"
                            class="border-2 border-dashed rounded-xl p-8 text-center transition-colors cursor-pointer"
                            @click="$refs.fileInput.click()">
                            <i class="fas fa-file-excel text-4xl text-green-500 mb-3"></i>
                            <p class="text-sm text-gray-600" x-text="fileName || 'Klik atau tarik file ke sini'"></p>
                            <p class="text-xs text-gray-400 mt-1">Format: .xlsx, .xls, .csv, .ods — Maks. 20MB</p>
                        </div>
                        <input type="file" name="file" id="file" accept=".xlsx,.xls,.csv,.ods" required
                               x-ref="fileInput"
                               @change="fileName = $event.target.files[0]?.name || ''"
                               class="hidden">
                        @error('file')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <button type="submit"
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2.5 px-4 rounded-lg transition-colors flex items-center justify-center space-x-2">
                        <i class="fas fa-arrow-right"></i>
                        <span>Lanjut ke Mapping</span>
                    </button>
                </form>
            </div>
        </div>

        {{-- Recent Import Histories --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">
                    <i class="fas fa-history text-gray-400 mr-1"></i> Riwayat Import
                </h3>
                @forelse($histories as $h)
                    <a href="{{ route('import.logs', $h) }}"
                       class="block py-2 border-b border-gray-100 last:border-0 hover:bg-gray-50 transition-colors px-1 rounded">
                        <div class="flex items-center justify-between">
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-medium text-gray-700 truncate">{{ $h->profile->name ?? '-' }}</p>
                                <p class="text-xs text-gray-400 truncate">{{ $h->original_filename }}</p>
                            </div>
                            <span class="ml-2 px-2 py-0.5 text-xs rounded-full font-medium flex-shrink-0
                                @if($h->status === 'done') bg-green-100 text-green-700
                                @elseif($h->status === 'failed') bg-red-100 text-red-700
                                @elseif($h->status === 'processing') bg-blue-100 text-blue-700
                                @else bg-yellow-100 text-yellow-700 @endif">
                                {{ $h->status }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $h->created_at->diffForHumans() }}</p>
                    </a>
                @empty
                    <p class="text-xs text-gray-400 text-center py-4">Belum ada riwayat import.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
