# 📘 Quick Insert Step - User Guide

## 🎯 Overview

**Quick Insert Step** adalah fitur yang memungkinkan approver menambahkan step approval tambahan **hanya dengan centang checkbox**, tanpa perlu mengisi form lengkap. Step yang akan ditambahkan sudah **pre-configured** di workflow template.

---

## 💡 Use Case

### Skenario: Manager Butuh Verifikasi Keuangan

**Workflow Normal:**
```
Step 1: Manager Department → Step 2: Direktur
```

**Dengan Quick Insert:**
```
Manager melihat checkbox:
☑️ "Tambahkan step: Manager Keuangan - Verifikasi Dokumen"

Setelah approve:
Step 1: Manager (✓ approved) 
  → Step 2: Manager Keuangan (🆕 inserted) 
  → Step 3: Direktur
```

---

## 🔧 Configuration (Admin)

### 1. Edit Workflow Template

Di halaman **Approval Workflows**, edit workflow dan configure step:

```json
{
  "workflow_steps": [
    {
      "name": "Manager Department",
      "approver_type": "requester_department_manager",
      "can_insert_step": true,
      "insert_step_template": {
        "name": "Manager Keuangan - Verifikasi Dokumen",
        "approver_type": "role",
        "approver_role_id": 5,
        "required_action": "upload_document",
        "can_insert_step": false
      }
    }
  ]
}
```

### 2. Template Structure

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `name` | String | Yes | Nama step yang akan ditambahkan |
| `approver_type` | Enum | Yes | user/role/department_manager/etc |
| `approver_id` | Integer | Conditional | ID user (jika type=user) |
| `approver_role_id` | Integer | Conditional | ID role (jika type=role) |
| `approver_department_id` | Integer | Conditional | ID dept (jika type=department_manager) |
| `required_action` | String | No | Kode aksi (upload_document, etc) |
| `can_insert_step` | Boolean | No | Inserted step bisa insert lagi? |

---

## 👤 User Experience

### Untuk Approver

1. **Buka approval form**
2. **Lihat checkbox** (jika template configured):
   ```
   ☑️ Tambahkan step: Manager Keuangan - Verifikasi Dokumen
      Centang jika diperlukan verifikasi tambahan dari role Manager Keuangan
   ```
3. **Centang checkbox** jika diperlukan
4. **Klik Approve**
5. **Done!** Step baru otomatis ditambahkan

### Visual Indicator

**Checkbox muncul dengan:**
- 🟨 Background kuning
- ℹ️ Informasi approver target
- ✓ Simple checkbox (no form fields)

---

## 🔄 Flow Comparison

### Traditional Insert (Manual Form)

```
1. Click "Tambah Step" button
2. Fill form:
   - Step name
   - Approver type
   - Select approver
   - Reason (min 10 chars)
   - Optional fields
3. Submit
4. Step inserted
```

**Time:** ~2-3 minutes

### Quick Insert (Checkbox)

```
1. ☑️ Check checkbox
2. Click Approve
3. Step inserted automatically
```

**Time:** ~5 seconds ⚡

---

## 🎨 UI Components

### Checkbox Location
- **Inside approval form**
- **Above submit button**
- **Only visible when:**
  - Action = "Approve"
  - Template is configured
  - User can approve current step

### Checkbox Design
```blade
<div class="bg-yellow-50 border border-yellow-200 rounded-md p-2">
    <label class="flex items-start">
        <input type="checkbox" name="quick_insert_step" value="1">
        <span class="ml-2 text-xs">
            <i class="fas fa-plus-circle text-yellow-600"></i>
            <strong>Tambahkan step: Manager Keuangan</strong>
            <br>
            <span class="text-[10px]">
                Centang jika diperlukan verifikasi dari role Manager Keuangan
            </span>
        </span>
    </label>
</div>
```

---

## 🔒 Security & Validation

### Backend Validation

```php
// In ApprovalItemApprovalController::approve()

if ($request->has('quick_insert_step') && $currentStep->insert_step_template) {
    // Validate template exists
    // Validate user can approve current step
    // Insert step using template
}
```

### Authorization Checks

✅ **Allowed if:**
- Current step has `can_insert_step = true`
- Current step has `insert_step_template` configured
- User can approve current step
- Item status is pending/on progress

❌ **Blocked if:**
- Template not configured
- User not authorized
- Item already approved/rejected

---

## 📊 Database Schema

### approval_item_steps Table

```sql
-- New column
insert_step_template JSON NULL
  COMMENT 'Pre-configured step template for quick insertion'

-- Example value:
{
  "name": "Manager Keuangan - Verifikasi",
  "approver_type": "role",
  "approver_role_id": 5,
  "required_action": "upload_document"
}
```

---

## 🧪 Testing Scenarios

### Test Case 1: Quick Insert Success

**Setup:**
- Workflow with template configured
- User is Manager with approval permission

**Steps:**
1. Login as Manager
2. Open pending approval
3. See checkbox for quick insert
4. Check checkbox
5. Click Approve

**Expected:**
- Current step approved
- New step inserted automatically
- Steps renumbered correctly
- Redirect to show page with success message

### Test Case 2: No Template Configured

**Setup:**
- Step has `can_insert_step = true`
- But `insert_step_template = null`

**Expected:**
- Checkbox NOT shown
- Manual "Tambah Step" button available
- Normal approval flow works

### Test Case 3: Template with Nested Insert

**Setup:**
- Template has `can_insert_step = true`
- Allows chain insertion

**Expected:**
- Inserted step also has insert permission
- Can insert another step if needed

---

## 🎯 Benefits

### For Users
- ⚡ **Faster:** 5 seconds vs 2-3 minutes
- 🎯 **Simpler:** Checkbox vs full form
- ✅ **No errors:** Pre-configured data
- 🧠 **Less thinking:** No need to remember approver details

### For Admins
- 🎛️ **Centralized config:** Template in workflow
- 🔄 **Reusable:** Same template for all requests
- 📊 **Trackable:** Audit log for insertions
- 🛡️ **Controlled:** Only allowed steps can be inserted

---

## 📈 Comparison Matrix

| Feature | Manual Insert | Quick Insert |
|---------|--------------|--------------|
| **Form fields** | 6+ fields | 1 checkbox |
| **Time required** | 2-3 minutes | 5 seconds |
| **Error prone** | Yes (typos, wrong approver) | No (pre-configured) |
| **User training** | Required | Minimal |
| **Configuration** | Per-request | Per-workflow (reusable) |
| **Flexibility** | High (any step) | Medium (template only) |
| **Best for** | Ad-hoc cases | Common scenarios |

---

## 🚀 Implementation Checklist

- [x] Add `insert_step_template` column to database
- [x] Update ApprovalItemStep model with cast
- [x] Update ApprovalWorkflow to pass template
- [x] Create `quickInsertStep()` method in controller
- [x] Add checkbox to approval form UI
- [x] Handle checkbox in approve() method
- [x] Add route for quick insert
- [x] Test insertion flow
- [x] Document feature

---

## 📝 Example Workflow Configurations

### Example 1: Manager → Keuangan → Direktur

```json
{
  "workflow_steps": [
    {
      "name": "Manager Department",
      "approver_type": "requester_department_manager",
      "can_insert_step": true,
      "insert_step_template": {
        "name": "Manager Keuangan - Verifikasi Budget",
        "approver_type": "role",
        "approver_role_id": 5,
        "required_action": "verify_budget"
      }
    },
    {
      "name": "Direktur",
      "approver_type": "role",
      "approver_role_id": 3,
      "can_insert_step": false
    }
  ]
}
```

### Example 2: Manager → IT Support (Optional) → Direktur

```json
{
  "workflow_steps": [
    {
      "name": "Manager Department",
      "approver_type": "requester_department_manager",
      "can_insert_step": true,
      "insert_step_template": {
        "name": "IT Support - Technical Review",
        "approver_type": "department_manager",
        "approver_department_id": 10,
        "required_action": "technical_review"
      }
    },
    {
      "name": "Direktur",
      "approver_type": "role",
      "approver_role_id": 3,
      "can_insert_step": false
    }
  ]
}
```

---

## 🔍 Troubleshooting

### Checkbox Not Showing

**Possible causes:**
1. Template not configured in workflow
2. User cannot approve current step
3. Item already approved/rejected

**Solution:**
- Check workflow configuration
- Verify user permissions
- Check item status

### Step Not Inserted

**Possible causes:**
1. Checkbox not checked
2. Template data invalid
3. Database error

**Solution:**
- Check logs: `storage/logs/laravel.log`
- Search for: `Quick insert step`
- Verify template JSON structure

---

## 📞 Support

**Questions?**
- Check workflow configuration in admin panel
- Review logs for insertion errors
- Verify approver exists in database

**Common Issues:**

1. **Template not working:**
   - Ensure JSON structure is valid
   - Check approver_id/role_id exists
   - Verify approver_type matches required fields

2. **Checkbox not visible:**
   - Check `can_insert_step = true`
   - Verify `insert_step_template` not null
   - Ensure user has approval permission

---

## 🎉 Summary

**Quick Insert Step** memberikan cara **super cepat** untuk menambahkan step approval yang sering dibutuhkan. Dengan **pre-configured template**, user hanya perlu **centang checkbox** dan step langsung ditambahkan.

**Key Features:**
- ✅ One-click insertion
- ✅ Pre-configured templates
- ✅ No form filling required
- ✅ Automatic renumbering
- ✅ Full audit trail
- ✅ Fallback to manual insert

**Perfect for:**
- Frequently needed steps
- Standardized approval flows
- Reducing user errors
- Improving approval speed

---

**Last Updated:** 2025-11-06  
**Version:** 1.0.0
