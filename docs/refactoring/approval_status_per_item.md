# Refactoring: Approval Status Per Item

## Context
Currently, the system maintains a `status` column on the `ApprovalRequest` model (parent) and also on `ApprovalRequestItem` (child).
The `ApprovalRequest` status is updated via `refreshStatus()` whenever an item is updated.
However, this creates a dependency where the parent status drives some logic (e.g., validation in Controller, visibility in Views), which causes issues when items have mixed statuses (e.g., one approved, one pending).

## Goal
Decouple the approval logic from `ApprovalRequest->status`. The status of an item should be the **sole source of truth** for its approval workflow. The `ApprovalRequest` status should either be removed or treated strictly as a read-only aggregate for high-level filtering, without affecting business logic.

## Changes

### 1. Database
-   (Optional) We could drop `status` from `approval_requests`, but for backward compatibility and ease of filtering "Open Requests", we might keep it or replace it with a computed scope.
-   **Decision**: We will ignore the `status` column in business logic. We will rely on `ApprovalRequestItem::status`.

### 2. Models

#### `App\Models\ApprovalRequest`
-   Remove `refreshStatus()` logic that enforces state transitions on the parent.
-   Remove `scopeStatus` that relies on the parent column (or update it to query items).
-   Remove `status` from `$fillable` (if we decide to stop writing to it).

#### `App\Models\ApprovalRequestItem`
-   Ensure `status` is the primary driver.
-   Ensure `scopePending`, `scopeApproved`, etc., are robust.

### 3. Controllers

#### `App\Http\Controllers\ApprovalRequestController`
-   **`approve` / `reject`**:
    -   Remove check: `if (!in_array($approvalRequest->status, ['pending', 'on progress']))`.
    -   Instead, check: `$item->status === 'pending'`.
    -   Remove call to `$approvalRequest->refreshStatus()`.
-   **`edit` / `update`**:
    -   Allow editing if the specific items being modified are editable (pending).
    -   Currently, `edit` works on the whole request. We should allow access if *at least one* item is pending, but in the UI, disable inputs for approved items.
    -   Remove check: `$approvalRequest->status !== 'pending'`.
-   **`destroy`**:
    -   Allow delete if *all* items are pending (or no items approved).

### 4. Views
-   **`index.blade.php`**:
    -   Update "Edit" button condition. instead of checking `$request->status == 'pending'`, check if the item is pending (since the table is flattened per item).
    -   `@if($row->itemData->status == 'pending' ...)`
-   **`show.blade.php`**:
    -   Ensure approval actions are visible based on `$item->status`.

## Logic Removal
-   The `refreshStatus` method in `ApprovalRequest` is the main target for removal.
-   The `status` column on `ApprovalRequest` will become a "legacy" field or a simple cached value, but not a control gate.

## Implementation Plan
1.  Modify `ApprovalRequest.php` to remove `refreshStatus`.
2.  Modify `ApprovalRequestController.php` to remove request-status checks.
3.  Modify `index.blade.php` to use item status for actions.
