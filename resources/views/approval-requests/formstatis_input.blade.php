<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Input Form - Identifikasi Kebutuhan</title>
  <style>
    :root{
      --line:#d1d5db; --muted:#6b7280; --header:#f3f4f6; --text:#111827; --accent:#059669;
    }
    *{box-sizing:border-box}
    html,body{font-family: Arial, Helvetica, sans-serif; color:var(--text); font-size:12px; line-height:1.35;}
    .wrap{max-width: 950px; margin:12px auto;}
    h1{font-size:18px; margin:0 0 8px;}
    .note{color:var(--muted); margin-bottom:10px}
    .section{margin:8px 0;}
    .section h2{background:var(--header); border:1px solid var(--line); padding:6px; font-size:12px; margin:0;}
    .grid{display:grid; grid-template-columns: 220px 1fr; gap:6px; align-items:center; border:1px solid var(--line); border-top:0; padding:8px;}
    .grid.compact{grid-template-columns: 28px 1fr 1fr;}
    label{color:#111;}
    .muted{color:var(--muted)}
    input[type="text"], input[type="number"], input[type="date"], textarea, select{
      width:100%; border:1px solid var(--line); border-radius:4px; padding:4px 6px; font-size:12px; height:26px; outline:none; background:#fff;
    }
    input[type="text"]:focus, input[type="number"]:focus, input[type="date"]:focus, textarea:focus, select:focus{
      border-color: var(--accent);
      box-shadow: 0 0 0 2px rgba(5,150,105,0.12) inset;
      outline: none;
    }
    textarea{height:auto; min-height:44px;}
    .inline{display:flex; gap:10px; align-items:center; flex-wrap:wrap}
    .check{display:inline-flex; gap:4px; align-items:center;}
    .table{width:100%; border-collapse: collapse;}
    .table th,.table td{border:1px solid var(--line); padding:6px;}
    .table th{background:var(--header); text-align:left}
    .actions{margin-top:10px; display:flex; gap:8px}
    button{border:1px solid var(--line); background:#fff; padding:6px 10px; border-radius:6px; cursor:pointer;}
    .btn{display:inline-block; border:1px solid var(--line); background:#fff; padding:6px 10px; border-radius:6px; color:var(--text); text-decoration:none;}
    .btn:hover{background:#f9fafb}
    .primary{background: var(--accent); color:#fff; border-color:#047857}
  </style>
</head>
<body>
  <div class="wrap">
    <h1>Form Input Identifikasi Kebutuhan</h1>
    <div class="note">Desain minimalis untuk input. Semua kolom disediakan, silakan sesuaikan default-nya.</div>

    <form method="GET" action="#">
      <!-- Header/Meta -->
      <div class="section">
        <h2>Informasi Umum</h2>
        <div class="grid">
          <label>Rumah Sakit</label>
          <input type="text" name="hospital" value="{{ old('hospital', 'Rumah sakit Azra') }}">
          <label>Departemen/Unit</label>
          <input type="text" name="department" value="{{ old('department', optional(auth()->user()->departments()->wherePivot('is_primary', true)->first())->name) }}">
          <label>No Form Identifikasi</label>
          <input type="text" name="form_no" value="">
          <label>Manager Peminta</label>
          <input type="text" name="request_manager" value="{{ old('request_manager', auth()->user()->name) }}">
          <label>Jenis Kebutuhan</label>
          <div class="inline">
            <label class="check"><input type="radio" name="jenis" value="barang" checked> Barang</label>
            <label class="check"><input type="radio" name="jenis" value="jasa"> Jasa</label>
          </div>
          <label>Kategori</label>
          <div class="inline">
            <label class="check"><input type="checkbox" name="kategori[]" value="capex"> Capex</label>
            <label class="check"><input type="checkbox" name="kategori[]" value="opex"> Opex</label>
            <label class="check"><input type="checkbox" name="kategori[]" value="inventaris" checked> Inventaris</label>
          </div>
          <label>Output</label>
          <textarea name="output"></textarea>
        </div>
      </div>

      <!-- Section A -->
      <div class="section">
        <h2>A Identifikasi kebutuhan Barang</h2>
        <div class="grid">
          <label>1. Nama/Jenis Barang</label>
          <input type="text" name="a_nama" value="">
          <label>2. Fungsikegunaan</label>
          <input type="text" name="a_fungsi" value="">
          <label>3. Ukuran/Kapasitas</label>
          <input type="text" name="a_ukuran" value="">
          <label>4. Jumlah Barang</label>
          <div class="inline">
            <input type="number" name="a_jumlah" value="" style="width:90px"> 
            <select name="a_satuan" style="width:120px">
              <option>Unit</option><option>Buah</option><option>Box</option><option>Pcs</option><option>Lot</option>
            </select>
          </div>
          <label>5. Waktu Pemanfaatan</label>
          <input type="text" name="a_waktu" value="">
          <label>6. Pengguna/Pengelola</label>
          <input type="text" name="a_pengguna" value="">
          <label>7. Perkiraan Waktu Pengadaan</label>
          <input type="text" name="a_leadtime" placeholder="cth: 2 minggu" value="">
          <label>8. Ada di e-Katalog LKPP?</label>
          <div class="inline">
            <label class="check"><input type="radio" name="a_ekatalog" value="ya"> Ya</label>
            <label class="check"><input type="radio" name="a_ekatalog" value="tidak" checked> Tidak</label>
            <input type="text" name="a_ekatalog_ket" placeholder="Catatan" style="width:200px" value="">
          </div>
          <label>9. Harga Perkiraan</label>
          <input type="text" name="a_harga" value="">
          <label>10. Kategori Permintaan</label>
          <div class="inline">
            <label class="check"><input type="radio" name="a_kategori_perm" value="baru" checked> Investasi Baru</label>
            <label class="check"><input type="radio" name="a_kategori_perm" value="replacement"> Replacement</label>
          </div>
          <label>11. Lampiran Analisa</label>
          <div class="inline">
            <label class="check"><input type="radio" name="a_lampiran" value="ada" checked> Ada</label>
            <label class="check"><input type="radio" name="a_lampiran" value="tidak"> Tidak ada</label>
          </div>
        </div>
      </div>

      <!-- Section B -->
      <div class="section">
        <h2>B Dalam Rangka menunjang Tugas dan fungsi Unit / Depatmenet / Ruangan</h2>
        <div class="grid">
          <label>13. Jumlah Pegawai</label>
          <input type="number" name="b_pegawai" value="">
          <label>14. Jumlah Dokter</label>
          <input type="number" name="b_dokter" value="">
          <label>15. Tingkat Beban Tugas</label>
          <div class="inline">
            <label class="check"><input type="radio" name="b_beban" value="tinggi" checked> Tinggi</label>
            <label class="check"><input type="radio" name="b_beban" value="sedang"> Sedang</label>
            <label class="check"><input type="radio" name="b_beban" value="rendah"> Rendah</label>
          </div>
          <label>16. Barang Tersedia Memenuhi?</label>
          <div class="inline">
            <label class="check"><input type="radio" name="b_memenuhi" value="ya"> Ya</label>
            <label class="check"><input type="radio" name="b_memenuhi" value="tidak" checked> Tidak</label>
          </div>
        </div>
      </div>

      <!-- Section C -->
      <div class="section">
        <h2>C Identifikasi barang yang telah tersedia / dimiliki / dikuasai</h2>
        <div class="grid">
          <label>17. Jumlah barang sejenis yang telah tersedia/ dimiliki/ dikuasai</label>
          <div class="inline">
            <input type="number" name="c_jumlah" value="" style="width:90px">
            <span class="muted">Unit / buah / box / Pcs</span>
            <select name="c_satuan" style="width:120px">
              <option>Unit</option><option>Buah</option><option>Box</option><option>Pcs</option>
            </select>
          </div>
          <label>18. Kondisi/kelayakan Barang</label>
          <div class="inline">
            <label class="check"><input type="checkbox" name="c_kondisi[]" value="layak"> Layak Pakai</label>
            <label class="check"><input type="checkbox" name="c_kondisi[]" value="rusak"> Rusak / dalam perbaikan</label>
            <label class="check"><input type="checkbox" name="c_kondisi[]" value="tdk_dapat"> Tidak dapat digunakan</label>
            <label class="check"><input type="checkbox" name="c_kondisi[]" value="lainnya"> Lainnya</label>
            <input type="text" name="c_kondisi_lain" placeholder="(sebutkan)" style="width:180px" value="">
          </div>
          <label>19. Lokasi/keberadaan Barang</label>
          <input type="text" name="c_lokasi" value="">
          <label>20. Sumber dana pengadaan barang yang telah tersedia/ dimiliki/ dikuasai</label>
          <div class="inline">
            <label class="check"><input type="checkbox" name="c_sumber[]" value="milik_rs" checked> Milik RS</label>
            <label class="check"><input type="checkbox" name="c_sumber[]" value="kso"> KSO</label>
            <label class="check"><input type="checkbox" name="c_sumber[]" value="donasi"> Donasi</label>
          </div>
          <label>21. Kemudahan memperoleh barang di pasaran Indonesia sesuai jumlah yang dibutuhkan</label>
          <div class="inline">
            <label class="check"><input type="radio" name="d_mudah" value="ya"> Ya</label>
            <label class="check"><input type="radio" name="d_mudah" value="tidak"> Tidak</label>
          </div>
          <label>22. Terdapat Produsen/ pelaku usaha yang dinilai mampu dan memenuhi syarat</label>
          <div class="inline">
            <label class="check"><input type="radio" name="d_produsen" value="banyak"> Banyak</label>
            <label class="check"><input type="radio" name="d_produsen" value="terbatas"> Terbatas</label>
          </div>
          <label>23. Kriteria Barang <span class="muted">(dapat di centang lebih dari satu)</span></label>
          <div class="inline">
            <label class="check"><input type="checkbox" name="d_klasifikasi[]" value="dalam_negeri"> Produk dalam negeri</label>
            <label class="check"><input type="checkbox" name="d_klasifikasi[]" value="impor"> Barang impor</label>
            <label class="check"><input type="checkbox" name="d_klasifikasi[]" value="pabrikan"> Pabrikan</label>
            <label class="check"><input type="checkbox" name="d_klasifikasi[]" value="produk_tangan_manual"> Produk tangan / Manual</label>
            <label class="check"><input type="checkbox" name="d_klasifikasi[]" value="kerajinan"> Produk kerajinan tangan</label>
            <label class="check"><input type="checkbox" name="d_klasifikasi[]" value="jasa"> Jasa</label>
          </div>
          <label>24. Persyaratan Barang memiliki nilai TKDN tertentu</label>
          <div class="inline">
            <label class="check"><input type="radio" name="d_tkdn_req" value="ya"> Ya</label>
            <label class="check"><input type="radio" name="d_tkdn_req" value="tidak" checked> Tidak</label>
            <span class="muted">Paling sedikit TKDN:</span>
            <input type="number" name="d_tkdn_min" placeholder="%" style="width:90px" value="">
          </div>
        </div>
      </div>
      <!-- Section D -->
      <div class="section">
        <h2>D Persyaratan Lain yang diperlukan</h2>
        <div class="grid">
          <label>25. Cara Pengiriman</label>
          <input type="text" name="e_kirim" value="">
          <label>26. Cara Pengangkutan</label>
          <input type="text" name="e_angkut" value="">
          <label>27. Instalasi/Pemasangan</label>
          <input type="text" name="e_instalasi" value="">
          <label>28. Penyimpanan/Penimbunan</label>
          <input type="text" name="e_penyimpanan" value="">
          <label>29. Pengoperasian</label>
          <div class="inline">
            <label class="check"><input type="radio" name="e_operasi" value="otomatis"> Otomatis</label>
            <label class="check"><input type="radio" name="e_operasi" value="manual" checked> Manual</label>
          </div>
          <label>30. Catatan Pengoperasian</label>
          <textarea name="e_catatan"></textarea>
          <label>31. Perlu Pelatihan?</label>
          <div class="inline">
            <label class="check"><input type="radio" name="e_pelatihan" value="ya"> Ya</label>
            <label class="check"><input type="radio" name="e_pelatihan" value="tidak" checked> Tidak</label>
          </div>
          <label>32. Aspek Bekalan/Layanan</label>
          <div class="inline">
            <label class="check"><input type="radio" name="e_aspek" value="ya"> Ya</label>
            <label class="check"><input type="radio" name="e_aspek" value="tidak" checked> Tidak</label>
          </div>
        </div>
      </div>

      <!-- Section E -->
      <div class="section">
        <h2>E Konsolidasi pengadaan Barang</h2>
        <div class="grid">
          <label>33. Ada pengadaan sejenis lain?</label>
          <div class="inline">
            <label class="check"><input type="radio" name="f_ada" value="ada"> Ada</label>
            <label class="check"><input type="radio" name="f_ada" value="tidak" checked> Tidak ada</label>
          </div>
          <label>34. Rekomendasi Konsolidasi</label>
          <div class="inline">
            <label class="check"><input type="radio" name="f_rekom" value="direkomendasikan"> Direkomendasikan</label>
            <label class="check"><input type="radio" name="f_rekom" value="tidak" checked> Tidak direkomendasikan</label>
          </div>
        </div>
      </div>

      <div class="actions">
        <button type="submit" class="primary">Preview</button>
        <button type="reset">Reset</button>
        <a href="{{ route('approval-requests.my-requests') }}" class="btn" style="text-decoration:none;">Kembali</a>
      </div>
    </form>
  </div>
</body>
</html>
