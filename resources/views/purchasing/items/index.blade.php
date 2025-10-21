@extends('layouts.app')

@section('title', 'Purchasing Items')

@section('content')
<div class="space-y-3">
    <div class="bg-white p-3 rounded-lg border border-gray-200">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-2 items-end">
            <div class="md:col-span-3">
                <label class="block text-xs font-medium text-gray-600 mb-0.5">Pencarian</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari No Input / Nama Item" class="w-full h-8 px-2 py-1 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-0.5">Status</label>
                <select name="status" class="w-full h-8 px-2 py-1 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Semua</option>
                    @foreach(['unprocessed','benchmarking','comparing','selected','po_issued','grn_received','done'] as $st)
                        <option value="{{ $st }}" {{ request('status')===$st ? 'selected' : '' }}>{{ strtoupper($st) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2 flex gap-2">
                <button class="h-8 px-3 bg-indigo-600 text-white rounded-md text-xs">Filter</button>
                <a href="{{ route('purchasing.items.index') }}" class="h-8 px-3 border border-gray-300 rounded-md text-xs flex items-center">Reset</a>
            </div>
        </form>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <div class="overflow-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-700">
                    <tr>
                        <th class="px-3 py-2 text-left">No Input</th>
                        <th class="px-3 py-2 text-left">Item</th>
                        <th class="px-3 py-2 text-left">Qty</th>
                        <th class="px-3 py-2 text-left">Status</th>
                        <th class="px-3 py-2 text-left">Preferred Vendor</th>
                        <th class="px-3 py-2 text-left">PO</th>
                        <th class="px-3 py-2 text-left">GRN Date</th>
                        <th class="px-3 py-2 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $pi)
                        <tr class="border-t">
                            <td class="px-3 py-2 text-sm text-gray-900">{{ $pi->approvalRequest->request_number ?? '-' }}</td>
                            <td class="px-3 py-2 text-sm text-gray-900">{{ $pi->masterItem->name ?? '-' }}</td>
                            <td class="px-3 py-2 text-sm text-gray-900">{{ $pi->quantity }}</td>
                            <td class="px-3 py-2 text-sm">
                                @php
                                    $ps = $pi->status ?? 'unprocessed';
                                    $psText = match($ps){
                                        'unprocessed' => 'Belum diproses',
                                        'benchmarking' => 'Pemilihan vendor',
                                        'selected' => 'Uji coba/Proses PR sistem',
                                        'po_issued' => 'Proses di vendor',
                                        'grn_received' => 'Barang sudah diterima',
                                        'done' => 'Selesai',
                                        default => strtoupper($ps),
                                    };
                                    $psColor = match($ps){
                                        'unprocessed' => 'bg-gray-100 text-gray-700',
                                        'benchmarking' => 'bg-yellow-100 text-yellow-800',
                                        'selected' => 'bg-blue-100 text-blue-800',
                                        'po_issued' => 'bg-indigo-100 text-indigo-800',
                                        'grn_received' => 'bg-teal-100 text-teal-800',
                                        'done' => 'bg-green-100 text-green-800',
                                        default => 'bg-gray-100 text-gray-700',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $psColor }}">{{ $psText }}</span>
                            </td>
                            <td class="px-3 py-2 text-sm text-gray-900">{{ $pi->preferredVendor->name ?? '-' }}</td>
                            <td class="px-3 py-2 text-sm text-gray-900">{{ $pi->po_number ?? '-' }}</td>
                            <td class="px-3 py-2 text-sm text-gray-900">{{ $pi->grn_date ? $pi->grn_date->format('Y-m-d') : '-' }}</td>
                            <td class="px-3 py-2 text-sm">
                                <a href="{{ route('purchasing.items.show', $pi) }}" class="text-indigo-600 hover:underline">Buka</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-3 py-4 text-center text-sm text-gray-500">Belum ada data</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-2 border-t">{{ $items->links() }}</div>
    </div>
</div>
@endsection
