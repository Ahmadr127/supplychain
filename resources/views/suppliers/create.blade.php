@extends('layouts.app')

@section('title', 'Tambah Supplier')

@section('content')
<div class="bg-white rounded-lg border border-gray-200">
    <div class="p-3 border-b border-gray-200 flex items-center justify-between">
        <h3 class="text-lg font-semibold">Form Supplier</h3>
        <a href="{{ route('suppliers.index') }}" class="px-3 py-2 bg-gray-600 text-white rounded">Kembali</a>
    </div>
    <form method="POST" action="{{ route('suppliers.store') }}" class="p-3 space-y-3">
        @csrf
        @if ($errors->any())
            <div class="p-3 bg-red-50 border border-red-200 rounded text-red-700 text-sm">
                <div class="font-semibold mb-1">Terjadi kesalahan validasi:</div>
                <ul class="list-disc pl-5 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @include('suppliers._form')
        <div class="pt-2">
            <button class="px-4 py-2 bg-blue-600 text-white rounded">Simpan</button>
        </div>
    </form>
</div>
@endsection
