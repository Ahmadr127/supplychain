@php
    $isEdit = ($mode ?? 'create') === 'edit';
@endphp

<div class="space-y-2 max-w-full overflow-hidden">
    <!-- Main Form -->
    <div class="space-y-2 max-w-full">
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

        

        <!-- Items Section -->
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
    
    /* Transition for form extra toggle */
    .form-extra-icon {
        transition: transform 0.2s ease;
    }
    
    /* Form extra content animation */
    .form-extra-content {
        transition: all 0.3s ease;
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

    // Position dropdown using fixed positioning (viewport-based)
    // IMPORTANT: For position: fixed, coordinates are relative to viewport.
    // Do NOT add window scroll offsets, otherwise it will shift incorrectly.
    function positionDropdown(input, dropdown) {
        const rect = input.getBoundingClientRect();
        dropdown.style.top = rect.bottom + 'px';
        dropdown.style.left = rect.left + 'px';
        dropdown.style.width = rect.width + 'px';
    }
    
    // Rupiah helpers
    function formatRupiahInputValue(val) {
        // Integer-only Rupiah formatting: group with dot, no decimals
        if (val === '' || val === null || typeof val === 'undefined') return '';
        let digits = String(val).replace(/\D/g, '');
        if (!digits) return '';
        // remove leading zeros but keep single 0
        digits = digits.replace(/^0+(\d)/, '$1');
        return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }
    function parseRupiahToNumber(str) {
        // Return integer-only numeric string (no separators). '' if empty
        if (str == null) return '';
        let digits = String(str).replace(/\D/g, '');
        return digits;
    }

    // Sync main inputs -> Form Statis (Section A) for the same row
    function syncFormExtraFields(rowIndex, opts = { force: false }) {
        const row = (rows || []).find(r => r.index === rowIndex);
        if (!row) return;
        const trFs = document.getElementById(`row-${rowIndex}-static`);
        if (!trFs) return;
        const setIfEmpty = (selector, val) => {
            const el = trFs.querySelector(selector);
            if (!el) return;
            const current = (el.value ?? '').toString().trim();
            if (opts.force || current === '') {
                el.value = val != null ? String(val) : '';
            }
        };
        // a_nama from main item name
        setIfEmpty('.fs-a_nama', row.name || '');
        // a_jumlah from main quantity
        setIfEmpty('.fs-a_jumlah', (row.quantity != null && row.quantity !== '') ? row.quantity : '');
        // a_harga from TOTAL (quantity × unit_price), not unit_price
        const qty = parseInt(row.quantity || 0) || 0;
        const price = parseInt(row.unit_price || 0) || 0;
        const total = qty * price;
        const harga = total > 0 ? formatRupiahInputValue(total) : '';
        setIfEmpty('.fs-a_harga', harga);
    }

    let rows = [];
    let staticSectionShown = false;
    let staticRowsAppended = false;
    let currentItemTypeId = {!! $isEdit ? $approvalRequest->item_type_id ?? 'null' : 'null' !!};
    const allCategories = {!! json_encode(($itemCategories ?? collect())->map(function($c){return ['id'=>$c->id,'name'=>$c->name];})->values(), JSON_HEX_APOS|JSON_HEX_QUOT) !!} || [];
    const allDepartments = {!! json_encode(($departments ?? collect())->map(function($d){return ['id'=>$d->id,'name'=>$d->name];})->values(), JSON_HEX_APOS|JSON_HEX_QUOT) !!} || [];
    
    // Dynamic FS settings from database
    // Simplified: two thresholds with clear purpose
    // - thresholdShow: when to show form and enable inputs (e.g., 50jt)
    // - thresholdUpload: when to require document upload (e.g., 100jt)
    const fsSettings = {
        enabled: {!! json_encode($fsSettings['fs_document_enabled'] ?? true) !!},
        thresholdShow: {!! json_encode($fsSettings['fs_threshold_per_item'] ?? 50000000) !!},
        thresholdUpload: {!! json_encode($fsSettings['fs_threshold_total'] ?? 100000000) !!}
    };
    
    // Track if total threshold is met
    let totalThresholdMet = false;

    document.addEventListener('DOMContentLoaded', function() {
        initializeItemTypeSelection();
        

        @if ($isEdit)
            const existing = {!! json_encode(
                $approvalRequest->masterItems->map(function ($item) use ($itemExtras, $itemFiles) {
                    $itemExtra = isset($itemExtras) ? $itemExtras->get($item->id) : null;
                    $filesForItem = isset($itemFiles) ? ($itemFiles->get($item->id) ?? collect()) : collect();
                    return [
                        'master_item_id' => $item->id,
                        'name' => $item->name,
                        'quantity' => $item->pivot->quantity,
                        // IMPORTANT: cast decimal(18,2) to integer rupiah to avoid appending two zeros in JS
                        'unit_price' => (int) $item->pivot->unit_price,
                        'item_category_id' => $item->item_category_id,
                        'item_category_name' => optional($item->itemCategory)->name,
                        'specification' => $item->pivot->specification,
                        'brand' => $item->pivot->brand,
                        'alternative_vendor' => $item->pivot->alternative_vendor,
                        'allocation_department_id' => $item->pivot->allocation_department_id,
                        'allocation_department_name' => optional($item->pivot->allocationDepartment)->name,
                        'letter_number' => $item->pivot->letter_number,
                        'notes' => $item->pivot->notes,
                        'fs_document' => $item->pivot->fs_document,
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
                // Check total threshold after loading all items
                checkTotalThreshold();
            } else {
                addRow();
            }
        @else
            addRow();
        @endif

        // Pasang watcher total harga
        installTotalWatcher();

        // Serialize rows on submit
        const form = document.getElementById('approval-form');
        form.addEventListener('submit', async function(e) {
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

            // Collect form extra data for each item if form statis is visible
            try {
                collectFormExtraData();
            } catch (err) {
                console.error('Error collecting form extra data:', err);
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
                
                // Add form extra data if exists
                if (row.formExtraData) {
                    Object.keys(row.formExtraData).forEach(key => {
                        const value = row.formExtraData[key];
                        if (value !== null && value !== undefined && value !== '') {
                            form.insertAdjacentHTML('beforeend',
                                `<input class="item-hidden" type="hidden" name="items[${row.index}][form_extra][${key}]" value="${escapeHtml(String(value))}">`
                            );
                        }
                    });
                }

                // Preserve existing per-item FS document on edit if no new file selected
                try {
                    const trFs = document.getElementById(`row-${row.index}-static`);
                    const fileInput = trFs ? trFs.querySelector('.fs-document-input') : null;
                    const hasNewFile = !!(fileInput && fileInput.files && fileInput.files.length > 0);
                    if (row.fs_document && !hasNewFile) {
                        form.insertAdjacentHTML('beforeend',
                            `<input class="item-hidden" type="hidden" name="items[${row.index}][existing_fs_document]" value="${escapeHtml(row.fs_document)}">`
                        );
                    }
                } catch (_) { /* no-op */ }
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

    function maybeToggleStaticSection() {
        // This function is kept for backward compatibility but not used anymore
        // We now use toggleRowStaticSectionForRow for per-item threshold
    }
    
    // Fungsi ini tidak lagi diperlukan karena kita hanya menggunakan FS per-item
    function checkFSDocumentThreshold() {
        // Fungsi dikosongkan, hanya untuk backward compatibility
        // FS document sekarang hanya per-item, tidak ada global FS document
    }

    function installTotalWatcher() {
        // Untuk kompatibilitas lama (global), tidak dipakai lagi.
        // Kita cukup memastikan setiap baris mengevaluasi dirinya saat dibuat.
        rows.forEach(r => toggleRowStaticSectionForRow(r.index));
    }

    // Toggle tampilkan Form Statis utk baris tertentu berdasarkan subtotal
    // Simplified logic:
    // - subtotal >= thresholdShow (50jt): Show form + enable inputs
    // - subtotal >= thresholdUpload (100jt): Show form + enable inputs + require upload
    function toggleRowStaticSectionForRow(rowIndex) {
        const row = rows.find(r => r.index === rowIndex);
        if (!row) return;
        const subtotal = (parseInt(row.quantity || 0) || 0) * (parseInt(row.unit_price || 0) || 0);
        const trFs = document.getElementById(`row-${rowIndex}-static`);
        if (!trFs) return;
        
        // If FS is disabled globally, always hide the form
        if (!fsSettings.enabled) {
            trFs.classList.add('hidden');
            setRadiosRequired(trFs, false);
            return;
        }
        
        const meetsShowThreshold = subtotal >= fsSettings.thresholdShow;
        const meetsUploadThreshold = subtotal >= fsSettings.thresholdUpload;

        if (meetsShowThreshold) {
            trFs.classList.remove('hidden');
            configureFormState(rowIndex, meetsShowThreshold, meetsUploadThreshold);
        } else {
            trFs.classList.add('hidden');
            setRadiosRequired(trFs, false);
        }
    }
    
    // No longer needed - per-item thresholds only
    function checkTotalThreshold() {
        // Kept for backward compatibility
    }
    
    // Configure form state based on threshold conditions and settings
    // Helper: toggle required attribute for all radios inside a form-static container
    function setRadiosRequired(container, shouldRequire) {
        if (!container) return;
        const radios = container.querySelectorAll('input[type="radio"]');
        radios.forEach(r => {
            if (shouldRequire) r.setAttribute('required', '');
            else r.removeAttribute('required');
        });
    }

    function configureFormState(rowIndex, meetsShowThreshold, meetsUploadThreshold) {
        const trFs = document.getElementById(`row-${rowIndex}-static`);
        if (!trFs) return;
        
        // Simplified: if form is shown, inputs are always enabled
        // Upload is enabled only when upload threshold is met
        const enableInputs = meetsShowThreshold;
        const enableUpload = meetsUploadThreshold;
        
        // Configure regular inputs (always enabled when form is shown)
        const inputs = trFs.querySelectorAll('input:not(.fs-document-input), select, textarea');
        inputs.forEach(input => {
            input.disabled = false;
            input.classList.remove('bg-gray-100', 'cursor-not-allowed', 'opacity-50');
        });

        // Configure radios: required when form is visible
        setRadiosRequired(trFs, true);
        
        // Configure upload section
        const uploadSection = trFs.querySelector('.fs-upload-section');
        const fileInput = trFs.querySelector('.fs-document-input');
        if (uploadSection) {
            if (enableUpload) {
                uploadSection.classList.remove('hidden');
                if (fileInput) {
                    fileInput.disabled = false; // keep enabled when visible
                    fileInput.classList.remove('bg-gray-100', 'cursor-not-allowed', 'opacity-50');
                    // Make FS file input required when visible and no existing FS document
                    try {
                        const row = (rows || []).find(r => r.index === rowIndex);
                        const hasExisting = !!(row && row.fs_document);
                        fileInput.required = !hasExisting;
                    } catch (_) { fileInput.required = true; }
                }
            } else {
                // Hide and clear value for safety so hidden files are not submitted
                uploadSection.classList.add('hidden');
                if (fileInput) {
                    try { fileInput.value = ''; } catch (_) {}
                    // Ensure not required when hidden
                    fileInput.required = false;
                }
            }
        }

        // Prefill visible form statis fields from main inputs
        if (!trFs.classList.contains('hidden')) {
            try { syncFormExtraFields(rowIndex); } catch(_) {}
        }
    }
    
    // Backward compatibility - keep old function name
    function setFormStatisInputsState(rowIndex, meetsPerItemThreshold) {
        configureFormState(rowIndex, meetsPerItemThreshold);
    }

    // Kumpulkan field dari setiap Form Statis per-baris yang aktif dan append item terkunci
    function appendStaticRowsFromActiveRows() {
        // Append one global B summary row if section visible and not yet appended
        try {
            const secB = document.getElementById('staticFormSection');
            if (secB && !secB.classList.contains('hidden') && !staticRowsAppended) {
                const b_jml_pegawai = (document.getElementById('fsb_jml_pegawai')?.value || '').trim();
                const b_jml_dokter = (document.getElementById('fsb_jml_dokter')?.value || '').trim();
                const b_beban = (document.querySelector('input[name="fs-b_beban_tugas"]:checked')?.value || '').trim();
                const b_barang_ada = (document.querySelector('input[name="fs-b_barang_ada"]:checked')?.value || '').trim();

                // Tentukan kategori default untuk baris ringkasan
                let catIdB = '';
                let catNameB = '';
                if ((allCategories || []).length > 0) {
                    catIdB = allCategories[0].id;
                    catNameB = allCategories[0].name;
                }

                const specB = [
                    `Jumlah pegawai pengguna barang: ${b_jml_pegawai}`,
                    `Jumlah dokter: ${b_jml_dokter}`,
                    `Tingkat beban tugas: ${b_beban}`,
                    `Barang sejenis sudah tersedia/dimiliki/dikuasai: ${b_barang_ada}`
                ].join('\n');

                addRow({
                    name: 'Dukungan Unit (Form Statis) - Global',
                    quantity: 1,
                    unit_price: '',
                    item_category_id: catIdB,
                    item_category_name: catNameB,
                    specification: specB,
                    notes: '',
                    locked: true
                });
                staticRowsAppended = true;
            }
        } catch (e) {}

        rows.forEach((r) => {
            const trFs = document.getElementById(`row-${r.index}-static`);
            if (!trFs || trFs.classList.contains('hidden') || r.staticAppended) return;

            // Ambil nilai dari elemen di dalam trFs
            const gv = (sel) => {
                const el = trFs.querySelector(sel);
                return el ? (el.value || '').trim() : '';
            };
            const gvr = (name) => {
                const el = trFs.querySelector(`input[name="${name}"]:checked`);
                return el ? el.value : '';
            };

            const a_nama = gv('.fs-a_nama');
            const a_fungsi = gv('.fs-a_fungsi');
            const a_ukuran = gv('.fs-a_ukuran');
            const a_jumlah = gv('.fs-a_jumlah');
            const a_satuan = gv('.fs-a_satuan');
            const a_waktu = gv('.fs-a_waktu');
            const a_waktu_satuan = (function(){ const el = trFs.querySelector('.fs-a_waktu_satuan'); return el ? (el.value||'') : ''; })();
            const a_pengguna = gv('.fs-a_pengguna');
            const a_leadtime = gv('.fs-a_leadtime');
            const a_ekatalog = gvr(`fs-a_ekatalog-${r.index}`);
            const a_ekatalog_ket = gv('.fs-a_ekatalog_ket');
            const a_harga = gv('.fs-a_harga');
            const a_kategori_perm = gvr(`fs-a_kategori_perm-${r.index}`);
            const a_lampiran = gvr(`fs-a_lampiran-${r.index}`);

            const e_kirim = gv('.fs-e_kirim');
            const e_angkut = gv('.fs-e_angkut');
            const e_instalasi = gv('.fs-e_instalasi');
            const e_penyimpanan = gv('.fs-e_penyimpanan');
            const e_operasi = gvr(`fs-e_operasi-${r.index}`);
            const e_catatan = gv('.fs-e_catatan');
            const e_pelatihan = gvr(`fs-e_pelatihan-${r.index}`);
            const e_aspek = gvr(`fs-e_aspek-${r.index}`);

            // Section C fields
            const c_jumlah = gv('.fs-c_jumlah');
            const c_satuan = (function(){ const el = trFs.querySelector('.fs-c_satuan'); return el ? (el.value||'') : ''; })();
            const c_kondisi = gvr(`fs-c_kondisi-${r.index}`);
            const c_kondisi_lain = gv('.fs-c_kondisi_lain');
            const c_lokasi = gv('.fs-c_lokasi');
            const c_sumber = gvr(`fs-c_sumber-${r.index}`);
            const c_kemudahan = gvr(`fs-c_kemudahan-${r.index}`);
            const c_produsen = gvr(`fs-c_produsen-${r.index}`);
            const c_kriteria_dn = !!trFs.querySelector('.fs-c_kriteria_dn')?.checked;
            const c_kriteria_impor = !!trFs.querySelector('.fs-c_kriteria_impor')?.checked;
            const c_kriteria_kerajinan = !!trFs.querySelector('.fs-c_kriteria_kerajinan')?.checked;
            const c_kriteria_jasa = !!trFs.querySelector('.fs-c_kriteria_jasa')?.checked;
            const c_tkdn = gvr(`fs-c_tkdn-${r.index}`);
            const c_tkdn_min = gv('.fs-c_tkdn_min');

            // Kategori: gunakan milik baris r bila ada, jika tidak fallback kategori pertama
            let catId = r.item_category_id || '';
            let catName = r.item_category_name || '';
            if ((!catId && !catName) && (allCategories || []).length > 0) {
                catId = allCategories[0].id;
                catName = allCategories[0].name;
            }

            const specA = [
                `Nama/Jenis: ${a_nama}`,
                `Fungsikegunaan: ${a_fungsi}`,
                `Ukuran/Kapasitas: ${a_ukuran}`,
                `Jumlah: ${a_jumlah} ${a_satuan}`,
                `Waktu Pemanfaatan: ${a_waktu} ${a_waktu_satuan}`,
                `Pengguna/Pengelola: ${a_pengguna}`,
                `Perkiraan Waktu Pengadaan: ${a_leadtime}`,
                `e-Katalog LKPP: ${a_ekatalog}${a_ekatalog_ket ? ' ('+a_ekatalog_ket+')' : ''}`,
                `Kategori Permintaan: ${a_kategori_perm}`,
                `Lampiran Analisa: ${a_lampiran}`,
                `Harga Perkiraan (Form Statis): ${a_harga}`
            ].join('\n');

            addRow({
                name: `Identifikasi Kebutuhan (Form Statis) - Item #${r.index + 1}`,
                quantity: 1,
                unit_price: '',
                item_category_id: catId,
                item_category_name: catName,
                specification: specA,
                notes: '',
                locked: true
            });

            const specDE = [
                `Cara Pengiriman: ${e_kirim}`,
                `Cara Pengangkutan: ${e_angkut}`,
                `Instalasi/Pemasangan: ${e_instalasi}`,
                `Penyimpanan/Penimbunan: ${e_penyimpanan}`,
                `Pengoperasian: ${e_operasi}`,
                `Catatan Pengoperasian: ${e_catatan}`,
                `Perlu Pelatihan: ${e_pelatihan}`,
                `Aspek Bekalan/Layanan: ${e_aspek}`
            ].join('\n');

            addRow({
                name: `Persyaratan & Operasional (Form Statis) - Item #${r.index + 1}`,
                quantity: 1,
                unit_price: '',
                item_category_id: catId,
                item_category_name: catName,
                specification: specDE,
                notes: '',
                locked: true
            });

            const kriteriaList = [
                c_kriteria_dn ? 'Produk dalam negeri' : null,
                c_kriteria_impor ? 'Barang impor' : null,
                c_kriteria_kerajinan ? 'Produk kerajinan tangan' : null,
                c_kriteria_jasa ? 'Jasa' : null
            ].filter(Boolean).join(', ');

            const specC = [
                `Jumlah barang sejenis telah tersedia: ${c_jumlah} ${c_satuan}`,
                `Kondisi/Kelayakan: ${c_kondisi}${c_kondisi==='lainnya' && c_kondisi_lain ? ' ('+c_kondisi_lain+')' : ''}`,
                `Lokasi/Keberadaan: ${c_lokasi}`,
                `Sumber/Asal barang: ${c_sumber}`,
                `Kemudahan diperoleh di pasar: ${c_kemudahan}`,
                `Produsen/Pelaku usaha yang mampu: ${c_produsen}`,
                `Kriteria Barang: ${kriteriaList || '-'}`,
                `Persyaratan TKDN: ${c_tkdn}${c_tkdn==='ya' && c_tkdn_min ? ' (≥ '+c_tkdn_min+'%)' : ''}`
            ].join('\n');

            addRow({
                name: `Identifikasi Barang Eksisting (Form Statis) - Item #${r.index + 1}`,
                quantity: 1,
                unit_price: '',
                item_category_id: catId,
                item_category_name: catName,
                specification: specC,
                notes: '',
                locked: true
            });

            // Tandai sudah diappend untuk baris sumber ini
            r.staticAppended = true;
        });
    }

    function hideAllSuggestions() {
        document.querySelectorAll('.suggestions, .supplier-suggestions, .category-suggestions, .dept-suggestions')
            .forEach(el => el.classList.add('hidden'));
    }

    // Reposition/hide dropdowns on scroll/resize to avoid misalignment and lingering
    window.addEventListener('scroll', hideAllSuggestions, true);
    window.addEventListener('resize', hideAllSuggestions);

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
            locked: defaults.locked || false,
            itemExtra: defaults.itemExtra || null // Add itemExtra data
        };
        // Get department name if ID exists but name doesn't
        if (row.allocation_department_id && !row.allocation_department_name) {
            const dept = (allDepartments || []).find(d => String(d.id) === String(row.allocation_department_id));
            if (dept) row.allocation_department_name = dept.name;
        }
        rows.push(row);
        renderRow(row);
        // Re-evaluasi thresholds setelah menambah baris
        toggleRowStaticSectionForRow(row.index);
        
        // Load itemExtra data if exists (for edit mode)
        if (row.itemExtra) {
            setTimeout(() => loadItemExtraData(row.index, row.itemExtra), 100);
        }
        
        // Show existing FS document status if exists (for edit mode)
        if (row.fs_document) {
            setTimeout(() => {
                const trFs = document.getElementById(`row-${row.index}-static`);
                if (trFs) {
                    const uploadSection = trFs.querySelector('.fs-upload-section');
                    if (uploadSection) {
                        const existingNote = document.createElement('div');
                        existingNote.className = 'text-xs text-green-700 mt-1';
                        existingNote.innerHTML = '<i class="fas fa-check-circle mr-1"></i>Dokumen FS sudah tersimpan';
                        uploadSection.appendChild(existingNote);
                    }
                }
            }, 150);
        }
    }

    function removeRow(index) {
        const row = rows.find(r => r.index === index);
        if (row && row.locked) return; // cegah hapus untuk baris statis
        rows = rows.filter(r => r.index !== index);
        document.getElementById('row-' + index)?.remove();
        document.getElementById(`row-${index}-static`)?.remove();
        // Update numbering for remaining items
        updateItemNumbers();
        // Re-check total threshold after removing item
        checkTotalThreshold();
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
        
        itemDiv.innerHTML = `
        <!-- Item header with delete button -->
        <div class="flex justify-between items-center mb-1">
            <h4 class="text-xs font-medium text-gray-700">Item #${row.index + 1}</h4>
            <button type="button" class="text-red-600 hover:text-red-800 hover:bg-red-50 p-0.5 rounded" onclick="removeRow(${row.index})" title="Hapus item">
                <i class="fas fa-trash text-xs"></i>
            </button>
        </div>
        
        <!-- First row of inputs (6 fields) -->
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-1">
            <div>
                <label class="block text-xs text-gray-600">Nama Item <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input type="text" class="item-name w-full h-6 px-1 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="Cari atau ketik" value="${escapeHtml(row.name)}" autocomplete="off" required>
                    <div class="suggestions hidden"></div>
                </div>
            </div>
            <div>
                <label class="block text-xs text-gray-600">Jumlah</label>
                <input type="number" min="1" class="item-qty w-full h-6 px-1 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" value="${row.quantity}">
            </div>
            <div>
                <label class="block text-xs text-gray-600">Harga Satuan</label>
                <input type="text" inputmode="numeric" class="item-price w-full h-6 px-1 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="0" value="${formatRupiahInputValue(row.unit_price)}">
            </div>
            <div>
                <label class="block text-xs text-gray-600">Total (Qty x Harga)</label>
                <input type="text" class="item-total w-full h-6 px-1 border border-gray-200 rounded text-xs bg-gray-50 text-gray-700" readonly value="${formatRupiahInputValue(((parseInt(row.quantity||0)||0) * (parseInt(row.unit_price||0)||0)))}">
            </div>
            <!-- Kategori dipindah ke baris kedua -->
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
                <div class="relative">
                    <input type="text" class="allocation-dept w-full h-6 px-1 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="Pilih unit" value="${escapeHtml(row.allocation_department_name||'')}" autocomplete="off">
                    <div class="dept-suggestions hidden"></div>
                </div>
            </div>
            <div>
                <label class="block text-xs text-gray-600">Catatan</label>
                <textarea class="item-notes w-full h-12 px-1 py-0.5 border border-gray-300 rounded text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 resize-y" placeholder="Catatan tambahan">${escapeHtml(row.notes)}</textarea>
            </div>
            <div>
                <label class="block text-xs text-gray-600">Dokumen</label>
                <div class="flex flex-col gap-1">
                    <div class="flex items-center">
                        <input type="file" name="items[${row.index}][files][]" class="item-files hidden" multiple accept=".pdf,.doc,.docx,.xls,.xlsx">
                        <button type="button" class="item-files-btn h-6 px-2 inline-flex items-center justify-center text-gray-700 hover:text-blue-700 hover:bg-blue-50 border border-gray-300 rounded text-xs" title="Unggah dokumen">
                            <i class="fas fa-paperclip mr-0.5 text-xs"></i> <span class="text-xs">Upload</span>
                        </button>
                        <span class="item-files-count text-xs text-gray-600 ml-1"></span>
                        ${row.fs_document ? `<input type="hidden" name="items[${row.index}][existing_fs_document]" value="${escapeHtml(row.fs_document)}">` : ''}
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
        // Tambahkan div kedua untuk Form Statis per-item (tersembunyi default)
        const trFs = document.createElement('div');
        trFs.id = `row-${row.index}-static`;
        trFs.className = 'hidden bg-gray-50 border border-gray-200 rounded-lg p-2 mt-1';
        // Use the template function from _form-extra.blade.php
        trFs.innerHTML = getFormStatisHTML(row.index);
        container.appendChild(trFs);
        
        // Add event listener for toggle button
        const toggleBtn = trFs.querySelector('.form-extra-toggle');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                toggleFormExtra(this);
            });
        }

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
        const priceInput = itemDiv.querySelector('.item-price');
        const totalInput = itemDiv.querySelector('.item-total');
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
        const deptInput = itemDiv.querySelector('.allocation-dept');
        const deptSugBox = itemDiv.querySelector('.dept-suggestions');
        const letterInput = itemDiv.querySelector('.item-letter');

        nameInput.addEventListener('input', async function() {
            row.name = this.value;
            row.master_item_id = '';
            // Realtime sync to form statis (force overwrite to mirror main field)
            syncFormExtraFields(row.index, { force: true });
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
            renderSuggestions(sugBox, data.items || [], row, nameInput, priceInput, categoryInput);
        });
        nameInput.addEventListener('blur', function() {
            setTimeout(() => sugBox.classList.add('hidden'), 200);
        });
        nameInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                resolveTyped(nameInput, row, priceInput);
            } else if (e.key === 'Escape') {
                sugBox.classList.add('hidden');
            }
        });

        qtyInput.addEventListener('change', function() {
            row.quantity = parseInt(this.value) || 1;
            toggleRowStaticSectionForRow(row.index);
            // Check total threshold for all items
            checkTotalThreshold();
            // update total display
            const q = parseInt(row.quantity || 0) || 0;
            const p = parseInt(row.unit_price || 0) || 0;
            if (totalInput) totalInput.value = formatRupiahInputValue(q * p);
            // Realtime sync quantity to form statis
            syncFormExtraFields(row.index, { force: true });
        });
        const handlePriceInput = function() {
            const v = (priceInput.value || '').trim();
            const normalized = parseRupiahToNumber(v);
            row.unit_price = normalized; // numeric string, can be long, integer only
            priceInput.value = formatRupiahInputValue(row.unit_price);
            toggleRowStaticSectionForRow(row.index);
            // Check total threshold for all items
            checkTotalThreshold();
            // update total display
            const q = parseInt(row.quantity || 0) || 0;
            const p = parseInt(row.unit_price || 0) || 0;
            if (totalInput) totalInput.value = formatRupiahInputValue(q * p);
            // Realtime sync price to form statis
            syncFormExtraFields(row.index, { force: true });
        };
        priceInput.addEventListener('input', handlePriceInput);
        priceInput.addEventListener('blur', handlePriceInput);
        // initialize total display once
        (function(){ const q = parseInt(row.quantity || 0) || 0; const p = parseInt(row.unit_price || 0) || 0; if (totalInput) totalInput.value = formatRupiahInputValue(q * p); })();
        if (fileBtn && fileInput) {
            fileBtn.addEventListener('click', function() {
                fileInput.click();
            });
            fileInput.addEventListener('change', function() {
                const n = fileInput.files?.length || 0;
                if (fileCount) {
                    fileCount.innerHTML = n ? '<i class="fas fa-check text-green-600"></i>' : '';
                    if (n) fileCount.setAttribute('title', `${n} file`); else fileCount.removeAttribute('title');
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
        
        // Allocation department input handling
        if (deptInput) {
            deptInput.addEventListener('input', function() {
                const val = this.value.trim();
                row.allocation_department_name = val;
                row.allocation_department_id = '';
                
                if (val.length < 1) {
                    deptSugBox.classList.add('hidden');
                    return;
                }
                
                // Filter departments based on input
                const filtered = (allDepartments || []).filter(d => 
                    (d.name || '').toLowerCase().includes(val.toLowerCase())
                );
                
                renderDepartmentSuggestions(deptSugBox, filtered, row.index);
            });
            
            deptInput.addEventListener('blur', function() {
                setTimeout(() => deptSugBox.classList.add('hidden'), 200);
            });
            
            deptInput.addEventListener('change', function() {
                // Try to match exact department name
                const val = this.value.trim();
                if (val) {
                    const exact = (allDepartments || []).find(d => 
                        (d.name || '').toLowerCase() === val.toLowerCase()
                    );
                    if (exact) {
                        row.allocation_department_id = exact.id;
                        row.allocation_department_name = exact.name;
                    }
                }
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

    function renderSuggestions(container, items, row, nameInput, priceInput, categoryInput) {
        if (!items.length) {
            // Auto hide if no results
            container.classList.add('hidden');
            container.innerHTML = '';
            return;
        }
        container.innerHTML = items.map(it => `
        <div class="px-3 py-2 hover:bg-gray-50 cursor-pointer" onclick='selectSuggestion(${JSON.stringify(it).replace(/'/g, "&#39;")}, ${row.index})'>
            <div class="flex justify-between">
                <span>${escapeHtml(it.name)} <span class="text-xs text-gray-500">(${escapeHtml(it.code)})</span></span>
                <span class="text-xs text-green-600">Rp ${parseFloat(it.total_price||0).toLocaleString('id-ID')}${it.unit? ' / '+escapeHtml(it.unit.name):''}</span>
            </div>
            <div class="text-xs text-gray-500">${it.category? 'Kategori: '+escapeHtml(it.category.name):''}</div>
        </div>
    `).join('');
        positionDropdown(nameInput, container);
        container.classList.remove('hidden');
    }

    function renderCategorySuggestions(container, categories, rowIndex) {
        const tr = document.getElementById('row-' + rowIndex);
        const input = tr.querySelector('.item-category');
        if (!categories.length) {
            container.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500">Tidak ada hasil. Ketik untuk membuat kategori baru.</div>';
            positionDropdown(input, container);
            container.classList.remove('hidden');
            return;
        }
        container.innerHTML = categories.map(c => `
            <div class="px-3 py-2 hover:bg-gray-50 cursor-pointer" onclick='selectCategorySuggestion(${JSON.stringify(c).replace(/'/g, "&#39;")}, ${rowIndex})'>
                <div class="flex justify-between">
                    <span>${escapeHtml(c.name)}</span>
                </div>
            </div>
        `).join('');
        positionDropdown(input, container);
        container.classList.remove('hidden');
    }

    function renderSupplierSuggestions(container, suppliers, rowIndex) {
        const tr = document.getElementById('row-' + rowIndex);
        const input = tr.querySelector('.alt-vendor');
        if (!suppliers.length) {
            // Auto hide if no results
            container.classList.add('hidden');
            container.innerHTML = '';
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
        positionDropdown(input, container);
        container.classList.remove('hidden');
    }
    
    function renderDepartmentSuggestions(container, departments, rowIndex) {
        const tr = document.getElementById('row-' + rowIndex);
        const input = tr.querySelector('.allocation-dept');
        if (!departments.length) {
            container.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500">Tidak ada hasil.</div>';
            positionDropdown(input, container);
            container.classList.remove('hidden');
            return;
        }
        container.innerHTML = departments.map(d => `
            <div class="px-3 py-2 hover:bg-gray-50 cursor-pointer" onclick='selectDepartmentSuggestion(${JSON.stringify(d).replace(/'/g, "&#39;")}, ${rowIndex})'>
                <div class="flex justify-between">
                    <span>${escapeHtml(d.name)}</span>
                </div>
            </div>
        `).join('');
        positionDropdown(input, container);
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
        const categoryInput = tr.querySelector('.item-category');
        const sugBox = tr.querySelector('.suggestions');
        const row = rows.find(r => r.index === rowIndex);
        row.master_item_id = it.id;
        row.name = it.name;
        // Do not auto-fetch price from DB; keep price as user input (empty by default)
        row.unit_price = row.unit_price;
        if (it.category) {
            row.item_category_id = it.category.id;
            row.item_category_name = it.category.name;
            if (categoryInput) categoryInput.value = it.category.name;
        }
        nameInput.value = it.name;
        // Leave price input unchanged (empty unless user provided)
        sugBox.classList.add('hidden');
    }

    function selectCategorySuggestion(c, rowIndex) {
        const tr = document.getElementById('row-' + rowIndex);
        const input = tr.querySelector('.item-category');
        const sug = tr.querySelector('.category-suggestions');
        const row = rows.find(r => r.index === rowIndex);
        row.item_category_id = c.id;
        row.item_category_name = c.name;
        input.value = c.name;
        sug.classList.add('hidden');
    }
    
    function selectDepartmentSuggestion(d, rowIndex) {
        const tr = document.getElementById('row-' + rowIndex);
        const input = tr.querySelector('.allocation-dept');
        const sug = tr.querySelector('.dept-suggestions');
        const row = rows.find(r => r.index === rowIndex);
        row.allocation_department_id = d.id;
        row.allocation_department_name = d.name;
        input.value = d.name;
        sug.classList.add('hidden');
    }

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

    // Collect form extra data from visible form statis sections
    function collectFormExtraData() {
        rows.forEach((row) => {
            const trFs = document.getElementById(`row-${row.index}-static`);
            if (!trFs || trFs.classList.contains('hidden')) return;
            
            const formExtraData = {};
            
            // Helper to get value from selector
            const gv = (sel) => {
                const el = trFs.querySelector(sel);
                return el ? (el.value || '').trim() : '';
            };
            
            // Helper to get radio value
            const gvr = (name) => {
                const el = trFs.querySelector(`input[name="${name}"]:checked`);
                return el ? el.value : '';
            };
            
            // Helper to get checkbox value
            const gvc = (sel) => {
                const el = trFs.querySelector(sel);
                return el ? el.checked : false;
            };
            
            // Section A
            formExtraData.a_nama = gv('.fs-a_nama');
            formExtraData.a_fungsi = gv('.fs-a_fungsi');
            formExtraData.a_ukuran = gv('.fs-a_ukuran');
            formExtraData.a_jumlah = gv('.fs-a_jumlah');
            formExtraData.a_satuan = gv('.fs-a_satuan');
            formExtraData.a_waktu = gv('.fs-a_waktu');
            formExtraData.a_waktu_satuan = gv('.fs-a_waktu_satuan');
            formExtraData.a_pengguna = gv('.fs-a_pengguna');
            formExtraData.a_leadtime = gv('.fs-a_leadtime');
            formExtraData.a_ekatalog = gvr(`fs-a_ekatalog-${row.index}`);
            formExtraData.a_ekatalog_ket = gv('.fs-a_ekatalog_ket');
            formExtraData.a_harga = gv('.fs-a_harga');
            formExtraData.a_kategori_perm = gvr(`fs-a_kategori_perm-${row.index}`);
            formExtraData.a_lampiran = gvr(`fs-a_lampiran-${row.index}`);
            
            // Section B
            formExtraData.b_jml_pegawai = gv('.fs-b_jml_pegawai');
            formExtraData.b_jml_dokter = gv('.fs-b_jml_dokter');
            formExtraData.b_beban = gvr(`fs-b_beban-${row.index}`);
            formExtraData.b_barang_ada = gvr(`fs-b_barang_ada-${row.index}`);
            
            // Section C
            formExtraData.c_jumlah = gv('.fs-c_jumlah');
            formExtraData.c_satuan = gv('.fs-c_satuan');
            formExtraData.c_kondisi = gvr(`fs-c_kondisi-${row.index}`);
            formExtraData.c_kondisi_lain = gv('.fs-c_kondisi_lain');
            formExtraData.c_lokasi = gv('.fs-c_lokasi');
            formExtraData.c_sumber = gvr(`fs-c_sumber-${row.index}`);
            formExtraData.c_kemudahan = gvr(`fs-c_kemudahan-${row.index}`);
            formExtraData.c_produsen = gvr(`fs-c_produsen-${row.index}`);
            formExtraData.c_kriteria_dn = gvc('.fs-c_kriteria_dn');
            formExtraData.c_kriteria_impor = gvc('.fs-c_kriteria_impor');
            formExtraData.c_kriteria_kerajinan = gvc('.fs-c_kriteria_kerajinan');
            formExtraData.c_kriteria_jasa = gvc('.fs-c_kriteria_jasa');
            formExtraData.c_tkdn = gvr(`fs-c_tkdn-${row.index}`);
            formExtraData.c_tkdn_min = gv('.fs-c_tkdn_min');
            
            // Section D/E
            formExtraData.e_kirim = gv('.fs-e_kirim');
            formExtraData.e_angkut = gv('.fs-e_angkut');
            formExtraData.e_instalasi = gv('.fs-e_instalasi');
            formExtraData.e_penyimpanan = gv('.fs-e_penyimpanan');
            formExtraData.e_operasi = gvr(`fs-e_operasi-${row.index}`);
            formExtraData.e_catatan = gv('.fs-e_catatan');
            formExtraData.e_pelatihan = gvr(`fs-e_pelatihan-${row.index}`);
            formExtraData.e_aspek = gvr(`fs-e_aspek-${row.index}`);
            
            // Note: FS document file upload is handled separately via FormData
            
            row.formExtraData = formExtraData;
        });
    }
    
    // Disable auto-fill of form extra to avoid default values
    function autoFillFormExtra(rowIndex) {
        // Intentionally left blank: no default auto-filling
        return;
    }
    
    // Load item extra data into form statis (for edit mode)
    function loadItemExtraData(rowIndex, itemExtraData) {
        const trFs = document.getElementById(`row-${rowIndex}-static`);
        if (!trFs || !itemExtraData) return;
        
        // Helper to set value
        const sv = (selector, value) => {
            const el = trFs.querySelector(selector);
            if (el && value !== null && value !== undefined) {
                el.value = value;
            }
        };
        
        // Helper to set radio
        const sr = (name, value) => {
            const el = trFs.querySelector(`input[name="${name}"][value="${value}"]`);
            if (el) el.checked = true;
        };
        
        // Helper to set checkbox
        const sc = (selector, value) => {
            const el = trFs.querySelector(selector);
            if (el) el.checked = value === true || value === 1 || value === '1';
        };
        
        // Load Section A data
        sv('.fs-a_nama', itemExtraData.a_nama);
        sv('.fs-a_fungsi', itemExtraData.a_fungsi);
        sv('.fs-a_ukuran', itemExtraData.a_ukuran);
        sv('.fs-a_jumlah', itemExtraData.a_jumlah);
        sv('.fs-a_satuan', itemExtraData.a_satuan);
        sv('.fs-a_waktu', itemExtraData.a_waktu);
        sv('.fs-a_waktu_satuan', itemExtraData.a_waktu_satuan);
        sv('.fs-a_pengguna', itemExtraData.a_pengguna);
        sv('.fs-a_leadtime', itemExtraData.a_leadtime);
        sr(`fs-a_ekatalog-${rowIndex}`, itemExtraData.a_ekatalog);
        sv('.fs-a_ekatalog_ket', itemExtraData.a_ekatalog_ket);
        sv('.fs-a_harga', itemExtraData.a_harga);
        sr(`fs-a_kategori_perm-${rowIndex}`, itemExtraData.a_kategori_perm);
        sr(`fs-a_lampiran-${rowIndex}`, itemExtraData.a_lampiran);
        
        // Load Section B data
        sv('.fs-b_jml_pegawai', itemExtraData.b_jml_pegawai);
        sv('.fs-b_jml_dokter', itemExtraData.b_jml_dokter);
        sr(`fs-b_beban-${rowIndex}`, itemExtraData.b_beban);
        sr(`fs-b_barang_ada-${rowIndex}`, itemExtraData.b_barang_ada);
        
        // Load Section C data
        sv('.fs-c_jumlah', itemExtraData.c_jumlah);
        sv('.fs-c_satuan', itemExtraData.c_satuan);
        sr(`fs-c_kondisi-${rowIndex}`, itemExtraData.c_kondisi);
        sv('.fs-c_kondisi_lain', itemExtraData.c_kondisi_lain);
        sv('.fs-c_lokasi', itemExtraData.c_lokasi);
        sr(`fs-c_sumber-${rowIndex}`, itemExtraData.c_sumber);
        sr(`fs-c_kemudahan-${rowIndex}`, itemExtraData.c_kemudahan);
        sr(`fs-c_produsen-${rowIndex}`, itemExtraData.c_produsen);
        sc('.fs-c_kriteria_dn', itemExtraData.c_kriteria_dn);
        sc('.fs-c_kriteria_impor', itemExtraData.c_kriteria_impor);
        sc('.fs-c_kriteria_kerajinan', itemExtraData.c_kriteria_kerajinan);
        sc('.fs-c_kriteria_jasa', itemExtraData.c_kriteria_jasa);
        sr(`fs-c_tkdn-${rowIndex}`, itemExtraData.c_tkdn);
        sv('.fs-c_tkdn_min', itemExtraData.c_tkdn_min);
        
        // Load Section D/E data
        sv('.fs-e_kirim', itemExtraData.e_kirim);
        sv('.fs-e_angkut', itemExtraData.e_angkut);
        sv('.fs-e_instalasi', itemExtraData.e_instalasi);
        sv('.fs-e_penyimpanan', itemExtraData.e_penyimpanan);
        sr(`fs-e_operasi-${rowIndex}`, itemExtraData.e_operasi);
        sv('.fs-e_catatan', itemExtraData.e_catatan);
        sr(`fs-e_pelatihan-${rowIndex}`, itemExtraData.e_pelatihan);
        sr(`fs-e_aspek-${rowIndex}`, itemExtraData.e_aspek);
    }
    
    // expose functions to window
    window.addRow = addRow;
    window.selectDepartmentSuggestion = selectDepartmentSuggestion;
</script>
