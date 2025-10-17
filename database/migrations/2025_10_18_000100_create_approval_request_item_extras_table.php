<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('approval_request_item_extras')) {
            return;
        }
        
        Schema::create('approval_request_item_extras', function (Blueprint $table) {
            $table->id();
            
            // Foreign keys - relasi dengan approval_request_master_items pivot table
            $table->foreignId('approval_request_id')->constrained('approval_requests')->onDelete('cascade');
            $table->foreignId('master_item_id')->constrained('master_items')->onDelete('cascade');
            
            // Section A - Identifikasi Kebutuhan Barang
            $table->string('a_nama')->nullable()->comment('Nama/Jenis Barang');
            $table->string('a_fungsi')->nullable()->comment('Fungsikegunaan');
            $table->string('a_ukuran')->nullable()->comment('Ukuran/Kapasitas');
            $table->integer('a_jumlah')->nullable()->comment('Jumlah Barang');
            $table->string('a_satuan', 50)->nullable()->comment('Satuan');
            $table->string('a_waktu')->nullable()->comment('Waktu Pemanfaatan');
            $table->string('a_waktu_satuan', 20)->nullable()->comment('Satuan Waktu (hari/minggu/bulan)');
            $table->string('a_pengguna')->nullable()->comment('Pengguna/Pengelola');
            $table->string('a_leadtime')->nullable()->comment('Perkiraan Waktu Pengadaan');
            $table->enum('a_ekatalog', ['ya', 'tidak'])->default('tidak')->comment('Ada di e-Katalog LKPP');
            $table->string('a_ekatalog_ket')->nullable()->comment('Catatan e-Katalog');
            $table->string('a_harga')->nullable()->comment('Harga Perkiraan');
            $table->enum('a_kategori_perm', ['baru', 'replacement'])->default('baru')->comment('Kategori Permintaan');
            $table->enum('a_lampiran', ['ada', 'tidak'])->default('ada')->comment('Lampiran Analisa');
            
            // Section B - Dukungan Unit
            $table->integer('b_jml_pegawai')->nullable()->comment('Jumlah pegawai dalam unit kerja');
            $table->integer('b_jml_dokter')->nullable()->comment('Jumlah dokter dalam unit kerja');
            $table->enum('b_beban', ['tinggi', 'sedang', 'rendah'])->default('tinggi')->comment('Tingkat beban tugas');
            $table->enum('b_barang_ada', ['ya', 'tidak'])->default('tidak')->comment('Barang sejenis sudah tersedia');
            
            // Section C - Identifikasi Barang Eksisting
            $table->integer('c_jumlah')->nullable()->comment('Jumlah barang sejenis yang telah tersedia');
            $table->string('c_satuan', 50)->nullable()->comment('Satuan barang eksisting');
            $table->enum('c_kondisi', ['layak', 'rusak', 'tdk_dapat_digunakan', 'lainnya'])->default('layak')->comment('Kondisi/Kelayakan Barang');
            $table->string('c_kondisi_lain')->nullable()->comment('Kondisi lainnya jika dipilih');
            $table->string('c_lokasi')->nullable()->comment('Lokasi/Keberadaan Barang');
            $table->enum('c_sumber', ['milik_rs', 'kso', 'donasi'])->default('milik_rs')->comment('Sumber/Asal barang');
            $table->enum('c_kemudahan', ['ya', 'tidak'])->default('ya')->comment('Kemudahan diperoleh di pasar');
            $table->enum('c_produsen', ['banyak', 'terbatas'])->default('banyak')->comment('Produsen/Pelaku usaha yang mampu');
            $table->boolean('c_kriteria_dn')->default(false)->comment('Produk dalam negeri');
            $table->boolean('c_kriteria_impor')->default(false)->comment('Barang impor');
            $table->boolean('c_kriteria_kerajinan')->default(false)->comment('Produk kerajinan tangan');
            $table->boolean('c_kriteria_jasa')->default(false)->comment('Jasa');
            $table->enum('c_tkdn', ['ya', 'tidak'])->default('tidak')->comment('Persyaratan nilai TKDN tertentu');
            $table->decimal('c_tkdn_min', 5, 2)->nullable()->comment('Minimum TKDN (%)');
            
            // Section D/E - Persyaratan & Operasional
            $table->string('e_kirim')->nullable()->comment('Cara Pengiriman');
            $table->string('e_angkut')->nullable()->comment('Cara Pengangkutan');
            $table->string('e_instalasi')->nullable()->comment('Instalasi/Pemasangan');
            $table->string('e_penyimpanan')->nullable()->comment('Penyimpanan/Penimbunan');
            $table->enum('e_operasi', ['otomatis', 'manual'])->default('manual')->comment('Pengoperasian');
            $table->text('e_catatan')->nullable()->comment('Catatan Pengoperasian');
            $table->enum('e_pelatihan', ['ya', 'tidak'])->default('tidak')->comment('Perlu Pelatihan');
            $table->enum('e_aspek', ['ya', 'tidak'])->default('tidak')->comment('Aspek Bekalan/Layanan');

            $table->timestamps();

            // Unique constraint to prevent duplicate entries per item
            $table->unique(['approval_request_id', 'master_item_id'], 'unique_request_item');
        });
    }

    /**
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_request_item_extras');
    }
};
