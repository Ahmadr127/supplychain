@extends('layouts.app')

@section('title', 'Laporan Pengajuan')

@section('content')
<x-responsive-table 
    title="Laporan Pengajuan"
    :pagination="$paginator"
    :emptyState="$paginator->count() === 0"
    emptyMessage="Belum ada data">

    <x-slot name="filters">
        <form method="GET" class="w-full">
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
                        <button class="h-8 px-3 bg-indigo-600 text-white rounded-md text-xs whitespace-nowrap">Filter</button>
                        <a href="{{ route('reports.approval-requests') }}" class="h-8 px-3 border border-gray-300 rounded-md text-xs flex items-center whitespace-nowrap">Reset</a>
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

                @if(auth()->user()->hasPermission('manage_purchasing'))
                <div class="flex justify-end">
                    <a href="{{ route('purchasing.items.index') }}" class="h-8 px-3 bg-emerald-600 text-white rounded-md text-xs whitespace-nowrap">Purchasing</a>
                </div>
                @endif
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
