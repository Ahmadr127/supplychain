@extends('layouts.app')

@section('title', 'Edit Approval Request')

@section('content')
<div class="w-full px-0">
    <div class="bg-white overflow-hidden w-full shadow-none rounded-none">
        <div class="p-2">
            <form id="approval-form" action="{{ route('approval-requests.update', $approvalRequest) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                
                <div class="space-y-2">
                    <!-- Main Form -->
                    <div class="space-y-2">
                        <!-- Top grid: Jenis Pengajuan & Tipe Barang -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            <!-- Jenis Pengajuan -->
                            <div>
                                <label for="submission_type_id" class="block text-sm font-medium text-gray-700 mb-1">
                                    Jenis Pengajuan <span class="text-red-500">*</span>
                                </label>
                                <select id="submission_type_id" name="submission_type_id" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('submission_type_id') border-red-500 @enderror">
                                    <option value="">Pilih Jenis Pengajuan</option>
                                    @foreach($submissionTypes as $stype)
                                        <option value="{{ $stype->id }}" {{ old('submission_type_id', $approvalRequest->submission_type_id) == $stype->id ? 'selected' : '' }}>
                                            {{ $stype->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('submission_type_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Item Type Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Tipe Barang <span class="text-red-500">*</span>
                                </label>
                                <div class="space-y-2">
                                    @foreach($itemTypes as $itemType)
                                    <div class="flex items-center">
                                        <input type="radio" id="item_type_{{ $itemType->id }}" name="item_type_id" value="{{ $itemType->id }}" 
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300" 
                                               {{ old('item_type_id', $approvalRequest->item_type_id) == $itemType->id ? 'checked' : '' }}
                                               required>
                                        <label for="item_type_{{ $itemType->id }}" class="ml-2 text-sm text-gray-700">
                                            <span class="font-medium">{{ $itemType->name }}</span>
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                                @error('item_type_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Request Number (moved below top grid) -->
                        <div>
                            <label for="request_number" class="block text-sm font-medium text-gray-700 mb-1">
                                Nomor Request
                            </label>
                            <input type="text" id="request_number" name="request_number" value="{{ old('request_number', $approvalRequest->request_number) }}"
                                   class="w-full px-3 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('request_number') border-red-500 @enderror"
                                   placeholder="Auto-generated jika kosong">
                            @error('request_number')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>





                        <!-- Master Items Selection -->
                        <div id="itemsSection">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-lg font-semibold text-gray-900">Item yang Diminta</h3>
                                <button type="button" onclick="addRow()" 
                                        class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-3 rounded-lg text-sm transition-colors duration-200">
                                    <i class="fas fa-plus mr-1"></i> Tambah Baris
                                </button>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-2">
                                <p class="text-sm text-gray-600 mb-2">Isi item, jumlah, harga dan catatan. Ketik untuk menampilkan rekomendasi item. Jika belum ada di master, akan dibuat otomatis.</p>
                                <div class="overflow-visible">
                                    <table class="min-w-full text-sm">
                                        <thead>
                                            <tr class="text-left text-gray-600">
                                                <th class="px-2 py-1 w-2/5">Item</th>
                                                <th class="px-2 py-1 w-24">Jumlah</th>
                                                <th class="px-2 py-1 w-32">Harga</th>
                                                <th class="px-2 py-1">Catatan</th>
                                                <th class="px-2 py-1 w-10"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="itemsTableBody">
                                            <!-- rows injected by JS -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Hidden fields -->
                    <input type="hidden" name="workflow_id" value="{{ $defaultWorkflow->id }}">
                    <input type="hidden" name="request_type" value="normal">
                </div>

                <div class="flex justify-end space-x-3 mt-2">
                    <a href="{{ route('approval-requests.show', $approvalRequest) }}" 
                       class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-1 px-6 rounded-lg transition-colors duration-200">
                        Batal
                    </a>
                    <button type="submit" 
                            class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-1 px-6 rounded-lg transition-colors duration-200">
                        <i class="fas fa-save mr-1"></i> Update Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Include Modal Form for Adding Items -->
@include('components.modals.form-master-items')

<script>
// Helper function to escape HTML
function escapeHtml(text) {
    if (text == null) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
}

// Row-based manager with suggest/resolve (prefill from backend)
let rows = [];
let currentItemTypeId = {{ $approvalRequest->item_type_id ?? 'null' }};

document.addEventListener('DOMContentLoaded', function() {
    initializeItemTypeSelection();
    // Prefill existing items
    const existing = {!! json_encode($approvalRequest->masterItems->map(function($item) {
        return [
            'master_item_id' => $item->id,
            'name' => $item->name,
            'quantity' => $item->pivot->quantity,
            'unit_price' => $item->pivot->unit_price,
            'notes' => $item->pivot->notes,
        ];
    }), JSON_HEX_APOS | JSON_HEX_QUOT) !!};
    if (existing.length) {
        existing.forEach(e => addRow(e));
    } else {
        addRow();
    }

    // Serialize rows on submit
    const form = document.getElementById('approval-form');
    form.addEventListener('submit', async function(e) {
        // Validate required fields per row (only if row has content)
        let valid = true;
        let message = '';
        rows.forEach((row) => {
            const hasContent = (row.master_item_id && String(row.master_item_id).length > 0) || (row.name && row.name.trim().length > 0);
            if (hasContent) {
                if (!row.name || row.name.trim() === '') {
                    valid = false;
                    message = 'Nama item wajib diisi.';
                }
                if (!(parseFloat(row.unit_price) > 0)) {
                    valid = false;
                    message = message || 'Harga wajib diisi dan lebih dari 0.';
                }
            }
        });
        // Extra check: at least one row has content
        const anyContent = rows.some(r => (r.master_item_id && String(r.master_item_id).length > 0) || (r.name && r.name.trim().length > 0));
        if (!anyContent) {
            e.preventDefault();
            alert('Minimal 1 item harus diisi.');
            return;
        }
        if (!valid) {
            e.preventDefault();
            alert(message);
            return;
        }

        // Resolve any rows that have a name but no master_item_id
        e.preventDefault();
        const toResolve = rows.filter(r => (!r.master_item_id || String(r.master_item_id).length === 0) && r.name && r.name.trim().length > 0);
        if (toResolve.length) {
            await Promise.all(toResolve.map(async (r) => {
                try {
                    const payload = { name: r.name.trim() };
                    const checked = document.querySelector('input[name="item_type_id"]:checked');
                    if (checked) payload.item_type_id = checked.value;
                    const res = await fetch("{{ route('api.items.resolve') }}", {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                        body: JSON.stringify(payload)
                    });
                    const data = await res.json();
                    if (data && data.item && data.item.id) {
                        r.master_item_id = data.item.id;
                    }
                } catch (err) { /* ignore */ }
            }));
        }

        form.querySelectorAll('.item-hidden').forEach(e => e.remove());
        rows.forEach((row, idx) => {
            const hasContent = (row.master_item_id && String(row.master_item_id).length > 0) || (row.name && row.name.trim().length > 0);
            if (!hasContent) return;
            if (row.master_item_id) {
                form.insertAdjacentHTML('beforeend', `<input class="item-hidden" type="hidden" name="items[${idx}][master_item_id]" value="${row.master_item_id}">`);
            }
            form.insertAdjacentHTML('beforeend', `<input class="item-hidden" type="hidden" name="items[${idx}][name]" value="${escapeHtml(row.name || '')}">`);
            form.insertAdjacentHTML('beforeend', `<input class="item-hidden" type="hidden" name="items[${idx}][quantity]" value="${row.quantity || 1}">`);
            // Only send unit_price when positive, otherwise let backend fallback to master price
            if (parseFloat(row.unit_price) > 0) {
                form.insertAdjacentHTML('beforeend', `<input class="item-hidden" type="hidden" name="items[${idx}][unit_price]" value="${row.unit_price}">`);
            }
            form.insertAdjacentHTML('beforeend', `<input class="item-hidden" type="hidden" name="items[${idx}][notes]" value="${escapeHtml(row.notes || '')}">`);
        });

        form.submit();
    });
});

function addRow(defaults = {}) {
    const idx = rows.length;
    const row = {
        index: idx,
        master_item_id: defaults.master_item_id || '',
        name: defaults.name || '',
        quantity: defaults.quantity || 1,
        unit_price: defaults.unit_price || 0,
        notes: defaults.notes || ''
    };
    rows.push(row);
    renderRow(row);
}

function removeRow(index) {
    rows = rows.filter(r => r.index !== index);
    document.getElementById('row-' + index)?.remove();
}

function renderRow(row) {
    const tbody = document.getElementById('itemsTableBody');
    const tr = document.createElement('tr');
    tr.id = 'row-' + row.index;
    tr.innerHTML = `
        <td class="px-2 py-1 align-top">
            <div class="relative">
                <input type="text" class="item-name w-full px-2 py-1 border border-gray-300 rounded" placeholder="Cari atau ketik nama item" value="${escapeHtml(row.name)}" autocomplete="off" required>
                <div class="suggestions absolute left-0 right-0 mt-1 bg-white border border-gray-200 rounded shadow-lg max-h-56 overflow-auto hidden z-50"></div>
            </div>
        </td>
        <td class="px-2 py-1 align-top">
            <input type="number" min="1" class="item-qty w-24 px-2 py-1 border border-gray-300 rounded" value="${row.quantity}">
        </td>
        <td class="px-2 py-1 align-top">
            <input type="number" min="0" step="0.01" class="item-price w-32 px-2 py-1 border border-gray-300 rounded" value="${row.unit_price}" required>
        </td>
        <td class="px-2 py-1 align-top">
            <input type="text" class="item-notes w-full px-2 py-1 border border-gray-300 rounded" placeholder="Catatan" value="${escapeHtml(row.notes)}">
        </td>
        <td class="px-2 py-1 align-top text-right">
            <button type="button" class="text-red-600 hover:text-red-800" onclick="removeRow(${row.index})"><i class="fas fa-trash"></i></button>
        </td>`;
    tbody.appendChild(tr);
    bindRowEvents(tr, row);
}

function bindRowEvents(tr, row) {
    const nameInput = tr.querySelector('.item-name');
    const qtyInput = tr.querySelector('.item-qty');
    const priceInput = tr.querySelector('.item-price');
    const notesInput = tr.querySelector('.item-notes');
    const sugBox = tr.querySelector('.suggestions');

    nameInput.addEventListener('input', async function() {
        row.name = this.value;
        row.master_item_id = '';
        if (this.value.trim().length < 2) { sugBox.classList.add('hidden'); return; }
        const url = new URL("{{ route('api.items.suggest') }}", window.location.origin);
        url.searchParams.set('search', this.value.trim());
        const checked = document.querySelector('input[name="item_type_id"]:checked');
        if (checked) url.searchParams.set('item_type_id', checked.value);
        const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
        const data = await res.json();
        renderSuggestions(sugBox, data.items || [], row);
    });

    nameInput.addEventListener('blur', function(){ setTimeout(()=>sugBox.classList.add('hidden'), 200); });
    nameInput.addEventListener('keydown', function(e){ if(e.key==='Enter'){ e.preventDefault(); resolveTyped(nameInput, row, priceInput); }});

    qtyInput.addEventListener('change', function(){ row.quantity = parseInt(this.value)||1; });
    priceInput.addEventListener('change', function(){ row.unit_price = parseFloat(this.value)||0; });
    notesInput.addEventListener('change', function(){ row.notes = this.value; });
}

function renderSuggestions(container, items, row) {
    if (!items.length) {
        container.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500">Tidak ada hasil. Tekan Enter untuk menambahkan.</div>';
        container.classList.remove('hidden');
        return;
    }
    container.innerHTML = items.map(it => `
        <div class="px-3 py-2 hover:bg-gray-50 cursor-pointer" onclick='selectSuggestion(${JSON.stringify(it).replace(/'/g, "&#39;")}, ${row.index})'>
            <div class="flex justify-between">
                <span>${escapeHtml(it.name)} <span class="text-xs text-gray-500">(${escapeHtml(it.code)})</span></span>
                <span class="text-xs text-green-600">Rp ${parseFloat(it.total_price||0).toLocaleString('id-ID')}${it.unit? ' / '+escapeHtml(it.unit.name):''}</span>
            </div>
        </div>`).join('');
    container.classList.remove('hidden');
}

function selectSuggestion(it, rowIndex) {
    const tr = document.getElementById('row-' + rowIndex);
    const nameInput = tr.querySelector('.item-name');
    const priceInput = tr.querySelector('.item-price');
    const sugBox = tr.querySelector('.suggestions');
    const row = rows.find(r => r.index === rowIndex);
    row.master_item_id = it.id;
    row.name = it.name;
    // Auto-fill price from suggestion for better UX; user can still edit
    row.unit_price = parseFloat(it.total_price || 0) || '';
    nameInput.value = it.name;
    priceInput.value = row.unit_price;
    sugBox.classList.add('hidden');
}

async function resolveTyped(nameInput, row, priceInput) {
    const payload = { name: nameInput.value.trim() };
    const checked = document.querySelector('input[name="item_type_id"]:checked');
    if (checked) payload.item_type_id = checked.value;
    if (!payload.name) return;
    const res = await fetch("{{ route('api.items.resolve') }}", {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
        body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (data && data.item) {
        row.master_item_id = data.item.id;
        row.name = data.item.name;
        row.unit_price = parseFloat(data.item.total_price||0);
        nameInput.value = data.item.name;
        priceInput.value = row.unit_price;
    }
}
</script>
@endsection
