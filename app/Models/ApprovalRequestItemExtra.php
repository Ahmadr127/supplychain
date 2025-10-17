<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalRequestItemExtra extends Model
{
    use HasFactory;

    protected $table = 'approval_request_item_extras';

    protected $fillable = [
        'approval_request_id',
        'master_item_id',
        
        // Section A - Identifikasi Kebutuhan Barang
        'a_nama',
        'a_fungsi',
        'a_ukuran',
        'a_jumlah',
        'a_satuan',
        'a_waktu',
        'a_waktu_satuan',
        'a_pengguna',
        'a_leadtime',
        'a_ekatalog',
        'a_ekatalog_ket',
        'a_harga',
        'a_kategori_perm',
        'a_lampiran',
        
        // Section B - Dukungan Unit
        'b_jml_pegawai',
        'b_jml_dokter',
        'b_beban',
        'b_barang_ada',
        
        // Section C - Identifikasi Barang Eksisting
        'c_jumlah',
        'c_satuan',
        'c_kondisi',
        'c_kondisi_lain',
        'c_lokasi',
        'c_sumber',
        'c_kemudahan',
        'c_produsen',
        'c_kriteria_dn',
        'c_kriteria_impor',
        'c_kriteria_kerajinan',
        'c_kriteria_jasa',
        'c_tkdn',
        'c_tkdn_min',
        
        // Section D/E - Persyaratan & Operasional
        'e_kirim',
        'e_angkut',
        'e_instalasi',
        'e_penyimpanan',
        'e_operasi',
        'e_catatan',
        'e_pelatihan',
        'e_aspek',
    ];

    protected $casts = [
        'a_jumlah' => 'integer',
        'b_jml_pegawai' => 'integer',
        'b_jml_dokter' => 'integer',
        'c_jumlah' => 'integer',
        'c_kriteria_dn' => 'boolean',
        'c_kriteria_impor' => 'boolean',
        'c_kriteria_kerajinan' => 'boolean',
        'c_kriteria_jasa' => 'boolean',
        'c_tkdn_min' => 'decimal:2',
    ];

    /**
     * Get the approval request that owns this extra data.
     */
    public function approvalRequest()
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    /**
     * Get the master item that owns this extra data.
     */
    public function masterItem()
    {
        return $this->belongsTo(MasterItem::class);
    }

    /**
     * Get the pivot data from approval_request_master_items table
     */
    public function getRequestItemPivot()
    {
        return \DB::table('approval_request_master_items')
            ->where('approval_request_id', $this->approval_request_id)
            ->where('master_item_id', $this->master_item_id)
            ->first();
    }

    /**
     * Auto-fill from main form data
     */
    public function autoFillFromMainForm()
    {
        $pivot = $this->getRequestItemPivot();
        
        if ($pivot) {
            // Auto-fill nama from master item if not set
            if (empty($this->a_nama) && $this->masterItem) {
                $this->a_nama = $this->masterItem->name;
            }
            
            // Auto-fill jumlah from pivot quantity
            if (empty($this->a_jumlah) && $pivot->quantity) {
                $this->a_jumlah = $pivot->quantity;
            }
            
            // Auto-fill harga from pivot unit_price
            if (empty($this->a_harga) && $pivot->unit_price) {
                $this->a_harga = 'Rp ' . number_format($pivot->unit_price, 0, ',', '.');
            }
            
            // Auto-fill spesifikasi ke fungsi if available
            if (empty($this->a_fungsi) && $pivot->specification) {
                $this->a_fungsi = $pivot->specification;
            }
            
            // Auto-fill unit peruntukan from allocation_department
            if (empty($this->a_pengguna) && $pivot->allocation_department_id) {
                $dept = Department::find($pivot->allocation_department_id);
                if ($dept) {
                    $this->a_pengguna = $dept->name;
                }
            }
        }
    }

    /**
     * Get formatted display for Section A
     */
    public function getSectionADisplay()
    {
        return [
            'Nama/Jenis Barang' => $this->a_nama,
            'Fungsikegunaan' => $this->a_fungsi,
            'Ukuran/Kapasitas' => $this->a_ukuran,
            'Jumlah' => $this->a_jumlah . ' ' . $this->a_satuan,
            'Waktu Pemanfaatan' => $this->a_waktu . ' ' . $this->a_waktu_satuan,
            'Pengguna/Pengelola' => $this->a_pengguna,
            'Perkiraan Waktu Pengadaan' => $this->a_leadtime,
            'e-Katalog LKPP' => $this->a_ekatalog . ($this->a_ekatalog_ket ? ' (' . $this->a_ekatalog_ket . ')' : ''),
            'Harga Perkiraan' => $this->a_harga,
            'Kategori Permintaan' => $this->a_kategori_perm == 'baru' ? 'Investasi Baru' : 'Replacement',
            'Lampiran Analisa' => $this->a_lampiran == 'ada' ? 'Ada' : 'Tidak ada',
        ];
    }

    /**
     * Get formatted display for Section B
     */
    public function getSectionBDisplay()
    {
        return [
            'Jumlah pegawai dalam unit kerja' => $this->b_jml_pegawai,
            'Jumlah dokter dalam unit kerja' => $this->b_jml_dokter,
            'Tingkat beban tugas' => ucfirst($this->b_beban),
            'Barang sejenis sudah tersedia' => $this->b_barang_ada == 'ya' ? 'Ya' : 'Tidak',
        ];
    }

    /**
     * Get formatted display for Section C
     */
    public function getSectionCDisplay()
    {
        $kriteria = [];
        if ($this->c_kriteria_dn) $kriteria[] = 'Produk dalam negeri';
        if ($this->c_kriteria_impor) $kriteria[] = 'Barang impor';
        if ($this->c_kriteria_kerajinan) $kriteria[] = 'Produk kerajinan tangan';
        if ($this->c_kriteria_jasa) $kriteria[] = 'Jasa';
        
        return [
            'Jumlah barang sejenis yang telah tersedia' => $this->c_jumlah . ' ' . $this->c_satuan,
            'Kondisi/Kelayakan Barang' => $this->getKondisiLabel(),
            'Lokasi/Keberadaan Barang' => $this->c_lokasi,
            'Sumber/Asal barang' => $this->getSumberLabel(),
            'Kemudahan diperoleh di pasar Indonesia' => $this->c_kemudahan == 'ya' ? 'Ya' : 'Tidak',
            'Produsen/Pelaku usaha yang mampu' => ucfirst($this->c_produsen),
            'Kriteria Barang' => implode(', ', $kriteria) ?: '-',
            'Persyaratan nilai TKDN' => $this->c_tkdn == 'ya' ? 'Ya (Min ' . $this->c_tkdn_min . '%)' : 'Tidak',
        ];
    }

    /**
     * Get formatted display for Section D/E
     */
    public function getSectionDEDisplay()
    {
        return [
            'Cara Pengiriman' => $this->e_kirim,
            'Cara Pengangkutan' => $this->e_angkut,
            'Instalasi/Pemasangan' => $this->e_instalasi,
            'Penyimpanan/Penimbunan' => $this->e_penyimpanan,
            'Pengoperasian' => ucfirst($this->e_operasi),
            'Catatan Pengoperasian' => $this->e_catatan,
            'Perlu Pelatihan' => $this->e_pelatihan == 'ya' ? 'Ya' : 'Tidak',
            'Aspek Bekalan/Layanan' => $this->e_aspek == 'ya' ? 'Ya' : 'Tidak',
        ];
    }

    private function getKondisiLabel()
    {
        $labels = [
            'layak' => 'Layak Pakai',
            'rusak' => 'Rusak',
            'tdk_dapat_digunakan' => 'Tidak dapat digunakan',
            'lainnya' => $this->c_kondisi_lain ?: 'Lainnya',
        ];
        
        return $labels[$this->c_kondisi] ?? $this->c_kondisi;
    }

    private function getSumberLabel()
    {
        $labels = [
            'milik_rs' => 'Milik RS',
            'kso' => 'KSO',
            'donasi' => 'Donasi',
        ];
        
        return $labels[$this->c_sumber] ?? $this->c_sumber;
    }
}
