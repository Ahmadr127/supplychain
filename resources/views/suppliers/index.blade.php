@extends('layouts.app')

@section('title', 'Suppliers')

@section('content')
<div class="bg-white rounded-lg border border-gray-200">
    <div class="p-3 border-b border-gray-200 flex items-center justify-between gap-2">
        <form method="GET" class="flex items-center gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, kode, kontak, email, telepon" class="px-3 py-2 border rounded w-64">
            <select name="status" class="px-3 py-2 border rounded">
                <option value="">Semua Status</option>
                <option value="active" @selected(request('status')==='active')>Aktif</option>
                <option value="inactive" @selected(request('status')==='inactive')>Nonaktif</option>
            </select>
            <button class="px-4 py-2 bg-green-600 text-white rounded">Filter</button>
        </form>
        <a href="{{ route('suppliers.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded"><i class="fas fa-plus mr-1"></i>Tambah Supplier</a>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-left">
                    <th class="px-3 py-2">Nama</th>
                    <th class="px-3 py-2">Kode</th>
                    <th class="px-3 py-2">Email</th>
                    <th class="px-3 py-2">Telepon</th>
                    <th class="px-3 py-2">Status</th>
                    <th class="px-3 py-2 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($suppliers as $s)
                <tr class="border-t">
                    <td class="px-3 py-2">{{ $s->name }}</td>
                    <td class="px-3 py-2">{{ $s->code }}</td>
                    <td class="px-3 py-2">{{ $s->email }}</td>
                    <td class="px-3 py-2">{{ $s->phone }}</td>
                    <td class="px-3 py-2">
                        <span class="px-2 py-1 text-xs rounded {{ $s->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">{{ $s->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                    </td>
                    <td class="px-3 py-2 text-right space-x-2">
                        <a href="{{ route('suppliers.show', $s) }}" class="text-blue-600">Detail</a>
                        <a href="{{ route('suppliers.edit', $s) }}" class="text-yellow-700">Edit</a>
                        <form method="POST" action="{{ route('suppliers.destroy', $s) }}" class="inline" onsubmit="return confirm('Hapus supplier ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-700">Hapus</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-3 py-6 text-center text-gray-500">Tidak ada data</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-3">{{ $suppliers->links() }}</div>
</div>
@endsection
