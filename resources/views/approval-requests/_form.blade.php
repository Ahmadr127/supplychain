@php
    $isEdit = ($mode ?? 'create') === 'edit';
    $itemExtras = $itemExtras ?? collect();
    $itemFiles  = $itemFiles  ?? collect();
@endphp

<div class="space-y-2 max-w-full">
    <!-- Main Form -->
    <div class="space-y-2 max-w-full">
        <!-- Top grid: Jenis Pengajuan, Sifat Pengadaan, Tipe Barang -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
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

            <!-- Procurement Type Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Sifat Pengadaan <span class="text-red-500">*</span>
                </label>
                <div class="space-y-2">
                    @foreach ($procurementTypes as $procType)
                        <div class="flex items-center">
                            <input type="radio" id="procurement_type_{{ $procType->id }}" name="procurement_type_id"
                                value="{{ $procType->id }}"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                {{ old('procurement_type_id', $isEdit ? $approvalRequest->procurement_type_id : null) == $procType->id ? 'checked' : '' }}
                                required>
                            <label for="procurement_type_{{ $procType->id }}" class="ml-2 text-sm text-gray-700">
                                <span class="font-medium">{{ $procType->name }}</span>
                            </label>
                        </div>
                    @endforeach
                </div>
                @error('procurement_type_id')
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

        </div>        <!-- Items Section -->
        <div id="itemsSection" class="w-full">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-lg font-semibold text-gray-900">Item yang Diminta</h3>
                <button type="button" onclick="addRow()"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-1 px-3 rounded-lg text-sm transition-colors duration-200">
                    <i class="fas fa-plus mr-1"></i> Tambah Item
                </button>
            </div>
            <!-- Items container -->
            <div id="itemsContainer" class="space-y-2">
                <!-- Item rows will be injected here by JS -->
            </div>
        </div>
        
    <!-- Hidden fields -->
    <input type="hidden" name="workflow_id" id="workflow_id" value="{{ $defaultWorkflow->id }}">
    <input type="hidden" name="request_type" value="normal">
</div>

<div class="flex justify-end space-x-3 mt-2">
    <a href="{{ $isEdit && $approvalRequest->items->first() ? route('approval-items.show', $approvalRequest->items->first()) : route('approval-requests.index') }}"
        class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-1 px-6 rounded-lg transition-colors duration-200">
        Batal
    </a>
    <button type="submit" id="submitButton"
        class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-1 px-6 rounded-lg transition-colors duration-200">
        <i class="fas fa-save mr-1"></i> <span id="submitButtonText">{{ $isEdit ? 'Update Request' : 'Buat Request' }}</span>
    </button>
</div>

<style>
    /* Prevent horizontal scroll on the page */
    #itemsSection {
        position: relative;
        max-width: 100%;
    }
    
    /* Items container */
    #itemsContainer {
        max-width: 100%;
        position: relative;
    }
    
    /* Ensure suggestions dropdowns are positioned correctly */
    .suggestions, .supplier-suggestions, .category-suggestions, .dept-suggestions {
        position: fixed !important; /* Use fixed positioning to escape overflow containers */
        z-index: 9999 !important;
        max-height: 200px;
        overflow-y: auto;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.375rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }
    
    /* Ensure dropdown containers don't clip content */
    .relative {
        position: relative;
    }
    
    /* Smooth rotation for chevron icon */
    .rotate-180 {
        transform: rotate(180deg);
    }
    
    /* Table cells should not clip */
    #itemsTableBody td {
        position: static;
    }
    
    /* Custom scrollbar for better UX */
    #itemsSection .overflow-x-auto::-webkit-scrollbar {
        height: 8px;
    }
    
    #itemsSection .overflow-x-auto::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }
    
    #itemsSection .overflow-x-auto::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 4px;
    }
    
    #itemsSection .overflow-x-auto::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
</style>

<script>
    // Note: Helper functions (escapeHtml, positionDropdown, formatRupiahInputValue, parseRupiahToNumber)
    // are now loaded from form-helpers.js

    let rows = [];
    let staticSectionShown = false;
    let staticRowsAppended = false;
    let currentItemTypeId = {!! $isEdit ? $approvalRequest->item_type_id ?? 'null' : 'null' !!};
    const allCategories = {!! json_encode(($itemCategories ?? collect())->map(function($c){return ['id'=>$c->id,'name'=>$c->name];})->values(), JSON_HEX_APOS|JSON_HEX_QUOT) !!} || [];
    const allDepartments = {!! json_encode(($departments ?? collect())->map(function($d){return ['id'=>$d->id,'name'=>$d->name];})->values(), JSON_HEX_APOS|JSON_HEX_QUOT) !!} || [];


    document.addEventListener('DOMContentLoaded', function() {
        initializeItemTypeSelection();
        

        @if ($isEdit)
            const existing = {!! json_encode(
                $approvalRequest->items->map(function ($item) use ($itemExtras, $itemFiles) {
                    $masterItem = $item->masterItem;
                    $itemExtra = isset($itemExtras) ? $itemExtras->get($masterItem->id) : null;
                    $filesForItem = isset($itemFiles) ? ($itemFiles->get($masterItem->id) ?? collect()) : collect();
                    return [
                        'master_item_id' => $masterItem->id,
                        'name' => $masterItem->name,
                        'quantity' => $item->quantity,
                        // IMPORTANT: cast decimal(18,2) to integer rupiah to avoid appending two zeros in JS
                        'unit_price' => (int) $item->unit_price,
                        'item_category_id' => $masterItem->item_category_id,
                        'item_category_name' => optional($masterItem->itemCategory)->name,
                        'specification' => $item->specification,
                        'brand' => $item->brand,
                        'alternative_vendor' => $item->alternative_vendor,
                        'allocation_department_id' => $item->allocation_department_id,
                        'allocation_department_name' => optional($item->allocationDepartment)->name,
                        'letter_number' => $item->letter_number,
                        'notes' => $item->notes,
                        'fs_document' => $item->fs_document,
                        'existing_files' => $filesForItem->map(function($file) {
                            return [
                                'id' => $file->id,
                                'original_name' => $file->original_name,
                                'path' => $file->path,
                            ];
                        })->values()->toArray(),
                        'itemExtra' => $itemExtra ? $itemExtra->toArray() : null,
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
        const submitButton = document.getElementById('submitButton');
        const submitButtonText = document.getElementById('submitButtonText');
        let isSubmitting = false;

        form.addEventListener('submit', async function(e) {
            // Prevent double submission
            if (isSubmitting) {
                e.preventDefault();
                return;
            }

            // Hide any open suggestion dropdowns before submit
            hideAllSuggestions();
            // Sync latest input values from DOM (especially category) before validation
            rows.forEach((row) => {
                const tr = document.getElementById('row-' + row.index);
                if (!tr) return;
                const catInput = tr.querySelector('.item-category');
                if (catInput) {
                    const val = (catInput.value || '').trim();
                    row.item_category_name = val;
                    if (val) {
                        const exact = (allCategories || []).find(c => (c.name || '').toLowerCase() === val.toLowerCase());
                        if (exact) {
                            row.item_category_id = exact.id;
                            row.item_category_name = exact.name;
                        }
                    }
                }
            });

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
                    // If creating a new item (no master_item_id), category is required
                    const creatingNew = !row.master_item_id && row.name && row.name.trim().length > 0;
                    if (creatingNew) {
                        const hasCategory = (row.item_category_id && String(row.item_category_id).length > 0) || (row.item_category_name && row.item_category_name.trim().length > 0);
                        if (!hasCategory) {
                            valid = false;
                            message = message || 'Kategori wajib diisi untuk item baru.';
                        }
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

            // Disable submit button and show loading state
            isSubmitting = true;
            submitButton.disabled = true;
            submitButton.classList.add('opacity-50', 'cursor-not-allowed');
            submitButton.classList.remove('hover:bg-blue-700');
            submitButtonText.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...';

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
                        if (r.item_category_id) payload.item_category_id = r.item_category_id;
                        else if (r.item_category_name) payload.item_category_name = r.item_category_name;
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
                if ((row.unit_price !== '' && !isNaN(parseFloat(row.unit_price)))) form.insertAdjacentHTML('beforeend',
                    `<input class="item-hidden" type="hidden" name="items[${row.index}][unit_price]" value="${row.unit_price}">`
                    );
                if (row.item_category_id) form.insertAdjacentHTML('beforeend',
                    `<input class="item-hidden" type="hidden" name="items[${row.index}][item_category_id]" value="${row.item_category_id}">`
                    );
                else if (row.item_category_name) form.insertAdjacentHTML('beforeend',
                    `<input class="item-hidden" type="hidden" name="items[${row.index}][item_category_name]" value="${escapeHtml(row.item_category_name)}">`
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
                if (row.allocation_department_id) form.insertAdjacentHTML('beforeend',
                    `<input class="item-hidden" type="hidden" name="items[${row.index}][allocation_department_id]" value="${row.allocation_department_id}">`
                    );
                if (row.letter_number) form.insertAdjacentHTML('beforeend',
                    `<input class="item-hidden" type="hidden" name="items[${row.index}][letter_number]" value="${escapeHtml(row.letter_number)}">`
                    );
                form.insertAdjacentHTML('beforeend',
                    `<input class="item-hidden" type="hidden" name="items[${row.index}][notes]" value="${escapeHtml(row.notes || '')}">`
                    );
            });

            form.submit();
        });
    });

    // ===== Total watcher & static section control =====
    function computeTotal() {
        let total = 0;
        rows.forEach(r => {
            const qty = parseInt(r.quantity || 0);
            const price = parseInt(r.unit_price || 0);
            if (!isNaN(qty) && !isNaN(price)) total += (qty * price);
        });
        return total;
    }

    // Note: appendStaticRowsFromActiveRows was removed - dead code (never called)
    // Note: hideAllSuggestions and scroll/resize listeners are now in autocomplete-suggestions.js

    /**
     * Helper to render searchable select in JS
     */
    function renderSearchableSelect(options, selectedId, callback, placeholder = 'Pilih...') {
        const found = options.find(o => String(o.id) === String(selectedId));
        const selectedLabel = found ? (found.label || found.name) : placeholder;
        const mappedOptions = options.map(o => ({ id: o.id, label: o.label || o.name }));
        const optionsJson = JSON.stringify(mappedOptions).replace(/"/g, '&quot;');
        
        return `
            <div x-data="{
                open: false,
                search: '',
                selectedId: '${selectedId}',
                selectedLabel: '${selectedLabel.replace(/'/g, "\\'")}',
                options: ${optionsJson},
                get filtered() {
                    if (!this.search) return this.options;
                    const q = this.search.toLowerCase();
                    return this.options.filter(o => o.label.toLowerCase().includes(q));
                },
                select(id, label) {
                    this.selectedId = id;
                    this.selectedLabel = label;
                    this.open = false;
                    this.search = '';
                    // Custom callback to sync with rows array
                    if (typeof ${callback} === 'function') {
                        ${callback}(id, label);
                    }
                }
            }" @click.outside="open = false" class="relative">
                <button type="button" @click="open = !open" 
                    class="w-full flex items-center justify-between h-6 px-1 text-xs border border-gray-300 rounded bg-white hover:border-blue-400 focus:outline-none focus:ring-1 focus:ring-blue-500 transition-colors">
                    <span class="truncate text-gray-700" x-text="selectedLabel"></span>
                    <i class="fas fa-chevron-down text-gray-400 text-[10px] ml-1 transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                </button>
                <div x-show="open" 
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    style="display:none;" 
                    class="absolute z-[9999] mt-1 w-64 bg-white border border-gray-200 rounded shadow-lg">
                    <div class="p-1 border-b border-gray-100">
                        <div class="relative">
                            <i class="fas fa-search absolute left-2 top-1/2 -translate-y-1/2 text-gray-400 text-[10px]"></i>
                            <input type="text" x-model="search" placeholder="Cari..." x-ref="searchInput"
                                @keydown.escape.stop="open = false" 
                                x-effect="if(open) $nextTick(() => $refs.searchInput.focus())"
                                class="w-full pl-5 pr-2 py-0.5 text-xs border border-gray-200 rounded focus:outline-none focus:ring-1 focus:ring-blue-500">
                        </div>
                    </div>
                    <ul class="max-h-48 overflow-y-auto py-1">
                        <li @click="select('', '${placeholder}')" class="px-2 py-1 text-xs cursor-pointer hover:bg-blue-50 flex items-center gap-1" :class="selectedId === '' ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-700'">
                            <i class="fas fa-check text-blue-600 text-[10px] w-2" x-show="selectedId === ''"></i>
                            <span :class="selectedId === '' ? '' : 'ml-3'">${placeholder}</span>
                        </li>
                        <template x-for="opt in filtered" :key="opt.id">
                            <li @click="select(opt.id, opt.label)" class="px-2 py-1 text-xs cursor-pointer hover:bg-blue-50 flex items-center gap-1" :class="selectedId == opt.id ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-700'">
                                <i class="fas fa-check text-blue-600 text-[10px] w-2" x-show="selectedId == opt.id"></i>
                                <span :class="selectedId == opt.id ? '' : 'ml-3'" x-text="opt.label"></span>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>
        `;
    }

    function addRow(defaults = {}) {
        const idx = rows.length;
        const row = {
            index: idx,
            master_item_id: defaults.master_item_id || '',
            name: defaults.name || '',
            quantity: defaults.quantity || 1,
            unit_price: (defaults.unit_price !== undefined && defaults.unit_price !== null) ? defaults.unit_price : '',
            item_category_id: defaults.item_category_id || '',
            item_category_name: defaults.item_category_name || '',
            specification: defaults.specification || '',
            brand: defaults.brand || '',
            supplier_id: defaults.supplier_id || '',
            alternative_vendor: defaults.alternative_vendor || '',
            notes: defaults.notes || '',
            allocation_department_id: defaults.allocation_department_id || '',
            allocation_department_name: defaults.allocation_department_name || '',
            letter_number: defaults.letter_number || '',
            fs_document: defaults.fs_document || '', // Add existing FS document
            existing_files: defaults.existing_files || [], // Add existing files
            locked: defaults.locked || false
        };
        // Get department name if ID exists but name doesn't
        if (row.allocation_department_id && !row.allocation_department_name) {
            const dept = (allDepartments || []).find(d => String(d.id) === String(row.allocation_department_id));
            if (dept) row.allocation_department_name = dept.name;
        }
        rows.push(row);
        renderRow(row);
    }

    function removeRow(index) {
        const row = rows.find(r => r.index === index);
        if (row && row.locked) return; // cegah hapus untuk baris statis
        rows = rows.filter(r => r.index !== index);
        document.getElementById('row-' + index)?.remove();
        // Update numbering for remaining items
        updateItemNumbers();
    }
    
    function updateItemNumbers() {
        const container = document.getElementById('itemsContainer');
        if (!container) return;
        const items = container.querySelectorAll('[id^="row-"]:not([id*="-static"])');
        items.forEach((item, idx) => {
            const header = item.querySelector('h4');
            if (header) {
                header.textContent = `Item #${idx + 1}`;
            }
        });
    }

    function renderRow(row) {
        const container = document.getElementById('itemsContainer');
        const itemDiv = document.createElement('div');
        itemDiv.id = 'row-' + row.index;
        itemDiv.className = 'bg-white border border-gray-200 rounded-lg p-2 space-y-1';
        
        // Get department name if ID exists
        if (row.allocation_department_id && !row.allocation_department_name) {
            const dept = (allDepartments || []).find(d => String(d.id) === String(row.allocation_department_id));
            if (dept) row.allocation_department_name = dept.name;
        }
        
        // Check if this is create mode (no price input)
        const isCreateMode = {{ $isEdit ? 'false' : 'true' }};
        
        // Global callback for department selection sync
        window[`onSelectDept_${row.index}`] = (id, label) => {
            row.allocation_department_id = id;
            row.allocation_department_name = label;
        };

        const deptDropdown = renderSearchableSelect(allDepartments, row.allocation_department_id, `onSelectDept_${row.index}`, 'Pilih unit');
        
        itemDiv.innerHTML = `
        <!-- Item header with delete button -->
        <div class="flex justify-between items-center mb-1">
            <h4 class="text-xs font-medium text-gray-700">Item #${row.index + 1}</h4>
            <button type="button" class="text-red-600 hover:text-red-800 hover:bg-red-50 p-0.5 rounded" onclick="removeRow(${row.index})" title="Hapus item">
                <i class="fas fa-trash text-xs"></i>
            </button>
        </div>
        
        <!-- First row of inputs -->
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-1">
            <div>
                <label class="block text-xs text-gray-600">Nama Item <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input type="text" class="item-name w-full h-6 px-1 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="Cari atau ketik" value="${escapeHtml(row.name)}" autocomplete="off" required>
                    <div class="suggestions hidden"></div>
                </div>
            </div>
            <div>
                <label class="block text-xs text-gray-600">Jumlah <span class="text-red-500">*</span></label>
                <input type="number" min="1" class="item-qty w-full h-6 px-1 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" value="${row.quantity}" required>
            </div>
            <div>
                <label class="block text-xs text-gray-600">Merk</label>
                <input type="text" class="item-brand w-full h-6 px-1 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="Merk barang" value="${escapeHtml(row.brand)}">
            </div>
            <div>
                <label class="block text-xs text-gray-600">Vendor Alternatif</label>
                <div class="relative">
                    <input type="text" class="alt-vendor w-full h-6 px-1 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="Nama vendor" value="${escapeHtml(row.alternative_vendor)}" autocomplete="off">
                    <div class="supplier-suggestions hidden"></div>
                </div>
            </div>
        </div>
        
        <!-- Second row of inputs (6 fields) -->
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-1">
            <div>
                <label class="block text-xs text-gray-600">Spesifikasi</label>
                <textarea class="item-spec w-full h-12 px-1 py-0.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 resize-y" placeholder="Detail spesifikasi">${escapeHtml(row.specification)}</textarea>
            </div>
            <div>
                <label class="block text-xs text-gray-600">Kategori</label>
                <div class="relative">
                    <input type="text" class="item-category w-full h-6 px-1 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="Pilih kategori" value="${escapeHtml(row.item_category_name||'')}" autocomplete="off">
                    <div class="category-suggestions hidden"></div>
                </div>
            </div>
            <div>
                <label class="block text-xs text-gray-600">No. Surat</label>
                <input type="text" class="item-letter w-full h-6 px-1 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="Nomor surat" value="${escapeHtml(row.letter_number)}">
            </div>
            <div>
                <label class="block text-xs text-gray-600">Unit Peruntukan</label>
                ${deptDropdown}
            </div>
            <div>
                <label class="block text-xs text-gray-600">Catatan</label>
                <textarea class="item-notes w-full h-12 px-1 py-0.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 resize-y" placeholder="Catatan tambahan">${escapeHtml(row.notes)}</textarea>
            </div>
            <div>
                <label class="block text-xs text-gray-600">Dokumen</label>
                <div class="flex flex-col gap-1">
                    <div class="flex flex-col gap-1.5 items-start w-full">
                        <div class="flex items-center gap-2">
                            <input type="file" name="items[${row.index}][files][]" class="item-files hidden" multiple accept=".pdf">
                            <button type="button" class="item-files-btn shrink-0 whitespace-nowrap h-7 px-3 inline-flex items-center justify-center text-blue-700 bg-blue-50 hover:bg-blue-100 hover:text-blue-800 border border-blue-200 rounded-md text-xs font-medium transition-colors duration-150 shadow-sm" title="Unggah dokumen PDF">
                                <i class="fas fa-cloud-upload-alt mr-1.5 text-[13px]"></i> <span>Upload PDF</span>
                            </button>
                            ${row.fs_document ? `<input type="hidden" name="items[${row.index}][existing_fs_document]" value="${escapeHtml(row.fs_document)}">` : ''}
                        </div>
                        <div class="item-files-count text-xs text-gray-600 w-full flex items-center min-w-0"></div>
                    </div>
                    ${row.existing_files && row.existing_files.length > 0 ? `
                        <div class="existing-files text-xs text-gray-600">
                            <span class="font-medium">File tersimpan:</span>
                            ${row.existing_files.map(f => `<span class="ml-1">${escapeHtml(f.original_name)}</span>`).join(', ')}
                        </div>
                    ` : ''}
                </div>
            </div>
        </div>`;
        
        container.appendChild(itemDiv);

        bindRowEvents(itemDiv, row);
        // Kunci elemen jika baris locked (statis)
        if (row.locked) {
            itemDiv.querySelectorAll('input, textarea, select, button').forEach(el => {
                if (el.type !== 'hidden') {
                    el.disabled = true;
                    el.classList.add('bg-gray-100', 'cursor-not-allowed');
                }
            });
            const delBtn = itemDiv.querySelector('button[onclick^="removeRow("]');
            if (delBtn) delBtn.classList.add('hidden');
        }
    }

    function bindRowEvents(itemDiv, row) {
        const nameInput = itemDiv.querySelector('.item-name');
        const qtyInput = itemDiv.querySelector('.item-qty');
        const notesInput = itemDiv.querySelector('.item-notes');
        const specInput = itemDiv.querySelector('.item-spec');
        const brandInput = itemDiv.querySelector('.item-brand');
        const altVendorInput = itemDiv.querySelector('.alt-vendor');
        const fileInput = itemDiv.querySelector('.item-files');
        const fileBtn = itemDiv.querySelector('.item-files-btn');
        const fileCount = itemDiv.querySelector('.item-files-count');
        const supplierSugBox = itemDiv.querySelector('.supplier-suggestions');
        const sugBox = itemDiv.querySelector('.suggestions');
        const categoryInput = itemDiv.querySelector('.item-category');
        const categorySugBox = itemDiv.querySelector('.category-suggestions');
        const letterInput = itemDiv.querySelector('.item-letter');

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
            renderSuggestions(sugBox, data.items || [], row, nameInput, null, categoryInput);
        });
        nameInput.addEventListener('blur', function() {
            setTimeout(() => sugBox.classList.add('hidden'), 200);
        });
        nameInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                resolveTyped(nameInput, row, null);
            } else if (e.key === 'Escape') {
                sugBox.classList.add('hidden');
            }
        });

        qtyInput.addEventListener('change', function() {
            row.quantity = parseInt(this.value) || 1;
        });
        if (fileBtn && fileInput) {
            fileBtn.addEventListener('click', function() {
                fileInput.click();
            });
            fileInput.addEventListener('change', function() {
                const files = fileInput.files;
                const n = files?.length || 0;
                if (fileCount) {
                    if (n > 0) {
                        const fileNames = Array.from(files).map(f => escapeHtml(f.name)).join(', ');
                        const displayTxt = n === 1 ? fileNames : `${n} file: ${fileNames}`;
                        fileCount.innerHTML = `<i class="fas fa-check-circle text-green-500 mr-1.5 text-sm shrink-0"></i> <span class="truncate font-medium text-gray-700" title="${fileNames}">${displayTxt}</span>`;
                        fileCount.setAttribute('title', fileNames);
                    } else {
                        fileCount.innerHTML = '';
                        fileCount.removeAttribute('title');
                    }
                }
            });
        }
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

        // Category input handling
        if (categoryInput) {
            categoryInput.addEventListener('input', function() {
                const val = this.value.trim();
                row.item_category_name = val;
                row.item_category_id = '';
                
                if (val.length < 1) {
                    categorySugBox.classList.add('hidden');
                    return;
                }
                
                // Filter categories based on input
                const filtered = (allCategories || []).filter(c => 
                    (c.name || '').toLowerCase().includes(val.toLowerCase())
                );
                
                renderCategorySuggestions(categorySugBox, filtered, row.index);
            });
            
            categoryInput.addEventListener('blur', function() {
                setTimeout(() => categorySugBox.classList.add('hidden'), 200);
            });
        }

        if (letterInput) {
            letterInput.addEventListener('change', function(){
                row.letter_number = this.value || '';
            });
        }

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
        altVendorInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') supplierSugBox.classList.add('hidden');
        });
    }

    // Note: Autocomplete functions (renderSuggestions, renderCategorySuggestions, renderSupplierSuggestions,
    // renderDepartmentSuggestions, selectSuggestion, selectCategorySuggestion, selectSupplierSuggestion,
    // selectDepartmentSuggestion) are now loaded from autocomplete-suggestions.js

    async function resolveTyped(nameInput, row, priceInput) {
        const payload = {
            name: nameInput.value.trim()
        };
        const checked = document.querySelector('input[name="item_type_id"]:checked');
        if (checked) payload.item_type_id = checked.value;
        if (row.item_category_id) payload.item_category_id = row.item_category_id;
        else if (row.item_category_name) payload.item_category_name = row.item_category_name;
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
            // Do not auto-fill price from resolved item
            nameInput.value = data.item.name;
        }
    }

    function initializeItemTypeSelection() {
        const itemTypeRadios = document.querySelectorAll('input[name="item_type_id"]');
        itemTypeRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                currentItemTypeId = this.value;
            });
        });
        const checkedRadio = document.querySelector('input[name="item_type_id"]:checked');
        if (checkedRadio) {
            currentItemTypeId = checkedRadio.value;
        }
    }
    
    // expose functions to window
    window.addRow = addRow;
</script>
