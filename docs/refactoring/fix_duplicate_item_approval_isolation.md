# Fix Duplicate Item Approval Isolation

## Problem Description
Currently, the approval workflow system identifies items within a request using `approval_request_id` and `master_item_id`. This creates a critical issue when a request contains multiple instances of the same item (e.g., two separate lines for "Laptop").

**Symptoms:**
1.  **State Leakage:** Approving one instance of an item updates the approval steps for *all* instances of that item in the request, because they share the same `master_item_id`.
2.  **Merged Logs:** The approval history (logs) for duplicate items are merged into a single list, making it impossible to distinguish which action belongs to which item line.
3.  **"Item Ditolak" Leak:** If one instance is rejected, the "Item Ditolak" banner appears for *all* instances of that item, blocking approval for the non-rejected instances.

**Root Cause:**
The `approval_item_steps` table lacks a direct reference to the specific `approval_request_items` row (the specific line item). It relies on `master_item_id`, which is not unique per line item.

## Proposed Solution
We need to isolate approval steps to the specific `ApprovalRequestItem` instance by introducing a direct foreign key relationship.

### 1. Database Schema Changes
Add `approval_request_item_id` to the `approval_item_steps` table.

```php
Schema::table('approval_item_steps', function (Blueprint $table) {
    $table->foreignId('approval_request_item_id')
          ->nullable() // Nullable for migration, but should be filled
          ->after('approval_request_id')
          ->constrained('approval_request_items')
          ->onDelete('cascade');
});
```

### 2. Model Relationship Updates

**`App\Models\ApprovalItemStep`**
Update the model to belong to a specific request item.

```php
public function requestItem()
{
    return $this->belongsTo(ApprovalRequestItem::class, 'approval_request_item_id');
}
```

**`App\Models\ApprovalRequestItem`**
Update the relationship to fetch steps strictly belonging to this line item.

```php
public function steps()
{
    return $this->hasMany(ApprovalItemStep::class, 'approval_request_item_id')
                ->orderBy('step_number');
}

public function currentStep()
{
    return $this->hasOne(ApprovalItemStep::class, 'approval_request_item_id')
                ->where('status', 'pending')
                ->orderBy('step_number');
}

// Helper to get current pending step
public function getCurrentPendingStep()
{
    return $this->steps()
        ->where('status', 'pending')
        ->first();
}
```

### 3. Controller Logic Updates

**`App\Http\Controllers\ApprovalRequestController`**

Update `initializeItemSteps` to accept the item instance and link steps to it.

```php
// Old Signature
// private function initializeItemSteps(ApprovalRequest $approvalRequest, int $masterItemId): void

// New Signature
private function initializeItemSteps(ApprovalRequest $approvalRequest, ApprovalRequestItem $item): void
{
    // ... resolution logic ...
    
    foreach ($workflowSteps as $step) {
        \App\Models\ApprovalItemStep::create([
            'approval_request_id' => $approvalRequest->id,
            'approval_request_item_id' => $item->id, // <--- NEW LINK
            'master_item_id' => $item->master_item_id, // Keep for backward compat if needed, or remove
            // ... other fields ...
        ]);
    }
}
```

Update `store` and `update` methods to pass the `$item` object to `initializeItemSteps`.

**`App\Http\Controllers\ApprovalItemApprovalController`**

Update approval/rejection logic to rely on the relationship or `approval_request_item_id`.

```php
// In approve() method
// $currentStep = $item->getCurrentPendingStep(); 
// Ensure getCurrentPendingStep uses approval_request_item_id
```

### 4. View Updates

**`resources/views/approval-requests/show.blade.php`**
Update the loop that displays approval history to use the relation.

```php
// Old
// $itemSteps = \App\Models\ApprovalItemStep::where('approval_request_id', $approvalRequest->id)
//    ->where('master_item_id', $masterItem->id)...

// New
$itemSteps = $item->steps()->with('approver')->get();
```

**`resources/views/components/item-workflow-approval.blade.php`**
Update the step fetching logic.

```php
// Old
// $itemSteps = \App\Models\ApprovalItemStep::where('approval_request_id', $item->approval_request_id)
//    ->where('master_item_id', $item->master_item_id)...

// New
$itemSteps = $item->steps()->with('approver')->get();
```

### 5. Migration Strategy (Optional / For Existing Data)
For existing data where `approval_request_item_id` is null:
1.  Run a script to iterate over `ApprovalRequestItem`.
2.  Find matching `ApprovalItemStep` rows based on `approval_request_id` and `master_item_id`.
3.  **Heuristic:** If there are duplicate items, assign steps sequentially or based on creation time. (This is risky for existing corrupted data, but necessary if we want to fix old records).
4.  For the purpose of this fix, we prioritize *new* requests working correctly.

## Implementation Steps
1.  Create migration to add `approval_request_item_id`.
2.  Update Models (`ApprovalItemStep`, `ApprovalRequestItem`).
3.  Update `ApprovalRequestController` to populate the new column.
4.  Update `ApprovalItemApprovalController` to use the new column/relationship.
5.  Update Views (`show.blade.php`, `item-workflow-approval.blade.php`).
