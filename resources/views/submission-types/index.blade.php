@extends('layouts.app')

@section('title', 'Jenis Pengajuan')

@section('content')
<div class="bg-white shadow rounded-lg p-3">
    <div class="flex items-center justify-between mb-2">
        <form method="GET" class="flex items-center space-x-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama/kode/deskripsi"
                   class="w-64 px-3 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            <button class="px-3 py-1 text-sm bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200">Cari</button>
        </form>
        <button type="button" onclick="openCreateModal()" class="px-3 py-1 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700">
            <i class="fas fa-plus mr-1"></i> Tambah
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-gray-600 border-b">
                    <th class="py-2 px-2 w-12">#</th>
                    <th class="py-2 px-2">Nama</th>
                    <th class="py-2 px-2 w-32">Kode</th>
                    <th class="py-2 px-2">Deskripsi</th>
                    <th class="py-2 px-2 w-24">Aktif</th>
                    <th class="py-2 px-2 w-28 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($submissionTypes as $i => $st)
                <tr class="border-b hover:bg-gray-50">
                    <td class="py-2 px-2">{{ $submissionTypes->firstItem() + $i }}</td>
                    <td class="py-2 px-2 font-medium">{{ $st->name }}</td>
                    <td class="py-2 px-2">{{ $st->code }}</td>
                    <td class="py-2 px-2 text-gray-600">{{ $st->description }}</td>
                    <td class="py-2 px-2">
                        <span class="inline-flex items-center text-xs px-2 py-0.5 rounded {{ $st->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">{{ $st->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                    </td>
                    <td class="py-2 px-2 text-right space-x-2">
                        <button type="button" class="px-2 py-1 text-xs bg-yellow-500 text-white rounded hover:bg-yellow-600" onclick='openEditModal(@json($st))'>Edit</button>
                        <form method="POST" action="{{ route('submission-types.destroy', $st) }}" class="inline" onsubmit="return confirm('Hapus jenis pengajuan ini?')">
                            @csrf
                            @method('DELETE')
                            <button class="px-2 py-1 text-xs bg-red-600 text-white rounded hover:bg-red-700">Hapus</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-6 text-center text-gray-500">Tidak ada data</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-2">{{ $submissionTypes->links() }}</div>
</div>

<!-- Create Modal -->
<div id="modalCreate" class="fixed inset-0 bg-black bg-opacity-40 hidden items-center justify-center z-50">
    <div class="bg-white rounded-md w-full max-w-md p-3 shadow">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-base font-semibold">Tambah Jenis Pengajuan</h3>
            <button class="text-gray-500 hover:text-gray-700" onclick="closeCreateModal()"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" action="{{ route('submission-types.store') }}" class="space-y-2">
            @csrf
            <div>
                <label class="text-sm">Nama</label>
                <input type="text" name="name" class="w-full px-3 py-1 border rounded" required>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="text-sm">Kode</label>
                    <input type="text" name="code" class="w-full px-3 py-1 border rounded" required>
                </div>
                <div class="flex items-center space-x-2 mt-6">
                    <input type="checkbox" name="is_active" id="create_is_active" value="1" checked>
                    <label for="create_is_active" class="text-sm">Aktif</label>
                </div>
            </div>
            <div>
                <label class="text-sm">Deskripsi</label>
                <input type="text" name="description" class="w-full px-3 py-1 border rounded">
            </div>
            <div class="flex justify-end space-x-2 pt-1">
                <button type="button" class="px-3 py-1 border rounded" onclick="closeCreateModal()">Batal</button>
                <button class="px-3 py-1 bg-blue-600 text-white rounded">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="modalEdit" class="fixed inset-0 bg-black bg-opacity-40 hidden items-center justify-center z-50">
    <div class="bg-white rounded-md w-full max-w-md p-3 shadow">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-base font-semibold">Edit Jenis Pengajuan</h3>
            <button class="text-gray-500 hover:text-gray-700" onclick="closeEditModal()"><i class="fas fa-times"></i></button>
        </div>
        <form id="editForm" method="POST" class="space-y-2">
            @csrf
            @method('PUT')
            <div>
                <label class="text-sm">Nama</label>
                <input type="text" name="name" id="edit_name" class="w-full px-3 py-1 border rounded" required>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="text-sm">Kode</label>
                    <input type="text" name="code" id="edit_code" class="w-full px-3 py-1 border rounded" required>
                </div>
                <div class="flex items-center space-x-2 mt-6">
                    <input type="checkbox" name="is_active" id="edit_is_active" value="1">
                    <label for="edit_is_active" class="text-sm">Aktif</label>
                </div>
            </div>
            <div>
                <label class="text-sm">Deskripsi</label>
                <input type="text" name="description" id="edit_description" class="w-full px-3 py-1 border rounded">
            </div>
            <div class="flex justify-end space-x-2 pt-1">
                <button type="button" class="px-3 py-1 border rounded" onclick="closeEditModal()">Batal</button>
                <button class="px-3 py-1 bg-blue-600 text-white rounded">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateModal(){
    document.getElementById('modalCreate').classList.remove('hidden');
    document.getElementById('modalCreate').classList.add('flex');
}
function closeCreateModal(){
    document.getElementById('modalCreate').classList.add('hidden');
    document.getElementById('modalCreate').classList.remove('flex');
}
function openEditModal(data){
    const form = document.getElementById('editForm');
    form.action = "{{ url('submission-types') }}/" + data.id;
    document.getElementById('edit_name').value = data.name || '';
    document.getElementById('edit_code').value = data.code || '';
    document.getElementById('edit_description').value = data.description || '';
    document.getElementById('edit_is_active').checked = !!data.is_active;
    document.getElementById('modalEdit').classList.remove('hidden');
    document.getElementById('modalEdit').classList.add('flex');
}
function closeEditModal(){
    document.getElementById('modalEdit').classList.add('hidden');
    document.getElementById('modalEdit').classList.remove('flex');
}
</script>
@endsection
