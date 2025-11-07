# 📘 Seeder Update Guide - Dynamic Step Insertion

## 🎯 Overview

Seeder `ApprovalWorkflowSeeder` telah diupdate untuk include konfigurasi **dynamic step insertion** dengan pre-configured templates.

---

## 🔄 What's Changed

### Before (Old Seeder)
```php
[
    'name' => 'Manager',
    'approver_type' => 'any_department_manager',
    'description' => 'Manager unit input harga dan approve'
]
```

### After (New Seeder)
```php
[
    'name' => 'Manager',
    'approver_type' => 'any_department_manager',
    'description' => 'Manager unit input harga dan approve',
    // NEW: Dynamic step insertion
    'can_insert_step' => true,
    'insert_step_template' => [
        'name' => 'Manager Keuangan - Verifikasi Budget',
        'approver_type' => 'role',
        'approver_role_id' => $managerKeuanganRole->id,
        'required_action' => 'verify_budget',
        'can_insert_step' => false
    ]
]
```

---

## 📋 Updated Workflows

### 1. Workflow Barang Medis

**Step 1: Manager** ✅ Can Insert Step
- Template: "Manager Keuangan - Verifikasi Budget"
- Target: Role Manager Keuangan
- Action: verify_budget

**Step 2: Keuangan** ❌ Cannot Insert
- Conditional step (if total >= 100jt)

**Step 3: Direktur RS** ❌ Cannot Insert
- Final approval

### 2. Workflow Barang Non Medis

**Step 1: Manager** ✅ Can Insert Step
- Template: "Manager Keuangan - Verifikasi Budget"
- Target: Role Manager Keuangan
- Action: verify_budget

**Step 2: Keuangan** ❌ Cannot Insert
- Conditional step (if total >= 100jt)

**Step 3: Direktur RS** ❌ Cannot Insert
- Final approval

### 3. Standard Approval Workflow

**Step 1: Manager** ✅ Can Insert Step
- Template: "Manager Keuangan - Verifikasi Budget"
- Target: Role Manager Keuangan
- Action: verify_budget

**Step 2: Keuangan** ❌ Cannot Insert
- Conditional step (if total >= 100jt)

**Step 3: Direktur RS** ❌ Cannot Insert
- Final approval

---

## 🚀 How to Run

### Option 1: Fresh Database (Recommended for Development)

```bash
# Drop all tables and re-seed everything
php artisan migrate:fresh --seed
```

**⚠️ WARNING:** This will **delete all data**!

### Option 2: Run Specific Seeder Only

```bash
# Run only ApprovalWorkflowSeeder
php artisan db:seed --class=ApprovalWorkflowSeeder
```

**Note:** This uses `firstOrCreate()`, so:
- ✅ Existing workflows will be **updated** with new fields
- ✅ New workflows will be **created** if not exist
- ✅ Safe to run multiple times

### Option 3: Manual Update via Database

If you prefer manual update:

```sql
-- Update existing workflows
UPDATE approval_workflows 
SET workflow_steps = JSON_SET(
    workflow_steps,
    '$[0].can_insert_step', true,
    '$[0].insert_step_template', JSON_OBJECT(
        'name', 'Manager Keuangan - Verifikasi Budget',
        'approver_type', 'role',
        'approver_role_id', (SELECT id FROM roles WHERE name = 'manager_keuangan'),
        'required_action', 'verify_budget',
        'can_insert_step', false
    )
)
WHERE type IN ('medical', 'non_medical', 'standard');
```

---

## ✅ Verification

### 1. Check Database

```sql
-- View workflow steps with new fields
SELECT 
    id,
    name,
    type,
    JSON_EXTRACT(workflow_steps, '$[0].can_insert_step') as manager_can_insert,
    JSON_EXTRACT(workflow_steps, '$[0].insert_step_template.name') as template_name
FROM approval_workflows;
```

**Expected Output:**
```
+----+---------------------------+--------------+--------------------+----------------------------------------+
| id | name                      | type         | manager_can_insert | template_name                          |
+----+---------------------------+--------------+--------------------+----------------------------------------+
|  1 | Workflow Barang Medis     | medical      | 1                  | "Manager Keuangan - Verifikasi Budget" |
|  2 | Workflow Barang Non Medis | non_medical  | 1                  | "Manager Keuangan - Verifikasi Budget" |
|  3 | Standard Approval Workflow| standard     | 1                  | "Manager Keuangan - Verifikasi Budget" |
+----+---------------------------+--------------+--------------------+----------------------------------------+
```

### 2. Check via Tinker

```bash
php artisan tinker
```

```php
// Get workflow
$workflow = \App\Models\ApprovalWorkflow::where('type', 'medical')->first();

// Check steps
$workflow->steps->map(function($step) {
    return [
        'name' => $step->step_name,
        'can_insert' => $step->can_insert_step,
        'template' => $step->insert_step_template
    ];
});
```

**Expected Output:**
```php
[
    [
        "name" => "Manager",
        "can_insert" => true,
        "template" => [
            "name" => "Manager Keuangan - Verifikasi Budget",
            "approver_type" => "role",
            "approver_role_id" => 5,
            "required_action" => "verify_budget",
            "can_insert_step" => false
        ]
    ],
    [
        "name" => "Keuangan",
        "can_insert" => false,
        "template" => null
    ],
    [
        "name" => "Direktur RS",
        "can_insert" => false,
        "template" => null
    ]
]
```

### 3. Check via UI

1. Login as Manager
2. Create new approval request
3. Go to pending approvals
4. Open approval form
5. **Should see:** ☑️ Checkbox "Tambahkan step: Manager Keuangan - Verifikasi Budget"

---

## 🔧 Customization

### Change Template Name

Edit seeder line 157:
```php
'name' => 'Manager Keuangan - Verifikasi Budget',
// Change to:
'name' => 'Your Custom Step Name',
```

### Change Target Approver

Edit seeder line 159:
```php
'approver_role_id' => $managerKeuanganRole->id,
// Change to different role:
'approver_role_id' => $yourCustomRole->id,
```

### Change Required Action

Edit seeder line 160:
```php
'required_action' => 'verify_budget',
// Change to:
'required_action' => 'upload_document',
```

### Allow Nested Insertion

Edit seeder line 161:
```php
'can_insert_step' => false
// Change to:
'can_insert_step' => true  // Inserted step can also insert
```

---

## 📊 Seeder Output

When you run the seeder, you'll see:

```
Approval workflows seeded successfully!
New workflow structure with dynamic step insertion:
  Step 1: Manager Unit (input harga) - CAN INSERT STEP
          └─ Quick insert: Manager Keuangan - Verifikasi Budget
  Step 2: Keuangan (upload FS, conditional if total >= 100jt)
  Step 3: Direktur RS (final approval)
```

---

## 🐛 Troubleshooting

### Issue 1: Role Not Found

**Error:**
```
Trying to get property 'id' of non-object
```

**Solution:**
Ensure roles exist in database:
```bash
php artisan db:seed --class=RoleSeeder
```

### Issue 2: Workflow Not Updated

**Problem:** Old workflow still showing

**Solution:**
```bash
# Delete old workflows
php artisan tinker
>>> \App\Models\ApprovalWorkflow::truncate();
>>> exit

# Re-seed
php artisan db:seed --class=ApprovalWorkflowSeeder
```

### Issue 3: Template Not Showing in UI

**Check:**
1. ✅ Migration ran successfully
2. ✅ Seeder ran successfully
3. ✅ Workflow has `can_insert_step = true`
4. ✅ Template is not null
5. ✅ User is Manager role

**Debug:**
```php
// In tinker
$workflow = \App\Models\ApprovalWorkflow::first();
dd($workflow->workflow_steps);
```

---

## 📝 Best Practices

### 1. Backup Before Running

```bash
# Backup database
mysqldump -u root -p your_database > backup_$(date +%Y%m%d).sql
```

### 2. Test in Development First

```bash
# Run in dev environment
APP_ENV=local php artisan db:seed --class=ApprovalWorkflowSeeder
```

### 3. Version Control

```bash
# Commit seeder changes
git add database/seeders/ApprovalWorkflowSeeder.php
git commit -m "feat: add dynamic step insertion to workflow seeder"
```

---

## 🎉 Summary

**What Changed:**
- ✅ Added `can_insert_step` field to Manager step
- ✅ Added `insert_step_template` with pre-configured data
- ✅ Applied to all 3 workflows (Medis, Non Medis, Standard)
- ✅ Updated seeder output message

**How to Apply:**
```bash
php artisan db:seed --class=ApprovalWorkflowSeeder
```

**Result:**
- Manager can now quick insert "Manager Keuangan - Verifikasi Budget" step
- Just checkbox, no form filling required
- Template automatically configured

**Ready to use!** 🚀

---

**Last Updated:** 2025-11-06  
**Version:** 1.0.0
