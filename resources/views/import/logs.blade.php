@extends('layouts.app')

@section('title', 'Log Import')

@section('content')
<div class="max-w-6xl mx-auto" x-data="importProgressData">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Log Import</h2>
            <p class="text-gray-500 text-sm mt-1">
                {{ class_basename($history->target_model) }} &mdash;
                <span class="font-mono">{{ $history->original_filename }}</span>
            </p>
        </div>
        <a href="{{ route('import.index') }}"
           class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50 transition-colors">
            <i class="fas fa-plus mr-1"></i>Import Baru
        </a>
    </div>

    {{-- Status Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Status</p>
            <span
                class="px-2.5 py-1 text-sm font-semibold rounded-full capitalize
                    {{ $history->status === 'done' ? 'bg-green-100 text-green-700' : '' }}
                    {{ $history->status === 'failed' ? 'bg-red-100 text-red-700' : '' }}
                    {{ $history->status === 'processing' ? 'bg-blue-100 text-blue-700' : '' }}
                    {{ $history->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : '' }}"
                x-text="status || '{{ $history->status }}'">
                {{ $history->status }}
            </span>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-gray-800" x-text="totalRows !== null ? totalRows : '{{ $history->total_rows ?? 0 }}'">{{ $history->total_rows ?? 0 }}</p>
            <p class="text-xs text-gray-400">Total Baris</p>
        </div>
        <div class="bg-green-50 rounded-xl border border-green-200 p-4 text-center">
            <p class="text-2xl font-bold text-green-700" x-text="successRows !== null ? successRows : '{{ $history->success_rows ?? 0 }}'">{{ $history->success_rows ?? 0 }}</p>
            <p class="text-xs text-green-600">Berhasil</p>
        </div>
        <div class="bg-red-50 rounded-xl border border-red-200 p-4 text-center">
            <p class="text-2xl font-bold text-red-700" x-text="failedRows !== null ? failedRows : '{{ $history->failed_rows ?? 0 }}'">{{ $history->failed_rows ?? 0 }}</p>
            <p class="text-xs text-red-500">Gagal</p>
        </div>
    </div>

    {{-- Progress Bar (live) --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5 mb-6">
        <div class="flex justify-between text-xs text-gray-500 mb-2">
            <span>Progress</span>
            <span x-text="progress + '%'">{{ $history->progressPercent() }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
            <div class="h-3 rounded-full transition-all duration-500"
                 :class="status === 'failed' ? 'bg-red-500' : 'bg-green-500'"
                 :style="'width:' + progress + '%'">
            </div>
        </div>

        <div class="flex items-center gap-4 mt-3 text-xs text-gray-400">
            @if($history->started_at)
                <span><i class="fas fa-play mr-1"></i>{{ $history->started_at->format('d M Y H:i:s') }}</span>
            @endif
            @if($history->finished_at)
                <span><i class="fas fa-stop mr-1"></i>{{ $history->finished_at->format('d M Y H:i:s') }}</span>
            @endif
            @if($history->duration())
                <span><i class="fas fa-clock mr-1"></i>{{ $history->duration() }}</span>
            @endif
            <span><i class="fas fa-user mr-1"></i>{{ $history->importer->name ?? '-' }}</span>
        </div>

        <p x-show="isRunning" class="text-xs text-blue-600 mt-2 animate-pulse">
            <i class="fas fa-spinner fa-spin mr-1"></i>Import sedang berjalan... halaman akan diperbarui otomatis.
        </p>
    </div>

    {{-- Error Log Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700">
                <i class="fas fa-exclamation-triangle text-red-400 mr-1"></i>
                Baris Gagal ({{ $logs->total() }})
            </h3>
            @if($logs->total() > 0)
                <a href="{{ route('import.logs', $history) }}?export=1"
                   class="text-xs text-green-600 hover:underline">
                    <i class="fas fa-download mr-1"></i>Export Error CSV
                </a>
            @endif
        </div>

        @if($logs->isEmpty())
            <div class="text-center py-10 text-gray-400">
                <i class="fas fa-check-circle text-4xl text-green-300 mb-3"></i>
                <p class="text-sm">Tidak ada error! Semua baris berhasil diimport.</p>
            </div>
        @else
            <div class="overflow-x-auto allow-horizontal-scroll">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
                        <tr>
                            <th class="px-4 py-3 text-left border-b border-gray-200 w-16">Row</th>
                            <th class="px-4 py-3 text-left border-b border-gray-200">Data</th>
                            <th class="px-4 py-3 text-left border-b border-gray-200">Error</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($logs as $log)
                            <tr class="hover:bg-red-50">
                                <td class="px-4 py-3 font-mono text-gray-500">{{ $log->row_number }}</td>
                                <td class="px-4 py-3 text-xs text-gray-600 max-w-xs">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($log->row_data as $key => $val)
                                            <span class="bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded font-mono text-xs">
                                                {{ $key }}: <b>{{ $val ?? 'null' }}</b>
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-xs text-red-600 max-w-sm">
                                    @foreach($log->errors as $field => $messages)
                                        <div class="mb-1">
                                            <span class="font-semibold text-red-700 font-mono">{{ $field }}:</span>
                                            {{ is_array($messages) ? implode(', ', $messages) : $messages }}
                                        </div>
                                    @endforeach
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-5 py-3 border-t border-gray-100">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    // Pass server data safely via window variable (avoids double-quote issues in x-data attributes)
    window.__importHistory = @json($history);
    window.__progressUrl   = '{{ route("import.progress", $history) }}';

    document.addEventListener('alpine:init', () => {
        Alpine.data('importProgressData', () => {
            const h = window.__importHistory;
            return {
                status:      h.status,
                totalRows:   h.total_rows,
                successRows: h.success_rows,
                failedRows:  h.failed_rows,
                progress:    h.progress ?? (h.total_rows > 0 ? Math.round(((h.success_rows + h.failed_rows) / h.total_rows) * 100) : 0),
                isRunning:   h.status === 'processing' || h.status === 'pending',

                init() {
                    if (this.isRunning) this.poll();
                },

                poll() {
                    const url = window.__progressUrl;
                    const interval = setInterval(async () => {
                        try {
                            const res  = await fetch(url);
                            const data = await res.json();
                            this.status      = data.status;
                            this.totalRows   = data.total_rows;
                            this.successRows = data.success_rows;
                            this.failedRows  = data.failed_rows;
                            this.progress    = data.progress;
                            this.isRunning   = data.is_running;

                            if (!data.is_running) {
                                clearInterval(interval);
                                setTimeout(() => window.location.reload(), 1000);
                            }
                        } catch(e) {
                            clearInterval(interval);
                        }
                    }, 2000);
                }
            };
        });
    });
</script>
@endpush
@endsection
