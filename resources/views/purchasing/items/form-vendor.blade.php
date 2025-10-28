@extends('layouts.app')

@section('title', 'Kelola Vendor Purchasing')

@section('content')
<div class="space-y-3">
    {{-- Success handled by global toast --}}
    @if(session('error'))
        <div class="px-3 py-2 rounded bg-red-50 text-red-700 border border-red-200 text-sm">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="px-3 py-2 rounded bg-red-50 text-red-700 border border-red-200 text-sm">
            <div class="font-semibold mb-1">Terjadi kesalahan:</div>
            <ul class="list-disc ml-5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">Kelola Vendor</h2>
            <p class="text-sm text-gray-600">Request: {{ $item->approvalRequest->request_number ?? '-' }} • Item: {{ $item->masterItem->name ?? '-' }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('reports.approval-requests') }}" class="px-3 py-1.5 text-sm rounded border border-gray-300 hover:bg-gray-50">Kembali</a>
        </div>
    </div>

    @include('purchasing.items._form', [
        'item' => $item,
        'disableBenchmarking' => true,
        'disablePreferred' => false,
    ])
</div>
@endsection

@push('scripts')
    @include('purchasing.items._form-scripts', ['item' => $item])
@endpush

