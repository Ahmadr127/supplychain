# Migration: Request-Level to Per-Item Approval System

## Executive Summary
Migrasi sistem approval dari **per-request** menjadi **per-item** dengan workflow steps yang independen untuk setiap item dalam approval request.

---

## 1. ANALISIS PERUBAHAN ARSITEKTUR

### 1.1 Sistem Lama (Request-Level Approval)
```
ApprovalRequest (1) ──> ApprovalSteps (N)
      │
      └──> MasterItems (N) via pivot `approval_request_master_items`
```
- **Approval dilakukan di level request**: Satu approval untuk semua item
- **Pivot table**: `approval_request_master_items` menyimpan item details
- **Status**: Request memiliki satu status untuk semua item
- **Workflow**: ApprovalSteps terikat ke request, bukan per item

### 1.2 Sistem Baru (Per-Item Approval)
```
ApprovalRequest (1) ──> ApprovalRequestItems (N) ──> ApprovalItemSteps (N)
                                │
                                └──> MasterItem (1)
```
- **Approval dilakukan per item**: Setiap item punya workflow sendiri
- **Dedicated table**: `approval_request_items` (bukan pivot)
- **Status per item**: Setiap item tracking approval independen
- **Workflow per item**: `approval_item_steps` clone dari request workflow

---

## 2. DATABASE CHANGES

### 2.1 New Tables

#### A. `approval_request_items` (Replacement for pivot)
**Purpose**: Main item table dengan status approval per-item
```sql
- id (PK)
- approval_request_id (FK)
- master_item_id (FK)
- quantity, unit_price, total_price
- notes, specification, brand
- supplier_id, alternative_vendor
- allocation_department_id, letter_number
- fs_document
- status (pending|on progress|approved|rejected|cancelled)
- assignee_id, reviewed_by, reviewed_at
- approved_by, approved_at
- rejected_reason
- timestamps
```

#### B. `approval_item_steps` (Per-item workflow)
**Purpose**: Clone workflow steps untuk setiap item
```sql
- id (PK)
- approval_request_id (FK)
- master_item_id (FK)
- step_number, step_name
- approver_type (user|role|department_manager|...)
- approver_id, approver_role_id, approver_department_id
- status (pending|approved|rejected)
- approved_by, approved_at, comments
- timestamps
```

### 2.2 Tables to Deprecate (NOT DELETE)
- `approval_request_master_items` (pivot) → Keep for backward compatibility
- `approval_steps` (request-level) → Keep for display/audit

### 2.3 Tables to Modify
**`approval_requests`**:
- Keep `status`, `current_step`, `total_steps` for **aggregated display**
- Add computed logic: status = aggregate of item statuses

---

## 3. MODEL CHANGES

### 3.1 New Models
```php
// app/Models/ApprovalRequestItem.php
class ApprovalRequestItem extends Model {
    protected $fillable = [...all fields...];
    
    public function request() // belongsTo ApprovalRequest
    public function masterItem() // belongsTo MasterItem
    public function steps() // hasMany ApprovalItemStep
    public function currentStep() // hasOne based on status
}

// app/Models/ApprovalItemStep.php (DONE)
class ApprovalItemStep extends Model {
    public function canApprove(int $userId): bool
    public function request(), item(), approver()
}
```

### 3.2 Update Existing Models

#### `ApprovalRequest.php`
```php
// ADD
public function items() {
    return $this->hasMany(ApprovalRequestItem::class);
}
public function itemSteps() {
    return $this->hasMany(ApprovalItemStep::class);
}

// DEPRECATE (keep for compatibility)
public function masterItems() // pivot relation
public function approve(), reject() // move to item-level
```

#### `MasterItem.php`
```php
// ADD
public function approvalRequestItems() {
    return $this->hasMany(ApprovalRequestItem::class);
}
```

---

## 4. CONTROLLER CHANGES

### 4.1 New Controllers

#### `ApprovalRequestItemController.php`
```php
// CRUD for approval_request_items
public function store(Request $request, ApprovalRequest $approvalRequest)
public function update(Request $request, ApprovalRequestItem $item)
public function destroy(ApprovalRequestItem $item)
```

#### `ApprovalItemApprovalController.php`
```php
// Per-item approval actions
public function approve(ApprovalRequest $request, ApprovalRequestItem $item)
public function reject(ApprovalRequest $request, ApprovalRequestItem $item)
// Logic:
// 1. Find current pending step for item
// 2. Check canApprove(auth()->id())
// 3. Update step status
// 4. Advance to next step or mark item complete
// 5. Aggregate request status
```

### 4.2 Update `ApprovalRequestController.php`

#### `store()` method
```php
// OLD: $approvalRequest->masterItems()->attach(...)
// NEW:
foreach ($request->items as $itemData) {
    $item = $approvalRequest->items()->create([...]);
    
    // Initialize per-item steps from workflow
    $this->initializeItemSteps($approvalRequest, $item);
}
```

#### `update()` method
```php
// OLD: detach() + attach()
// NEW: sync items table + re-initialize steps if needed
```

#### New helper method
```php
private function initializeItemSteps(
    ApprovalRequest $request, 
    ApprovalRequestItem $item
): void {
    $workflowSteps = $request->workflow->steps;
    foreach ($workflowSteps as $step) {
        $request->itemSteps()->create([
            'master_item_id' => $item->master_item_id,
            'step_number' => $step->step_number,
            'step_name' => $step->step_name,
            'approver_type' => $step->approver_type,
            'approver_id' => $step->approver_id,
            'approver_role_id' => $step->approver_role_id,
            'approver_department_id' => $step->approver_department_id,
            'status' => 'pending',
        ]);
    }
}
```

---

## 5. VIEW CHANGES

### 5.1 `approval-requests/show.blade.php`

#### Current (Request-level)
```blade
<div>Status: {{ $approvalRequest->status }}</div>
<div>Step: {{ $approvalRequest->current_step }}/{{ $total_steps }}</div>
@if(canApprove)
    <form approve-request>
@endif
```

#### New (Per-item)
```blade
@foreach($approvalRequest->items as $item)
    <div class="item-card">
        <h4>{{ $item->masterItem->name }}</h4>
        <span>Status: {{ $item->status }}</span>
        
        <!-- Per-item steps -->
        <div class="steps">
            @foreach($item->steps as $step)
                <span class="badge {{ $step->status }}">
                    {{ $step->step_name }}
                </span>
            @endforeach
        </div>
        
        <!-- Approval actions for current step -->
        @php
            $currentStep = $item->steps()
                ->where('status', 'pending')
                ->orderBy('step_number')
                ->first();
        @endphp
        
        @if($currentStep && $currentStep->canApprove(auth()->id()))
            <form action="{{ route('approval.items.approve', [$approvalRequest, $item]) }}" method="POST">
                @csrf
                <textarea name="comments"></textarea>
                <button type="submit">Approve</button>
            </form>
            <form action="{{ route('approval.items.reject', [$approvalRequest, $item]) }}" method="POST">
                @csrf
                <textarea name="comments" required></textarea>
                <button type="submit">Reject</button>
            </form>
        @endif
    </div>
@endforeach

<!-- Request-level aggregated status (read-only) -->
<div class="summary">
    Overall Status: {{ $approvalRequest->status }}
</div>
```

### 5.2 `approval-requests/_form.blade.php`
- No major changes (still adds items)
- Backend will create `approval_request_items` instead of pivot

### 5.3 `approval-requests/index.blade.php`
- Show per-item rows (already done per memory)
- Add item-level status badges

---

## 6. ROUTE CHANGES

### 6.1 New Routes
```php
// routes/web.php

// Per-item approval
Route::post('approval-requests/{approvalRequest}/items/{item}/approve', 
    [ApprovalItemApprovalController::class, 'approve'])
    ->name('approval.items.approve');

Route::post('approval-requests/{approvalRequest}/items/{item}/reject', 
    [ApprovalItemApprovalController::class, 'reject'])
    ->name('approval.items.reject');

// Item CRUD (optional, if needed)
Route::resource('approval-requests.items', ApprovalRequestItemController::class)
    ->shallow();
```

### 6.2 Deprecated Routes (Keep for compatibility)
```php
// OLD request-level approval (can be removed later)
Route::post('approval-requests/{approvalRequest}/approve', ...);
Route::post('approval-requests/{approvalRequest}/reject', ...);
```

---

## 7. BUSINESS LOGIC RULES

### 7.1 Item Approval Progression
```
Item Step 1 (pending) 
    → approve → Item Step 2 (pending)
    → approve → ... 
    → approve → Item Step N (approved) 
    → Item status = 'approved'
```

### 7.2 Item Rejection
```
Any step rejected → Item status = 'rejected'
→ Check rule: Reject entire request? (configurable)
```

### 7.3 Request Status Aggregation
```php
// In ApprovalRequest model
public function aggregateStatus(): string {
    $items = $this->items;
    
    if ($items->contains('status', 'rejected')) {
        return 'rejected'; // ANY item rejected
    }
    
    if ($items->every(fn($i) => $i->status === 'approved')) {
        return 'approved'; // ALL items approved
    }
    
    if ($items->some(fn($i) => in_array($i->status, ['pending','on progress']))) {
        return 'on progress';
    }
    
    return 'pending';
}

// Call after each item approval/rejection
$approvalRequest->update(['status' => $approvalRequest->aggregateStatus()]);
```

### 7.4 Purchasing Integration
**Current**: `PurchasingItem` created when **request** approved
**New**: `PurchasingItem` created when **item** approved

```php
// In ApprovalItemApprovalController@approve
if ($item->isFullyApproved()) {
    $item->update(['status' => 'approved']);
    
    // Create purchasing item
    PurchasingItem::create([
        'approval_request_id' => $item->approval_request_id,
        'master_item_id' => $item->master_item_id,
        'quantity' => $item->quantity,
        'status' => 'unprocessed',
    ]);
}
```

---

## 8. MIGRATION STRATEGY

### 8.1 Phase 1: Database Setup
```bash
# Run migrations
php artisan migrate

# Migrations will:
# 1. Create approval_request_items table
# 2. Backfill from pivot (approval_request_master_items)
# 3. Create approval_item_steps table
```

### 8.2 Phase 2: Initialize Item Steps
```php
// Create artisan command
php artisan approval:initialize-item-steps

// Command logic:
foreach (ApprovalRequest::whereIn('status', ['pending','on progress'])->get() as $req) {
    foreach ($req->items as $item) {
        if ($item->steps()->count() === 0) {
            $this->initializeItemSteps($req, $item);
        }
    }
}
```

### 8.3 Phase 3: Code Deployment
1. Deploy new models
2. Deploy new controllers
3. Deploy updated views
4. Deploy new routes

### 8.4 Phase 4: Testing
- Test per-item approval flow
- Test rejection scenarios
- Test purchasing integration
- Test aggregated status display

### 8.5 Phase 5: Cleanup (Optional, after stable)
- Remove old request-level approve/reject methods
- Archive old routes
- Add deprecation notices

---

## 9. PURCHASING MODULE ADJUSTMENTS

### 9.1 Current Flow
```
Request approved → Create PurchasingItems for ALL items
```

### 9.2 New Flow
```
Item approved → Create PurchasingItem for THAT item immediately
```

### 9.3 Changes in `PurchasingItem` model
```php
// ADD foreign key to approval_request_items
Schema::table('purchasing_items', function (Blueprint $table) {
    $table->foreignId('approval_request_item_id')
          ->nullable()
          ->constrained('approval_request_items')
          ->nullOnDelete();
});

// Model relationship
public function approvalRequestItem() {
    return $this->belongsTo(ApprovalRequestItem::class);
}
```

### 9.4 Report & Index Views
**Current**: Show purchasing status per request
**New**: Show purchasing status per item (already implemented per memory)

---

## 10. TESTING CHECKLIST

### 10.1 Unit Tests
- [ ] ApprovalRequestItem model CRUD
- [ ] ApprovalItemStep::canApprove() logic
- [ ] ApprovalRequest::aggregateStatus()
- [ ] Item step progression logic

### 10.2 Feature Tests
- [ ] Create request with items → item steps initialized
- [ ] Approve item step → advances to next step
- [ ] Approve last step → item marked approved
- [ ] Reject item step → item marked rejected
- [ ] All items approved → request marked approved
- [ ] Any item rejected → request marked rejected (if rule enabled)
- [ ] Purchasing item created when item approved

### 10.3 Integration Tests
- [ ] Full approval workflow (multi-step, multi-item)
- [ ] Mixed approvers (user, role, department_manager)
- [ ] Purchasing process after item approval
- [ ] Report views show correct per-item status

---

## 11. ROLLBACK PLAN

### 11.1 If Issues Found
1. **Keep pivot table intact**: `approval_request_master_items` not deleted
2. **Revert controllers**: Use old approve/reject methods
3. **Revert views**: Show request-level approval UI
4. **Database**: Keep new tables but don't use them

### 11.2 Data Integrity
- Backfill migration is **idempotent** (safe to re-run)
- New tables have foreign keys with cascade
- No data loss: pivot table preserved

---

## 12. TODO LIST (DEVELOPMENT ORDER)

### Phase 1: Foundation (Database & Models)
- [x] Create `approval_request_items` migration
- [x] Create backfill migration
- [x] Create `approval_item_steps` migration
- [x] Create `ApprovalItemStep` model with `canApprove()`
- [ ] Create `ApprovalRequestItem` model
- [ ] Add relationships to `ApprovalRequest`
- [ ] Add relationships to `MasterItem`
- [ ] Run migrations

### Phase 2: Core Logic (Controllers)
- [ ] Create `ApprovalRequestItemController` (CRUD)
- [ ] Create `ApprovalItemApprovalController` (approve/reject)
- [ ] Update `ApprovalRequestController@store` to use items table
- [ ] Update `ApprovalRequestController@update` to use items table
- [ ] Add `initializeItemSteps()` helper method
- [ ] Add `aggregateStatus()` to ApprovalRequest model
- [ ] Update `ApprovalRequestController@show` to load item steps

### Phase 3: Routes
- [ ] Add per-item approval routes
- [ ] Add item CRUD routes (if needed)
- [ ] Test route bindings

### Phase 4: Views
- [ ] Update `show.blade.php` with per-item steps display
- [ ] Add per-item approve/reject forms
- [ ] Update `index.blade.php` item status badges
- [ ] Update `_form.blade.php` if needed
- [ ] Add loading states and error handling

### Phase 5: Purchasing Integration
- [ ] Add `approval_request_item_id` to `purchasing_items` table
- [ ] Update `PurchasingItem` model relationship
- [ ] Modify purchasing item creation logic (on item approval)
- [ ] Update `ReportController` to handle per-item purchasing
- [ ] Update purchasing views

### Phase 6: Artisan Commands
- [ ] Create `approval:initialize-item-steps` command
- [ ] Create `approval:aggregate-status` command (fix orphaned statuses)
- [ ] Add command to docs

### Phase 7: Testing
- [ ] Write unit tests for models
- [ ] Write feature tests for approval flow
- [ ] Write integration tests for purchasing
- [ ] Manual testing checklist
- [ ] Load testing (if high volume)

### Phase 8: Documentation & Cleanup
- [ ] Update API documentation
- [ ] Update user guide
- [ ] Add inline code comments
- [ ] Deprecation notices for old methods
- [ ] Performance optimization (N+1 queries)

### Phase 9: Deployment
- [ ] Backup database
- [ ] Run migrations on staging
- [ ] Test on staging
- [ ] Deploy to production
- [ ] Monitor logs
- [ ] Run initialization command

### Phase 10: Post-Deployment
- [ ] Monitor error rates
- [ ] Gather user feedback
- [ ] Fix bugs
- [ ] Optimize queries
- [ ] Consider cleanup of deprecated code (after 1-2 months)

---

## 13. BEST PRACTICES APPLIED

### 13.1 Laravel Conventions
- ✅ Eloquent relationships (hasMany, belongsTo)
- ✅ Route model binding
- ✅ Form requests for validation
- ✅ Database transactions for critical operations
- ✅ Eager loading to prevent N+1 queries
- ✅ Scopes for reusable queries
- ✅ Accessors/Mutators for computed fields
- ✅ Events/Observers for side effects (optional)

### 13.2 Database Design
- ✅ Foreign keys with proper constraints
- ✅ Indexes on frequently queried columns
- ✅ Unique constraints where needed
- ✅ Soft deletes for audit trail (optional)
- ✅ Timestamps for all tables

### 13.3 Code Organization
- ✅ Single Responsibility Principle (separate controllers)
- ✅ DRY (helper methods for repeated logic)
- ✅ Service classes for complex business logic (optional)
- ✅ Repository pattern (optional, for testability)

### 13.4 Security
- ✅ Authorization checks (canApprove)
- ✅ CSRF protection (forms)
- ✅ Input validation
- ✅ SQL injection prevention (Eloquent)
- ✅ Mass assignment protection ($fillable)

### 13.5 Performance
- ✅ Eager loading relationships
- ✅ Database indexes
- ✅ Chunking for large datasets (backfill)
- ✅ Caching (optional, for workflow definitions)
- ✅ Queue jobs for heavy operations (optional)

---

## 14. POTENTIAL ISSUES & SOLUTIONS

### Issue 1: N+1 Query Problem
**Problem**: Loading items with steps in loop
**Solution**: 
```php
$approvalRequest->load(['items.steps', 'items.masterItem']);
```

### Issue 2: Race Conditions (Double Approval)
**Problem**: Two users approve same step simultaneously
**Solution**: Database transaction + row locking
```php
DB::transaction(function() use ($step) {
    $step->lockForUpdate()->first();
    if ($step->status !== 'pending') {
        throw new Exception('Already processed');
    }
    $step->update(['status' => 'approved']);
});
```

### Issue 3: Orphaned Steps
**Problem**: Items without steps after migration
**Solution**: Run initialization command (Phase 2)

### Issue 4: Status Sync Issues
**Problem**: Request status doesn't match item statuses
**Solution**: Scheduled command to fix
```php
// In Kernel.php
$schedule->command('approval:aggregate-status')->hourly();
```

### Issue 5: Backward Compatibility
**Problem**: Old code still using pivot table
**Solution**: Keep pivot synced (write to both) during transition

---

## 15. MONITORING & METRICS

### Key Metrics to Track
- Average approval time per item
- Approval bottlenecks (which steps take longest)
- Rejection rates per step
- Number of pending items per user
- Request completion rate

### Logging
```php
Log::info('Item approved', [
    'item_id' => $item->id,
    'step_number' => $step->step_number,
    'approver_id' => auth()->id(),
    'duration' => $item->created_at->diffInHours(now()),
]);
```

---

## 16. CLEANUP STRATEGY (PRE-PRODUCTION)

### ⚠️ AGGRESSIVE CLEANUP - Karena Belum Production

Karena sistem masih **development/staging**, kita akan **HAPUS** semua logic dan table lama untuk menghindari konflik.

### 16.1 Tables to DELETE

#### A. Drop Pivot Table (Replaced by approval_request_items)
```php
// Create migration: 2025_11_02_070000_drop_approval_request_master_items.php
Schema::dropIfExists('approval_request_master_items');
```

#### B. Drop Request-Level Steps (Replaced by approval_item_steps)
```php
// Create migration: 2025_11_02_070001_drop_approval_steps.php
Schema::dropIfExists('approval_steps');
```

#### C. Clean approval_requests Table
```php
// Remove unused columns
Schema::table('approval_requests', function (Blueprint $table) {
    $table->dropColumn(['current_step', 'total_steps']); // Computed from items now
    // Keep: status (aggregated), approved_by, approved_at for display
});
```

### 16.2 Models to REMOVE/UPDATE

#### A. Remove Old Methods from ApprovalRequest.php
```php
// DELETE these methods:
public function masterItems() // Use items() instead
public function approve($userId, $comments) // Move to item-level
public function reject($userId, $reason) // Move to item-level
public function getCurrentApprover() // Use item steps
public function canApprove($userId) // Use item steps

// KEEP these (updated logic):
public function items() // New main relationship
public function itemSteps() // New workflow
public function aggregateStatus() // Compute from items
```

#### B. Delete ApprovalStep Model
```bash
rm app/Models/ApprovalStep.php
```
**Reason**: Replaced by `ApprovalItemStep`

### 16.3 Controllers to CLEAN

#### A. ApprovalRequestController.php - Remove Methods
```php
// DELETE these methods:
public function approve(Request $request, ApprovalRequest $approvalRequest)
public function reject(Request $request, ApprovalRequest $approvalRequest)

// UPDATE these methods:
public function store() // Use items()->create() instead of attach()
public function update() // Use items sync instead of detach/attach
public function show() // Load items.steps instead of steps
```

#### B. Remove Old Approval Routes
```php
// routes/web.php - DELETE:
Route::post('approval-requests/{approvalRequest}/approve', ...);
Route::post('approval-requests/{approvalRequest}/reject', ...);
```

### 16.4 Views to UPDATE

#### A. Remove Request-Level Approval UI
```blade
<!-- approval-requests/show.blade.php - DELETE: -->
@if($approvalRequest->canApprove(auth()->id()))
    <form action="{{ route('approval-requests.approve') }}">
        <!-- OLD request-level approval form -->
    </form>
@endif

<!-- REPLACE with per-item approval (see Section 5.1) -->
```

#### B. Remove Step Progress Display
```blade
<!-- DELETE: -->
<div>Step: {{ $approvalRequest->current_step }}/{{ $total_steps }}</div>

<!-- REPLACE with item-level progress -->
<div>Items: {{ $approved }}/{{ $total }} approved</div>
```

### 16.5 Migrations Order (Clean Approach)

```bash
# 1. Create new tables
2025_11_02_000000_create_approval_request_items_table.php
2025_11_02_000001_backfill_approval_request_items.php (from pivot)
2025_11_02_060000_create_approval_item_steps_table.php

# 2. Initialize item steps from workflow
php artisan approval:initialize-item-steps

# 3. Verify data migrated correctly
php artisan approval:verify-migration

# 4. DROP old tables (AFTER verification)
2025_11_02_070000_drop_approval_request_master_items.php
2025_11_02_070001_drop_approval_steps.php
2025_11_02_070002_clean_approval_requests_columns.php
```

### 16.6 Complete Removal Checklist

#### Database
- [ ] Drop `approval_request_master_items` table
- [ ] Drop `approval_steps` table  
- [ ] Remove `current_step`, `total_steps` from `approval_requests`
- [ ] Verify foreign keys updated

#### Models
- [ ] Delete `app/Models/ApprovalStep.php`
- [ ] Remove `masterItems()` from `ApprovalRequest.php`
- [ ] Remove `approve()`, `reject()` methods from `ApprovalRequest.php`
- [ ] Remove `getCurrentApprover()`, `canApprove()` from `ApprovalRequest.php`

#### Controllers
- [ ] Remove `approve()`, `reject()` from `ApprovalRequestController`
- [ ] Update `store()` to use `items()->create()`
- [ ] Update `update()` to use items sync
- [ ] Update `show()` to load `items.steps`

#### Routes
- [ ] Remove `approval-requests/{id}/approve` route
- [ ] Remove `approval-requests/{id}/reject` route
- [ ] Add new per-item approval routes

#### Views
- [ ] Remove request-level approval forms
- [ ] Remove step progress (current_step/total_steps)
- [ ] Add per-item approval UI
- [ ] Update index to show item statuses

#### Tests
- [ ] Delete old approval flow tests
- [ ] Write new per-item approval tests

### 16.7 Migration Script (All-in-One)

Create: `database/migrations/2025_11_02_080000_cleanup_old_approval_system.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Drop old pivot table
        Schema::dropIfExists('approval_request_master_items');
        
        // 2. Drop old request-level steps
        Schema::dropIfExists('approval_steps');
        
        // 3. Clean approval_requests table
        Schema::table('approval_requests', function (Blueprint $table) {
            $table->dropColumn(['current_step', 'total_steps']);
        });
    }

    public function down(): void
    {
        // Cannot rollback - data structure changed fundamentally
        throw new Exception('Cannot rollback cleanup migration. Restore from backup if needed.');
    }
};
```

### 16.8 Verification Command

Create: `app/Console/Commands/VerifyApprovalMigration.php`

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ApprovalRequest;

class VerifyApprovalMigration extends Command
{
    protected $signature = 'approval:verify-migration';
    protected $description = 'Verify approval system migration completed successfully';

    public function handle()
    {
        $this->info('Verifying approval migration...');
        
        // Check all requests have items
        $requestsWithoutItems = ApprovalRequest::doesntHave('items')->count();
        if ($requestsWithoutItems > 0) {
            $this->error("❌ Found {$requestsWithoutItems} requests without items!");
            return 1;
        }
        
        // Check all items have steps
        $itemsWithoutSteps = \App\Models\ApprovalRequestItem::doesntHave('steps')->count();
        if ($itemsWithoutSteps > 0) {
            $this->error("❌ Found {$itemsWithoutSteps} items without steps!");
            return 1;
        }
        
        // Check old tables don't exist
        if (Schema::hasTable('approval_request_master_items')) {
            $this->warn("⚠️  Old pivot table still exists");
        }
        
        if (Schema::hasTable('approval_steps')) {
            $this->warn("⚠️  Old approval_steps table still exists");
        }
        
        $this->info('✅ Migration verification passed!');
        return 0;
    }
}
```

---

## 17. UPDATED TODO LIST (WITH CLEANUP)

### Phase 1: Foundation
- [x] Create new tables migrations
- [x] Create models (ApprovalItemStep, ApprovalRequestItem)
- [ ] Run migrations
- [ ] Backfill data from pivot

### Phase 2: Core Logic
- [ ] Create ApprovalItemApprovalController
- [ ] Update ApprovalRequestController (store/update)
- [ ] Add initializeItemSteps() helper
- [ ] **DELETE old approve/reject methods**

### Phase 3: Routes
- [ ] Add per-item approval routes
- [ ] **DELETE old request-level approval routes**

### Phase 4: Views
- [ ] Update show.blade.php with per-item UI
- [ ] **REMOVE request-level approval forms**
- [ ] **REMOVE step progress display**

### Phase 5: Cleanup (NEW)
- [ ] Run verification command
- [ ] Drop `approval_request_master_items` table
- [ ] Drop `approval_steps` table
- [ ] Remove unused columns from `approval_requests`
- [ ] Delete `ApprovalStep.php` model
- [ ] Remove old methods from `ApprovalRequest.php`

### Phase 6: Testing
- [ ] Test per-item approval flow
- [ ] Test item rejection
- [ ] Test request aggregation
- [ ] **DELETE old approval tests**

---

## 18. CONCLUSION

### Pre-Production Strategy: AGGRESSIVE CLEANUP

**Karena belum production**, kita akan:
- ✅ **HAPUS** table lama (pivot, approval_steps)
- ✅ **HAPUS** model lama (ApprovalStep)
- ✅ **HAPUS** method lama (approve/reject di request-level)
- ✅ **HAPUS** route lama
- ✅ **HAPUS** UI lama

**Benefits**:
- 🎯 Clean codebase (no legacy code)
- 🎯 No confusion (single source of truth)
- 🎯 Easier to maintain
- 🎯 Better performance (no unused tables)

**Migration Path**:
```
1. Create new system (items + item_steps)
2. Backfill data from old system
3. Verify migration successful
4. DROP old tables
5. DELETE old code
6. Test thoroughly
7. Deploy
```

---

**Document Version**: 2.0 (Updated for Pre-Production Cleanup)  
**Last Updated**: 2025-11-02  
**Author**: Development Team  
**Status**: Ready for Aggressive Implementation  
**Environment**: Development/Staging (Pre-Production)
