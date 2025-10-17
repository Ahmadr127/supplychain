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
            <div class="space-y-2">
                <!-- Row 1: Search, Date From, Date To, Year, Buttons -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-end">
                    <div class="md:col-span-5">
                        <label class="block text-xs font-medium text-gray-600 mb-0.5">Pencarian</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari no input / item / status" class="w-full h-8 px-2 py-1 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-0.5">Dari</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full h-8 px-2 py-1 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-0.5">Sampai</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full h-8 px-2 py-1 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div class="md:col-span-1">
                        <label class="block text-xs font-medium text-gray-600 mb-0.5">Tahun</label>
                        <input type="number" name="year" value="{{ request('year') }}" class="w-full h-8 px-2 py-1 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500" placeholder="YYYY">
                    </div>
                    <div class="md:col-span-2 flex md:justify-end gap-2">
                        <button type="submit" class="h-8 px-3 bg-indigo-600 text-white rounded-md text-xs whitespace-nowrap">Filter</button>
                        <a href="{{ route('reports.approval-requests') }}" class="h-8 px-3 border border-gray-300 rounded-md text-xs flex items-center whitespace-nowrap">Reset</a>
                        <button type="button" onclick="exportData()" class="h-8 px-3 bg-green-600 text-white rounded-md text-xs whitespace-nowrap flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Export CSV
                        </button>
                    </div>
                </div>

                <!-- Row 2: Jenis, Unit, Kategori, Status -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-2 items-end">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-0.5">Jenis</label>
                        <select name="submission_type_id" class="w-full h-8 px-2 py-1 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Semua</option>
                            @foreach($submissionTypes as $s)
                                <option value="{{ $s->id }}" {{ request('submission_type_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-0.5">Unit Pengaju</label>
                        <select name="department_id" class="w-full h-8 px-2 py-1 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Semua</option>
                            @foreach($departments as $d)
                                <option value="{{ $d->id }}" {{ request('department_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-0.5">Kategori</label>
                        <select name="category_id" class="w-full h-8 px-2 py-1 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Semua</option>
                            @foreach($categories as $c)
                                <option value="{{ $c->id }}" {{ request('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-0.5">Status/Process</label>
                        <select name="status" class="w-full h-8 px-2 py-1 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Semua</option>
                            @foreach(['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','cancelled'=>'Cancelled'] as $k=>$v)
                                <option value="{{ $k }}" {{ request('status') === $k ? 'selected' : '' }}>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
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
</script>
@endpush
