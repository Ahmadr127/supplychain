@extends('layouts.app')

@section('title', 'Buat Approval Request')

@section('content')
<div class="w-full px-0">
    <div class="bg-white overflow-visible w-full shadow-none rounded-none">
        <div class="p-2">
            <form id="approval-form" action="{{ route('approval-requests.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <div class="space-y-2">
                    <!-- Main Form -->
                    <div class="space-y-2">
                        <!-- Top grid: Jenis Pengajuan & Tipe Barang -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            <!-- Jenis Pengajuan (Radio) -->
                            <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Jenis Pengajuan <span class="text-red-500">*</span>
                            </label>
                            <div class="space-y-2">
                                @foreach($submissionTypes as $stype)
                                <div class="flex items-center">
                                    <input type="radio" id="submission_type_{{ $stype->id }}" name="submission_type_id" value="{{ $stype->id }}"
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                           {{ old('submission_type_id') == $stype->id ? 'checked' : '' }} required>
                                    <label for="submission_type_{{ $stype->id }}" class="ml-2 text-sm text-gray-700">
                                        <span class="font-medium">{{ $stype->name }}</span>
                                    </label>
                                </div>
                                @endforeach
                            </div>
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
                                           {{ old('item_type_id') == $itemType->id ? 'checked' : '' }}
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
                            <input type="text" id="request_number" name="request_number" value="{{ old('request_number', $previewRequestNumber ?? '') }}"
                                   class="w-full px-3 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('request_number') border-red-500 @enderror"
                                   placeholder="Kosongkan untuk generate otomatis">
                            @error('request_number')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>




                        <!-- Master Items Selection -->
                        <div id="itemsSection">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-lg font-semibold text-gray-900">Item yang Diminta</h3>
                                <button type="button" onclick="addRow()" 
                                        class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-1 px-3 rounded-lg text-sm transition-colors duration-200">
                                    <i class="fas fa-plus mr-1"></i> Tambah Baris
                                </button>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-2">
                                <p class="text-sm text-gray-600 mb-2">Isi item, jumlah, harga dan catatan. Ketik untuk menampilkan rekomendasi item. Jika belum ada di master, akan dibuat otomatis saat Anda konfirmasi.</p>

                                <div class="overflow-visible">
                                    <table class="min-w-full text-sm">
                                        <thead>
                                            <tr class="text-left text-gray-600 align-top">
                                                <th class="px-2 py-1 w-64">Item</th>
                                                <th class="px-2 py-1 w-24">Jumlah</th>
                                                <th class="px-2 py-1 w-28">Harga</th>
                                                <th class="px-2 py-1 w-56">Spesifikasi</th>
                                                <th class="px-2 py-1 w-40">Merk</th>
                                                <th class="px-2 py-1 w-56">Vendor Alternatif</th>
                                                <th class="px-2 py-1">Catatan</th>
                                                <th class="px-2 py-1 w-48">Dokumen</th>
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
                    <input type="hidden" name="workflow_id" id="workflow_id" value="{{ $defaultWorkflow->id }}">
                    <input type="hidden" name="request_type" value="normal">
                </div>

                <div class="flex justify-end space-x-3 mt-2">
                    <a href="{{ route('approval-requests.index') }}" 
                       class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-1 px-6 rounded-lg transition-colors duration-200">
                        Batal
                    </a>
                    <button type="submit" 
                            class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-1 px-6 rounded-lg transition-colors duration-200">
                        <i class="fas fa-save mr-1"></i> Buat Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const itemTypeRadios = document.querySelectorAll('input[name="item_type_id"]');
    const workflowIdInput = document.getElementById('workflow_id');
    const workflowNameSpan = document.getElementById('workflow_name');

    function updateWorkflowByItemType(itemTypeId) {
        if (!itemTypeId) return;
        const url = "{{ route('api.approval-requests.workflow-for-item-type', ['itemTypeId' => 'ITEM_TYPE_ID']) }}".replace('ITEM_TYPE_ID', itemTypeId);
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
            .then(r => r.json())
            .then(data => {
                if (data && data.success && data.workflow) {
                    workflowIdInput.value = data.workflow.id;
                    if (workflowNameSpan) workflowNameSpan.textContent = data.workflow.name;
                }
            })
            .catch(() => {});
    }

    itemTypeRadios.forEach(r => {
        r.addEventListener('change', (e) => {
            updateWorkflowByItemType(e.target.value);
        });
        if (r.checked) {
            updateWorkflowByItemType(r.value);
        }
    });

    // Hook into item search to pass item_type_id if there is code using fetch to api.approval-requests.master-items
    // If you have a function to load items, ensure it adds ?item_type_id=<selectedId>
    window.getSelectedItemTypeId = function() {
        const checked = document.querySelector('input[name="item_type_id"]:checked');
        return checked ? checked.value : '';
    };
});
</script>
@endpush

<!-- Include Modal Form for Adding Items -->
@include('components.modals.form-master-items')

<script>
// Helper function to escape HTML
function escapeHtml(text) {
    if (text == null) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
}

// Row-based item manager with suggest/resolve
let rows = [];
let currentItemTypeId = null;

document.addEventListener('DOMContentLoaded', function() {
    initializeItemTypeSelection();
    // start with one row
    addRow();

    // Serialize rows on submit
    const form = document.getElementById('approval-form');
    form.addEventListener('submit', async function(e) {
        // Validate required fields for each row
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
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(payload)
                    });
                    const data = await res.json();
                    if (data && data.item && data.item.id) {
                        r.master_item_id = data.item.id;
                    }
                } catch (err) { /* ignore and let backend validation handle */ }
            }));
        }
        // Remove previous hidden inputs
        form.querySelectorAll('.item-hidden').forEach(e => e.remove());
        rows.forEach((row, idx) => {
            const hasContent = (row.master_item_id && String(row.master_item_id).length > 0) || (row.name && row.name.trim().length > 0);
            if (!hasContent) return;
            if (row.master_item_id) {
                form.insertAdjacentHTML('beforeend', `<input class="item-hidden" type="hidden" name="items[${idx}][master_item_id]" value="${row.master_item_id}">`);
            }
            // Always include name for backend flexibility
            form.insertAdjacentHTML('beforeend', `<input class="item-hidden" type="hidden" name="items[${idx}][name]" value="${escapeHtml(row.name || '')}">`);
            form.insertAdjacentHTML('beforeend', `<input class="item-hidden" type="hidden" name="items[${idx}][quantity]" value="${row.quantity || 1}">`);
            // Only send unit_price when positive, otherwise let backend fallback to master price
            if (parseFloat(row.unit_price) > 0) {
                form.insertAdjacentHTML('beforeend', `<input class="item-hidden" type="hidden" name="items[${idx}][unit_price]" value="${row.unit_price}">`);
            }
            // New fields
            form.insertAdjacentHTML('beforeend', `<input class="item-hidden" type="hidden" name="items[${idx}][specification]" value="${escapeHtml(row.specification || '')}">`);
            form.insertAdjacentHTML('beforeend', `<input class="item-hidden" type="hidden" name="items[${idx}][brand]" value="${escapeHtml(row.brand || '')}">`);
            if (row.supplier_id) {
                form.insertAdjacentHTML('beforeend', `<input class="item-hidden" type="hidden" name="items[${idx}][supplier_id]" value="${row.supplier_id}">`);
            } else if (row.alternative_vendor) {
                // treat alternative vendor text as supplier_name for auto-create
                form.insertAdjacentHTML('beforeend', `<input class="item-hidden" type="hidden" name="items[${idx}][supplier_name]" value="${escapeHtml(row.alternative_vendor)}">`);
            }
            form.insertAdjacentHTML('beforeend', `<input class="item-hidden" type="hidden" name="items[${idx}][alternative_vendor]" value="${escapeHtml(row.alternative_vendor || '')}">`);
            form.insertAdjacentHTML('beforeend', `<input class="item-hidden" type="hidden" name="items[${idx}][notes]" value="${escapeHtml(row.notes || '')}">`);
        });

        // Submit after building hidden inputs
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
        specification: defaults.specification || '',
        brand: defaults.brand || '',
        supplier_id: defaults.supplier_id || '',
        alternative_vendor: defaults.alternative_vendor || '',
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
            <textarea class="item-spec w-full px-2 py-1 border border-gray-300 rounded" rows="2" placeholder="Spesifikasi">${escapeHtml(row.specification)}</textarea>
        </td>
        <td class="px-2 py-1 align-top">
            <input type="text" class="item-brand w-full px-2 py-1 border border-gray-300 rounded" placeholder="Merk" value="${escapeHtml(row.brand)}">
        </td>
        <td class="px-2 py-1 align-top">
            <div class="relative">
                <input type="text" class="alt-vendor w-full px-2 py-1 border border-gray-300 rounded" placeholder="Vendor alternatif" value="${escapeHtml(row.alternative_vendor)}" autocomplete="off">
                <div class="supplier-suggestions absolute left-0 right-0 mt-1 bg-white border border-gray-200 rounded shadow-lg max-h-56 overflow-auto hidden z-50"></div>
            </div>
        </td>
        <td class="px-2 py-1 align-top">
            <input type="text" class="item-notes w-full px-2 py-1 border border-gray-300 rounded" placeholder="Catatan" value="${escapeHtml(row.notes)}">
        </td>
        <td class="px-2 py-1 align-top">
            <input type="file" name="items[${row.index}][files][]" class="item-files w-full text-sm" multiple accept=".pdf,.doc,.docx,.xls,.xlsx">
        </td>
        <td class="px-2 py-1 align-top text-right">
            <button type="button" class="text-red-600 hover:text-red-800" onclick="removeRow(${row.index})"><i class="fas fa-trash"></i></button>
        </td>
    `;
    tbody.appendChild(tr);

    bindRowEvents(tr, row);
}

function bindRowEvents(tr, row) {
    const nameInput = tr.querySelector('.item-name');
    const qtyInput = tr.querySelector('.item-qty');
    const priceInput = tr.querySelector('.item-price');
    const notesInput = tr.querySelector('.item-notes');
    const specInput = tr.querySelector('.item-spec');
    const brandInput = tr.querySelector('.item-brand');
    const altVendorInput = tr.querySelector('.alt-vendor');
    const supplierSugBox = tr.querySelector('.supplier-suggestions');
    const sugBox = tr.querySelector('.suggestions');

    nameInput.addEventListener('input', async function() {
        row.name = this.value;
        row.master_item_id = '';
        if (this.value.trim().length < 2) {
            sugBox.classList.add('hidden');
            return;
        }
        const url = new URL("{{ route('api.items.suggest') }}", window.location.origin);
        url.searchParams.set('search', this.value.trim());
        const checked = document.querySelector('input[name="item_type_id"]:checked');
        if (checked) url.searchParams.set('item_type_id', checked.value);
        const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
        const data = await res.json();
        renderSuggestions(sugBox, data.items || [], row, nameInput, priceInput);
    });

    nameInput.addEventListener('blur', function() {
        setTimeout(() => sugBox.classList.add('hidden'), 200);
    });

    nameInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            // Resolve typed value
            resolveTyped(nameInput, row, priceInput);
        }
    });

    qtyInput.addEventListener('change', function(){ row.quantity = parseInt(this.value)||1; });
    priceInput.addEventListener('change', function(){ row.unit_price = parseFloat(this.value)||0; });
    notesInput.addEventListener('change', function(){ row.notes = this.value; });
    specInput.addEventListener('change', function(){ row.specification = this.value; });
    brandInput.addEventListener('change', function(){ row.brand = this.value; });
    altVendorInput.addEventListener('change', function(){ row.alternative_vendor = this.value; });

    // Alternative vendor suggest (also used to auto-create supplier on submit)
    altVendorInput.addEventListener('input', async function() {
        row.alternative_vendor = this.value;
        row.supplier_id = '';
        if (this.value.trim().length < 2) {
            supplierSugBox.classList.add('hidden');
            return;
        }
        const url = new URL("{{ route('api.suppliers.suggest') }}", window.location.origin);
        url.searchParams.set('search', this.value.trim());
        const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
        const data = await res.json();
        renderSupplierSuggestions(supplierSugBox, data.suppliers || [], row.index);
    });
    altVendorInput.addEventListener('blur', function(){ setTimeout(()=> supplierSugBox.classList.add('hidden'), 200); });
}

function renderSuggestions(container, items, row, nameInput, priceInput) {
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
        </div>
    `).join('');
    container.classList.remove('hidden');
}

// Supplier suggestions for Alternative Vendor field
function renderSupplierSuggestions(container, suppliers, rowIndex) {
    if (!suppliers.length) {
        container.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500">Tidak ada hasil. Ketik lalu submit untuk membuat vendor baru.</div>';
        container.classList.remove('hidden');
        return;
    }
    container.innerHTML = suppliers.map(s => `
        <div class="px-3 py-2 hover:bg-gray-50 cursor-pointer" onclick='selectSupplierSuggestion(${JSON.stringify(s).replace(/'/g, "&#39;")}, ${rowIndex})'>
            <div class="flex justify-between">
                <span>${escapeHtml(s.name)} <span class="text-xs text-gray-500">${s.code? '('+escapeHtml(s.code)+')':''}</span></span>
                <span class="text-xs text-gray-500">${escapeHtml(s.email||'')}${s.phone? ' • '+escapeHtml(s.phone):''}</span>
            </div>
        </div>
    `).join('');
    container.classList.remove('hidden');
}

function selectSupplierSuggestion(s, rowIndex) {
    const tr = document.getElementById('row-' + rowIndex);
    const input = tr.querySelector('.alt-vendor');
    const sug = tr.querySelector('.supplier-suggestions');
    const row = rows.find(r => r.index === rowIndex);
    row.supplier_id = s.id;
    row.alternative_vendor = s.name; // show chosen supplier name
    input.value = s.name;
    sug.classList.add('hidden');
}

function selectSuggestion(it, rowIndex) {
    const tr = document.getElementById('row-' + rowIndex);
    const nameInput = tr.querySelector('.item-name');
    const priceInput = tr.querySelector('.item-price');
    const sugBox = tr.querySelector('.suggestions');
    const row = rows.find(r => r.index === rowIndex);
    row.master_item_id = it.id;
    row.name = it.name;
    // Auto-fill price from suggestion total_price for better UX; user can still edit
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
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
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

// Initialize item type selection (kept from previous logic)
function initializeItemTypeSelection() {
    const itemTypeRadios = document.querySelectorAll('input[name="item_type_id"]');
    itemTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            currentItemTypeId = this.value;
            updateWorkflowForItemType(currentItemTypeId);
        });
    });
    const checkedRadio = document.querySelector('input[name="item_type_id"]:checked');
    if (checkedRadio) {
        currentItemTypeId = checkedRadio.value;
        updateWorkflowForItemType(currentItemTypeId);
    }
}

function updateWorkflowForItemType(itemTypeId) {
    if (!itemTypeId) return;
    fetch("{{ route('api.approval-requests.workflow-for-item-type', ['itemTypeId' => 'ITEM_TYPE_ID']) }}".replace('ITEM_TYPE_ID', itemTypeId))
        .then(r=>r.json())
        .then(data=>{ if(data.success){ document.querySelector('input[name="workflow_id"]').value = data.workflow.id; showWorkflowInfo(data.workflow, itemTypeId);} })
        .catch(()=>{});
}

function showWorkflowInfo(workflow, itemTypeId){
    document.querySelectorAll('.workflow-inline').forEach(e=>e.remove());
    const label = document.querySelector('label[for="item_type_' + itemTypeId + '"]');
    if (!label) return;
    const span = document.createElement('span');
    span.className = 'workflow-inline ml-2 text-xs text-blue-600';
    span.textContent = `(Workflow: ${escapeHtml(workflow.name)})`;
    label.appendChild(span);
}

// expose addRow for button
window.addRow = addRow;

// Fitur preview file dihapus sesuai permintaan
</script>
@endsection
