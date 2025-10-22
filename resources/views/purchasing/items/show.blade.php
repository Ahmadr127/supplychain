@extends('layouts.app')

@section('title', 'Purchasing Item Detail')

@section('content')
<div class="space-y-3">
    <!-- Received Date (Tanggal Diterima) -->
    <div class="bg-white border border-gray-200 rounded-lg p-3">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm font-semibold text-gray-900">Tanggal Diterima Dokumen</div>
                <div class="text-xs text-gray-600">Set tanggal diterima di level request.</div>
            </div>
            @if(auth()->user()->hasPermission('manage_purchasing'))
            <form method="POST" action="{{ route('approval-requests.set-received-date', $item->approvalRequest) }}" class="flex items-end gap-2">
                @csrf
                <div>
                    <label class="block text-xs text-gray-600 mb-0.5">Tanggal</label>
                    <input type="date" name="received_at" value="{{ $item->approvalRequest->received_at ? $item->approvalRequest->received_at->format('Y-m-d') : '' }}" class="h-8 px-2 border border-gray-300 rounded text-sm" />
                </div>
                <button class="px-3 py-1.5 bg-blue-600 text-white rounded text-sm">Simpan</button>
            </form>
            @else
                <div class="text-sm text-gray-700">Tanggal: <span class="font-medium text-gray-900">{{ $item->approvalRequest->received_at ? $item->approvalRequest->received_at->format('Y-m-d') : '-' }}</span></div>
            @endif
        </div>
    </div>
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">Purchasing Item</h2>
            <p class="text-sm text-gray-600">Request: {{ $item->approvalRequest->request_number ?? '-' }} • Item: {{ $item->masterItem->name ?? '-' }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('purchasing.items.index') }}" class="px-3 py-1.5 text-sm rounded border border-gray-300 hover:bg-gray-50">Kembali</a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
        <div class="bg-white border border-gray-200 rounded-lg p-3">
            <div class="text-xs text-gray-600">Status</div>
            @php
                $ps = $item->status ?? 'unprocessed';
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
            <div class="mt-1"><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $psColor }}">{{ $psText }}</span></div>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-3">
            <div class="text-xs text-gray-600">Preferred Vendor</div>
            <div class="text-sm font-medium text-gray-900">{{ $item->preferredVendor->name ?? '-' }}</div>
        </div>
            <div class="text-xs text-gray-600">PO</div>
            <div class="text-sm font-medium text-gray-900">{{ $item->po_number ?? '-' }}</div>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-3">
            <div class="text-xs text-gray-600">GRN Date • Proc Cycle</div>
            <div class="text-sm font-medium text-gray-900">{{ $item->grn_date ? $item->grn_date->format('Y-m-d') : '-' }} @if($item->proc_cycle_days) • {{ $item->proc_cycle_days }} hari @endif</div>
        </div>
    </div>

    @include('purchasing.items._form', ['item' => $item])

    <!-- PO & GRN -->
    <div class="bg-white border border-gray-200 rounded-lg">
        <div class="px-3 py-2 border-b border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900">PO & GRN</h3>
{{ ... }}
        <div class="p-3 grid grid-cols-1 md:grid-cols-2 gap-3">
            @if(auth()->user()->hasPermission('manage_purchasing'))
            <form method="POST" action="{{ route('purchasing.items.po', $item) }}" class="space-y-2">
                @csrf
                <label class="block text-xs text-gray-600 mb-0.5">PO Number</label>
                <div class="flex gap-2">
                    <input type="text" name="po_number" value="{{ $item->po_number }}" class="flex-1 h-8 px-2 border border-gray-300 rounded text-sm" placeholder="PO..." />
                    <button class="px-3 py-1.5 bg-blue-600 text-white rounded text-sm">Simpan PO</button>
                </div>
            </form>
            <form method="POST" action="{{ route('purchasing.items.grn', $item) }}" class="space-y-2">
                @csrf
                <label class="block text-xs text-gray-600 mb-0.5">GRN Date</label>
                <div class="flex gap-2">
                    <input type="date" name="grn_date" value="{{ $item->grn_date ? $item->grn_date->format('Y-m-d') : '' }}" class="h-8 px-2 border border-gray-300 rounded text-sm" />
                    <button class="px-3 py-1.5 bg-blue-600 text-white rounded text-sm">Simpan GRN</button>
                </div>
            </form>
            @else
                <div class="text-sm text-gray-700">PO: <span class="font-medium text-gray-900">{{ $item->po_number ?? '-' }}</span></div>
                <div class="text-sm text-gray-700">GRN: <span class="font-medium text-gray-900">{{ $item->grn_date ? $item->grn_date->format('Y-m-d') : '-' }}</span></div>
            @endif
        </div>
    </div>

    <!-- Mark DONE -->
    @if(auth()->user()->hasPermission('manage_purchasing'))
    <div class="flex justify-end">
        <form method="POST" action="{{ route('purchasing.items.done', $item) }}" onsubmit="return confirm('Tandai item ini sebagai DONE?');" class="w-full md:w-auto grid grid-cols-1 md:grid-cols-3 gap-2 md:items-end">
            @csrf
            <div class="md:col-span-2">
                <label class="block text-xs text-gray-600 mb-0.5">Catatan (opsional)</label>
                <textarea name="done_notes" rows="2" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" placeholder="Tulis catatan untuk penutupan (DONE)...">{{ old('done_notes', $item->done_notes) }}</textarea>
            </div>
            <div>
                <button class="px-4 py-2 bg-green-600 text-white rounded text-sm w-full">Mark as DONE</button>
            </div>
        </form>
    </div>
    @endif
</div>
@endsection

@push('scripts')
    @include('purchasing.items._form-scripts', ['item' => $item])
@endpush
