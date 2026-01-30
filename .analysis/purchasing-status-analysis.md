# Analisis Status Purchasing & Solusi

## 🔍 MASALAH YANG DITEMUKAN

### 1. **Status Purchasing Tidak Muncul dengan Benar**

#### Penyebab:
- **Logic di `purchasing-status-badge.blade.php` (baris 27-31)**:
  ```php
  } elseif (in_array($item->status, ['in_purchasing', 'approved', 'in_release'])) {
      $finalStatus = 'unprocessed';
  } else {
      $finalStatus = 'pending_approval';
  }
  ```
  - Item yang sudah `approved` tapi belum ada `PurchasingItem` ditampilkan sebagai "Belum diproses"
  - Item yang masih `pending` atau `on progress` ditampilkan sebagai "Belum diproses"
  - **TIDAK ADA PEMBEDA** antara item yang siap diproses vs yang belum siap

#### Dampak:
- User tidak bisa membedakan item yang:
  - ✅ Sudah selesai approval, siap input data purchasing
  - ⏳ Masih dalam proses approval, belum bisa input data

---

### 2. **Perhitungan Jumlah Status Tidak Akurat**

#### Di `ReportController.php` (baris 362-392):
```php
// Hanya menghitung dari PurchasingItem table
$purchasingCounts = \App\Models\PurchasingItem::select('status', \DB::raw('count(*) as count'))
    ->groupBy('status')
    ->pluck('count', 'status')
    ->toArray();

// Menambahkan item yang ready tapi belum ada PI
$readyButNoPI = \App\Models\ApprovalRequestItem::whereIn('status', ['in_purchasing', 'approved', 'in_release'])
    ->whereNotExists(...)
    ->count();

$totalUnprocessed = $piUnprocessed + $readyButNoPI;
```

#### Masalah:
- Counter "Belum diproses" mencampur:
  - Item yang SIAP diproses (approval selesai)
  - Item yang BELUM SIAP (approval belum selesai)
- Tidak ada counter untuk item yang masih dalam approval

---

## 💡 SOLUSI YANG DIUSULKAN

### **Status Baru untuk Purchasing:**

| Status Code | Label Indonesia | Warna | Kondisi |
|------------|----------------|-------|---------|
| `pending_approval` | **Menunggu Approval** | Gray (bg-gray-300) | Item masih dalam proses approval (`pending`, `on progress`) |
| `ready_to_process` | **Siap Diproses** | Blue (bg-blue-500) | Approval selesai, belum ada PurchasingItem, siap input data |
| `unprocessed` | **Belum Diproses** | Gray (bg-gray-200) | Ada PurchasingItem dengan status unprocessed |
| `benchmarking` | **Pemilihan Vendor** | Red (bg-red-600) | Sedang benchmarking vendor |
| `selected` | **Proses PR & PO** | Yellow (bg-yellow-400) | Vendor sudah dipilih |
| `po_issued` | **Proses di Vendor** | Orange (bg-orange-500) | PO sudah diterbitkan |
| `grn_received` | **Barang Diterima** | Green (bg-green-600) | GRN sudah diterima |
| `done` | **Selesai** | Dark Green (bg-green-700) | Proses purchasing selesai |

---

## 🔧 IMPLEMENTASI

### 1. Update `purchasing-status-badge.blade.php`

**Logika Baru:**
```php
if ($pi) {
    // Jika ada PurchasingItem, gunakan status dari PI
    $finalStatus = $pi->status;
} else {
    // Jika tidak ada PurchasingItem, cek status approval item
    if (in_array($item->status, ['in_purchasing', 'approved', 'in_release'])) {
        // Approval selesai, SIAP untuk input data purchasing
        $finalStatus = 'ready_to_process';
    } else {
        // Masih dalam proses approval, BELUM BISA input data
        $finalStatus = 'pending_approval';
    }
}
```

### 2. Update Status Counts di Controllers

**Untuk semua controller yang menampilkan purchasing counts:**
- `ReportController::approvalRequests()`
- `ApprovalRequestController::index()`
- `ApprovalRequestController::myRequests()`
- `ApprovalRequestController::pendingApprovals()`

**Logika Perhitungan:**
```php
// 1. Count dari PurchasingItem
$piCounts = PurchasingItem::select('status', DB::raw('count(*) as count'))
    ->groupBy('status')
    ->pluck('count', 'status')
    ->toArray();

// 2. Count item yang SIAP diproses (ready_to_process)
$readyToProcess = ApprovalRequestItem::whereIn('status', ['in_purchasing', 'approved', 'in_release'])
    ->whereNotExists(function($query) {
        $query->select(DB::raw(1))
              ->from('purchasing_items')
              ->whereColumn('purchasing_items.approval_request_id', 'approval_request_items.approval_request_id')
              ->whereColumn('purchasing_items.master_item_id', 'approval_request_items.master_item_id');
    })
    ->count();

// 3. Count item yang MENUNGGU approval (pending_approval)
$pendingApproval = ApprovalRequestItem::whereIn('status', ['pending', 'on progress'])
    ->count();

$purchasingCounts = [
    'pending_approval' => $pendingApproval,
    'ready_to_process' => $readyToProcess,
    'unprocessed' => $piCounts['unprocessed'] ?? 0,
    'benchmarking' => $piCounts['benchmarking'] ?? 0,
    'selected' => $piCounts['selected'] ?? 0,
    'po_issued' => $piCounts['po_issued'] ?? 0,
    'grn_received' => $piCounts['grn_received'] ?? 0,
    'done' => $piCounts['done'] ?? 0,
];
```

### 3. Update `info-status.blade.php`

Tambahkan badge untuk status baru:
```php
@if($variant === 'purchasing')
    <x-approval-status-badge status="pending_approval" :count="$counts['pending_approval'] ?? 0" variant="solid" />
    <x-approval-status-badge status="ready_to_process" :count="$counts['ready_to_process'] ?? 0" variant="solid" />
    <x-approval-status-badge status="unprocessed" :count="$counts['unprocessed'] ?? 0" variant="solid" />
    <!-- ... status lainnya -->
@endif
```

---

## 📊 KEUNTUNGAN SOLUSI INI

### ✅ **Clarity (Kejelasan)**
- User langsung tahu item mana yang bisa diproses
- User tahu item mana yang masih menunggu approval

### ✅ **Accuracy (Akurasi)**
- Counter status purchasing akurat
- Tidak ada lagi pencampuran antara "siap" dan "belum siap"

### ✅ **User Experience**
- Workflow lebih jelas
- Mengurangi kebingungan user

### ✅ **Data Integrity**
- Status mencerminkan kondisi sebenarnya
- Memudahkan tracking dan reporting

---

## 🎯 PRIORITAS IMPLEMENTASI

1. **HIGH**: Update `purchasing-status-badge.blade.php` - Perbaiki logic status
2. **HIGH**: Update `ReportController::approvalRequests()` - Perbaiki counts
3. **MEDIUM**: Update controllers lain (index, myRequests, pendingApprovals)
4. **MEDIUM**: Update `info-status.blade.php` - Tambah badge baru
5. **LOW**: Update dokumentasi dan user guide

---

## 📝 CATATAN TAMBAHAN

### Status Approval Item vs Purchasing Status

**Approval Item Status:**
- `pending` - Belum ada yang approve
- `on progress` - Sedang dalam proses approval
- `approved` - Semua approval selesai
- `in_purchasing` - Masuk fase purchasing
- `in_release` - Masuk fase release
- `rejected` - Ditolak
- `cancelled` - Dibatalkan

**Purchasing Status (BARU):**
- `pending_approval` - Item masih dalam approval (pending/on progress)
- `ready_to_process` - Approval selesai, siap input purchasing
- `unprocessed` - Ada PI tapi belum diproses
- `benchmarking` - Sedang pilih vendor
- `selected` - Vendor dipilih
- `po_issued` - PO diterbitkan
- `grn_received` - Barang diterima
- `done` - Selesai

### Mapping Logic:
```
Approval Status → Purchasing Status
=====================================
pending          → pending_approval
on progress      → pending_approval
approved         → ready_to_process (jika belum ada PI)
in_purchasing    → ready_to_process (jika belum ada PI)
in_release       → ready_to_process (jika belum ada PI)
[ada PI]         → [gunakan status PI]
```
