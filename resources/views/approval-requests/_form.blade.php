@php
    $isEdit = ($mode ?? 'create') === 'edit';
@endphp

<div class="space-y-2">
    <!-- Main Form -->
    <div class="space-y-2">
        <!-- Top grid: Jenis Pengajuan & Tipe Barang -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
            <!-- Jenis Pengajuan -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Jenis Pengajuan <span class="text-red-500">*</span>
                </label>
                <div class="space-y-2">
                    @foreach ($submissionTypes as $stype)
                        <div class="flex items-center">
                            <input type="radio" id="submission_type_{{ $stype->id }}" name="submission_type_id"
                                value="{{ $stype->id }}"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                {{ old('submission_type_id', $isEdit ? $approvalRequest->submission_type_id : null) == $stype->id ? 'checked' : '' }} required>
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
                    @foreach ($itemTypes as $itemType)
                        <div class="flex items-center">
                            <input type="radio" id="item_type_{{ $itemType->id }}" name="item_type_id"
                                value="{{ $itemType->id }}"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                {{ old('item_type_id', $isEdit ? $approvalRequest->item_type_id : null) == $itemType->id ? 'checked' : '' }}
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

        <!-- Request Number -->
        <div>
            <label for="request_number" class="block text-sm font-medium text-gray-700 mb-1">
                Nomor Request
            </label>
            <input type="text" id="request_number" name="request_number"
                value="{{ old('request_number', $isEdit ? $approvalRequest->request_number : $previewRequestNumber ?? '') }}"
                class="w-full px-3 py-1 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('request_number') border-red-500 @enderror"
                placeholder="{{ $isEdit ? 'Auto-generated jika kosong' : 'Kosongkan untuk generate otomatis' }}">
            @error('request_number')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Items Section -->
        <div id="itemsSection">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-lg font-semibold text-gray-900">Item yang Diminta</h3>
                <button type="button" onclick="addRow()"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-1 px-3 rounded-lg text-sm transition-colors duration-200">
                    <i class="fas fa-plus mr-1"></i> Tambah Baris
                </button>
            </div>
            <div class="bg-gray-50 rounded-lg p-2">
                <div class="overflow-visible">
                    <table class="min-w-full text-sm table-fixed">
                        <thead>
                            <tr class="text-left text-gray-600 align-middle select-none">
                                <th class="px-1 py-1 w-[22rem] text-xs font-medium tracking-wide">Item</th>
                                <th class="px-1 py-1 w-14 text-xs font-medium tracking-wide whitespace-nowrap">Jumlah</th>
                                <th class="px-1 py-1 w-24 text-xs font-medium tracking-wide whitespace-nowrap">Harga</th>
                                <th class="px-1 py-1 w-48 text-xs font-medium tracking-wide">Spesifikasi</th>
                                <th class="px-1 py-1 w-32 text-xs font-medium tracking-wide">Merk</th>
                                <th class="px-1 py-1 w-48 text-xs font-medium tracking-wide whitespace-nowrap">Vendor Alternatif</th>
                                <th class="px-1 py-1 w-40 text-xs font-medium tracking-wide">Catatan</th>
                                <th class="px-1 py-1 w-36 text-xs font-medium tracking-wide">Dokumen</th>
                                <th class="px-1 py-1 w-8"></th>
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
    <a href="{{ $isEdit ? route('approval-requests.show', $approvalRequest) : route('approval-requests.index') }}"
        class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-1 px-6 rounded-lg transition-colors duration-200">
        Batal
    </a>
    <button type="submit"
        class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-1 px-6 rounded-lg transition-colors duration-200">
        <i class="fas fa-save mr-1"></i> {{ $isEdit ? 'Update Request' : 'Buat Request' }}
    </button>
</div>

<script>
    // Shared helpers
    function escapeHtml(text) {
        if (text == null) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, function(m) {
            return map[m];
        });
    }

    let rows = [];
    let currentItemTypeId = {!! $isEdit ? $approvalRequest->item_type_id ?? 'null' : 'null' !!};

    document.addEventListener('DOMContentLoaded', function() {
        initializeItemTypeSelection();

        @if ($isEdit)
            const existing = {!! json_encode(
                $approvalRequest->masterItems->map(function ($item) {
                    return [
                        'master_item_id' => $item->id,
                        'name' => $item->name,
                        'quantity' => $item->pivot->quantity,
                        'unit_price' => $item->pivot->unit_price,
                        'specification' => $item->pivot->specification,
                        'brand' => $item->pivot->brand,
                        'alternative_vendor' => $item->pivot->alternative_vendor,
                        'notes' => $item->pivot->notes,
                    ];
                }),
                JSON_HEX_APOS | JSON_HEX_QUOT,
            ) !!};
            if (existing.length) {
                existing.forEach(e => addRow(e));
            } else {
                addRow();
            }
        @else
            addRow();
        @endif

        // Serialize rows on submit
        const form = document.getElementById('approval-form');
        form.addEventListener('submit', async function(e) {
            // Validate rows with content
            let valid = true;
            let message = '';
            rows.forEach((row) => {
                const hasContent = (row.master_item_id && String(row.master_item_id)
                    .length > 0) || (row.name && row.name.trim().length > 0);
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
            @if ($isEdit)
                const anyContent = rows.some(r => (r.master_item_id && String(r.master_item_id)
                    .length > 0) || (r.name && r.name.trim().length > 0));
                if (!anyContent) {
                    e.preventDefault();
                    alert('Minimal 1 item harus diisi.');
                    return;
                }
            @endif
            if (!valid) {
                e.preventDefault();
                alert(message);
                return;
            }

            // Resolve new items by name
            e.preventDefault();
            const toResolve = rows.filter(r => (!r.master_item_id || String(r.master_item_id)
                .length === 0) && r.name && r.name.trim().length > 0);
            if (toResolve.length) {
                await Promise.all(toResolve.map(async (r) => {
                    try {
                        const payload = {
                            name: r.name.trim()
                        };
                        const checked = document.querySelector(
                            'input[name="item_type_id"]:checked');
                        if (checked) payload.item_type_id = checked.value;
                        const res = await fetch(
                        "{{ route('api.items.resolve') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector(
                                        'meta[name="csrf-token"]')
                                    .getAttribute('content')
                            },
                            body: JSON.stringify(payload)
                        });
                        const data = await res.json();
                        if (data && data.item && data.item.id) {
                            r.master_item_id = data.item.id;
                        }
                    } catch (err) {}
                }));
            }

            // Rebuild hidden inputs
            form.querySelectorAll('.item-hidden').forEach(e => e.remove());
            rows.forEach((row, idx) => {
                const hasContent = (row.master_item_id && String(row.master_item_id)
                    .length > 0) || (row.name && row.name.trim().length > 0);
                if (!hasContent) return;
                if (row.master_item_id) form.insertAdjacentHTML('beforeend',
                    `<input class="item-hidden" type="hidden" name="items[${row.index}][master_item_id]" value="${row.master_item_id}">`
                    );
                form.insertAdjacentHTML('beforeend',
                    `<input class="item-hidden" type="hidden" name="items[${row.index}][name]" value="${escapeHtml(row.name || '')}">`
                    );
                form.insertAdjacentHTML('beforeend',
                    `<input class="item-hidden" type="hidden" name="items[${row.index}][quantity]" value="${row.quantity || 1}">`
                    );
                if (parseFloat(row.unit_price) > 0) form.insertAdjacentHTML('beforeend',
                    `<input class="item-hidden" type="hidden" name="items[${row.index}][unit_price]" value="${row.unit_price}">`
                    );
                form.insertAdjacentHTML('beforeend',
                    `<input class="item-hidden" type="hidden" name="items[${row.index}][specification]" value="${escapeHtml(row.specification || '')}">`
                    );
                form.insertAdjacentHTML('beforeend',
                    `<input class="item-hidden" type="hidden" name="items[${row.index}][brand]" value="${escapeHtml(row.brand || '')}">`
                    );
                if (row.supplier_id) {
                    form.insertAdjacentHTML('beforeend',
                        `<input class="item-hidden" type="hidden" name="items[${row.index}][supplier_id]" value="${row.supplier_id}">`
                        );
                } else if (row.alternative_vendor) {
                    form.insertAdjacentHTML('beforeend',
                        `<input class="item-hidden" type="hidden" name="items[${row.index}][supplier_name]" value="${escapeHtml(row.alternative_vendor)}">`
                        );
                }
                form.insertAdjacentHTML('beforeend',
                    `<input class="item-hidden" type="hidden" name="items[${row.index}][alternative_vendor]" value="${escapeHtml(row.alternative_vendor || '')}">`
                    );
                form.insertAdjacentHTML('beforeend',
                    `<input class="item-hidden" type="hidden" name="items[${row.index}][notes]" value="${escapeHtml(row.notes || '')}">`
                    );
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
        <td class="px-1 py-1 align-top">
            <div class="relative">
                <input type="text" class="item-name w-full h-8 px-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="Cari atau ketik nama item" value="${escapeHtml(row.name)}" autocomplete="off" required>
                <div class="suggestions absolute left-0 right-0 mt-0.5 bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-auto hidden z-50 text-sm"></div>
            </div>
        </td>
        <td class="px-1 py-1 align-top">
            <input type="number" min="1" class="item-qty w-14 h-8 px-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-center" value="${row.quantity}">
        </td>
        <td class="px-1 py-1 align-top">
            <input type="number" min="0" step="0.01" class="item-price w-24 h-8 px-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" value="${row.unit_price}" required>
        </td>
        <td class="px-1 py-1 align-top">
            <input type="text" class="item-spec w-full h-8 px-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="Spesifikasi" value="${escapeHtml(row.specification)}">
        </td>
        <td class="px-1 py-1 align-top">
            <input type="text" class="item-brand w-full h-8 px-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="Merk" value="${escapeHtml(row.brand)}">
        </td>
        <td class="px-1 py-1 align-top">
            <div class="relative">
                <input type="text" class="alt-vendor w-full h-8 px-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="Vendor alternatif" value="${escapeHtml(row.alternative_vendor)}" autocomplete="off">
                <div class="supplier-suggestions absolute left-0 right-0 mt-0.5 bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-auto hidden z-50 text-sm"></div>
            </div>
        </td>
        <td class="px-1 py-1 align-top">
            <input type="text" class="item-notes w-full h-8 px-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="Catatan" value="${escapeHtml(row.notes)}">
        </td>
        <td class="px-1 py-1 align-top">
            <input type="file" name="items[${row.index}][files][]" class="item-files w-full h-8 text-xs file:mr-1 file:py-0.5 file:px-2 file:rounded file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" multiple accept=".pdf,.doc,.docx,.xls,.xlsx">
        </td>
        <td class="px-1 py-1 align-top text-right">
            <button type="button" class="h-7 w-7 inline-flex items-center justify-center text-red-600 hover:text-red-800" onclick="removeRow(${row.index})"><i class="fas fa-trash text-sm"></i></button>
        </td>`;
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
            const res = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await res.json();
            renderSuggestions(sugBox, data.items || [], row, nameInput, priceInput);
        });
        nameInput.addEventListener('blur', function() {
            setTimeout(() => sugBox.classList.add('hidden'), 200);
        });
        nameInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                resolveTyped(nameInput, row, priceInput);
            }
        });

        qtyInput.addEventListener('change', function() {
            row.quantity = parseInt(this.value) || 1;
        });
        priceInput.addEventListener('change', function() {
            row.unit_price = parseFloat(this.value) || 0;
        });
        notesInput.addEventListener('change', function() {
            row.notes = this.value;
        });
        specInput.addEventListener('change', function() {
            row.specification = this.value;
        });
        brandInput.addEventListener('change', function() {
            row.brand = this.value;
        });
        altVendorInput.addEventListener('change', function() {
            row.alternative_vendor = this.value;
        });

        // Alternative vendor suggest
        altVendorInput.addEventListener('input', async function() {
            row.alternative_vendor = this.value;
            row.supplier_id = '';
            if (this.value.trim().length < 2) {
                supplierSugBox.classList.add('hidden');
                return;
            }
            const url = new URL("{{ route('api.suppliers.suggest') }}", window.location.origin);
            url.searchParams.set('search', this.value.trim());
            const res = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await res.json();
            renderSupplierSuggestions(supplierSugBox, data.suppliers || [], row.index);
        });
        altVendorInput.addEventListener('blur', function() {
            setTimeout(() => supplierSugBox.classList.add('hidden'), 200);
        });
    }

    function renderSuggestions(container, items, row, nameInput, priceInput) {
        if (!items.length) {
            container.innerHTML =
                '<div class="px-3 py-2 text-sm text-gray-500">Tidak ada hasil. Tekan Enter untuk menambahkan.</div>';
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

    function renderSupplierSuggestions(container, suppliers, rowIndex) {
        if (!suppliers.length) {
            container.innerHTML =
                '<div class="px-3 py-2 text-sm text-gray-500">Tidak ada hasil. Ketik lalu submit untuk membuat vendor baru.</div>';
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
        row.alternative_vendor = s.name;
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
        row.unit_price = parseFloat(it.total_price || 0) || '';
        nameInput.value = it.name;
        priceInput.value = row.unit_price;
        sugBox.classList.add('hidden');
    }

    async function resolveTyped(nameInput, row, priceInput) {
        const payload = {
            name: nameInput.value.trim()
        };
        const checked = document.querySelector('input[name="item_type_id"]:checked');
        if (checked) payload.item_type_id = checked.value;
        if (!payload.name) return;
        const res = await fetch("{{ route('api.items.resolve') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content')
            },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data && data.item) {
            row.master_item_id = data.item.id;
            row.name = data.item.name;
            row.unit_price = parseFloat(data.item.total_price || 0);
            nameInput.value = data.item.name;
            priceInput.value = row.unit_price;
        }
    }

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
        fetch("{{ route('api.approval-requests.workflow-for-item-type', ['itemTypeId' => 'ITEM_TYPE_ID']) }}".replace(
                'ITEM_TYPE_ID', itemTypeId))
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.querySelector('input[name="workflow_id"]').value = data.workflow.id;
                    showWorkflowInfo(data.workflow, itemTypeId);
                }
            })
            .catch(() => {});
    }

    function showWorkflowInfo(workflow, itemTypeId) {
        document.querySelectorAll('.workflow-inline').forEach(e => e.remove());
        const label = document.querySelector('label[for="item_type_' + itemTypeId + '"]');
        if (!label) return;
        const span = document.createElement('span');
        span.className = 'workflow-inline ml-2 text-xs text-blue-600';
        span.textContent = `(Workflow: ${escapeHtml(workflow.name)})`;
        label.appendChild(span);
    }

    // expose
    window.addRow = addRow;
</script>
