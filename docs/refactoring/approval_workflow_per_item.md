# Design: Refactoring Approval Workflow to Item-Based Granularity

## 1. Overview
Currently, the system is in a hybrid state. While the database supports per-item steps (`approval_item_steps`), the application logic and UI still contain remnants of request-level approval. The goal of this refactoring is to complete the transition to a fully **Item-Based Approval System**, where each item in a request is processed, approved, or rejected independently.

## 2. Current State Analysis
*   **Database**: `approval_item_steps` table exists and is populated. `approval_request_items` has a `status` column.
*   **Models**: `ApprovalRequest` still contains legacy methods (`approve()`, `reject()`) that operate on a deprecated `current_step` column.
*   **Controllers**: `ApprovalItemApprovalController` handles the new per-item logic, but `ApprovalRequestController` still retains legacy request-level actions.
*   **Workflow**: Currently, a single `Workflow` is assigned to the `Request`, and its steps are copied to all items. This limits flexibility if different items need different approvers (e.g., based on Allocation Department).

## 3. Objectives
1.  **Eliminate Legacy Request-Level Logic**: Remove or refactor code that treats approval as a single action for the entire request.
2.  **Enable Context-Aware Approvers**: Allow items in the same request to have different approvers based on their specific attributes (e.g., Allocation Department).
3.  **UI Consistency**: Ensure the UI clearly reflects that approvals are per-item.

## 4. Technical Architecture

### 4.1. Database Changes
*   **Cleanup**: Remove `current_step` and `total_steps` columns from `approval_requests` (if not already done).
*   **Enhancement**: Ensure `approval_item_steps` supports a new approver type: `allocation_department_manager`.

### 4.2. Model Refactoring

#### `App\Models\ApprovalRequest`
*   **Remove**: `approve()`, `reject()`, `currentStep()`, `steps()`.
*   **Retain/Enhance**: `aggregateStatus()` (already exists in controller, move to Model) to update the Request status based on its Items.
    *   If *any* item is `rejected` -> Request is `rejected` (or `partially_rejected` if business logic allows).
    *   If *all* items are `approved` -> Request is `approved`.
    *   Otherwise -> `on progress` / `pending`.

#### `App\Models\ApprovalItemStep`
*   **New Approver Type**: Add `allocation_department_manager`.
    *   Logic: Look up the `allocation_department_id` from the `ApprovalRequestItem` and find its manager.
*   **Method**: Update `canApprove($userId)` to handle this new type.

### 4.3. Controller Refactoring

#### `ApprovalRequestController`
*   **Remove**: `approve()` and `reject()` actions that target the Request.
*   **Update**: `store()` method is already good (initializes item steps).
*   **Update**: `show()` method should strictly load item-based relations.

#### `ApprovalItemApprovalController`
*   **Verify**: Ensure `approve()` calls `aggregateStatus()` on the parent request after every action.

### 4.4. Workflow Logic
*   **Dynamic Step Generation**:
    *   When `initializeItemSteps` runs, if a step is defined as `allocation_department_manager`, it resolves the approver dynamically for that specific item.

## 5. Implementation Plan

### Step 1: Model Enhancements
1.  Modify `ApprovalItemStep.php`:
    *   Update `canApprove` to support `allocation_department_manager`.
    *   Update `getApprover` (if used for display) to resolve the allocation department manager.

### Step 2: Legacy Cleanup
1.  In `ApprovalRequest.php`, mark `approve`, `reject`, `currentStep` as `@deprecated` or remove them.
2.  In `ApprovalRequestController.php`, remove the request-level `approve` and `reject` methods.

### Step 3: Status Aggregation Logic
1.  Move `aggregateRequestStatus` from `ApprovalItemApprovalController` to `ApprovalRequest` model as a public method `refreshStatus()`.
2.  Ensure it handles edge cases (e.g., all items cancelled).

### Step 4: UI Updates
1.  **Show Page**: Ensure no "Approve Request" button exists.
2.  **Pending Approvals**: Verify the list shows *Items*, not Requests (already seems to be the case).

### Step 5: Verification
1.  Create a Request with 2 items:
    *   Item A: Allocated to Dept IT (Manager: User X).
    *   Item B: Allocated to Dept HR (Manager: User Y).
2.  Verify User X sees *only* Item A in "Pending Approvals".
3.  Verify User Y sees *only* Item B.
4.  Verify approving Item A does *not* approve Item B.
5.  Verify Request status updates only when *both* are processed.

## 6. Future Considerations
*   **Per-Item Workflow Selection**: In the future, we might allow selecting a specific Workflow *per item* during creation, rather than one Workflow for the whole Request. For now, the "Dynamic Approver" approach solves the immediate need for flexibility.
