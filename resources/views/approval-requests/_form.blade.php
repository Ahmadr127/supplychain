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
                    <i class="fas fa-plus mr-1"></i> Tambah Baris
                </button>
            </div>
            <!-- Scrollable table container with max width -->
            <div class="bg-white border border-gray-200 rounded-lg w-full max-w-full" style="max-width: calc(100vw - 2rem);">
                <div class="overflow-x-auto max-w-full" style="max-width: 100%;">
                    <div class="inline-block min-w-full align-middle">
                        <table class="text-sm border-collapse allow-horizontal-scroll" style="min-width: 1800px;">
                            <thead class="sticky top-0 z-10 bg-gray-50">
                                <tr class="text-left text-gray-600 align-middle select-none border-b border-gray-200">
                                    <th class="px-2 py-2 min-w-[18rem] text-xs font-medium tracking-wide bg-gray-50">Item</th>
                                    <th class="px-2 py-2 min-w-[4rem] text-xs font-medium tracking-wide whitespace-nowrap bg-gray-50">Jumlah</th>
                                    <th class="px-2 py-2 min-w-[7rem] text-xs font-medium tracking-wide whitespace-nowrap bg-gray-50">Harga</th>
                                    <th class="px-2 py-2 min-w-[10rem] text-xs font-medium tracking-wide whitespace-nowrap bg-gray-50">Kategori</th>
                                    <th class="px-2 py-2 min-w-[18rem] text-xs font-medium tracking-wide bg-gray-50">Spesifikasi</th>
                                    <th class="px-2 py-2 min-w-[10rem] text-xs font-medium tracking-wide bg-gray-50">Merk</th>
                                    <th class="px-2 py-2 min-w-[14rem] text-xs font-medium tracking-wide whitespace-nowrap bg-gray-50">Vendor Alternatif</th>
                                    <th class="px-2 py-2 min-w-[10rem] text-xs font-medium tracking-wide whitespace-nowrap bg-gray-50">No Surat</th>
                                    <th class="px-2 py-2 min-w-[12rem] text-xs font-medium tracking-wide whitespace-nowrap bg-gray-50">Unit Peruntukan</th>
                                    <th class="px-2 py-2 min-w-[16rem] text-xs font-medium tracking-wide bg-gray-50">Catatan</th>
                                    <th class="px-2 py-2 min-w-[7rem] text-xs font-medium tracking-wide bg-gray-50">Dokumen</th>
                                    <th class="px-2 py-2 min-w-[4rem] text-xs font-medium tracking-wide bg-gray-50">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="itemsTableBody" class="bg-white divide-y divide-gray-200">
                                <!-- rows injected by JS -->
                            </tbody>
                        </table>
                    </div>
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

<style>
    /* Prevent horizontal scroll on the page */
    #itemsSection {
        position: relative;
    }
    
    /* Table container with controlled overflow */
    #itemsSection > div:last-child {
        max-width: 100%;
        position: relative;
    }
    
    /* Scrollable area */
    #itemsSection .overflow-x-auto {
        max-width: 100%;
        overflow-x: auto;
        overflow-y: visible;
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

    // Position dropdown using fixed positioning
    function positionDropdown(input, dropdown) {
        const rect = input.getBoundingClientRect();
        dropdown.style.top = (rect.bottom + window.scrollY) + 'px';
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
                $approvalRequest->masterItems->map(function ($item) {
                    return [
                        'master_item_id' => $item->id,
                        'name' => $item->name,
                        'quantity' => $item->pivot->quantity,
                        'unit_price' => $item->pivot->unit_price,
                        'item_category_id' => $item->item_category_id,
                        'item_category_name' => optional($item->itemCategory)->name,
                        'specification' => $item->pivot->specification,
                        'brand' => $item->pivot->brand,
                        'alternative_vendor' => $item->pivot->alternative_vendor,
                        'allocation_department_id' => $item->pivot->allocation_department_id,
                        'allocation_department_name' => optional($item->pivot->allocationDepartment)->name,
                        'letter_number' => $item->pivot->letter_number,
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

            // Jika ada baris dengan subtotal ≥ 100jt dan section per-baris tampil, append baris statis untuk baris tsb (sekali per baris)
            try {
                appendStaticRowsFromActiveRows();
            } catch (err) {}

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

    function maybeToggleStaticSection() {
        const section = document.getElementById('staticFormSection');
        if (!section) return;
        const total = computeTotal();
        if (total >= 100000000) {
            section.classList.remove('hidden');
            staticSectionShown = true;
        } else {
            // Tetap dibiarkan statis jika sudah muncul sebelumnya (sesuai permintaan)
            // Jangan menyembunyikan kembali bila sudah muncul.
        }
    }

    function installTotalWatcher() {
        // Untuk kompatibilitas lama (global), tidak dipakai lagi.
        // Kita cukup memastikan setiap baris mengevaluasi dirinya saat dibuat.
        rows.forEach(r => toggleRowStaticSectionForRow(r.index));
    }

    // Toggle tampilkan Form Statis utk baris tertentu berdasarkan subtotal
    function toggleRowStaticSectionForRow(rowIndex) {
        const row = rows.find(r => r.index === rowIndex);
        if (!row) return;
        const subtotal = (parseInt(row.quantity || 0) || 0) * (parseInt(row.unit_price || 0) || 0);
        const trFs = document.getElementById(`row-${rowIndex}-static`);
        if (!trFs) return;
        if (subtotal >= 100000000) trFs.classList.remove('hidden'); else trFs.classList.add('hidden');
    }

    // Kumpulkan field dari setiap Form Statis per-baris yang aktif dan append item terkunci
    function appendStaticRowsFromActiveRows() {
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
                `Waktu Pemanfaatan: ${a_waktu}`,
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
            locked: defaults.locked || false
        };
        // Get department name if ID exists but name doesn't
        if (row.allocation_department_id && !row.allocation_department_name) {
            const dept = (allDepartments || []).find(d => String(d.id) === String(row.allocation_department_id));
            if (dept) row.allocation_department_name = dept.name;
        }
        rows.push(row);
        renderRow(row);
        // Re-evaluasi total setelah menambah baris
        maybeToggleStaticSection();
    }

    function removeRow(index) {
        const row = rows.find(r => r.index === index);
        if (row && row.locked) return; // cegah hapus untuk baris statis
        rows = rows.filter(r => r.index !== index);
        document.getElementById('row-' + index)?.remove();
    }

    function renderRow(row) {
        const tbody = document.getElementById('itemsTableBody');
        const tr = document.createElement('tr');
        tr.id = 'row-' + row.index;
        tr.className = 'hover:bg-gray-50';
        // Get department name if ID exists
        if (row.allocation_department_id && !row.allocation_department_name) {
            const dept = (allDepartments || []).find(d => String(d.id) === String(row.allocation_department_id));
            if (dept) row.allocation_department_name = dept.name;
        }
        tr.innerHTML = `
        <td class="px-1 py-1 align-top">
            <div class="relative">
                <input type="text" class="item-name w-full h-7 px-1 border border-gray-300 rounded-md text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="Cari atau ketik nama item" value="${escapeHtml(row.name)}" autocomplete="off" required>
                <div class="suggestions hidden"></div>
            </div>
        </td>
        <td class="px-1 py-1 align-top">
            <input type="number" min="1" class="item-qty w-full h-7 px-1 border border-gray-300 rounded-md text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-center" value="${row.quantity}">
        </td>
        <td class="px-1 py-1 align-top">
            <input type="text" inputmode="numeric" class="item-price w-full h-7 px-1 border border-gray-300 rounded-md text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-right" placeholder="0" value="${formatRupiahInputValue(row.unit_price)}">
        </td>
        <td class="px-1 py-1 align-top">
            <div class="relative">
                <input type="text" class="item-category w-full h-7 px-1 border border-gray-300 rounded-md text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="Kategori" value="${escapeHtml(row.item_category_name||'')}" autocomplete="off">
                <div class="category-suggestions hidden"></div>
            </div>
        </td>
        <td class="px-1 py-1 align-top">
            <textarea class="item-spec w-full h-12 px-1 py-1 border border-gray-300 rounded-md text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 resize-y" placeholder="Spesifikasi">${escapeHtml(row.specification)}</textarea>
        </td>
        <td class="px-1 py-1 align-top">
            <input type="text" class="item-brand w-full h-7 px-1 border border-gray-300 rounded-md text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="Merk" value="${escapeHtml(row.brand)}">
        </td>
        <td class="px-1 py-1 align-top">
            <div class="relative">
                <input type="text" class="alt-vendor w-full h-7 px-1 border border-gray-300 rounded-md text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="Vendor alternatif" value="${escapeHtml(row.alternative_vendor)}" autocomplete="off">
                <div class="supplier-suggestions hidden"></div>
            </div>
        </td>
        <td class="px-1 py-1 align-top">
            <input type="text" class="item-letter w-full h-7 px-1 border border-gray-300 rounded-md text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="No surat (opsional)" value="${escapeHtml(row.letter_number)}">
        </td>
        <td class="px-1 py-1 align-top">
            <div class="relative">
                <input type="text" class="allocation-dept w-full h-7 px-1 border border-gray-300 rounded-md text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="Unit peruntukan" value="${escapeHtml(row.allocation_department_name||'')}" autocomplete="off">
                <div class="dept-suggestions hidden"></div>
            </div>
        </td>
        <td class="px-1 py-1 align-top">
            <textarea class="item-notes w-full h-12 px-1 py-1 border border-gray-300 rounded-md text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 resize-y" placeholder="Catatan">${escapeHtml(row.notes)}</textarea>
        </td>
        <td class="px-1 py-1 align-top">
            <div class="flex items-center">
                <input type="file" name="items[${row.index}][files][]" class="item-files hidden" multiple accept=".pdf,.doc,.docx,.xls,.xlsx">
                <button type="button" class="item-files-btn h-7 w-7 inline-flex items-center justify-center text-gray-700 hover:text-blue-700 hover:bg-blue-50 border border-gray-300 rounded-md" title="Unggah dokumen">
                    <i class="fas fa-paperclip"></i>
                </button>
                <span class="item-files-count text-xs text-gray-600 ml-1"></span>
            </div>
        </td>
        <td class="px-1 py-1 align-top text-center">
            <button type="button" class="h-7 w-7 inline-flex items-center justify-center text-red-600 hover:text-red-800 hover:bg-red-50 rounded" onclick="removeRow(${row.index})" title="Hapus baris">
                <i class="fas fa-trash text-sm"></i>
            </button>
        </td>`;
        tbody.appendChild(tr);
        // Tambahkan TR kedua untuk Form Statis per-item (tersembunyi default)
        const trFs = document.createElement('tr');
        trFs.id = `row-${row.index}-static`;
        trFs.className = 'hidden';
        trFs.innerHTML = `
            <td class=\"px-1 pt-1 pb-2\" colspan=\"12\">                
                    <div class=\"grid grid-cols-1 md:grid-cols-2 gap-2\">
                        <div class="space-y-1">
                            <div class=\"text-xs font-medium text-gray-700\">A. Identifikasi Kebutuhan Barang</div>
                            <label class="block text-xs text-gray-600">1. Nama/Jenis Barang</label>
                            <input type="text" class="fs-a_nama w-full h-7 px-1 border border-gray-300 rounded-md text-xs" data-row-index="${row.index}" />
                            <label class="block text-xs text-gray-600">2. Fungsikegunaan</label>
                            <input type="text" class="fs-a_fungsi w-full h-7 px-1 border border-gray-300 rounded-md text-xs" data-row-index="${row.index}" />
                            <label class="block text-xs text-gray-600">3. Ukuran/Kapasitas</label>
                            <input type="text" class="fs-a_ukuran w-full h-7 px-1 border border-gray-300 rounded-md text-xs" data-row-index="${row.index}" />
                            <label class="block text-xs text-gray-600">4. Jumlah Barang</label>
                            <div class="grid grid-cols-2 gap-1">
                                <input type="number" class="fs-a_jumlah h-7 px-1 border border-gray-300 rounded-md text-xs" data-row-index="${row.index}" />
                                <select class="fs-a_satuan h-7 px-1 border border-gray-300 rounded-md text-xs" data-row-index="${row.index}">
                                    <option>Unit</option><option>Buah</option><option>Box</option><option>Pcs</option><option>Lot</option>
                                </select>
                            </div>
                            <label class="block text-xs text-gray-600">5. Waktu Pemanfaatan</label>
                            <input type="text" class="fs-a_waktu w-full h-7 px-1 border border-gray-300 rounded-md text-xs" data-row-index="${row.index}" />
                            <label class="block text-xs text-gray-600">6. Pengguna/Pengelola</label>
                            <input type="text" class="fs-a_pengguna w-full h-7 px-1 border border-gray-300 rounded-md text-xs" data-row-index="${row.index}" />
                            <label class="block text-xs text-gray-600">7. Perkiraan Waktu Pengadaan</label>
                            <input type="text" class="fs-a_leadtime w-full h-7 px-1 border border-gray-300 rounded-md text-xs" placeholder="cth: 2 minggu" data-row-index="${row.index}" />
                            <label class="block text-xs text-gray-600">8. Ada di e-Katalog LKPP?</label>
                            <div class="flex items-center gap-3">
                                <label class="text-xs text-gray-700"><input type="radio" name="fs-a_ekatalog-${row.index}" value="ya" class="mr-1"> Ya</label>
                                <label class="text-xs text-gray-700"><input type="radio" name="fs-a_ekatalog-${row.index}" value="tidak" class="mr-1" checked> Tidak</label>
                                <input type="text" class="fs-a_ekatalog_ket h-7 px-1 border border-gray-300 rounded-md text-xs w-40" placeholder="Catatan" data-row-index="${row.index}" />
                            </div>
                            <label class="block text-xs text-gray-600">9. Harga Perkiraan</label>
                            <input type="text" class="fs-a_harga w-full h-7 px-1 border border-gray-300 rounded-md text-xs" data-row-index="${row.index}" />
                            <label class="block text-xs text-gray-600">10. Kategori Permintaan</label>
                            <div class="flex items-center gap-3">
                                <label class="text-xs text-gray-700"><input type="radio" name="fs-a_kategori_perm-${row.index}" value="baru" class="mr-1" checked> Investasi Baru</label>
                                <label class="text-xs text-gray-700"><input type="radio" name="fs-a_kategori_perm-${row.index}" value="replacement" class="mr-1"> Replacement</label>
                            </div>
                            <label class="block text-xs text-gray-600">11. Lampiran Analisa</label>
                            <div class="flex items-center gap-3">
                                <label class="text-xs text-gray-700"><input type="radio" name="fs-a_lampiran-${row.index}" value="ada" class="mr-1" checked> Ada</label>
                                <label class="text-xs text-gray-700"><input type="radio" name="fs-a_lampiran-${row.index}" value="tidak" class="mr-1"> Tidak ada</label>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div class="text-xs font-medium text-gray-700">D/E. Persyaratan & Operasional</div>
                            <label class="block text-xs text-gray-600">25. Cara Pengiriman</label>
                            <input type="text" class="fs-e_kirim w-full h-7 px-1 border border-gray-300 rounded-md text-xs" data-row-index="${row.index}" />
                            <label class="block text-xs text-gray-600">26. Cara Pengangkutan</label>
                            <input type="text" class="fs-e_angkut w-full h-7 px-1 border border-gray-300 rounded-md text-xs" data-row-index="${row.index}" />
                            <label class="block text-xs text-gray-600">27. Instalasi/Pemasangan</label>
                            <input type="text" class="fs-e_instalasi w-full h-7 px-1 border border-gray-300 rounded-md text-xs" data-row-index="${row.index}" />
                            <label class="block text-xs text-gray-600">28. Penyimpanan/Penimbunan</label>
                            <input type="text" class="fs-e_penyimpanan w-full h-7 px-1 border border-gray-300 rounded-md text-xs" data-row-index="${row.index}" />
                            <label class="block text-xs text-gray-600">29. Pengoperasian</label>
                            <div class="flex items-center gap-3">
                                <label class="text-xs text-gray-700"><input type="radio" name="fs-e_operasi-${row.index}" value="otomatis" class="mr-1"> Otomatis</label>
                                <label class="text-xs text-gray-700"><input type="radio" name="fs-e_operasi-${row.index}" value="manual" class="mr-1" checked> Manual</label>
                            </div>
                            <label class="block text-xs text-gray-600">30. Catatan Pengoperasian</label>
                            <textarea class="fs-e_catatan w-full h-14 px-1 py-1 border border-gray-300 rounded-md text-xs resize-y" data-row-index="${row.index}"></textarea>
                            <label class="block text-xs text-gray-600">31. Perlu Pelatihan?</label>
                            <div class="flex items-center gap-3">
                                <label class="text-xs text-gray-700"><input type="radio" name="fs-e_pelatihan-${row.index}" value="ya" class="mr-1"> Ya</label>
                                <label class="text-xs text-gray-700"><input type="radio" name="fs-e_pelatihan-${row.index}" value="tidak" class="mr-1" checked> Tidak</label>
                            </div>
                            <label class="block text-xs text-gray-600">32. Aspek Bekalan/Layanan</label>
                            <div class="flex items-center gap-3">
                                <label class="text-xs text-gray-700"><input type="radio" name="fs-e_aspek-${row.index}" value="ya" class="mr-1"> Ya</label>
                                <label class="text-xs text-gray-700"><input type="radio" name="fs-e_aspek-${row.index}" value="tidak" class="mr-1" checked> Tidak</label>
                            </div>
                        </div>
                    </div>
            </td>`;
        tbody.appendChild(trFs);

        bindRowEvents(tr, row);
        // Kunci elemen jika baris locked (statis)
        if (row.locked) {
            tr.querySelectorAll('input, textarea, select, button').forEach(el => {
                if (el.type !== 'hidden') {
                    el.disabled = true;
                    el.classList.add('bg-gray-100', 'cursor-not-allowed');
                }
            });
            const delBtn = tr.querySelector('button[onclick^="removeRow("]');
            if (delBtn) delBtn.classList.add('hidden');
        }
    }

    function bindRowEvents(tr, row) {
        const nameInput = tr.querySelector('.item-name');
        const qtyInput = tr.querySelector('.item-qty');
        const priceInput = tr.querySelector('.item-price');
        const notesInput = tr.querySelector('.item-notes');
        const specInput = tr.querySelector('.item-spec');
        const brandInput = tr.querySelector('.item-brand');
        const altVendorInput = tr.querySelector('.alt-vendor');
        const fileInput = tr.querySelector('.item-files');
        const fileBtn = tr.querySelector('.item-files-btn');
        const fileCount = tr.querySelector('.item-files-count');
        const supplierSugBox = tr.querySelector('.supplier-suggestions');
        const sugBox = tr.querySelector('.suggestions');
        const categoryInput = tr.querySelector('.item-category');
        const categorySugBox = tr.querySelector('.category-suggestions');
        const deptInput = tr.querySelector('.allocation-dept');
        const deptSugBox = tr.querySelector('.dept-suggestions');
        const letterInput = tr.querySelector('.item-letter');

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
        });
        const handlePriceInput = function() {
            const v = (priceInput.value || '').trim();
            const normalized = parseRupiahToNumber(v);
            row.unit_price = normalized; // numeric string, can be long, integer only
            priceInput.value = formatRupiahInputValue(row.unit_price);
            toggleRowStaticSectionForRow(row.index);
        };
        priceInput.addEventListener('input', handlePriceInput);
        priceInput.addEventListener('blur', handlePriceInput);
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

    // expose functions to window
    window.addRow = addRow;
    window.selectDepartmentSuggestion = selectDepartmentSuggestion;
</script>
