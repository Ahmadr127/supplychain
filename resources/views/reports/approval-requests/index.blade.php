@extends('layouts.app')

@section('title', 'Laporan Pengajuan')

@section('content')
<x-responsive-table 
    title="Laporan Pengajuan"
    :pagination="$paginator"
    :emptyState="$paginator->count() === 0"
    emptyMessage="Belum ada data">

    <x-slot name="filters">


        <form method="GET" class="w-full" id="filter-form">
            <div class="space-y-2" x-data="{ showAdvanced: false }" @toggle-advanced-filter.window="showAdvanced = !showAdvanced">
                <!-- Main Filter Bar -->
                <div class="flex flex-col lg:flex-row gap-2">
                    <!-- Search Section -->
                    <div class="flex flex-1 gap-2 items-center">
                        <div class="flex-1 max-w-md">
                            <div class="relative">
                                <input type="text" name="search" value="{{ request('search') }}" 
                                       placeholder="Cari request..."
                                       class="w-full h-9 pl-9 pr-3 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <svg class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                        </div>
                        
                        <!-- Purchasing Status Filter -->
                        <select name="purchasing_status" class="h-9 px-3 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Semua Proses</option>
                            <option value="unprocessed" {{ request('purchasing_status') === 'unprocessed' ? 'selected' : '' }}>Belum diproses</option>
                            <option value="benchmarking" {{ request('purchasing_status') === 'benchmarking' ? 'selected' : '' }}>Pemilihan vendor</option>
                            <option value="selected" {{ request('purchasing_status') === 'selected' ? 'selected' : '' }}>Proses PR & PO</option>
                            <option value="po_issued" {{ request('purchasing_status') === 'po_issued' ? 'selected' : '' }}>Proses di vendor</option>
                            <option value="grn_received" {{ request('purchasing_status') === 'grn_received' ? 'selected' : '' }}>Barang diterima</option>
                            <option value="done" {{ request('purchasing_status') === 'done' ? 'selected' : '' }}>Selesai</option>
                        </select>
                        
                        <!-- Advanced Filters Toggle -->
                        <button type="button" @click="$dispatch('toggle-advanced-filter')" 
                                class="h-9 px-3 text-sm text-gray-600 hover:text-gray-900 border border-gray-300 rounded-md hover:bg-gray-50">
                            <i class="fas fa-filter mr-1"></i>
                            <span x-data="{ open: false }" @toggle-advanced-filter.window="open = !open" x-text="open ? 'Sembunyikan' : 'Filter Lanjutan'"></span>
                        </button>
                        
                        <!-- Submit Button -->
                        <button type="submit" class="h-9 px-4 text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors">
                            <i class="fas fa-search mr-1"></i>
                            Filter
                        </button>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex gap-2 flex-shrink-0">
                        <!-- Per Page Selector -->
                        <select name="per_page" class="h-9 px-3 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="this.form.submit()">
                            <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                            <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                        </select>
                        
                        <a href="{{ route('reports.approval-requests') }}" 
                           class="h-9 px-3 inline-flex items-center text-sm font-medium border border-gray-300 hover:bg-gray-50 rounded-md transition-colors"
                           title="Reset Filter">
                            <i class="fas fa-undo"></i>
                        </a>
                        
                        <button type="button" onclick="exportData()" 
                                class="h-9 px-3 inline-flex items-center text-sm font-medium bg-green-600 hover:bg-green-700 text-white rounded-md transition-colors"
                                title="Export CSV">
                            <i class="fas fa-download mr-1.5"></i>
                            <span class="hidden sm:inline">Export</span>
                        </button>
                    </div>
                </div>
                
                <!-- Advanced Filters Row (shown below main filter) -->
                <div x-show="showAdvanced" x-transition 
                     class="flex flex-wrap gap-2 p-3 bg-gray-50 rounded-md border border-gray-200">
                    <div class="flex-1 min-w-[150px]">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Tanggal</label>
                        <input type="date" id="single-date" 
                               value="{{ request('date_from') && request('date_to') && request('date_from')===request('date_to') ? request('date_from') : '' }}" 
                               class="w-full h-9 px-3 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <input type="hidden" name="date_from" id="date_from" value="{{ request('date_from') }}">
                        <input type="hidden" name="date_to" id="date_to" value="{{ request('date_to') }}">
                    </div>
                    
                    <div class="flex-1 min-w-[150px]">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Jenis Pengajuan</label>
                        <select name="submission_type_id" class="w-full h-9 px-3 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Semua Jenis</option>
                            @foreach($submissionTypes as $s)
                                <option value="{{ $s->id }}" {{ request('submission_type_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="flex-1 min-w-[150px]">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Unit Pengaju</label>
                        <select name="department_id" class="w-full h-9 px-3 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Semua Unit</option>
                            @foreach($departments as $d)
                                <option value="{{ $d->id }}" {{ request('department_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    
                </div>
                
                <!-- Status Legend with Counts -->
                <div class="flex flex-wrap gap-2 py-1">
                    <x-info-status class="py-1" variant="purchasing" size="sm" :counts="$purchasingCounts ?? []" />
                </div>
            </div>
        </form>
    </x-slot>

    @php
        $data = $rows;
        $columns = $columns;
    @endphp
    <x-data-table :columns="$columns" :data="$data" :actions="true" />

</x-responsive-table>

 

@endsection

@push('scripts')
<script>
function exportData() {
    const form = document.getElementById('filter-form');
    const formData = new FormData(form);
    const params = new URLSearchParams(formData);
    const exportUrl = '{{ route('reports.approval-requests.export') }}?' + params.toString();
    window.location.href = exportUrl;
}
// Keep single date in sync with backend date_from/date_to
document.addEventListener('DOMContentLoaded', function(){
    const single = document.getElementById('single-date');
    const from = document.getElementById('date_from');
    const to = document.getElementById('date_to');
    if(single){
        single.addEventListener('change', function(){
            from.value = this.value || '';
            to.value = this.value || '';
        });
    }
});
</script>
@endpush
