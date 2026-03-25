# Approval Workflows Views

## Struktur File

- **_form.blade.php** - Reusable form component untuk create dan edit
- **create.blade.php** - Halaman tambah workflow (menggunakan _form.blade.php)
- **edit.blade.php** - Halaman edit workflow (menggunakan _form.blade.php)
- **index.blade.php** - Daftar workflow
- **show.blade.php** - Detail workflow

## Perbaikan yang Dilakukan

### 1. Refactoring ke Component Pattern
- Membuat file `_form.blade.php` yang reusable untuk create dan edit
- Menghilangkan duplikasi kode yang sangat banyak
- Membuat kode lebih maintainable dan mudah di-update

### 2. Perbaikan User Experience
- Struktur form yang lebih terorganisir dengan section yang jelas
- Validasi form yang lebih baik
- Pesan error yang lebih informatif
- Styling yang konsisten

### 3. Perbaikan JavaScript
- Menghilangkan template literal yang tercampur dengan Blade syntax
- Menggunakan `@json()` untuk passing data dari PHP ke JavaScript
- Menambahkan dynamic rendering untuk users, roles, dan departments
- Perbaikan logic untuk toggle fields

### 4. Perbaikan Logic
- Handling untuk edit mode (load existing steps)
- Proper data binding untuk existing data
- Currency formatting yang benar
- Conditional fields yang bekerja dengan baik

## Fitur

### Basic Information
- Nama Workflow
- Tipe Workflow
- Deskripsi
- Status (Aktif/Tidak Aktif)

### Procurement Configuration
- Sifat Pengadaan (Procurement Type)
- Nominal Minimum
- Nominal Maksimum

### Workflow Steps
- Tambah/Hapus step
- Nama step
- Fase step (Approver/Releaser)
- Tipe approver (User, Role, Department Manager, dll)
- Deskripsi step
- Required action (Input Harga, Verifikasi Budget)
- Conditional step (skip berdasarkan kondisi)
- Dynamic step insertion (approver bisa tambah step)

## Data Flow

### Create Mode
1. User akses `/approval-workflows/create`
2. Controller mengirim: roles, departments, users, procurementTypes
3. Form ditampilkan kosong dengan 1 step default
4. User isi form dan submit ke `approval-workflows.store`

### Edit Mode
1. User akses `/approval-workflows/{id}/edit`
2. Controller mengirim: approvalWorkflow + roles, departments, users, procurementTypes
3. Form ditampilkan dengan data existing
4. Existing steps di-load dari database
5. User bisa edit dan submit ke `approval-workflows.update`

## Validasi

### Client-side
- Minimal 1 step harus ada
- Nama step wajib diisi
- Tipe approver wajib dipilih
- Currency formatting otomatis

### Server-side
- Validasi di controller (lihat ApprovalWorkflowController)
- Validasi di model (lihat ApprovalWorkflow model)

## Notes

- File ini menggunakan Tailwind CSS untuk styling
- Font Awesome icons untuk UI elements
- Blade template syntax untuk PHP logic
- Vanilla JavaScript untuk interaktivitas (tidak ada jQuery dependency)
