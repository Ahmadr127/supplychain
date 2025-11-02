{{-- Form Statis HTML Template for Per-Item --}}
<script type="text/html" id="form-statis-template">
    <!-- Collapsible Form Extra Header -->
    <div class="mb-2">
        <button type="button" class="form-extra-toggle w-full flex items-center justify-between p-2 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors duration-200" data-row-index="__ROW_INDEX__">
            <div class="flex items-center">
                <i class="fas fa-chevron-down form-extra-icon mr-2 text-gray-600 transition-transform duration-200 rotate-180"></i>
                <span class="text-sm font-medium text-gray-700">Form Kelangkapan Pengadaan</span>
            </div>
            <span class="text-xs text-gray-500">Klik untuk membuka/tutup</span>
        </button>
    </div>
    
    <!-- Collapsible Content (Default Open) -->
    <div class="form-extra-content">
        <!-- FS Document Upload Section - Simplified and moved to top -->
        <div class="fs-upload-section mb-3 p-2 bg-blue-50 border border-blue-200 rounded-md">
            <div class="flex items-center justify-between">
                <div class="text-xs font-medium text-gray-700">
                    <i class="fas fa-file-alt mr-1 text-blue-600"></i>
                    Dokumen Feasibility Study (FS)
                </div>
                <input type="file" 
                    name="items[__ROW_INDEX__][fs_document]" 
                    class="fs-document-input text-xs file:mr-2 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:font-medium file:bg-blue-600 file:text-white hover:file:bg-blue-700"
                    accept=".pdf,.doc,.docx"
                    data-row-index="__ROW_INDEX__">
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
        <div class="space-y-1">
            <div class="text-xs font-medium text-gray-700">A. Identifikasi Kebutuhan Barang</div>
            <label class="block text-xs text-gray-600">1. Nama/Jenis Barang</label>
            <input type="text" class="fs-a_nama w-full h-7 px-1 border border-gray-300 rounded-md text-xs" data-row-index="__ROW_INDEX__" />
            <label class="block text-xs text-gray-600">2. Fungsikegunaan</label>
            <input type="text" class="fs-a_fungsi w-full h-7 px-1 border border-gray-300 rounded-md text-xs" data-row-index="__ROW_INDEX__" />
            <label class="block text-xs text-gray-600">3. Ukuran/Kapasitas</label>
            <input type="text" class="fs-a_ukuran w-full h-7 px-1 border border-gray-300 rounded-md text-xs" data-row-index="__ROW_INDEX__" />
            <label class="block text-xs text-gray-600">4. Jumlah Barang</label>
            <div class="grid grid-cols-2 gap-1">
                <input type="number" class="fs-a_jumlah h-7 px-1 border border-gray-300 rounded-md text-xs" data-row-index="__ROW_INDEX__" />
                <select class="fs-a_satuan h-7 px-1 border border-gray-300 rounded-md text-xs" data-row-index="__ROW_INDEX__">
                    <option value="">-- Pilih --</option>
                    <option value="Unit">Unit</option>
                    <option value="Buah">Buah</option>
                    <option value="Box">Box</option>
                    <option value="Pcs">Pcs</option>
                    <option value="Lot">Lot</option>
                </select>
            </div>
            <label class="block text-xs text-gray-600">5. Waktu Pemanfaatan</label>
            <div class="grid grid-cols-3 gap-1">
                <input type="text" class="fs-a_waktu col-span-2 h-7 px-1 border border-gray-300 rounded-md text-xs" placeholder="Jumlah waktu" data-row-index="__ROW_INDEX__" />
                <select class="fs-a_waktu_satuan h-7 px-1 border border-gray-300 rounded-md text-xs" data-row-index="__ROW_INDEX__">
                    <option value="">-- Pilih --</option>
                    <option value="hari">Hari</option>
                    <option value="minggu">Minggu</option>
                    <option value="bulan">Bulan</option>
                </select>
            </div>
            <label class="block text-xs text-gray-600">6. Pengguna/Pengelola</label>
            <input type="text" class="fs-a_pengguna w-full h-7 px-1 border border-gray-300 rounded-md text-xs" data-row-index="__ROW_INDEX__" />
            <label class="block text-xs text-gray-600">7. Perkiraan Waktu Pengadaan</label>
            <input type="text" class="fs-a_leadtime w-full h-7 px-1 border border-gray-300 rounded-md text-xs" placeholder="cth: 2 minggu" data-row-index="__ROW_INDEX__" />
            <label class="block text-xs text-gray-600">8. Ada di e-Katalog LKPP?</label>
            <div class="flex items-center gap-3">
                <label class="text-xs text-gray-700"><input type="radio" name="fs-a_ekatalog-__ROW_INDEX__" value="ya" class="mr-1" required> Ya</label>
                <label class="text-xs text-gray-700"><input type="radio" name="fs-a_ekatalog-__ROW_INDEX__" value="tidak" class="mr-1" required> Tidak</label>
                <input type="text" class="fs-a_ekatalog_ket h-7 px-1 border border-gray-300 rounded-md text-xs w-40" placeholder="Catatan" data-row-index="__ROW_INDEX__" />
            </div>
            <label class="block text-xs text-gray-600">9. Harga Perkiraan</label>
            <input type="text" class="fs-a_harga w-full h-7 px-1 border border-gray-300 rounded-md text-xs" data-row-index="__ROW_INDEX__" />
            <label class="block text-xs text-gray-600">10. Kategori Permintaan</label>
            <div class="flex items-center gap-3">
                <label class="text-xs text-gray-700"><input type="radio" name="fs-a_kategori_perm-__ROW_INDEX__" value="baru" class="mr-1" required> Investasi Baru</label>
                <label class="text-xs text-gray-700"><input type="radio" name="fs-a_kategori_perm-__ROW_INDEX__" value="replacement" class="mr-1" required> Replacement</label>
            </div>
            <label class="block text-xs text-gray-600">11. Lampiran Analisa</label>
            <div class="flex items-center gap-3">
                <label class="text-xs text-gray-700"><input type="radio" name="fs-a_lampiran-__ROW_INDEX__" value="ada" class="mr-1" required> Ada</label>
                <label class="text-xs text-gray-700"><input type="radio" name="fs-a_lampiran-__ROW_INDEX__" value="tidak" class="mr-1" required> Tidak ada</label>
            </div>
            <!-- B. Dalam rangka menunjang tugas & fungsi unit -->
            <div class="mt-2">
                <div class="text-xs font-medium text-gray-700">B. Dalam rangka menunjang tugas & fungsi Unit/Dept/Ruangan</div>
                <label class="block text-xs text-gray-600">13. Jumlah pegawai dalam unit kerja (pengguna barang)</label>
                <input type="number" min="0" class="fs-b_jml_pegawai w-full h-7 px-1 border border-gray-300 rounded-md text-xs" data-row-index="__ROW_INDEX__" />
                <label class="block text-xs text-gray-600 mt-1">14. Jumlah dokter dalam unit kerja</label>
                <input type="number" min="0" class="fs-b_jml_dokter w-full h-7 px-1 border border-gray-300 rounded-md text-xs" data-row-index="__ROW_INDEX__" />
                <label class="block text-xs text-gray-600 mt-1">15. Tingkat beban tugas</label>
                <div class="flex items-center gap-3">
                    <label class="text-xs text-gray-700"><input type="radio" name="fs-b_beban-__ROW_INDEX__" value="tinggi" class="mr-1" required> Tinggi</label>
                    <label class="text-xs text-gray-700"><input type="radio" name="fs-b_beban-__ROW_INDEX__" value="sedang" class="mr-1" required> Sedang</label>
                    <label class="text-xs text-gray-700"><input type="radio" name="fs-b_beban-__ROW_INDEX__" value="rendah" class="mr-1" required> Rendah</label>
                </div>
                <label class="block text-xs text-gray-600 mt-1">16. Barang sejenis sudah tersedia/dimiliki/dikuasai?</label>
                <div class="flex items-center gap-3">
                    <label class="text-xs text-gray-700"><input type="radio" name="fs-b_barang_ada-__ROW_INDEX__" value="ya" class="mr-1" required> Ya</label>
                    <label class="text-xs text-gray-700"><input type="radio" name="fs-b_barang_ada-__ROW_INDEX__" value="tidak" class="mr-1" required> Tidak</label>
                </div>
            </div>
        </div>
        <div class="space-y-1">
            <!-- Section C (17-19) moved to right column -->
            <div class="text-xs font-medium text-gray-700">C. Identifikasi barang yang telah tersedia/dimiliki/dikuasai</div>
            <label class="block text-xs text-gray-600">17. Jumlah barang sejenis yang telah tersedia</label>
            <div class="grid grid-cols-2 gap-1">
                <input type="number" class="fs-c_jumlah h-7 px-1 border border-gray-300 rounded-md text-xs" data-row-index="__ROW_INDEX__" />
                <select class="fs-c_satuan h-7 px-1 border border-gray-300 rounded-md text-xs" data-row-index="__ROW_INDEX__">
                    <option value="">-- Pilih --</option>
                    <option value="Unit">Unit</option>
                    <option value="Buah">Buah</option>
                    <option value="Box">Box</option>
                    <option value="Pcs">Pcs</option>
                </select>
            </div>
            <label class="block text-xs text-gray-600">18. Kondisi/Kelayakan Barang</label>
            <div class="flex flex-wrap items-center gap-2">
                <label class="text-xs text-gray-700"><input type="radio" name="fs-c_kondisi-__ROW_INDEX__" value="layak" class="mr-1" required> Layak Pakai</label>
                <label class="text-xs text-gray-700"><input type="radio" name="fs-c_kondisi-__ROW_INDEX__" value="rusak" class="mr-1" required> Rusak</label>
                <label class="text-xs text-gray-700"><input type="radio" name="fs-c_kondisi-__ROW_INDEX__" value="tdk_dapat_digunakan" class="mr-1" required> Tidak dapat digunakan</label>
                <label class="text-xs text-gray-700"><input type="radio" name="fs-c_kondisi-__ROW_INDEX__" value="lainnya" class="mr-1" required> Lainnya</label>
            </div>
            <input type="text" class="fs-c_kondisi_lain h-7 px-1 border border-gray-300 rounded-md text-xs w-full" placeholder="Sebutkan jika lainnya" data-row-index="__ROW_INDEX__" />
            <label class="block text-xs text-gray-600">19. Lokasi/Keberadaan Barang</label>
            <input type="text" class="fs-c_lokasi w-full h-7 px-1 border border-gray-300 rounded-md text-xs" data-row-index="__ROW_INDEX__" />
            <label class="block text-xs text-gray-600">20. Sumber/Asal barang yang telah tersedia</label>
            <div class="flex items-center gap-3">
                <label class="text-xs text-gray-700"><input type="radio" name="fs-c_sumber-__ROW_INDEX__" value="milik_rs" class="mr-1" required> Milik RS</label>
                <label class="text-xs text-gray-700"><input type="radio" name="fs-c_sumber-__ROW_INDEX__" value="kso" class="mr-1" required> KSO</label>
                <label class="text-xs text-gray-700"><input type="radio" name="fs-c_sumber-__ROW_INDEX__" value="donasi" class="mr-1" required> Donasi</label>
            </div>
            <label class="block text-xs text-gray-600">21. Kemudahan diperoleh di pasar Indonesia</label>
            <div class="flex items-center gap-3">
                <label class="text-xs text-gray-700"><input type="radio" name="fs-c_kemudahan-__ROW_INDEX__" value="ya" class="mr-1" required> Ya</label>
                <label class="text-xs text-gray-700"><input type="radio" name="fs-c_kemudahan-__ROW_INDEX__" value="tidak" class="mr-1" required> Tidak</label>
            </div>
            <label class="block text-xs text-gray-600">22. Produsen/Pelaku usaha yang mampu</label>
            <div class="flex items-center gap-3">
                <label class="text-xs text-gray-700"><input type="radio" name="fs-c_produsen-__ROW_INDEX__" value="banyak" class="mr-1" required> Banyak</label>
                <label class="text-xs text-gray-700"><input type="radio" name="fs-c_produsen-__ROW_INDEX__" value="terbatas" class="mr-1" required> Terbatas</label>
            </div>
            <label class="block text-xs text-gray-600">23. Kriteria Barang (boleh lebih dari satu)</label>
            <div class="flex flex-wrap items-center gap-2">
                <label class="text-xs text-gray-700"><input type="checkbox" class="fs-c_kriteria_dn mr-1"> Produk dalam negeri</label>
                <label class="text-xs text-gray-700"><input type="checkbox" class="fs-c_kriteria_impor mr-1"> Barang impor</label>
                <label class="text-xs text-gray-700"><input type="checkbox" class="fs-c_kriteria_kerajinan mr-1"> Produk kerajinan tangan</label>
                <label class="text-xs text-gray-700"><input type="checkbox" class="fs-c_kriteria_jasa mr-1"> Jasa</label>
            </div>
            <label class="block text-xs text-gray-600">24. Persyaratan nilai TKDN tertentu</label>
            <div class="flex items-center gap-3">
                <label class="text-xs text-gray-700"><input type="radio" name="fs-c_tkdn-__ROW_INDEX__" value="ya" class="mr-1" required> Ya</label>
                <label class="text-xs text-gray-700"><input type="radio" name="fs-c_tkdn-__ROW_INDEX__" value="tidak" class="mr-1" required> Tidak</label>
                <span class="text-xs text-gray-600">Min TKDN</span>
                <input type="number" min="0" max="100" step="0.01" class="fs-c_tkdn_min h-7 px-1 border border-gray-300 rounded-md text-xs w-16" placeholder="%" data-row-index="__ROW_INDEX__" />
            </div>
            <!-- D/E Section (25-32) -->
            <div class="text-xs font-medium text-gray-700 mt-2">D/E. Persyaratan & Operasional</div>
            <label class="block text-xs text-gray-600">25. Cara Pengiriman</label>
            <input type="text" class="fs-e_kirim w-full h-7 px-1 border border-gray-300 rounded-md text-xs" data-row-index="__ROW_INDEX__" />
            <label class="block text-xs text-gray-600">26. Cara Pengangkutan</label>
            <input type="text" class="fs-e_angkut w-full h-7 px-1 border border-gray-300 rounded-md text-xs" data-row-index="__ROW_INDEX__" />
            <label class="block text-xs text-gray-600">27. Instalasi/Pemasangan</label>
            <input type="text" class="fs-e_instalasi w-full h-7 px-1 border border-gray-300 rounded-md text-xs" data-row-index="__ROW_INDEX__" />
            <label class="block text-xs text-gray-600">28. Penyimpanan/Penimbunan</label>
            <input type="text" class="fs-e_penyimpanan w-full h-7 px-1 border border-gray-300 rounded-md text-xs" data-row-index="__ROW_INDEX__" />
            <label class="block text-xs text-gray-600">29. Pengoperasian</label>
            <div class="flex items-center gap-3">
                <label class="text-xs text-gray-700"><input type="radio" name="fs-e_operasi-__ROW_INDEX__" value="otomatis" class="mr-1" required> Otomatis</label>
                <label class="text-xs text-gray-700"><input type="radio" name="fs-e_operasi-__ROW_INDEX__" value="manual" class="mr-1" required> Manual</label>
            </div>
            <label class="block text-xs text-gray-600">30. Catatan Pengoperasian</label>
            <textarea class="fs-e_catatan w-full h-12 px-1 py-1 border border-gray-300 rounded-md text-xs resize-y" data-row-index="__ROW_INDEX__"></textarea>
            <label class="block text-xs text-gray-600">31. Perlu Pelatihan?</label>
            <div class="flex items-center gap-3">
                <label class="text-xs text-gray-700"><input type="radio" name="fs-e_pelatihan-__ROW_INDEX__" value="ya" class="mr-1" required> Ya</label>
                <label class="text-xs text-gray-700"><input type="radio" name="fs-e_pelatihan-__ROW_INDEX__" value="tidak" class="mr-1" required> Tidak</label>
            </div>
            <label class="block text-xs text-gray-600">32. Aspek Bekalan/Layanan</label>
            <div class="flex items-center gap-3">
                <label class="text-xs text-gray-700"><input type="radio" name="fs-e_aspek-__ROW_INDEX__" value="ya" class="mr-1" required> Ya</label>
                <label class="text-xs text-gray-700"><input type="radio" name="fs-e_aspek-__ROW_INDEX__" value="tidak" class="mr-1" required> Tidak</label>
            </div>
        </div>
    </div>
    </div> <!-- End of form-extra-content -->
</script>

<script>
    // Function to get form statis HTML for a specific row
    function getFormStatisHTML(rowIndex) {
        const template = document.getElementById('form-statis-template').innerHTML;
        return template.replace(/__ROW_INDEX__/g, rowIndex);
    }
    
    // Function to toggle form extra visibility
    function toggleFormExtra(button) {
        const rowIndex = button.getAttribute('data-row-index');
        const container = button.closest('div').parentElement;
        const content = container.querySelector('.form-extra-content');
        const icon = button.querySelector('.form-extra-icon');
        
        if (content.classList.contains('hidden')) {
            content.classList.remove('hidden');
            icon.classList.add('rotate-180');
            // When showing, require radios only if they are enabled
            const radios = content.querySelectorAll('input[type="radio"]');
            radios.forEach(r => {
                if (!r.disabled) r.setAttribute('required', '');
            });
            // Do not force file input required here; it's handled by configureFormState based on thresholds
        } else {
            content.classList.add('hidden');
            icon.classList.remove('rotate-180');
            // When hiding, remove required to avoid focusable validation errors
            const radios = content.querySelectorAll('input[type="radio"]');
            radios.forEach(r => r.removeAttribute('required'));
            // Also ensure FS file input is not required when section is hidden
            const fileInput = content.querySelector('.fs-document-input');
            if (fileInput) {
                fileInput.required = false;
            }
        }
    }
    
    // Sync main form fields to Form Extra (auto-fill)
    // This function is called when main form values change
    function syncFormExtraFields(rowIndex, opts = { force: false }) {
        if (typeof rows === 'undefined') return;
        const row = rows.find(r => r.index === rowIndex);
        if (!row) return;
        
        const trFs = document.getElementById(`row-${rowIndex}-static`);
        if (!trFs) return;
        
        // Helper to set value only if empty (or force)
        const setIfEmpty = (selector, val) => {
            const el = trFs.querySelector(selector);
            if (!el) return;
            const current = (el.value ?? '').toString().trim();
            if (opts.force || current === '') {
                el.value = val != null ? String(val) : '';
            }
        };
        
        // Sync: a_nama from main item name
        setIfEmpty('.fs-a_nama', row.name || '');
        
        // Sync: a_jumlah from main quantity
        setIfEmpty('.fs-a_jumlah', (row.quantity != null && row.quantity !== '') ? row.quantity : '');
        
        // Sync: a_harga from TOTAL (quantity × unit_price)
        const qty = parseInt(row.quantity || 0) || 0;
        const price = parseInt(row.unit_price || 0) || 0;
        const total = qty * price;
        const harga = total > 0 ? (typeof formatRupiahInputValue === 'function' ? formatRupiahInputValue(total) : total) : '';
        setIfEmpty('.fs-a_harga', harga);
    }
    
    // Initialize form extra for a row (attach to DOM and bind events)
    function initFormExtra(rowIndex) {
        const trFs = document.getElementById(`row-${rowIndex}-static`);
        if (!trFs) return;
        
        // Add event listener for toggle button
        const toggleBtn = trFs.querySelector('.form-extra-toggle');
        if (toggleBtn && !toggleBtn.hasAttribute('data-listener-attached')) {
            toggleBtn.addEventListener('click', function() {
                toggleFormExtra(this);
            });
            toggleBtn.setAttribute('data-listener-attached', 'true');
        }
    }
    
    // Collect form extra data from visible form statis sections
    function collectFormExtraData() {
        if (typeof rows === 'undefined') return;
        
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
            
            row.formExtraData = formExtraData;
        });
    }
    
    // Helper: toggle required attribute for all radios inside a form-static container
    function setRadiosRequired(container, shouldRequire) {
        if (!container) return;
        const radios = container.querySelectorAll('input[type="radio"]');
        radios.forEach(r => {
            if (shouldRequire) r.setAttribute('required', '');
            else r.removeAttribute('required');
        });
    }
    
    // Configure form state based on threshold conditions and settings
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
                    fileInput.disabled = false;
                    fileInput.classList.remove('bg-gray-100', 'cursor-not-allowed', 'opacity-50');
                    // Make FS file input required when visible and no existing FS document
                    try {
                        const row = (typeof rows !== 'undefined' ? rows : []).find(r => r.index === rowIndex);
                        const hasExisting = !!(row && row.fs_document);
                        fileInput.required = !hasExisting;
                    } catch (_) { fileInput.required = true; }
                }
            } else {
                // Hide and clear value for safety so hidden files are not submitted
                uploadSection.classList.add('hidden');
                if (fileInput) {
                    try { fileInput.value = ''; } catch (_) {}
                    fileInput.required = false;
                }
            }
        }

        // Prefill visible form statis fields from main inputs
        if (!trFs.classList.contains('hidden')) {
            try { syncFormExtraFields(rowIndex); } catch(_) {}
        }
    }
    
    // Toggle form extra visibility based on subtotal threshold
    // Requires: rows array and fsSettings object from parent scope
    function toggleRowStaticSectionForRow(rowIndex) {
        if (typeof rows === 'undefined' || typeof fsSettings === 'undefined') return;
        
        const row = rows.find(r => r.index === rowIndex);
        if (!row) return;
        
        const subtotal = (parseInt(row.quantity || 0) || 0) * (parseInt(row.unit_price || 0) || 0);
        const trFs = document.getElementById(`row-${rowIndex}-static`);
        if (!trFs) return;
        
        // If FS is disabled globally, always hide the form
        if (!fsSettings.enabled) {
            trFs.classList.add('hidden');
            setRadiosRequired(trFs, false);
            const fileInput = trFs.querySelector('.fs-document-input');
            if (fileInput) fileInput.required = false;
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
            const fileInput = trFs.querySelector('.fs-document-input');
            if (fileInput) fileInput.required = false;
        }
    }
    
    // Build hidden inputs for form extra data and FS document
    function buildFormExtraHiddenInputs(form, row) {
        if (!form || !row) return;
        
        // Add form extra data if exists
        if (row.formExtraData) {
            Object.keys(row.formExtraData).forEach(key => {
                const value = row.formExtraData[key];
                if (value !== null && value !== undefined && value !== '') {
                    form.insertAdjacentHTML('beforeend',
                        `<input class="item-hidden" type="hidden" name="items[${row.index}][form_extra][${key}]" value="${typeof escapeHtml === 'function' ? escapeHtml(String(value)) : String(value)}">`
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
                    `<input class="item-hidden" type="hidden" name="items[${row.index}][existing_fs_document]" value="${typeof escapeHtml === 'function' ? escapeHtml(row.fs_document) : row.fs_document}">`
                );
            }
        } catch (_) { /* no-op */ }
    }
    
    // Serialize form extra data to hidden inputs for submission
    function serializeFormExtraToHiddenInputs(form, row) {
        // Add form extra data if exists
        if (row.formExtraData) {
            Object.keys(row.formExtraData).forEach(key => {
                const value = row.formExtraData[key];
                if (value !== null && value !== undefined && value !== '') {
                    form.insertAdjacentHTML('beforeend',
                        `<input class="item-hidden" type="hidden" name="items[${row.index}][form_extra][${key}]" value="${typeof escapeHtml === 'function' ? escapeHtml(String(value)) : String(value).replace(/"/g, '&quot;')}">`
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
                    `<input class="item-hidden" type="hidden" name="items[${row.index}][existing_fs_document]" value="${typeof escapeHtml === 'function' ? escapeHtml(row.fs_document) : row.fs_document.replace(/"/g, '&quot;')}">`
                );
            }
        } catch (_) { /* no-op */ }
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
</script>