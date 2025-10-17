<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Formulir Pertanyaan Identifikasi Kebutuhan</title>
    <style>
        /* Ukuran kertas & margin untuk cetak A4 portrait */
        @page {
            size: A4;
            margin: 15mm;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print {
                display: none !important;
            }

            a[href]:after {
                content: "" !important;
            }
        }

        :root {
            --text: #111827;
            /* gray-900 */
            --muted: #6b7280;
            /* gray-500 */
            --line: #d1d5db;
            /* gray-300 */
            --header: #f3f4f6;
            /* gray-100 */
            --accent: #10b981;
            /* emerald-500 */
            --accent-dark: #059669;
            /* emerald-600 */
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            height: 100%;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            color: var(--text);
            font-size: 12px;
            line-height: 1.35;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
        }

        .logo {
            height: 40px;
            object-fit: contain;
        }

        .title {
            font-size: 18px;
            font-weight: 700;
            margin: 8px 0 16px;
        }

        .meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .meta td {
            padding: 3px 6px;
            vertical-align: top;
        }

        .meta td.label {
            width: 120px;
            color: var(--muted);
        }

        .meta td.sep {
            width: 10px;
            color: var(--muted);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            border: 1px solid var(--line);
            padding: 6px;
            vertical-align: top;
        }

        .table th {
            background: #e5e7eb;
            text-align: left;
            font-weight: 700;
        }

        .section-head {
            background: var(--header);
            font-weight: 700;
            padding: 6px;
            border: 1px solid var(--line);
        }

        .small {
            font-size: 11px;
            color: var(--muted);
        }

        .num {
            width: 22px;
            text-align: center;
        }

        .question {
            width: 45%;
        }

        .answer {
            width: 55%;
        }

        .box {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 1px solid var(--line);
            vertical-align: middle;
            margin: 0 6px;
        }

        .box.checked {
            background: #fff;
            position: relative;
        }

        .box.checked:after {
            content: "✓";
            position: absolute;
            inset: -1px 0 0 1px;
            font-size: 12px;
            color: var(--accent-dark);
        }

        .input-line {
            display: inline-block;
            min-width: 120px;
            border-bottom: 1px dotted var(--line);
            padding-bottom: 2px;
        }

        .input-lg {
            min-width: 220px;
        }

        .footer-note {
            margin-top: 8px;
            color: var(--muted);
            font-size: 10px;
        }

        .sig-row {
            margin-top: 24px;
            display: flex;
            justify-content: space-between;
        }

        .sig {
            width: 50%;
        }

        .sig .line {
            height: 60px;
        }

        .sig .name {
            border-top: 1px solid var(--line);
            width: 220px;
            text-align: center;
            padding-top: 4px;
            margin-top: 4px;
        }

        .toolbar {
            position: sticky;
            top: 0;
            background: #fff;
            padding: 8px 0;
            display: flex;
            gap: 8px;
        }

        .btn {
            border: 1px solid var(--line);
            background: #fff;
            padding: 6px 10px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
        }

        .btn.primary {
            background: var(--accent);
            color: #fff;
            border-color: var(--accent-dark);
        }
    </style>
</head>

<body>
    <div class="page">
        <div class="toolbar no-print">
            <button class="btn primary" onclick="window.print()">Cetak</button>
        </div>

        <div class="header">
            <div>
                <div class="title">Formulir Pertanyaan Identifikasi Kebutuhan</div>
                <table class="meta">
                    <tr>
                        <td class="label">Rumah Sakit</td>
                        <td class="sep">:</td>
                        <td>Alia Hospital Jakarta Timur</td>
                    </tr>
                    <tr>
                        <td class="label">Departemen/Unit</td>
                        <td class="sep">:</td>
                        <td>General Affair / Kesehatan Lingkungan</td>
                    </tr>
                    <tr>
                        <td class="label">No Form Identifikasi</td>
                        <td class="sep">:</td>
                        <td>006/Form-Ident/KL/AHJT/III/2025</td>
                    </tr>
                    <tr>
                        <td class="label">Manager peminta</td>
                        <td class="sep">:</td>
                        <td>Rachmadi Fauzia Biba (Spv GA)</td>
                    </tr>
                    <tr>
                        <td class="label">Jenis kebutuhan</td>
                        <td class="sep">:</td>
                        <td>
                            Barang <span class="box checked"></span>
                            Jasa <span class="box"></span>
                        </td>
                    </tr>
                    <tr>
                        <td class="label">Kategori</td>
                        <td class="sep">:</td>
                        <td>
                            Capex <span class="box"></span>
                            Opex <span class="box"></span>
                            Inventaris <span class="box checked"></span>
                        </td>
                    </tr>
                    <tr>
                        <td class="label">Output</td>
                        <td class="sep">:</td>
                        <td>
                            Terpenuhi Nilai Ketaatan Pengelolaan Sampah (berdasar tempat sampah yang memenuhi standar,
                            warna dan ukuran).<br>
                            Dinas Lingkungan Hidup sesuai PerGub DKI Jakarta Nomor 102 Tahun 2021
                        </td>
                    </tr>
                </table>
            </div>
            <div>
                <img class="logo" src="{{ asset('images/logo_full.png') }}" alt="logo">
            </div>
        </div>

        <!-- Tabel utama -->
        <table class="table">
            <thead>
                <tr>
                    <th style="width:28px;">No</th>
                    <th>Pertanyaan</th>
                    <th style="width:55%;">Jawaban</th>
                </tr>
            </thead>
            <tbody>
                <!-- Section A -->
                <tr>
                    <td colspan="3" class="section-head">A &nbsp; Identifikasi kebutuhan Barang</td>
                </tr>
                <tr>
                    <td class="num">1</td>
                    <td class="question">Nama/jenis Barang</td>
                    <td class="answer">Tempat Sampah Terpilah 4R</td>
                </tr>
                <tr>
                    <td class="num">2</td>
                    <td>Fungsikegunaan</td>
                    <td>Penyediaan sampah wadah sesuai dengan regulasi</td>
                </tr>
                <tr>
                    <td class="num">3</td>
                    <td>Ukuran/kapasitas</td>
                    <td>sesuai dengan foto spesifikasi</td>
                </tr>
                <tr>
                    <td class="num">4</td>
                    <td>Jumlah Barang yang diperlukan</td>
                    <td>1 &nbsp; Unit / buah / box / Pcs / Lot</td>
                </tr>
                <tr>
                    <td class="num">5</td>
                    <td>Waktu pemanfaatan Barang</td>
                    <td>Setiap hari</td>
                </tr>
                <tr>
                    <td class="num">6</td>
                    <td>Pihak yang akan menggunakan/mengelola Barang</td>
                    <td>Staf Keling</td>
                </tr>
                <tr>
                    <td class="num">7</td>
                    <td>Tool perkiraan waktu pengadaan Barang (termaksud waktu pengiriman barang sampai tiba di lokasi)
                    </td>
                    <td>
                        Hari/minggu/bulan
                    </td>
                </tr>
                <tr>
                    <td class="num">8</td>
                    <td>Terdapat di e-katalog LKPP</td>
                    <td>
                        Ya <span class="box"></span>
                        Tidak <span class="box checked"></span>
                        <span class="input-line"></span>
                    </td>
                </tr>
                <tr>
                    <td class="num">9</td>
                    <td>Harga perkiraan kebutuhan Barang</td>
                    <td>Rp1.500.000/unit &nbsp; <span class="small">(contoh)</span></td>
                </tr>
                <tr>
                    <td class="num">10</td>
                    <td>Kategori Permintaan</td>
                    <td>
                        Investasi Baru <span class="box checked"></span>
                        Replacement <span class="box"></span>
                    </td>
                </tr>
                <tr>
                    <td class="num">11</td>
                    <td>Lampiran Analisa sesuai syarat kategori permintaan</td>
                    <td>
                        Ada <span class="box checked"></span>
                        Tidak ada <span class="box"></span>
                    </td>
                </tr>

                <!-- Section B -->
                <tr>
                    <td colspan="3" class="section-head">B &nbsp; Dalam Rangka menunjang Tugas dan fungsi Unit /
                        Departemen / Ruangan</td>
                </tr>
                <tr>
                    <td class="num">13</td>
                    <td>Jumlah pegawai dalam unit kerja</td>
                    <td>1 Orang</td>
                </tr>
                <tr>
                    <td class="num">14</td>
                    <td>Jumlah Dokter dalam unit kerja (pengguna barang)</td>
                    <td>0 Orang</td>
                </tr>
                <tr>
                    <td class="num">15</td>
                    <td>Tingkat beban tugas dan tanggungjawab pegawai dalam melaksanakan tugas dan fungsi unit kerja
                    </td>
                    <td>
                        Tinggi <span class="box checked"></span>
                        Sedang <span class="box"></span>
                        Rendah <span class="box"></span>
                    </td>
                </tr>
                <tr>
                    <td class="num">16</td>
                    <td>Jumlah barang yang telah tersedia/dimiliki/ dikuasai dapat memenuhi kebutuhan pada unit kerja
                    </td>
                    <td>
                        Ya <span class="box"></span>
                        Tidak <span class="box checked"></span>
                    </td>
                </tr>

                <!-- Section C -->
                <tr>
                    <td colspan="3" class="section-head">C &nbsp; Identifikasi barang yang telah tersedia / dimiliki
                        / dikuasai</td>
                </tr>
                <tr>
                    <td class="num">17</td>
                    <td>Jumlah barang sejenis yang telah tersedia/ dimiliki/ dikuasai</td>
                    <td>0 &nbsp; Unit / buah / box / Pcs</td>
                </tr>
                <tr>
                    <td class="num">18</td>
                    <td>Kondisi/kelayakan Barang</td>
                    <td>
                        Layak Pakai <span class="box"></span>
                        Rusak / dalam perbaikan <span class="box"></span>
                        Tidak dapat digunakan <span class="box"></span>
                        Lainnya (sebutkan) <span class="input-line input-lg"></span>
                    </td>
                </tr>
                <tr>
                    <td class="num">19</td>
                    <td>Lokasi/keberadaan Barang</td>
                    <td>Digunakan di lobby, poli magnolia</td>
                </tr>
                <tr>
                    <td class="num">20</td>
                    <td>Sumber dana pengadaan barang yang telah tersedia/ dimiliki/ dikuasai</td>
                    <td>
                        Milik RS <span class="box checked"></span>
                        KSO <span class="box"></span>
                        Donasi <span class="box"></span>
                    </td>
                </tr>

                <!-- Section D -->
                <tr>
                    <td colspan="3" class="section-head">D &nbsp; Kriteria Barang</td>
                </tr>
                <tr>
                    <td class="num">21</td>
                    <td>Kemudahan memperoleh barang di pasaran Indonesia sesuai jumlah yang dibutuhkan</td>
                    <td>
                        Ya <span class="box checked"></span>
                        Tidak <span class="box"></span>
                    </td>
                </tr>
                <tr>
                    <td class="num">22</td>
                    <td>Terdapat Produsen/ pelaku usaha yang mampu dan dinilai mampu dan kompeten</td>
                    <td>
                        Banyak <span class="box checked"></span>
                        Terbatas <span class="box"></span>
                    </td>
                </tr>
                <tr>
                    <td class="num">23</td>
                    <td>Klasifikasi Barang <span class="small">(dapat di centang lebih dari satu)</span></td>
                    <td>
                        Produk dalam negeri <span class="box"></span><br>
                        Barang Impor <span class="box"></span><br>
                        Produk tangan <span class="box"></span><br>
                        Produk kerajinan tangan <span class="box"></span><br>
                        Jasa <span class="box"></span>
                    </td>
                </tr>
                <tr>
                    <td class="num">24</td>
                    <td>Persyaratan Barang memiliki nilai TKDN tertentu</td>
                    <td>
                        Ya <span class="box"></span>
                        Tidak <span class="box checked"></span>
                        <div style="margin-top:6px">Paling sedikit TKDN: .......... %</div>
                    </td>
                </tr>

                <!-- Section E -->
                <tr>
                    <td colspan="3" class="section-head">E &nbsp; Persyaratan Lain yang diperlukan</td>
                </tr>
                <tr>
                    <td class="num">25</td>
                    <td>Cara pengiriman</td>
                    <td><span class="input-line input-lg"></span></td>
                </tr>
                <tr>
                    <td class="num">26</td>
                    <td>Cara pengangkutan</td>
                    <td><span class="input-line input-lg"></span></td>
                </tr>
                <tr>
                    <td class="num">27</td>
                    <td>Cara pekerjaan / Instalasi / pemasangan</td>
                    <td><span class="input-line input-lg"></span></td>
                </tr>
                <tr>
                    <td class="num">28</td>
                    <td>Cara Penyimpanan / penimbunan</td>
                    <td>Digunakan di lobby, poli magnolia</td>
                </tr>
                <tr>
                    <td class="num">29</td>
                    <td>Cara Pengoperasian / penggunaan</td>
                    <td>
                        Otomatis <span class="box"></span>
                        Manual <span class="box checked"></span>
                    </td>
                </tr>
                <tr>
                    <td class="num">30</td>
                    <td>Catatan Pengoperasian / penggunaan</td>
                    <td><span class="input-line input-lg"></span></td>
                </tr>
                <tr>
                    <td class="num">31</td>
                    <td>Kebutuhan Pelatihan untuk pengoperasian / pemeliharaan barang</td>
                    <td>
                        Ya <span class="box"></span>
                        Tidak <span class="box checked"></span>
                    </td>
                </tr>
                <tr>
                    <td class="num">32</td>
                    <td>Aspek pengadaan bekelan/layanan</td>
                    <td>
                        Ya <span class="box"></span>
                        Tidak <span class="box checked"></span>
                    </td>
                </tr>

                <!-- Section F -->
                <tr>
                    <td colspan="3" class="section-head">F &nbsp; Konsolidasi pengadaan Barang</td>
                </tr>
                <tr>
                    <td class="num">33</td>
                    <td>Terdapat pengadaan barang sejenis pada kegiatan lain / Dept / Unit lain</td>
                    <td>
                        Ada <span class="box"></span>
                        Tidak ada <span class="box checked"></span>
                    </td>
                </tr>
                <tr>
                    <td class="num">34</td>
                    <td>Indikasi konsolidasi atas pengadaan barang</td>
                    <td>
                        Direkomendasikan <span class="box"></span>
                        Tidak direkomendasikan <span class="box checked"></span>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="sig-row">
            <div class="sig">
                <div>Jakarta, 10 Maret 2025</div>
                <div>Peminta,</div>
                <div class="line"></div>
                <div class="name">(Ria Shania)</div>
            </div>
            <div class="sig"></div>
        </div>

        <div class="footer-note">Form statis untuk keperluan cetak. Ubah nilai default sesuai kebutuhan.</div>
    </div>
</body>

</html>
