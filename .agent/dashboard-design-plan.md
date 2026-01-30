# Rancangan Dashboard Supply Chain Management

## 1. Analisis Kebutuhan

### 1.1 Halaman yang Dianalisis
Berdasarkan file yang disebutkan, sistem memiliki 4 halaman utama yang perlu ditampilkan di dashboard:

1. **My Requests** (`approval-requests/my-requests.blade.php`)
   - Permission: `view_my_approvals`
   - Menampilkan request yang dibuat oleh user sendiri
   - Status: on progress, pending, approved, rejected, cancelled

2. **Pending Approvals** (`approval-requests/pending-approvals.blade.php`)
   - Permission: `approval`
   - Menampilkan approval yang menunggu persetujuan user
   - Status: pending, on progress, approved, rejected, cancelled

3. **Laporan Pengajuan** (`reports/approval-requests/index.blade.php`)
   - Permission: `view_process_purchasing`
   - Menampilkan laporan semua pengajuan dengan filter purchasing status
   - Purchasing Status: unprocessed, benchmarking, selected, po_issued, grn_received, done

4. **My Pending Releases** (`release-requests/my-pending.blade.php`)
   - Permission: `view_pending_release`
   - Menampilkan release requests yang menunggu approval user
   - Status: pending, approved, rejected

### 1.2 Permission System
Sistem menggunakan permission-based access control:
- User memiliki Role
- Role memiliki banyak Permission
- Setiap fitur dilindungi oleh permission tertentu

## 2. Struktur Dashboard

### 2.1 Layout Dashboard
Dashboard akan menampilkan:

1. **Welcome Section** - Informasi user dan role
2. **Statistics Cards** - Card dengan jumlah task berdasarkan permission
3. **Recent Updates** - Timeline/list update terbaru
4. **Quick Actions** - Tombol akses cepat ke fitur-fitur utama

### 2.2 Cards yang Ditampilkan (Berdasarkan Permission)

#### Card 1: My Requests (Permission: `view_my_approvals`)
- Total requests yang dibuat
- Breakdown by status:
  - On Progress
  - Pending
  - Approved
  - Rejected
  - Cancelled

#### Card 2: Pending Approvals (Permission: `approval`)
- Total approvals yang menunggu
- Breakdown by status:
  - Pending (perlu action)
  - On Progress
  - Completed Today

#### Card 3: Process Purchasing (Permission: `view_process_purchasing`)
- Total items dalam proses purchasing
- Breakdown by purchasing status:
  - Unprocessed (belum diproses)
  - Benchmarking (pemilihan vendor)
  - Selected (proses PR & PO)
  - PO Issued (proses di vendor)
  - GRN Received (barang diterima)
  - Done (selesai)

#### Card 4: Pending Releases (Permission: `view_pending_release`)
- Total release requests yang menunggu
- Breakdown by status:
  - Pending (perlu action)
  - Waiting Purchase
  - Completed

### 2.3 Recent Updates Section
Menampilkan 10 aktivitas terbaru dari:
- Approval requests yang baru dibuat
- Approval yang baru disetujui/ditolak
- Status purchasing yang berubah
- Release requests yang baru

## 3. Arsitektur Kode (Best Practice)

### 3.1 Struktur Folder
```
app/
├── Http/
│   └── Controllers/
│       └── Dashboard/
│           ├── DashboardController.php (Main controller)
│           ├── MyRequestsStatsController.php
│           ├── PendingApprovalsStatsController.php
│           ├── ProcessPurchasingStatsController.php
│           ├── PendingReleasesStatsController.php
│           └── RecentUpdatesController.php
│
├── Services/
│   └── Dashboard/
│       ├── MyRequestsStatsService.php
│       ├── PendingApprovalsStatsService.php
│       ├── ProcessPurchasingStatsService.php
│       ├── PendingReleasesStatsService.php
│       └── RecentUpdatesService.php
│
resources/
└── views/
    ├── dashboard.blade.php (Main view)
    └── components/
        └── dashboard/
            ├── stat-card.blade.php
            ├── recent-update-item.blade.php
            ├── quick-action-button.blade.php
            └── welcome-section.blade.php
```

### 3.2 Prinsip Clean Code
1. **Single Responsibility Principle**: Setiap service hanya menangani satu jenis statistik
2. **Dependency Injection**: Service di-inject ke controller
3. **Reusable Components**: Blade components untuk UI yang konsisten
4. **Separation of Concerns**: Logic di Service, Controller hanya orchestrate, View hanya display

### 3.3 Service Layer Pattern
Setiap service akan memiliki method:
- `getStats()`: Mengambil statistik utama
- `getBreakdown()`: Mengambil breakdown berdasarkan status
- `getRecentItems()`: Mengambil item terbaru (jika applicable)

## 4. Data Flow

```
Request → DashboardController
    ↓
    ├─→ MyRequestsStatsService → getStats()
    ├─→ PendingApprovalsStatsService → getStats()
    ├─→ ProcessPurchasingStatsService → getStats()
    ├─→ PendingReleasesStatsService → getStats()
    └─→ RecentUpdatesService → getRecentUpdates()
    ↓
View (dashboard.blade.php)
    ↓
    ├─→ Component: stat-card (untuk setiap card)
    ├─→ Component: recent-update-item (untuk setiap update)
    └─→ Component: quick-action-button (untuk quick actions)
```

## 5. Database Queries Optimization

### 5.1 Eager Loading
Semua query akan menggunakan eager loading untuk menghindari N+1 problem:
```php
ApprovalRequest::with(['items', 'requester', 'workflow'])->get();
```

### 5.2 Query Optimization
- Gunakan `select()` untuk memilih kolom yang diperlukan saja
- Gunakan `whereHas()` untuk filter relasi
- Gunakan `count()` untuk statistik tanpa load semua data

### 5.3 Caching Strategy
Untuk performa, statistik dapat di-cache:
- Cache key: `dashboard.stats.{user_id}`
- TTL: 5 menit
- Invalidate saat ada perubahan data

## 6. UI/UX Design

### 6.1 Card Design
- Menggunakan Tailwind CSS untuk styling
- Card dengan shadow dan hover effect
- Icon yang representatif untuk setiap jenis card
- Color coding berdasarkan status:
  - Blue: On Progress
  - Yellow: Pending
  - Green: Approved/Done
  - Red: Rejected
  - Gray: Cancelled

### 6.2 Responsive Design
- Mobile: Stack cards vertically
- Tablet: 2 columns
- Desktop: 4 columns

### 6.3 Interactive Elements
- Click pada card untuk navigate ke halaman detail
- Hover effect untuk visual feedback
- Loading state saat fetch data

## 7. Implementation Steps

### Phase 1: Service Layer
1. Buat folder structure
2. Implementasi MyRequestsStatsService
3. Implementasi PendingApprovalsStatsService
4. Implementasi ProcessPurchasingStatsService
5. Implementasi PendingReleasesStatsService
6. Implementasi RecentUpdatesService

### Phase 2: Controller Layer
1. Update DashboardController
2. Inject semua services
3. Aggregate data dari services
4. Pass data ke view

### Phase 3: View Layer
1. Buat blade components
2. Update dashboard.blade.php
3. Implementasi responsive design
4. Add loading states

### Phase 4: Testing & Optimization
1. Test dengan berbagai permission combinations
2. Optimize queries
3. Add caching
4. Performance testing

## 8. Permission Handling

Dashboard akan menampilkan card hanya jika user memiliki permission yang sesuai:

```php
@if(auth()->user()->hasPermission('view_my_approvals'))
    <x-dashboard.stat-card 
        title="My Requests" 
        :stats="$myRequestsStats" 
        icon="fas fa-file-alt"
        color="blue"
        route="approval-requests.my-requests"
    />
@endif
```

## 9. Error Handling

- Graceful degradation jika service gagal
- Fallback UI jika data tidak tersedia
- Error logging untuk debugging

## 10. Future Enhancements

1. Real-time updates menggunakan WebSocket/Pusher
2. Export dashboard data to PDF/Excel
3. Customizable dashboard (user can choose which cards to display)
4. Chart/Graph visualization
5. Notification system integration
