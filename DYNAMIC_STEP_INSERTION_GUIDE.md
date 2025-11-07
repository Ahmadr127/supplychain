# 📘 Dynamic Step Insertion - Implementation Guide

## 🎯 Overview

Fitur **Dynamic Step Insertion** memungkinkan approver tertentu untuk menambahkan step approval baru secara dinamis selama proses approval berlangsung.

### Use Case Example:
```
Original Flow:
Step 1: Manager Department → Step 2: Direktur

Manager membutuhkan input dari Keuangan:
Step 1: Manager Department → Step 2: Manager Keuangan (INSERTED) → Step 3: Direktur
```

---

## 🏗️ Architecture

### Permission-Based System
- **Tidak semua step bisa insert step baru**
- Hanya step dengan `can_insert_step = true` yang memiliki akses
- Configured di workflow template (JSON)

### Database Schema

**New Columns in `approval_item_steps`:**
```sql
can_insert_step         BOOLEAN      -- Permission to insert new steps
is_dynamic              BOOLEAN      -- Flag for dynamically inserted steps
inserted_by             BIGINT       -- User who inserted this step
inserted_at             TIMESTAMP    -- When step was inserted
insertion_reason        TEXT         -- Why this step was added
required_action         VARCHAR(100) -- Action code (optional)
```

---

## 📝 Configuration

### 1. Configure Workflow Template

Edit workflow di database atau admin panel:

```json
{
  "workflow_steps": [
    {
      "name": "Manager Department",
      "approver_type": "requester_department_manager",
      "can_insert_step": true    // ← Manager bisa insert step
    },
    {
      "name": "Direktur",
      "approver_type": "role",
      "approver_role_id": 3,
      "can_insert_step": false   // ← Direktur tidak bisa insert
    }
  ]
}
```

### 2. Run Migration

```bash
php artisan migrate
```

Migration file: `2025_11_06_000000_add_dynamic_step_insertion_support.php`

---

## 💻 How It Works

### Step Insertion Flow

1. **User opens approval form** → Sees "Tambah Step" button (if `can_insert_step = true`)
2. **User clicks button** → Modal opens with form
3. **User fills form:**
   - Step name
   - Approver type (role/user/department)
   - Reason for insertion
   - Optional: required action code
   - Optional: Allow inserted step to also insert steps
4. **Submit** → Backend validates and inserts step
5. **Automatic renumbering:**
   ```
   Before: Step 1 → Step 2 → Step 3
   After:  Step 1 → Step 2 (NEW) → Step 3 → Step 4
   ```

### Authorization Rules

✅ **Can Insert Step If:**
- Current step has `can_insert_step = true`
- User can approve current step (`canApprove()` returns true)
- Item status is `pending` or `on progress`

❌ **Cannot Insert Step If:**
- Step doesn't have permission
- User is not the approver
- Item already `approved` or `rejected`

---

## 🎨 UI Components

### Button Location
- **Approval Form Header** (next to "Approval Form" title)
- Yellow button: "Tambah Step"
- Only visible if `can_insert_step = true`

### Modal Form Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| Nama Step | Text | Yes | Name of new step |
| Tipe Approver | Select | Yes | role/user/department_manager/etc |
| Role/User/Dept | Select | Conditional | Based on approver type |
| Alasan | Textarea | Yes | Min 10 characters |
| Aksi Diperlukan | Text | No | Action code for tracking |
| Can Insert Step | Checkbox | No | Allow inserted step to insert more |

---

## 🔧 API Endpoints

### Insert Step
```
POST /approval-items/{item}/insert-step
```

**Parameters:**
```php
[
    'step_name' => 'Manager Keuangan - Upload Dokumen',
    'approver_type' => 'role',
    'approver_role_id' => 5,
    'insertion_reason' => 'Membutuhkan verifikasi dokumen keuangan',
    'required_action' => 'upload_document',
    'can_insert_step' => false
]
```

**Response:**
- Success: Redirect to show page with success message
- Error: Back with error message

### Delete Step (Admin Only)
```
DELETE /approval-steps/{step}/delete
```

**Authorization:**
- Only dynamic steps can be deleted
- Only if status is still `pending`
- Only by inserter or admin

---

## 📊 Database Queries

### Get All Steps for Item (Including Dynamic)
```php
$steps = ApprovalItemStep::where('approval_request_id', $requestId)
    ->where('master_item_id', $itemId)
    ->orderBy('step_number')
    ->with(['approver', 'inserter'])
    ->get();
```

### Check if Step Can Insert
```php
$currentStep = $item->getCurrentPendingStep();
if ($currentStep && $currentStep->can_insert_step) {
    // Show insert button
}
```

### Filter Dynamic Steps Only
```php
$dynamicSteps = ApprovalItemStep::where('is_dynamic', true)
    ->where('approval_request_id', $requestId)
    ->get();
```

---

## 🎯 Visual Indicators

### Dynamic Step Badge
```blade
@if($step->is_dynamic)
<span class="ml-2 text-xs bg-yellow-200 text-yellow-800 px-2 py-0.5 rounded">
    DITAMBAHKAN
</span>
@endif
```

### Audit Trail
```blade
@if($step->is_dynamic)
<div class="text-xs text-gray-600 mt-1">
    Ditambahkan oleh {{ $step->inserter->name }} 
    pada {{ $step->inserted_at->format('d/m/Y H:i') }}
    <br>Alasan: {{ $step->insertion_reason }}
</div>
@endif
```

---

## 🔒 Security Considerations

### Validation Rules
1. ✅ Step name required (max 255 chars)
2. ✅ Approver type must be valid enum
3. ✅ Approver ID/Role/Dept must exist
4. ✅ Reason required (min 10 chars)
5. ✅ User must have permission to insert

### Authorization Checks
```php
// In ApprovalItemStepController::insertStep()

// 1. Check step has permission
if (!$currentStep->can_insert_step) {
    return back()->withErrors(['error' => 'No permission']);
}

// 2. Check user can approve current step
if (!$currentStep->canApprove(auth()->id())) {
    return back()->withErrors(['error' => 'Not authorized']);
}

// 3. Check item not finalized
if (in_array($item->status, ['approved', 'rejected'])) {
    return back()->withErrors(['error' => 'Item already processed']);
}
```

### Audit Logging
```php
Log::info('Dynamic step inserted', [
    'approval_request_id' => $item->approval_request_id,
    'item_id' => $item->id,
    'new_step_number' => $newStep->step_number,
    'new_step_name' => $newStep->step_name,
    'inserted_by' => auth()->id(),
    'reason' => $validated['insertion_reason'],
]);
```

---

## 🧪 Testing Scenarios

### Test Case 1: Successful Insertion
1. Login as Manager
2. Open pending approval with `can_insert_step = true`
3. Click "Tambah Step"
4. Fill form with valid data
5. Submit
6. **Expected:** New step inserted, steps renumbered, redirect to show page

### Test Case 2: Unauthorized Insertion
1. Login as regular user
2. Try to access insert step endpoint directly
3. **Expected:** Error "Not authorized"

### Test Case 3: Insert After Insert
1. Manager inserts "Keuangan" step with `can_insert_step = true`
2. Manager approves current step
3. Keuangan receives approval task
4. Keuangan can now insert another step
5. **Expected:** Nested insertion works correctly

### Test Case 4: Delete Dynamic Step
1. Admin views dynamic step
2. Click delete (if still pending)
3. **Expected:** Step deleted, remaining steps renumbered

---

## 📈 Performance Considerations

### Renumbering Efficiency
```php
// Single UPDATE query with increment
ApprovalItemStep::where('approval_request_id', $requestId)
    ->where('master_item_id', $itemId)
    ->where('step_number', '>', $afterStepNumber)
    ->increment('step_number');
```

### Eager Loading
```php
// Load steps with relationships
$item->load([
    'steps.approver',
    'steps.inserter'
]);
```

---

## 🚀 Deployment Checklist

- [ ] Run migration: `php artisan migrate`
- [ ] Update workflow templates with `can_insert_step` field
- [ ] Test insertion flow in staging
- [ ] Verify renumbering logic
- [ ] Check authorization rules
- [ ] Test UI responsiveness
- [ ] Review audit logs
- [ ] Update user documentation
- [ ] Train users on new feature

---

## 📞 Support

**Questions?**
- Check logs: `storage/logs/laravel.log`
- Search for: `Dynamic step inserted` or `Failed to insert step`

**Common Issues:**

1. **Button not showing:**
   - Check `can_insert_step` in workflow JSON
   - Verify user can approve current step

2. **Validation errors:**
   - Ensure approver exists in database
   - Check reason length (min 10 chars)

3. **Renumbering issues:**
   - Check database transaction logs
   - Verify no concurrent insertions

---

## 📚 Related Files

**Controllers:**
- `app/Http/Controllers/ApprovalItemStepController.php`
- `app/Http/Controllers/ApprovalRequestController.php`

**Models:**
- `app/Models/ApprovalItemStep.php`
- `app/Models/ApprovalWorkflow.php`

**Views:**
- `resources/views/components/item-workflow-approval.blade.php`

**Migrations:**
- `database/migrations/2025_11_06_000000_add_dynamic_step_insertion_support.php`

**Routes:**
- `routes/web.php` (lines 112-114)

---

## 🎉 Summary

Fitur Dynamic Step Insertion memberikan **flexibility** dalam approval workflow tanpa harus mengubah template workflow. Approver yang memiliki permission dapat menambahkan step sesuai kebutuhan bisnis secara real-time.

**Key Benefits:**
- ✅ Flexible approval flow
- ✅ Permission-based control
- ✅ Full audit trail
- ✅ Automatic renumbering
- ✅ Nested insertion support
- ✅ Clean UI/UX

---

**Last Updated:** 2025-11-06  
**Version:** 1.0.0
