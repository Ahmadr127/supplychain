# Departments Views

## Struktur File

- **_form.blade.php** - Reusable form component untuk create dan edit
- **create.blade.php** - Halaman tambah department (menggunakan _form.blade.php)
- **edit.blade.php** - Halaman edit department (menggunakan _form.blade.php)
- **index.blade.php** - Daftar department
- **show.blade.php** - Detail department

## Perbaikan yang Dilakukan

### 1. Refactoring ke Component Pattern
- Membuat file `_form.blade.php` yang reusable untuk create dan edit
- Menghilangkan duplikasi kode yang sangat banyak
- Membuat kode lebih maintainable dan mudah di-update

### 2. Perbaikan Error "ADAH"
- Error terjadi karena template literal JavaScript tercampur dengan Blade syntax `@foreach`
- Solusi: Menggunakan `@json()` untuk passing data dari PHP ke JavaScript
- Semua data users di-pass sebagai JSON object, bukan di-loop dalam template literal

### 3. Perbaikan User Experience
- Struktur form yang lebih terorganisir dengan section yang jelas
- Validasi form yang lebih baik
- Pesan error yang lebih informatif
- Styling yang konsisten dan full width

### 4. Perbaikan JavaScript
- Menghilangkan template literal yang tercampur dengan Blade syntax
- Menggunakan `@json()` untuk passing data dari PHP ke JavaScript
- Menambahkan dynamic rendering untuk users
- Perbaikan logic untuk toggle fields dan member management

## Fitur

### Basic Information
- Nama Department
- Kode Department
- Parent Department (opsional)
- Level Department (1, 2, 3)
- Manager (opsional)
- Status (Aktif/Tidak Aktif)
- Deskripsi

### Members Management
- Tambah/Hapus anggota
- Pilih user
- Input jabatan
- Tandai departemen utama
- Validasi duplikasi user

## Data Flow

### Create Mode
1. User akses `/departments/create`
2. Controller mengirim: departments, users
3. Form ditampilkan kosong dengan 0 members
4. User isi form dan submit ke `departments.store`

### Edit Mode
1. User akses `/departments/{id}/edit`
2. Controller mengirim: department + departments, users
3. Form ditampilkan dengan data existing
4. Existing members di-load dari database
5. User bisa edit dan submit ke `departments.update`

## Validasi

### Client-side
- Nama department wajib diisi
- Kode department wajib diisi (max 10 karakter)
- Level department wajib dipilih
- Tidak boleh ada duplikasi user dalam members
- Currency formatting otomatis

### Server-side
- Validasi di controller
- Validasi di model

## Technical Details

- File menggunakan Tailwind CSS untuk styling
- Font Awesome icons untuk UI elements
- Blade template syntax untuk PHP logic
- Vanilla JavaScript untuk interaktivitas (tidak ada jQuery dependency)
- `@json()` untuk safe passing data dari PHP ke JavaScript

## Key Improvements

1. **No More Syntax Errors** - Blade syntax tidak lagi tercampur dengan JavaScript template literals
2. **Better Code Organization** - Reusable component pattern
3. **Improved Maintainability** - Single source of truth untuk form logic
4. **Better User Experience** - Full width layout, clear sections, better validation
5. **Safe Data Passing** - Menggunakan `@json()` untuk prevent injection attacks
