# Status Purchasing: Penjelasan Sederhana

Bayangkan kamu pesan barang di toko online. Prosesnya ada beberapa langkah. Di sistem kita, status purchasing (pembelian) mengikuti langkah-langkah ini:

- unprocessed → Belum diproses
- benchmarking → Pemilihan vendor (lihat harga dari beberapa toko)
- selected → Uji coba/Proses PR sistem (sudah pilih 1 toko terbaik)
- po_issued → Proses di vendor (sudah buat PO/pesanan ke toko)
- grn_received → Barang sudah diterima (barang datang)
- done → Selesai (semua beres)

## Dari mana status ini muncul?
Status ini diambil dari setiap item yang mau dibeli (`PurchasingItem`). Lalu, sistem membuat satu “status gabungan” untuk 1 pengajuan (`ApprovalRequest`) berdasarkan kemajuan paling jauh dari item-itemnya.

Contoh:
- Jika 1 item sudah "po_issued" dan item lain masih "benchmarking", maka status pengajuan jadi "po_issued" (mengikuti yang paling maju).

## Kapan status berubah otomatis?
Sistem mengubah status saat tim Purchasing melakukan aksi berikut:
- Simpan Benchmarking: status Belum diproses → Pemilihan vendor.
- Pilih Preferred Vendor: status jadi Uji coba/Proses PR sistem.
- Isi PO Number: status jadi Proses di vendor.
- Isi GRN Date: status jadi Barang sudah diterima.
- Mark as DONE: status jadi Selesai.

Sistem mengubah status saat tim Purchasing melakukan aksi berikut:
- Simpan Benchmarking: status Belum diproses → Pemilihan vendor.
- Pilih Preferred Vendor: status jadi Uji coba/Proses PR sistem.
- Isi PO Number: status jadi Proses di vendor.
- Isi GRN Date: status jadi Barang sudah diterima.
- Mark as DONE: status jadi Selesai.


Aksi-aksi ini diproses di `app/Services/Purchasing/PurchasingItemService.php` dan otomatis memanggil `ApprovalRequest->refreshPurchasingStatus()` agar status gabungan di pengajuan ikut update.

## Di mana status ini ditampilkan?
- Halaman daftar semua request: `resources/views/approval-requests/index.blade.php` (kolom "Status Purchasing").
- Halaman "My Requests": `resources/views/approval-requests/my-requests.blade.php` (kolom "Status Purchasing").
- Halaman "Pending Approvals": `resources/views/approval-requests/pending-approvals.blade.php` (kolom "Status Purchasing").
- Halaman detail request: `resources/views/approval-requests/show.blade.php` (badge "Status Purchasing").
- Laporan/Report: `ReportController@approvalRequests()` dan `exportApprovalRequests()` menampilkan teks status yang ramah pengguna.

## Cara membacanya seperti anak SD
- Belum diproses: belum mulai.
- Pemilihan vendor: lagi cari-cari toko dan harga.
- Uji coba/Proses PR sistem: sudah pilih toko terbaik.
- Proses di vendor: pesanan sudah dikirim ke toko (ada PO).
- Barang sudah diterima: barangnya sudah sampai.
- Selesai: semua langkah sudah beres.

Selesai! Kamu tinggal lihat badge/kolom "Status Purchasing" untuk tahu sampai mana proses pembeliannya.
