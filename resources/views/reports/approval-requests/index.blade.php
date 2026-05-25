@extends('layouts.app')

@section('title', 'Laporan Pengajuan')

@section('content')
<x-responsive-table 
    title="Laporan Pengajuan"
    :pagination="$paginator"
    :emptyState="$paginator->count() === 0"
    emptyMessage="Belum ada data">

    {{-- Per-page selector di bawah tabel (sebelah info entri) --}}
    <x-slot name="perPageSlot">
        <form method="GET" id="per-page-form">
            @foreach(request()->except(['per_page','page']) as $k => $v)
                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
            @endforeach
            <div class="flex items-center gap-1.5">
                <label class="text-xs text-gray-500">Tampilkan</label>
                <select name="per_page"
                        class="h-7 px-2 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500"
                        onchange="this.form.submit()">
                    <option value="10"  {{ $perPage == 10  ? 'selected' : '' }}>10</option>
                    <option value="25"  {{ $perPage == 25  ? 'selected' : '' }}>25</option>
                    <option value="50"  {{ $perPage == 50  ? 'selected' : '' }}>50</option>
                    <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                </select>
                <label class="text-xs text-gray-500">baris</label>
            </div>
        </form>
    </x-slot>

    <x-slot name="filters">
        <form method="GET" class="w-full" id="filter-form">
            <input type="hidden" name="reset" value="" id="reset-flag">

            <div x-data="{ showAdvanced: false }" class="space-y-2">

                {{-- ═══ MAIN TOOLBAR ═══ --}}
                <div class="flex gap-2 items-center flex-wrap">

                    {{-- Search (flexible) --}}
                    <div class="relative flex-1 min-w-[180px]">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Cari no request / nama barang..."
                               class="w-full h-9 pl-9 pr-3 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    {{-- Purchasing Status --}}
                    <select name="purchasing_status"
                            class="h-9 px-3 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 shrink-0">
                        <option value="">Semua Proses</option>
                        <option value="pending_approval" {{ request('purchasing_status') === 'pending_approval' ? 'selected' : '' }}>Menunggu Approval</option>
                        <option value="unprocessed"      {{ request('purchasing_status') === 'unprocessed'      ? 'selected' : '' }}>Belum diproses</option>
                        <option value="benchmarking"     {{ request('purchasing_status') === 'benchmarking'     ? 'selected' : '' }}>Pemilihan vendor</option>
                        <option value="selected"         {{ request('purchasing_status') === 'selected'         ? 'selected' : '' }}>Proses PR & PO</option>
                        <option value="po_issued"        {{ request('purchasing_status') === 'po_issued'        ? 'selected' : '' }}>Proses di vendor</option>
                        <option value="grn_received"     {{ request('purchasing_status') === 'grn_received'     ? 'selected' : '' }}>Barang diterima</option>
                        <option value="done"             {{ request('purchasing_status') === 'done'             ? 'selected' : '' }}>Selesai</option>
                    </select>

                    {{-- Filter Lanjutan toggle --}}
                    <button type="button" @click="showAdvanced = !showAdvanced"
                            class="h-9 px-3 text-sm border border-gray-300 rounded-md hover:bg-gray-50 flex items-center gap-1.5 shrink-0 transition-colors"
                            :class="showAdvanced ? 'bg-blue-50 border-blue-300 text-blue-700' : 'text-gray-600'">
                        <i class="fas fa-sliders-h text-xs"></i>
                        <span class="hidden sm:inline" x-text="showAdvanced ? 'Sembunyikan' : 'Filter'"></span>
                    </button>

                    {{-- Cari + Reset bersebelahan --}}
                    <div class="flex shrink-0">
                        <button type="submit"
                                class="h-9 px-4 text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white rounded-l-md transition-colors border-r border-blue-700">
                            <i class="fas fa-search mr-1"></i>Cari
                        </button>
                        <a href="{{ route('reports.approval-requests') }}?reset=1"
                           title="Reset semua filter"
                           class="h-9 w-9 inline-flex items-center justify-center bg-gray-100 hover:bg-gray-200 border border-gray-300 border-l-0 rounded-r-md transition-colors text-gray-500">
                            <i class="fas fa-undo text-xs"></i>
                        </a>
                    </div>

                    {{-- Divider --}}
                    <div class="h-6 w-px bg-gray-200 shrink-0 hidden sm:block"></div>

                    {{-- Column Toggler --}}
                    <x-column-toggler :columns="$columns" storage-key="report_columns_v1" />

                    {{-- Export (icon only) --}}
                    <button type="button" onclick="exportData()"
                            class="h-9 w-9 inline-flex items-center justify-center bg-green-600 hover:bg-green-700 text-white rounded-md transition-colors shrink-0"
                            title="Export CSV">
                        <i class="fas fa-download text-sm"></i>
                    </button>
                </div>

                {{-- ═══ ADVANCED FILTERS (collapsible grid) ═══ --}}
                <div x-show="showAdvanced"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-2 px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-md"
                     style="display:none;">

                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Tanggal</label>
                        <input type="date" id="single-date"
                               value="{{ request('date_from') && request('date_to') && request('date_from')===request('date_to') ? request('date_from') : '' }}"
                               class="w-full h-8 px-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <input type="hidden" name="date_from" id="date_from" value="{{ request('date_from') }}">
                        <input type="hidden" name="date_to"   id="date_to"   value="{{ request('date_to') }}">
                    </div>

                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Tahun</label>
                        <select name="year" class="w-full h-8 px-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                            <option value="">Semua Tahun</option>
                            @for($y = now()->year; $y >= now()->year - 5; $y--)
                                <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Jenis Pengajuan</label>
                        <x-async-select height="h-8"
                            name="submission_type_id"
                            placeholder="Semua Jenis"
                            :endpoint="route('api.reports.filter-options', 'submission_types')"
                            :value="request('submission_type_id')"
                            :selected-label="$submissionTypes->firstWhere('id', request('submission_type_id'))?->name ?? ''" />
                    </div>

                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Unit Pengaju</label>
                        <x-async-select height="h-8"
                            name="department_id"
                            placeholder="Semua Unit"
                            :endpoint="route('api.reports.filter-options', 'departments')"
                            :value="request('department_id')"
                            :selected-label="$departments->firstWhere('id', request('department_id'))?->name ?? ''" />
                    </div>

                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Nama Pengaju</label>
                        <x-async-select height="h-8"
                            name="requester_id"
                            placeholder="Semua Pengaju"
                            :endpoint="route('api.reports.filter-options', 'requesters')"
                            :value="request('requester_id')"
                            :selected-label="$requesterName ?? ''" />
                    </div>

                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Kategori</label>
                        <x-async-select height="h-8"
                            name="category_id"
                            placeholder="Semua Kategori"
                            :endpoint="route('api.reports.filter-options', 'categories')"
                            :value="request('category_id')"
                            :selected-label="$categories->firstWhere('id', request('category_id'))?->name ?? ''" />
                    </div>
                </div>

                {{-- ═══ STATUS COUNTS + ACTIVE FILTER BADGES ═══ --}}
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                    <x-info-status variant="purchasing" size="sm" :counts="$purchasingCounts ?? []" />

                    @php
                        $activeFilters = array_filter([
                            request('search')             ? '"' . request('search') . '"'                                                                  : null,
                            request('purchasing_status')  ? match(request('purchasing_status')) {
                                'pending_approval' => 'Menunggu Approval',
                                'unprocessed' => 'Belum diproses',
                                'benchmarking' => 'Pemilihan vendor',
                                'selected' => 'Proses PR & PO',
                                'po_issued'   => 'Proses di vendor',
                                'grn_received' => 'Barang diterima',
                                'done'        => 'Selesai',
                                default => request('purchasing_status'),
                            } : null,
                            request('date_from')          ? request('date_from')                                                                           : null,
                            request('year')               ? request('year')                                                                                : null,
                            request('submission_type_id') ? ($submissionTypes->firstWhere('id', request('submission_type_id'))?->name ?? null)             : null,
                            request('department_id')      ? ($departments->firstWhere('id', request('department_id'))?->name ?? null)                      : null,
                            request('category_id')        ? ($categories->firstWhere('id', request('category_id'))?->name ?? null)                        : null,
                            request('requester_id') && isset($requesterName) ? $requesterName                                                              : null,
                        ]);
                    @endphp
                    @if(count($activeFilters) > 0)
                        <span class="text-xs text-gray-300">|</span>
                        <span class="text-xs text-gray-500">Filter aktif:</span>
                        @foreach($activeFilters as $label)
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700">
                                {{ $label }}
                            </span>
                        @endforeach
                        <a href="{{ route('reports.approval-requests') }}?reset=1"
                           class="text-xs text-red-500 hover:text-red-700 underline">✕ hapus</a>
                    @endif
                </div>

            </div>
        </form>
    </x-slot>

    <x-data-table :columns="$columns" :data="$rows" :actions="true" />

</x-responsive-table>
@endsection

@push('scripts')
<script>
function exportData() {
    const params = new URLSearchParams(new FormData(document.getElementById('filter-form')));
    window.location.href = '{{ route('reports.approval-requests.export') }}?' + params.toString();
}
document.addEventListener('DOMContentLoaded', function () {
    const single = document.getElementById('single-date');
    const from   = document.getElementById('date_from');
    const to     = document.getElementById('date_to');
    if (single) {
        single.addEventListener('change', function () {
            from.value = to.value = this.value || '';
        });
    }
});
</script>
@endpush
