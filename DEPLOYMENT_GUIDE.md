# 🚀 Deployment Guide: Per-Item Approval System

## 📋 Overview

This guide covers the deployment of the new **per-item approval system** that replaces the old request-level approval flow.

**Key Changes:**
- ✅ Approval now happens at **item level** instead of request level
- ✅ Each item has independent workflow steps
- ✅ Items can be approved/rejected individually
- ✅ PurchasingItem created immediately when item approved
- ✅ Request status aggregated from item statuses

---

## ⚠️ PRE-DEPLOYMENT CHECKLIST

### 1. Backup Database
```bash
# Create backup before deployment
mysqldump -u username -p database_name > backup_before_per_item_approval.sql
```

### 2. Review Changes
- Read `MIGRATION_PER_ITEM_APPROVAL.md` for architecture details
- Review `IMPLEMENTATION_STATUS.md` for completion status

### 3. Test Environment
- Deploy to staging/test environment first
- Verify all functionality works
- Test approval flow end-to-end

---

## 🔧 DEPLOYMENT STEPS

### Step 1: Pull Latest Code
```bash
git pull origin main
```

### Step 2: Install Dependencies (if any new)
```bash
composer install
npm install && npm run build
```

### Step 3: Run Migrations (Create New Tables)
```bash
# Run migrations to create new tables
php artisan migrate

# This will create:
# - approval_request_items (replaces pivot)
# - approval_item_steps (per-item workflow)
# - Backfill data from old pivot table
```

**Expected Output:**
```
Migrating: 2025_11_02_000000_create_approval_request_items_table
Migrated:  2025_11_02_000000_create_approval_request_items_table

Migrating: 2025_11_02_000001_backfill_approval_request_items
Migrated:  2025_11_02_000001_backfill_approval_request_items

Migrating: 2025_11_02_060000_create_approval_item_steps_table
Migrated:  2025_11_02_060000_create_approval_item_steps_table
```

### Step 4: Initialize Item Steps
```bash
# Initialize approval steps for all existing items
php artisan approval:initialize-item-steps

# Options:
# --request-id=123  : Initialize for specific request only
# --force           : Re-initialize even if steps exist
```

**Expected Output:**
```
🚀 Initializing item approval steps...
Found 50 approval request(s) to process.

📋 Processing Request #1 - REQ-2024-001
  ✅ Created 3 steps for item #1
  ✅ Created 3 steps for item #2

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✅ Initialization complete!
   Items processed: 100
   Items skipped: 0
   Total steps created: 300
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

### Step 5: Verify Migration
```bash
# Verify that migration completed successfully
php artisan approval:verify-migration
```

**Expected Output:**
```
🔍 Verifying approval system migration...

1️⃣  Checking new tables...
   ✅ Table approval_request_items exists
   ✅ Table approval_item_steps exists

2️⃣  Checking all requests have items...
   ✅ All requests have items

3️⃣  Checking all items have approval steps...
   ✅ All items have approval steps

4️⃣  Checking old tables...
   ⚠️  Old pivot table approval_request_master_items still exists
      Contains 250 record(s)
      💡 Run cleanup migration to drop this table

5️⃣  Checking data integrity...
   📊 Total items: 100
   📊 Total steps: 300
   ✅ No orphaned steps found

6️⃣  Checking status consistency...
   ✅ Approved items have consistent step statuses
   ✅ Rejected items have consistent step statuses

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
⚠️  Migration verification PASSED with 2 warning(s)
   Core functionality is working, but cleanup recommended.
```

### Step 6: Test Functionality
**Before proceeding with cleanup, test:**

1. **Create New Request**
   - Add items
   - Submit
   - Verify items and steps created

2. **Approval Flow**
   - Login as approver
   - View pending approvals
   - Approve/reject items
   - Verify step progression

3. **Status Aggregation**
   - Approve all items → Request approved
   - Reject one item → Request rejected

4. **Purchasing Integration**
   - Item approved → PurchasingItem created
   - Verify in purchasing module

### Step 7: Run Cleanup Migrations (AFTER VERIFICATION)
```bash
# ⚠️ ONLY run this after verifying everything works!
# This will DROP old tables permanently

php artisan migrate

# This will:
# - Drop approval_request_master_items (old pivot)
# - Drop approval_steps (old request-level steps)
# - Remove current_step, total_steps columns from approval_requests
```

**Expected Output:**
```
Migrating: 2025_11_02_070000_drop_approval_request_master_items
Migrated:  2025_11_02_070000_drop_approval_request_master_items

Migrating: 2025_11_02_070001_drop_approval_steps
Migrated:  2025_11_02_070001_drop_approval_steps

Migrating: 2025_11_02_070002_clean_approval_requests_columns
Migrated:  2025_11_02_070002_clean_approval_requests_columns
```

### Step 8: Final Verification
```bash
# Run verification again to confirm cleanup
php artisan approval:verify-migration
```

**Expected Output:**
```
✅ Migration verification PASSED!
   All checks completed successfully.
```

### Step 9: Clear Cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## 🧪 TESTING CHECKLIST

### Functional Tests
- [ ] Create new approval request with multiple items
- [ ] Edit existing request (items should be editable)
- [ ] View request detail (per-item steps displayed)
- [ ] Approve item at step 1 (advances to step 2)
- [ ] Approve item at last step (item marked approved)
- [ ] Reject item (item and request marked rejected)
- [ ] All items approved (request marked approved)
- [ ] PurchasingItem created when item approved
- [ ] Form extra data (FS form) still works
- [ ] FS document upload (per-item and global) works
- [ ] Notifications show success/error messages

### Authorization Tests
- [ ] Only eligible approver can approve/reject
- [ ] Non-approver cannot see approve/reject forms
- [ ] Approver can only approve current pending step

### Data Integrity Tests
- [ ] Item status consistent with step statuses
- [ ] Request status aggregated correctly from items
- [ ] No orphaned steps or items
- [ ] Purchasing items linked correctly

---

## 🔄 ROLLBACK PROCEDURE

### If Issues Found BEFORE Cleanup Migrations

```bash
# 1. Rollback new tables
php artisan migrate:rollback --step=3

# 2. Old system will still work (pivot table intact)

# 3. Fix issues and redeploy
```

### If Issues Found AFTER Cleanup Migrations

```bash
# ⚠️ Cannot rollback automatically!
# Must restore from database backup

# 1. Restore database
mysql -u username -p database_name < backup_before_per_item_approval.sql

# 2. Rollback code changes
git revert <commit-hash>

# 3. Clear cache
php artisan cache:clear
```

---

## 📊 MONITORING

### Key Metrics to Monitor

1. **Approval Performance**
   ```sql
   -- Average time to approve items
   SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, approved_at)) as avg_hours
   FROM approval_request_items
   WHERE status = 'approved';
   ```

2. **Rejection Rate**
   ```sql
   -- Percentage of rejected items
   SELECT 
       COUNT(CASE WHEN status = 'rejected' THEN 1 END) * 100.0 / COUNT(*) as rejection_rate
   FROM approval_request_items;
   ```

3. **Step Bottlenecks**
   ```sql
   -- Steps taking longest time
   SELECT step_name, AVG(TIMESTAMPDIFF(HOUR, created_at, approved_at)) as avg_hours
   FROM approval_item_steps
   WHERE status = 'approved'
   GROUP BY step_name
   ORDER BY avg_hours DESC;
   ```

### Error Monitoring

Check logs for:
- `Item approval failed` - Transaction errors
- `Item rejection failed` - Validation errors
- `No workflow found` - Configuration issues

```bash
tail -f storage/logs/laravel.log | grep -i "approval"
```

---

## 🆘 TROUBLESHOOTING

### Issue: Items without steps

**Symptom:** Verification shows items without steps

**Solution:**
```bash
php artisan approval:initialize-item-steps --force
```

### Issue: Orphaned steps

**Symptom:** Steps exist but no matching request

**Solution:**
```sql
-- Find orphaned steps
SELECT * FROM approval_item_steps 
WHERE approval_request_id NOT IN (SELECT id FROM approval_requests);

-- Delete orphaned steps
DELETE FROM approval_item_steps 
WHERE approval_request_id NOT IN (SELECT id FROM approval_requests);
```

### Issue: Status inconsistency

**Symptom:** Item approved but has pending steps

**Solution:**
```php
// Run in tinker
php artisan tinker

// Fix specific item
$item = ApprovalRequestItem::find(123);
$allApproved = $item->steps->every(fn($s) => $s->status === 'approved');
if ($allApproved) {
    $item->update(['status' => 'approved']);
}
```

### Issue: Cannot approve item

**Symptom:** Approve button not showing

**Check:**
1. User has `approval` permission
2. Current step's `canApprove()` returns true
3. Item status is `pending` or `on progress`
4. Step status is `pending`

---

## 📝 POST-DEPLOYMENT TASKS

### 1. Update Documentation
- [ ] Update user manual with new approval flow
- [ ] Create training materials for approvers
- [ ] Document new permission requirements

### 2. User Communication
- [ ] Notify users about new approval system
- [ ] Explain per-item approval benefits
- [ ] Provide support contact

### 3. Performance Optimization
- [ ] Monitor query performance
- [ ] Add indexes if needed
- [ ] Optimize eager loading

### 4. Cleanup
- [ ] Remove old code comments
- [ ] Archive old documentation
- [ ] Update API documentation

---

## 📞 SUPPORT

**Issues or Questions?**
- Check `MIGRATION_PER_ITEM_APPROVAL.md` for architecture details
- Review `IMPLEMENTATION_STATUS.md` for feature status
- Check logs: `storage/logs/laravel.log`

**Emergency Rollback:**
- Restore from backup: `backup_before_per_item_approval.sql`
- Contact development team

---

**Deployment Date:** _________________  
**Deployed By:** _________________  
**Verification Status:** ⬜ Passed ⬜ Failed  
**Cleanup Completed:** ⬜ Yes ⬜ No  

---

**Version:** 1.0  
**Last Updated:** 2025-11-02  
**Status:** Ready for Production Deployment
