# Fix Menu Settings Permission di Production

## Masalah
Menu "Pengaturan" tidak muncul di sidebar setelah deploy ke production karena permission `manage_settings` belum ada di database production.

## Solusi

### Opsi 1: Menggunakan Migration (Recommended)
Jalankan migration yang sudah dibuat:
```bash
php artisan migrate
```

Migration `2025_10_20_add_settings_permission.php` akan:
- Membuat permission `manage_settings`
- Assign ke role `admin`
- Assign ke role `manager_it` dan `manager_keuangan`

### Opsi 2: Menggunakan Artisan Command
Jalankan command khusus yang sudah dibuat:
```bash
php artisan permission:sync-settings
```

Command ini akan:
- Membuat permission jika belum ada
- Sync permission ke role yang sesuai
- Menampilkan list user yang memiliki akses

### Opsi 3: Menjalankan Seeder Specific
```bash
php artisan db:seed --class=SettingsPermissionSeeder
```

## Verifikasi

Setelah menjalankan salah satu opsi di atas:

1. **Check di Database:**
```sql
-- Check permission exists
SELECT * FROM permissions WHERE name = 'manage_settings';

-- Check role assignments
SELECT r.name, r.display_name 
FROM roles r
JOIN role_permissions rp ON r.id = rp.role_id
JOIN permissions p ON rp.permission_id = p.id
WHERE p.name = 'manage_settings';
```

2. **Check via Artisan Command:**
```bash
php artisan permission:sync-settings
```
Command akan menampilkan user mana saja yang memiliki akses.

3. **Test di Browser:**
- Login sebagai admin
- Menu "Pengaturan" seharusnya muncul di sidebar
- Akses ke `/settings` seharusnya berhasil

## Role yang Memiliki Akses

Setelah fix diterapkan, role berikut akan memiliki akses ke menu Settings:
- **admin** - Full access
- **manager_it** - Untuk konfigurasi teknis
- **manager_keuangan** - Untuk konfigurasi threshold keuangan

## Troubleshooting

Jika menu masih tidak muncul:

1. **Clear Cache:**
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

2. **Check User Role:**
Pastikan user memiliki salah satu role di atas:
```php
// Via tinker
php artisan tinker
$user = User::find(1); // Ganti dengan user ID
$user->roles->pluck('name');
```

3. **Manual Fix via Tinker:**
```php
php artisan tinker

// Create permission
$permission = \App\Models\Permission::firstOrCreate(
    ['name' => 'manage_settings'],
    [
        'display_name' => 'Kelola Pengaturan',
        'description' => 'Mengelola pengaturan sistem termasuk threshold FS'
    ]
);

// Assign to admin
$admin = \App\Models\Role::where('name', 'admin')->first();
$admin->permissions()->syncWithoutDetaching([$permission->id]);

// Check
$admin->permissions->where('name', 'manage_settings')->count(); // Should be 1
```

## Notes
- Permission `manage_settings` mengontrol akses ke halaman pengaturan FS threshold
- Menu hanya muncul untuk user dengan permission ini
- Permission check ada di `resources/views/layouts/app.blade.php` line 187
