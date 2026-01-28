# Refactoring Status: Approval Workflow Per Item

## Completed Actions
- [x] **Model Enhancements**: Updated `ApprovalItemStep` to support `allocation_department_manager`.
- [x] **Legacy Cleanup**: Deprecated request-level methods in `ApprovalRequest` and removed logic from `ApprovalRequestController`.
- [x] **Status Aggregation**: Moved `aggregateRequestStatus` to `ApprovalRequest::refreshStatus()` and updated controllers to use it.
- [x] **UI Verification**: Confirmed `show.blade.php` uses per-item approval components.

## Next Steps
- [ ] **Testing**: Verify the flow with a request containing items for different allocation departments.
- [ ] **Cleanup**: Eventually remove the deprecated methods from `ApprovalRequest` model.
