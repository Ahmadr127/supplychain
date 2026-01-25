# 📊 Summary: Dynamic Workflow System V2

## Quick Reference

### Workflow Selection Matrix

| Jenis Pengadaan | Nominal | Workflow | Approval Steps | Release Steps |
|-----------------|---------|----------|----------------|---------------|
| **BARANG BARU** | ≤ 10 Juta | `procurement_low` | 5 (1 Maker + 4 Approvers) | 2 |
| **BARANG BARU** | 10 - 50 Juta | `procurement_medium` | 5 (1 Maker + 4 Approvers) | 2 |
| **BARANG BARU** | > 50 Juta | `procurement_high` | 6 (1 Maker + 5 Approvers) | 3 |
| **PEREMAJAAN** | ≤ 10 Juta | `renewal_low` | 4 (1 Maker + 3 Approvers) | 1 |
| **PEREMAJAAN** | 10 - 50 Juta | `renewal_medium` | 5 (1 Maker + 4 Approvers) | 2 |
| **PEREMAJAAN** | > 50 Juta | `renewal_high` | 6 (1 Maker + 5 Approvers) | 3 |

---

## 🔄 FLOW URUTAN (PENTING!)

```
┌───────────────────────────────────────────────────────────────┐
│           PHASE 1: APPROVAL (step_phase = 'approval')        │
├───────────────────────────────────────────────────────────────┤
│                                                               │
│  Maker ─────────────────────────────────────────────────────► │
│    │   Input Administrasi Permintaan CapEx                    │
│    │                                                          │
│    ▼                                                          │
│  Approver 1 (Manager Unit) ─────────────────────────────────► │
│    │   Pemilihan ID Number CapEx                              │
│    │                                                          │
│    ▼                                                          │
│  Approver 2 [+ FS jika > 50Jt] ─────────────────────────────► │
│    │                                                          │
│    ▼                                                          │
│  Approver 3, 4, 5... ───────────────────────────────────────► │
│    │                                                          │
│    ▼                                                          │
│  ✅ STATUS: APPROVED FOR PURCHASING                           │
│                                                               │
└───────────────────────────────────────────────────────────────┘
                          │
                          │ Auto-create PurchasingItem
                          ▼
┌───────────────────────────────────────────────────────────────┐
│       PHASE 2: PURCHASING (Existing System - Tidak diubah)   │
├───────────────────────────────────────────────────────────────┤
│                                                               │
│  PurchasingItem created (status: unprocessed)                 │
│    │                                                          │
│    ▼                                                          │
│  SPH 1: Request quotation dari vendor 1                       │
│    │                                                          │
│    ▼                                                          │
│  SPH 2: Request quotation dari vendor 2                       │
│    │                                                          │
│    ▼                                                          │
│  Benchmarking: Bandingkan harga                               │
│    │                                                          │
│    ▼                                                          │
│  Select preferred vendor                                      │
│    │                                                          │
│    ▼                                                          │
│  ✅ STATUS: VENDOR SELECTED (ready for release)               │
│                                                               │
└───────────────────────────────────────────────────────────────┘
                          │
                          │ Trigger Release steps
                          ▼
┌───────────────────────────────────────────────────────────────┐
│            PHASE 3: RELEASE (step_phase = 'release')         │
├───────────────────────────────────────────────────────────────┤
│                                                               │
│  Releaser 1 (Manager Pembelian) ────────────────────────────► │
│    │   Review hasil benchmarking                              │
│    │                                                          │
│    ▼                                                          │
│  Releaser 2 (Manager PT) ───────────────────────────────────► │
│    │   Final approval                                         │
│    │                                                          │
│    ▼                                                          │
│  Releaser 3 (Direktur PT) [jika > 50Jt] ────────────────────► │
│    │                                                          │
│    ▼                                                          │
│  ✅ STATUS: RELEASED → PO ISSUED                              │
│                                                               │
└───────────────────────────────────────────────────────────────┘
```

---

## Database Tables Created

### New Tables
1. **`procurement_types`** - Jenis pengadaan (BARANG_BARU, PEREMAJAAN)
2. **`capex_id_numbers`** - Master CapEx ID untuk budget allocation
3. **`capex_allocations`** - Tracking alokasi CapEx ke approval request

### Updated Tables
4. **`approval_workflows`** - Added: procurement_type_id, nominal_min/max, nominal_range, priority
5. **`approval_requests`** - Added: procurement_type_id, capex_id_number_id, total_amount
6. **`approval_item_steps`** - Added: step_type, **step_phase**, scope_process, selected_capex_id, skip fields

---

## Step Types (Approval Workflow)

| Type | Phase | Role | Purpose |
|------|-------|------|---------|
| `maker` | `approval` | Koordinator/Kepala/Supervisor/Manager | Input & create initial document |
| `approver` | `approval` | Various Managers/Directors | Review & approve |
| `releaser` | `release` | Manager Pembelian/PT/Direktur | Final release after purchasing |

---

## Key Scope Processes

| Scope | Who | Phase | What |
|-------|-----|-------|------|
| Input Administrasi Permintaan CapEx | Maker | Approval | Create initial request |
| Pemilihan ID Number CapEx | Manager Unit | Approval | Select & allocate CapEx budget |
| Pembuatan FS | Manager Keuangan | Approval | Create Feasibility Study (>50Jt) |
| - | SPH 1, SPH 2 | Purchasing | Vendor quotation (existing system) |
| - | Releasers | Release | Final release for PO |

---

## Files Created

### Migrations
```
database/migrations/
├── 2026_01_26_000001_create_procurement_types_table.php
├── 2026_01_26_000002_create_capex_id_numbers_table.php
├── 2026_01_26_000003_create_capex_allocations_table.php
├── 2026_01_26_000004_update_approval_workflows_for_procurement_type.php
├── 2026_01_26_000005_update_approval_requests_for_procurement_type.php
└── 2026_01_26_000006_update_approval_item_steps_for_step_types.php
```

### Models
```
app/Models/
├── ProcurementType.php
├── CapexIdNumber.php
└── CapexAllocation.php
```

### Seeders
```
database/seeders/
├── ProcurementTypeSeeder.php
├── CapexIdNumberSeeder.php
├── ExtendedRoleSeeder.php
└── DynamicWorkflowSeeder.php
```

### Services
```
app/Services/
└── WorkflowSelectorService.php
```

---

## How to Run

```bash
# 1. Run migrations
php artisan migrate

# 2. Run seeders in order
php artisan db:seed --class=ProcurementTypeSeeder
php artisan db:seed --class=ExtendedRoleSeeder
php artisan db:seed --class=CapexIdNumberSeeder
php artisan db:seed --class=DynamicWorkflowSeeder
```

---

## Detail Per Workflow (Berdasarkan Gambar)

### BARANG BARU ≤ 10 Juta

| Step | Phase | Name | PIC | Scope |
|------|-------|------|-----|-------|
| 1 | Approval | Maker | Koordinator/Kepala/Supervisor/Manager | Input Administrasi Permintaan CapEx |
| 2 | Approval | Approver 1 | Manager Unit | Pemilihan ID Number CapEx |
| 3 | Approval | Approver 2 | Hospital Director | - |
| 4 | Approval | Approver 3 | Manager PT | - |
| 5 | Approval | Approver 4 | Manager Pembelian | - |
| - | Purchasing | SPH 1, SPH 2 | (existing) | Benchmarking |
| 6 | Release | Releaser 1 | Manager Pembelian | - |
| 7 | Release | Releaser 2 | Manager PT | - |

### BARANG BARU > 50 Juta

| Step | Phase | Name | PIC | Scope |
|------|-------|------|-----|-------|
| 1 | Approval | Maker | Koordinator/Kepala/Supervisor/Manager | Input Administrasi Permintaan CapEx |
| 2 | Approval | Approver 1 | Manager Unit | Pemilihan ID Number CapEx |
| 3 | Approval | Approver 2 | Manager Keuangan | **Pembuatan FS** |
| 4 | Approval | Approver 3 | Hospital Director | - |
| 5 | Approval | Approver 4 | Manager PT | - |
| 6 | Approval | Approver 5 | Manager Pembelian | - |
| - | Purchasing | SPH 1, SPH 2 | (existing) | Benchmarking |
| 7 | Release | Releaser 1 | Manager Pembelian | - |
| 8 | Release | Releaser 2 | Manager PT | - |
| 9 | Release | Releaser 3 | **Direktur PT** | - |

### PEREMAJAAN ≤ 10 Juta

| Step | Phase | Name | PIC | Scope |
|------|-------|------|-----|-------|
| 1 | Approval | Maker | Koordinator/Kepala/Supervisor/Manager | Input Administrasi Permintaan CapEx |
| 2 | Approval | Approver 1 | Manager Unit | Pemilihan ID Number CapEx |
| 3 | Approval | Approver 2 | Hospital Director | - |
| 4 | Approval | Approver 3 | Manager Pembelian | - |
| - | Purchasing | SPH 1, SPH 2 | (existing) | Benchmarking |
| 5 | Release | Releaser 1 | Manager Pembelian | - |

---

*Last Updated: 2026-01-26*
