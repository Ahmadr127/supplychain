@extends('layouts.app')

@section('title', 'Edit Supplier')

@section('content')
<div class="bg-white rounded-lg border border-gray-200">
    <div class="p-3 border-b border-gray-200 flex items-center justify-between">
        <h3 class="text-lg font-semibold">Edit Supplier: {{ $supplier->name }}</h3>
        <a href="{{ route('suppliers.show', $supplier) }}" class="px-3 py-2 bg-gray-600 text-white rounded">Kembali</a>
    </div>
    <form method="POST" action="{{ route('suppliers.update', $supplier) }}" class="p-3 space-y-3">
        @csrf
        @method('PUT')
        @include('suppliers._form', ['supplier' => $supplier])
        <div class="pt-2">
            <button class="px-4 py-2 bg-blue-600 text-white rounded">Simpan Perubahan</button>
        </div>
    </form>
</div>
@endsection
