@extends('layouts.app')

@section('title', 'Detail Supplier')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
    <div class="lg:col-span-2 space-y-3">
        <div class="bg-white rounded-lg border border-gray-200">
            <div class="p-3 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold">Informasi Supplier</h3>
                <div class="space-x-2">
                    <a href="{{ route('suppliers.edit', $supplier) }}" class="px-3 py-2 bg-yellow-500 text-white rounded">Edit</a>
                    <a href="{{ route('suppliers.index') }}" class="px-3 py-2 bg-gray-600 text-white rounded">Kembali</a>
                </div>
            </div>
            <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <div class="text-gray-500">Nama</div>
                    <div class="font-medium">{{ $supplier->name }}</div>
                </div>
                <div>
                    <div class="text-gray-500">Kode</div>
                    <div class="font-medium">{{ $supplier->code }}</div>
                </div>
                <div>
                    <div class="text-gray-500">Email</div>
                    <div class="font-medium">{{ $supplier->email ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-gray-500">Telepon</div>
                    <div class="font-medium">{{ $supplier->phone ?? '-' }}</div>
                </div>
                <div class="md:col-span-2">
                    <div class="text-gray-500">Alamat</div>
                    <div class="font-medium">{{ $supplier->address ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-gray-500">Status</div>
                    <div>
                        <span class="px-2 py-1 text-xs rounded {{ $supplier->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">{{ $supplier->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                    </div>
                </div>
                <div class="md:col-span-2">
                    <div class="text-gray-500">Catatan</div>
                    <div class="font-medium">{{ $supplier->notes ?? '-' }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="space-y-3">
        <div class="bg-white rounded-lg border border-gray-200">
            <div class="p-3 border-b border-gray-200">
                <h3 class="text-lg font-semibold">Meta</h3>
            </div>
            <div class="p-4 text-sm space-y-2">
                <div class="flex justify-between"><span class="text-gray-500">Dibuat</span><span>{{ $supplier->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Diubah</span><span>{{ $supplier->updated_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</span></div>
            </div>
        </div>
    </div>
</div>
@endsection
