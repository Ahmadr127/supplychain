# 📋 Blade Views Implementation Plan

## Overview

Dokumen ini menjelaskan rencana implementasi untuk blade views yang mendukung **3-Phase Workflow System**:

```
PHASE 1: APPROVAL → PHASE 2: PURCHASING → PHASE 3: RELEASE
```

---

## 📁 Directory Structure

```
resources/views/
├── approval-requests/           # ✅ EXISTING - Perlu update
│   ├── _form-extra.blade.php    # Form statis
│   ├── _form.blade.php          # Form utama
│   ├── create.blade.php         # Buat request baru
│   ├── edit.blade.php           # Edit request
│   ├── index.blade.php          # List semua request
│   ├── my-requests.blade.php    # Request milik user
│   ├── pending-approvals.blade.php  # List approval pending
│   └── show.blade.php           # Detail request + approval
│
├── approval-workflows/          # ✅ EXISTING - Perlu update
│   ├── create.blade.php         # Buat workflow baru
│   ├── edit.blade.php           # Edit workflow
│   ├── index.blade.php          # List workflows
│   └── show.blade.php           # Detail workflow
│
├── approval-items/              # 🆕 NEW - Untuk approval per item
│   ├── index.blade.php          # List items pending approval
│   └── _item-card.blade.php     # Komponen card item
│
├── capex/                       # 🆕 NEW - CapEx ID Management
│   ├── index.blade.php          # List CapEx IDs
│   ├── add.blade.php            # Tambah CapEx ID baru
│   ├── edit.blade.php           # Edit CapEx ID
│   └── form/
│       └── _capex-form.blade.php  # Form component
│
├── release-requests/            # 🆕 NEW - Release phase tracking
│   ├── index.blade.php          # List release requests
│   ├── modals/
│   │   └── _release-modal.blade.php  # Modal for release action
│   └── _release-card.blade.php  # Card component
│
├── purchasing/                  # ✅ EXISTING
│   └── items/
│       ├── index.blade.php      # List purchasing items
│       ├── show.blade.php       # Detail item
│       ├── _form.blade.php      # Form benchmarking
│       ├── _form-scripts.blade.php
│       └── form-vendor.blade.php
│
└── components/                  # Blade Components
    ├── approval-status-badge.blade.php  # ✅ EXISTING
    ├── item-workflow-approval.blade.php # ✅ EXISTING - Perlu update
    ├── phase-indicator.blade.php        # 🆕 NEW
    ├── capex-selector.blade.php         # 🆕 NEW
    └── release-step-card.blade.php      # 🆕 NEW
```

---

## 🔄 Updates Required

### 1. `approval-requests/show.blade.php` (PRIORITY: HIGH)

**Current State:** Shows per-item approval steps
**Required Changes:**
- [ ] Add **Phase Indicator** to show current phase (Approval/Purchasing/Release)
- [ ] Show different UI based on `step_phase`:
  - `approval` phase: Standard approval flow
  - `release` phase: Show after purchasing complete
- [ ] Add CapEx information display
- [ ] Show `scope_process` for each step
- [ ] Handle new status: `in_purchasing`, `in_release`

**Example Phase Indicator:**
```blade
<x-phase-indicator :item="$item" />
<!-- Shows: [✓ Approval] → [● Purchasing] → [○ Release] -->
```

### 2. `approval-requests/pending-approvals.blade.php` (PRIORITY: HIGH)

**Required Changes:**
- [ ] Filter by `step_phase` (approval vs release)
- [ ] Add tabs: "Approval Pending" | "Release Pending"
- [ ] Show different columns for release items (vendor info, PO number)

### 3. `components/item-workflow-approval.blade.php` (PRIORITY: HIGH)

**Required Changes:**
- [ ] Add CapEx ID selector for Manager Unit step
- [ ] Add FS upload trigger for high-value items
- [ ] Show `scope_process` instruction
- [ ] Handle `pending_purchase` status (disabled until purchasing complete)
- [ ] Different approve button for release phase

### 4. `capex/index.blade.php` (PRIORITY: MEDIUM)

**New View - Features:**
- [ ] List all CapEx ID Numbers with budget info
- [ ] Show allocated vs remaining budget
- [ ] Filter by year, status
- [ ] CRUD actions

### 5. `capex/add.blade.php` & `edit.blade.php` (PRIORITY: MEDIUM)

**New View - Form Fields:**
- CapEx ID code
- Description
- Fiscal year
- Budget amount
- Status (active/inactive)

### 6. `release-requests/index.blade.php` (PRIORITY: MEDIUM)

**New View - Features:**
- [ ] List items in release phase
- [ ] Show purchasing info (vendor, price)
- [ ] Release approval actions
- [ ] Filter by status

### 7. `approval-workflows/create.blade.php` & `edit.blade.php` (PRIORITY: MEDIUM)

**Required Changes:**
- [ ] Add Procurement Type selector
- [ ] Add Nominal Range inputs (min/max)
- [ ] Step builder: Add `step_type` (maker/approver/releaser)
- [ ] Step builder: Add `step_phase` (approval/release)
- [ ] Step builder: Add `scope_process` field

---

## 🆕 New Blade Components

### 1. `components/phase-indicator.blade.php`

Visual indicator showing 3 phases:
```html
<div class="flex items-center gap-2">
    <span class="phase active">✓ Approval</span>
    <span class="arrow">→</span>
    <span class="phase current">● Purchasing</span>
    <span class="arrow">→</span>
    <span class="phase pending">○ Release</span>
</div>
```

**Props:**
- `$item` - ApprovalRequestItem
- `$purchasingItem` - Optional PurchasingItem

### 2. `components/capex-selector.blade.php`

Dropdown untuk Manager Unit memilih CapEx ID:
```html
<select name="selected_capex_id" class="...">
    @foreach($capexIds as $capex)
        <option value="{{ $capex->id }}">
            {{ $capex->code }} - {{ $capex->description }}
            (Sisa: Rp {{ number_format($capex->remaining_budget) }})
        </option>
    @endforeach
</select>
```

**Props:**
- `$capexIds` - Collection of CapexIdNumber
- `$selectedId` - Currently selected ID

### 3. `components/release-step-card.blade.php`

Card for release phase approval:
```html
<div class="release-step-card">
    <h4>{{ $step->step_name }}</h4>
    <div class="purchasing-info">
        <!-- Show vendor, price, PO info -->
    </div>
    <div class="actions">
        <button class="approve">Release</button>
        <button class="reject">Reject</button>
    </div>
</div>
```

---

## 📊 Status Badge Updates

Update `components/approval-status-badge.blade.php` to handle new statuses:

| Status | Badge Color | Label |
|--------|-------------|-------|
| `pending` | Gray | Pending |
| `on progress` | Yellow | On Progress |
| `in_purchasing` | Blue | In Purchasing |
| `in_release` | Purple | Awaiting Release |
| `approved` | Green | Approved |
| `rejected` | Red | Rejected |

---

## 🎯 Implementation Order

### Phase A: Core Updates (Days 1-2)
1. ✅ Update `approval-status-badge.blade.php`
2. ✅ Update `item-workflow-approval.blade.php`
3. ✅ Update `approval-requests/show.blade.php`

### Phase B: New Components (Days 2-3)
4. Create `phase-indicator.blade.php`
5. Create `capex-selector.blade.php`
6. Create `release-step-card.blade.php`

### Phase C: CapEx Management (Days 3-4)
7. Create `capex/index.blade.php`
8. Create `capex/add.blade.php`
9. Create `capex/edit.blade.php`

### Phase D: Release Views (Days 4-5)
10. Create `release-requests/index.blade.php`
11. Update `pending-approvals.blade.php` with tabs

### Phase E: Workflow Builder (Days 5-6)
12. Update `approval-workflows/create.blade.php`
13. Update `approval-workflows/edit.blade.php`

---

## 🔌 Required Controllers

### New Controllers:
1. `CapexIdNumberController` - CRUD for CapEx IDs
2. `ReleaseRequestController` - Handle release phase approvals

### Updated Controllers:
1. `ApprovalRequestController` - Add CapEx integration
2. `ApprovalItemApprovalController` - Handle phase transitions
3. `ApprovalWorkflowController` - Add procurement type fields

---

## 📝 Routes to Add

```php
// CapEx Management
Route::resource('capex', CapexIdNumberController::class);

// Release Requests (read-only + approve/reject)
Route::get('release-requests', [ReleaseRequestController::class, 'index'])->name('release-requests.index');
Route::post('release-requests/{item}/approve', [ReleaseRequestController::class, 'approve'])->name('release-requests.approve');
Route::post('release-requests/{item}/reject', [ReleaseRequestController::class, 'reject'])->name('release-requests.reject');
```

---

## Last Updated: 2026-01-26
