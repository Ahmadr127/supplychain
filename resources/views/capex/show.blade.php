@extends('layouts.app')

@section('title', 'Detail CapEx')

@section('content')
<div>
    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-600 mb-1">
                <a href="{{ route('capex.index') }}" class="hover:text-blue-600">Master CapEx</a>
                <span>/</span>
                <span>Detail</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">
                {{ $capex->department->name }} - {{ $capex->fiscal_year }}
            </h1>
        </div>
        <div class="flex gap-2">
            @if(auth()->user()->hasPermission('manage_capex'))
            <a href="{{ route('capex.import.upload-unit', $capex) }}"
               class="bg-blue-50 hover:bg-blue-100 text-blue-700 font-medium py-2 px-4 rounded-lg text-sm transition-colors">
                <i class="fas fa-file-import mr-2"></i>Import Excel
            </a>
            <form action="{{ route('capex.destroy', $capex) }}" method="POST" onsubmit="return confirm('Yakin hapus CapEx ini beserta semua itemnya?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-red-100 hover:bg-red-200 text-red-700 font-medium py-2 px-4 rounded-lg text-sm transition-colors">
                    <i class="fas fa-trash mr-2"></i>Hapus
                </button>
            </form>
            <a href="{{ route('capex.edit', $capex) }}" class="bg-yellow-100 hover:bg-yellow-200 text-yellow-700 font-medium py-2 px-4 rounded-lg text-sm transition-colors">
                <i class="fas fa-edit mr-2"></i>Edit Header
            </a>
            @endif
        </div>
    </div>

    {{-- Info Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        {{-- Status Card --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-4">Informasi Umum</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Status</span>
                    @php
                        $statusColors = [
                            'active' => 'bg-green-100 text-green-800',
                            'draft' => 'bg-gray-100 text-gray-800',
                            'closed' => 'bg-red-100 text-red-800',
                        ];
                    @endphp
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$capex->status] ?? 'bg-gray-100 text-gray-800' }}">
                        {{ ucfirst($capex->status) }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Dibuat Oleh</span>
                    <span class="text-sm font-medium text-gray-900">{{ $capex->creator->name ?? '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Tanggal Dibuat</span>
                    <span class="text-sm font-medium text-gray-900">{{ $capex->created_at->format('d M Y') }}</span>
                </div>
                @if($capex->notes)
                <div class="pt-2 border-t border-gray-100">
                    <span class="text-xs text-gray-500 block mb-1">Catatan</span>
                    <p class="text-sm text-gray-700">{{ $capex->notes }}</p>
                </div>
                @endif
            </div>
        </div>

        {{-- Budget Card --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-4">Ringkasan Budget</h3>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">Total Budget</span>
                        <span class="font-bold text-gray-900">Rp {{ number_format($capex->total_budget, 0, ',', '.') }}</span>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">Terpakai</span>
                        <span class="font-medium text-red-600">Rp {{ number_format($capex->total_used, 0, ',', '.') }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $capex->utilization_percent }}%"></div>
                    </div>
                    <div class="text-right text-xs text-gray-500 mt-1">{{ $capex->utilization_percent }}% Terpakai</div>
                </div>
                <div class="pt-2 border-t border-gray-100">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Sisa Budget</span>
                        <span class="font-bold text-green-600">Rp {{ number_format($capex->remaining_budget, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Add Item (Only if active) --}}
        @if($capex->status === 'active' && auth()->user()->hasPermission('manage_capex'))
        <div class="bg-blue-50 rounded-lg shadow-sm border border-blue-100 p-6">
            <h3 class="text-sm font-medium text-blue-800 mb-4">Tambah Item Baru</h3>
            <button onclick="document.getElementById('addItemModal').classList.remove('hidden')" 
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg text-sm transition-colors flex items-center justify-center">
                <i class="fas fa-plus mr-2"></i>Tambah Item
            </button>
            <p class="text-xs text-blue-600 mt-3 text-center">
                Tambahkan item CapEx baru ke dalam anggaran departemen ini.
            </p>
        </div>
        @endif
    </div>

    {{-- Items Table --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex flex-wrap justify-between items-center gap-3">
            <div class="flex items-center gap-3">
                <h3 class="font-semibold text-gray-900">Daftar Item CapEx</h3>
                <span class="bg-gray-200 text-gray-700 text-xs font-medium px-2.5 py-0.5 rounded-full">
                    {{ $capex->items()->count() }} Item
                </span>
            </div>
            <form method="GET" action="{{ route('capex.show', $capex) }}" class="flex items-center gap-2">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari ID, nama, PIC..."
                        class="pl-9 pr-4 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <button type="submit" class="px-3 py-1.5 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">
                    <i class="fas fa-search"></i>
                </button>
                @if(request('search'))
                <a href="{{ route('capex.show', $capex) }}" class="px-3 py-1.5 bg-gray-200 text-gray-700 text-sm rounded-lg hover:bg-gray-300">
                    <i class="fas fa-times"></i>
                </a>
                @endif
            </form>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-10">No.</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID CapEx</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Prioritas</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bulan</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount/Thn</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Nilai CapEx</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Terpakai</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PIC</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    @if(auth()->user()->hasPermission('manage_capex'))
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200" id="showCapexTableBody">
                @forelse($items as $item)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-center text-xs text-gray-500 whitespace-nowrap">{{ $loop->iteration + ($items->currentPage() - 1) * $items->perPage() }}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-xs font-mono text-blue-600">{{ $item->capex_id_number }}</td>
                    <td class="px-4 py-3">
                        <div class="text-sm font-medium text-gray-900">{{ $item->item_name }}</div>
                        @if($item->category)<div class="text-xs text-gray-400">{{ $item->category }}</div>@endif
                        @if($item->description)<div class="text-xs text-gray-400 italic">{{ Str::limit($item->description, 40) }}</div>@endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($item->capex_type)
                            <span class="inline-block px-2 py-0.5 text-xs rounded-full {{ $item->capex_type === 'New' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700' }}">{{ $item->capex_type }}</span>
                        @else —@endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($item->priority_scale)
                            <span class="inline-block px-2 py-0.5 text-xs font-bold rounded-full {{ $item->priority_scale == 1 ? 'bg-red-100 text-red-700' : ($item->priority_scale == 2 ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-600') }}">P{{ $item->priority_scale }}</span>
                        @else —@endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500">{{ $item->month ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm text-right text-gray-500">
                        {{ $item->amount_per_year ? 'Rp '.number_format($item->amount_per_year,0,',','.') : '—' }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-medium text-gray-900">
                        Rp {{ number_format($item->budget_amount, 0, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-500">
                        Rp {{ number_format($item->used_amount, 0, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500">{{ $item->pic ?? '—' }}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-center">
                        @php
                            $itemStatusColors = [
                                'available'      => 'bg-green-100 text-green-800',
                                'partially_used' => 'bg-yellow-100 text-yellow-800',
                                'exhausted'      => 'bg-red-100 text-red-800',
                                'cancelled'      => 'bg-gray-100 text-gray-800',
                            ];
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $itemStatusColors[$item->status] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ ucfirst(str_replace('_', ' ', $item->status)) }}
                        </span>
                    </td>
                    @if(auth()->user()->hasPermission('manage_capex'))
                    <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-medium">
                        <div class="flex justify-center gap-2">
                            <button onclick="editItem({{ $item->id }}, '{{ addslashes($item->item_name) }}', '{{ $item->capex_type }}', '{{ $item->priority_scale }}', '{{ $item->month }}', '{{ addslashes($item->category) }}', '{{ addslashes($item->pic) }}', '{{ addslashes($item->description) }}', '{{ number_format($item->budget_amount, 0, ',', '.') }}', '{{ $item->amount_per_year ? number_format($item->amount_per_year, 0, ',', '.') : '' }}')" 
                                class="text-indigo-600 hover:text-indigo-900" title="Edit Item">
                                <i class="fas fa-edit"></i>
                            </button>
                            @if($item->used_amount == 0)
                            <form action="{{ route('capex.items.destroy', $item) }}" method="POST" class="inline" onsubmit="return confirm('Yakin hapus item ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900" title="Hapus Item">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                        @if(request('search'))
                            <p>Tidak ada item yang sesuai dengan pencarian "{{ request('search') }}".</p>
                            <a href="{{ route('capex.show', $capex) }}" class="text-blue-600 hover:underline mt-2 inline-block">Hapus pencarian</a>
                        @else
                            <p>Belum ada item dalam CapEx ini.</p>
                            @if($capex->status === 'active' && auth()->user()->hasPermission('manage_capex'))
                            <button onclick="document.getElementById('addItemModal').classList.remove('hidden')" class="text-blue-600 hover:underline mt-2">
                                Tambah Item Sekarang
                            </button>
                            @endif
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-gray-100">
            {{ $items->links() }}
        </div>
    </div>
</div>

{{-- Add Item Modal --}}
<div id="addItemModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900">Tambah Item CapEx</h3>
            <button onclick="document.getElementById('addItemModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form action="{{ route('capex.items.store', $capex) }}" method="POST">
            @csrf
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Item <span class="text-red-500">*</span></label>
                    <input type="text" name="item_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kategori (Tipe)</label>
                        <select name="capex_type" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm bg-white focus:ring-blue-500 focus:border-blue-500">
                            <option value="">— Pilih —</option>
                            <option value="New">New</option>
                            <option value="Replacement">Replacement</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Skala Prioritas</label>
                        <select name="priority_scale" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm bg-white focus:ring-blue-500 focus:border-blue-500">
                            <option value="">—</option>
                            <option value="1">1 — Tinggi</option>
                            <option value="2">2 — Sedang</option>
                            <option value="3">3 — Rendah</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
                        <select name="month" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm bg-white focus:ring-blue-500 focus:border-blue-500">
                            <option value="">—</option>
                            @foreach(['January','February','March','April','May','June','July','August','September','October','November','December'] as $m)
                                <option value="{{ $m }}">{{ $m }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kode Instalasi</label>
                        <input type="text" name="category" placeholder="e.g. I-RI" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Amount/Tahun (Rp)</label>
                        <input type="text" name="amount_per_year" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500 rupiah-input">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nilai CapEx (Rp) <span class="text-red-500">*</span></label>
                        <input type="text" name="budget_amount" required class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500 rupiah-input">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">PIC</label>
                    <input type="text" name="pic" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
                    <textarea name="description" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('addItemModal').classList.add('hidden')" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">Batal</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Simpan</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Item Modal --}}
<div id="editItemModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900">Edit Item CapEx</h3>
            <button onclick="document.getElementById('editItemModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="editItemForm" method="POST">
            @csrf
            @method('PATCH')
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Item <span class="text-red-500">*</span></label>
                    <input type="text" name="item_name" id="edit_item_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kategori (Tipe)</label>
                        <select name="capex_type" id="edit_capex_type" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm bg-white focus:ring-blue-500 focus:border-blue-500">
                            <option value="">— Pilih —</option>
                            <option value="New">New</option>
                            <option value="Replacement">Replacement</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Skala Prioritas</label>
                        <select name="priority_scale" id="edit_priority_scale" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm bg-white focus:ring-blue-500 focus:border-blue-500">
                            <option value="">—</option>
                            <option value="1">1 — Tinggi</option>
                            <option value="2">2 — Sedang</option>
                            <option value="3">3 — Rendah</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
                        <select name="month" id="edit_month" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm bg-white focus:ring-blue-500 focus:border-blue-500">
                            <option value="">—</option>
                            @foreach(['January','February','March','April','May','June','July','August','September','October','November','December'] as $m)
                                <option value="{{ $m }}">{{ $m }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kode Instalasi</label>
                        <input type="text" name="category" id="edit_category" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Amount/Tahun (Rp)</label>
                        <input type="text" name="amount_per_year" id="edit_amount_per_year" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500 rupiah-input">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nilai CapEx (Rp) <span class="text-red-500">*</span></label>
                        <input type="text" name="budget_amount" id="edit_budget_amount" required class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500 rupiah-input">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">PIC</label>
                    <input type="text" name="pic" id="edit_pic" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
                    <textarea name="description" id="edit_description" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('editItemModal').classList.add('hidden')" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">Batal</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Format Rupiah
    document.querySelectorAll('.rupiah-input').forEach(input => {
        input.addEventListener('keyup', function(e) {
            input.value = formatRupiah(this.value);
        });
    });

    function filterShowCapexTable() {
        // search dipindah ke server-side
    }

    function formatRupiah(angka, prefix) {
        var number_string = angka.replace(/[^,\d]/g, '').toString(),
            split = number_string.split(','),
            sisa = split[0].length % 3,
            rupiah = split[0].substr(0, sisa),
            ribuan = split[0].substr(sisa).match(/\d{3}/gi);

        if (ribuan) {
            separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }

        rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
        return prefix == undefined ? rupiah : (rupiah ? 'Rp. ' + rupiah : '');
    }

    function editItem(id, name, capexType, priorityScale, month, category, pic, description, budget, amountPerYear) {
        document.getElementById('editItemForm').action = `/capex/items/${id}`;
        document.getElementById('edit_item_name').value    = name;
        document.getElementById('edit_capex_type').value   = capexType;
        document.getElementById('edit_priority_scale').value = priorityScale;
        document.getElementById('edit_month').value        = month;
        document.getElementById('edit_category').value     = category;
        document.getElementById('edit_pic').value          = pic;
        document.getElementById('edit_description').value  = description;
        document.getElementById('edit_budget_amount').value = budget;
        document.getElementById('edit_amount_per_year').value = amountPerYear;
        document.getElementById('editItemModal').classList.remove('hidden');
    }
</script>
@endsection
