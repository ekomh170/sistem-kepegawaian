# Filament Cheat Sheet (1 Halaman)

Tanggal update: 2026-04-27
Target: developer internal yang butuh cepat paham "ubah apa di file mana".

## 1) Login + Redirect Flow

- Entry login: `/login` (Filament Login page)
- Root route `/`:
  - guest -> `/login`
  - admin -> `/admin`
  - supervisor -> `/supervisor`
  - karyawan -> route `karyawan.beranda`
- Redirect setelah login sukses diatur custom, bukan default Filament.

File yang ngatur flow ini:
- `routes/web.php`
- `app/Http/Responses/FilamentLoginResponse.php`
- `app/Providers/AppServiceProvider.php`

## 2) Akses Role Ada di 3 Layer

1. Panel layer
   - `app/Models/User.php` -> `canAccessPanel(Panel $panel)`
2. Route layer
   - `app/Http/Middleware/RoleMiddleware.php`
   - alias middleware di `bootstrap/app.php`
3. Resource layer
   - `canViewAny/canCreate/canEdit/canDelete` di masing-masing `*Resource.php`

Rule praktis:
- Kena 403? cek dari panel -> route -> resource, urut.

## 3) Panel yang Aktif

- Admin panel
  - provider: `app/Providers/Filament/AdminPanelProvider.php`
  - path: `/admin`
  - auth middleware: `role:admin`
- Supervisor panel
  - provider: `app/Providers/Filament/SupervisorPanelProvider.php`
  - path: `/supervisor`
  - auth middleware: `role:supervisor`
- Registrasi provider panel:
  - `bootstrap/providers.php`

## 4) Kalau Mau Ubah X -> Sentuh Y

- Ubah tujuan redirect habis login
  - `app/Http/Responses/FilamentLoginResponse.php`
- Ubah siapa boleh akses panel tertentu
  - `app/Models/User.php`
  - `app/Providers/Filament/*PanelProvider.php`
- Ubah proteksi route web/api
  - `routes/web.php`
  - `app/Http/Middleware/RoleMiddleware.php`
- Ubah redirect logout karyawan
  - `app/Http/Controllers/KaryawanMobileController.php`
- Ubah field form resource
  - `app/Filament/Resources/<Resource>/Schemas/*Form.php`
- Ubah kolom table resource
  - `app/Filament/Resources/<Resource>/Tables/*Table.php`
  - atau method `table()` di `*Resource.php` (kalau belum dipisah)
- Ubah detail view
  - `app/Filament/Resources/<Resource>/Schemas/*Infolist.php`
- Ubah widget dashboard
  - logic: `app/Filament/Widgets/*.php`
  - view: `resources/views/filament/widgets/*.blade.php`

## 5) Resource Utama (Snapshot)

- `Admins`, `Karyawans`, `Supervisors`
- `JadwalKerjas`, `Presensis`, `Verifikasis`
- `Laporans`, `Notifikasis`, `LokasiGps`

Map file lengkap ada di:
- `docs/FILAMENT_FILE_MAP.md`

## 6) Seeder Notes Cepat

File utama:
- `database/seeders/DatabaseSeeder.php`

Hal penting saat ini:
- Seeder pakai `updateOrCreate` (idempotent)
- User `ekoaryo@example.com` role karyawan sudah ada
- Ekoaryo dibuatkan entitas `Karyawan`, tapi tidak dibuatkan `Presensi`

## 7) Checklist Sebelum Push

1. Syntax check file yang diubah
   - contoh: `php -l routes/web.php`
2. Pastikan route login kebaca
   - `php artisan route:list --path=login`
3. Uji alur login 3 role
   - admin, supervisor, karyawan
4. Kalau ubah seeder, jalankan ulang seed
   - `php artisan db:seed --class=DatabaseSeeder --no-interaction`

## 8) Command Cepat yang Sering Kepake

- Cek route login:
  - `php artisan route:list --path=login`
- Cek syntax file:
  - `php -l path/to/file.php`
- Seed database dev:
  - `php artisan db:seed --class=DatabaseSeeder --no-interaction`

## 9) Rujukan Lanjut

- Versi detail arsitektur + troubleshooting:
  - `docs/FILAMENT_DEVELOPER_PLAYBOOK.md`
- Peta file lengkap:
  - `docs/FILAMENT_FILE_MAP.md`
- Cara kerja runtime + diagram Mermaid:
  - `docs/FILAMENT_WORKFLOW_MERMAID.md`
