{{-- Form Statis HTML Template for Per-Item --}}
<script type="text/html" id="form-statis-template">
    <!-- FS Document Upload Section - Simplified and moved to top -->
    <div class="mb-3 p-2 bg-blue-50 border border-blue-200 rounded-md">
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
                    <option>Unit</option><option>Buah</option><option>Box</option><option>Pcs</option><option>Lot</option>
                </select>
            </div>
            <label class="block text-xs text-gray-600">5. Waktu Pemanfaatan</label>
            <div class="grid grid-cols-3 gap-1">
                <input type="text" class="fs-a_waktu col-span-2 h-7 px-1 border border-gray-300 rounded-md text-xs" placeholder="Jumlah waktu" data-row-index="__ROW_INDEX__" />
                <select class="fs-a_waktu_satuan h-7 px-1 border border-gray-300 rounded-md text-xs" data-row-index="__ROW_INDEX__">
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
                <label class="text-xs text-gray-700"><input type="radio" name="fs-a_ekatalog-__ROW_INDEX__" value="ya" class="mr-1"> Ya</label>
                <label class="text-xs text-gray-700"><input type="radio" name="fs-a_ekatalog-__ROW_INDEX__" value="tidak" class="mr-1" checked> Tidak</label>
                <input type="text" class="fs-a_ekatalog_ket h-7 px-1 border border-gray-300 rounded-md text-xs w-40" placeholder="Catatan" data-row-index="__ROW_INDEX__" />
            </div>
            <label class="block text-xs text-gray-600">9. Harga Perkiraan</label>
            <input type="text" class="fs-a_harga w-full h-7 px-1 border border-gray-300 rounded-md text-xs" data-row-index="__ROW_INDEX__" />
            <label class="block text-xs text-gray-600">10. Kategori Permintaan</label>
            <div class="flex items-center gap-3">
                <label class="text-xs text-gray-700"><input type="radio" name="fs-a_kategori_perm-__ROW_INDEX__" value="baru" class="mr-1" checked> Investasi Baru</label>
                <label class="text-xs text-gray-700"><input type="radio" name="fs-a_kategori_perm-__ROW_INDEX__" value="replacement" class="mr-1"> Replacement</label>
            </div>
            <label class="block text-xs text-gray-600">11. Lampiran Analisa</label>
            <div class="flex items-center gap-3">
                <label class="text-xs text-gray-700"><input type="radio" name="fs-a_lampiran-__ROW_INDEX__" value="ada" class="mr-1" checked> Ada</label>
                <label class="text-xs text-gray-700"><input type="radio" name="fs-a_lampiran-__ROW_INDEX__" value="tidak" class="mr-1"> Tidak ada</label>
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
                    <label class="text-xs text-gray-700"><input type="radio" name="fs-b_beban-__ROW_INDEX__" value="tinggi" class="mr-1" checked> Tinggi</label>
                    <label class="text-xs text-gray-700"><input type="radio" name="fs-b_beban-__ROW_INDEX__" value="sedang" class="mr-1"> Sedang</label>
                    <label class="text-xs text-gray-700"><input type="radio" name="fs-b_beban-__ROW_INDEX__" value="rendah" class="mr-1"> Rendah</label>
                </div>
                <label class="block text-xs text-gray-600 mt-1">16. Barang sejenis sudah tersedia/dimiliki/dikuasai?</label>
                <div class="flex items-center gap-3">
                    <label class="text-xs text-gray-700"><input type="radio" name="fs-b_barang_ada-__ROW_INDEX__" value="ya" class="mr-1"> Ya</label>
                    <label class="text-xs text-gray-700"><input type="radio" name="fs-b_barang_ada-__ROW_INDEX__" value="tidak" class="mr-1" checked> Tidak</label>
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
                    <option>Unit</option><option>Buah</option><option>Box</option><option>Pcs</option>
                </select>
            </div>
            <label class="block text-xs text-gray-600">18. Kondisi/Kelayakan Barang</label>
            <div class="flex flex-wrap items-center gap-2">
                <label class="text-xs text-gray-700"><input type="radio" name="fs-c_kondisi-__ROW_INDEX__" value="layak" class="mr-1" checked> Layak Pakai</label>
                <label class="text-xs text-gray-700"><input type="radio" name="fs-c_kondisi-__ROW_INDEX__" value="rusak" class="mr-1"> Rusak</label>
                <label class="text-xs text-gray-700"><input type="radio" name="fs-c_kondisi-__ROW_INDEX__" value="tdk_dapat_digunakan" class="mr-1"> Tidak dapat digunakan</label>
                <label class="text-xs text-gray-700"><input type="radio" name="fs-c_kondisi-__ROW_INDEX__" value="lainnya" class="mr-1"> Lainnya</label>
            </div>
            <input type="text" class="fs-c_kondisi_lain h-7 px-1 border border-gray-300 rounded-md text-xs w-full" placeholder="Sebutkan jika lainnya" data-row-index="__ROW_INDEX__" />
            <label class="block text-xs text-gray-600">19. Lokasi/Keberadaan Barang</label>
            <input type="text" class="fs-c_lokasi w-full h-7 px-1 border border-gray-300 rounded-md text-xs" data-row-index="__ROW_INDEX__" />
            <label class="block text-xs text-gray-600">20. Sumber/Asal barang yang telah tersedia</label>
            <div class="flex items-center gap-3">
                <label class="text-xs text-gray-700"><input type="radio" name="fs-c_sumber-__ROW_INDEX__" value="milik_rs" class="mr-1" checked> Milik RS</label>
                <label class="text-xs text-gray-700"><input type="radio" name="fs-c_sumber-__ROW_INDEX__" value="kso" class="mr-1"> KSO</label>
                <label class="text-xs text-gray-700"><input type="radio" name="fs-c_sumber-__ROW_INDEX__" value="donasi" class="mr-1"> Donasi</label>
            </div>
            <label class="block text-xs text-gray-600">21. Kemudahan diperoleh di pasar Indonesia</label>
            <div class="flex items-center gap-3">
                <label class="text-xs text-gray-700"><input type="radio" name="fs-c_kemudahan-__ROW_INDEX__" value="ya" class="mr-1" checked> Ya</label>
                <label class="text-xs text-gray-700"><input type="radio" name="fs-c_kemudahan-__ROW_INDEX__" value="tidak" class="mr-1"> Tidak</label>
            </div>
            <label class="block text-xs text-gray-600">22. Produsen/Pelaku usaha yang mampu</label>
            <div class="flex items-center gap-3">
                <label class="text-xs text-gray-700"><input type="radio" name="fs-c_produsen-__ROW_INDEX__" value="banyak" class="mr-1" checked> Banyak</label>
                <label class="text-xs text-gray-700"><input type="radio" name="fs-c_produsen-__ROW_INDEX__" value="terbatas" class="mr-1"> Terbatas</label>
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
                <label class="text-xs text-gray-700"><input type="radio" name="fs-c_tkdn-__ROW_INDEX__" value="ya" class="mr-1"> Ya</label>
                <label class="text-xs text-gray-700"><input type="radio" name="fs-c_tkdn-__ROW_INDEX__" value="tidak" class="mr-1" checked> Tidak</label>
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
                <label class="text-xs text-gray-700"><input type="radio" name="fs-e_operasi-__ROW_INDEX__" value="otomatis" class="mr-1"> Otomatis</label>
                <label class="text-xs text-gray-700"><input type="radio" name="fs-e_operasi-__ROW_INDEX__" value="manual" class="mr-1" checked> Manual</label>
            </div>
            <label class="block text-xs text-gray-600">30. Catatan Pengoperasian</label>
            <textarea class="fs-e_catatan w-full h-12 px-1 py-1 border border-gray-300 rounded-md text-xs resize-y" data-row-index="__ROW_INDEX__"></textarea>
            <label class="block text-xs text-gray-600">31. Perlu Pelatihan?</label>
            <div class="flex items-center gap-3">
                <label class="text-xs text-gray-700"><input type="radio" name="fs-e_pelatihan-__ROW_INDEX__" value="ya" class="mr-1"> Ya</label>
                <label class="text-xs text-gray-700"><input type="radio" name="fs-e_pelatihan-__ROW_INDEX__" value="tidak" class="mr-1" checked> Tidak</label>
            </div>
            <label class="block text-xs text-gray-600">32. Aspek Bekalan/Layanan</label>
            <div class="flex items-center gap-3">
                <label class="text-xs text-gray-700"><input type="radio" name="fs-e_aspek-__ROW_INDEX__" value="ya" class="mr-1"> Ya</label>
                <label class="text-xs text-gray-700"><input type="radio" name="fs-e_aspek-__ROW_INDEX__" value="tidak" class="mr-1" checked> Tidak</label>
            </div>
        </div>
    </div>
</script>

<script>
    // Function to get form statis HTML for a specific row
    function getFormStatisHTML(rowIndex) {
        const template = document.getElementById('form-statis-template').innerHTML;
        return template.replace(/__ROW_INDEX__/g, rowIndex);
    }
</script>