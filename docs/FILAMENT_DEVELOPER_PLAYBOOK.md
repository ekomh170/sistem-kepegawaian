# Filament Developer Playbook

Tanggal update: 2026-04-27

## 1) Tujuan Dokumen

Dokumen ini jadi pegangan cepat kalau kamu mau:
- ubah alur login
- ubah redirect setelah login/logout
- ubah hak akses role
- ubah form/table/infolist di Filament Resource
- ubah widget dashboard
- tambah resource/panel baru

## 2) Gambaran Arsitektur Saat Ini

### Panel

- Panel admin: path `/admin`
- Panel supervisor: path `/supervisor`
- Shared login route: `/login` (pakai halaman login Filament)

Panel provider yang dipakai:
- `app/Providers/Filament/AdminPanelProvider.php`
- `app/Providers/Filament/SupervisorPanelProvider.php`
- registrasinya ada di `bootstrap/providers.php`

### Auth + Role Control

Lapisan kontrol akses saat ini ada 3:

1. Panel-level access
   - `app/Models/User.php` -> method `canAccessPanel(Panel $panel)`
2. Route-level middleware
   - alias `role` didaftarkan di `bootstrap/app.php`
   - implementasi di `app/Http/Middleware/RoleMiddleware.php`
3. Resource-level guard
   - method seperti `canViewAny`, `canCreate`, `canEdit`, `canDelete` di setiap `*Resource.php`

### Login dan Redirect

- Route `/login` didefinisikan di `routes/web.php`.
- Setelah login sukses, redirect tidak default lagi, tapi lewat custom response:
  - `app/Http/Responses/FilamentLoginResponse.php`
  - binding contract di `app/Providers/AppServiceProvider.php`

Role redirect saat login:
- admin -> `/admin`
- supervisor -> `/supervisor`
- karyawan -> route `karyawan.beranda`

## 3) Alur Request Penting

### A. Masuk aplikasi dari root

1. User hit `/`
2. Kalau belum login -> redirect `/login`
3. Kalau sudah login -> redirect sesuai role (`/admin`, `/supervisor`, atau route karyawan)

File utama: `routes/web.php`

### B. Login Filament

1. User buka `/login`
2. Handler pakai `Filament\Auth\Pages\Login`
3. Login sukses -> `FilamentLoginResponse::toResponse()`
4. Redirect by role

File utama:
- `routes/web.php`
- `app/Http/Responses/FilamentLoginResponse.php`
- `app/Providers/AppServiceProvider.php`

### C. Logout karyawan mobile

- Logout dari halaman karyawan diarahkan ke `/login`

File utama:
- `app/Http/Controllers/KaryawanMobileController.php`

## 4) Kalau Mau Ubah X, Edit File Y

### Ubah URL panel

- Edit path panel di:
  - `app/Providers/Filament/AdminPanelProvider.php`
  - `app/Providers/Filament/SupervisorPanelProvider.php`
- Pastikan redirect root/login ikut disesuaikan di `routes/web.php` dan `FilamentLoginResponse.php`.

### Ubah siapa yang boleh akses panel

- Edit `canAccessPanel` di `app/Models/User.php`
- Cek `authMiddleware` role di masing-masing panel provider

### Ubah siapa yang boleh akses route web/api tertentu

- Edit middleware `role:...` di `routes/web.php`
- Kalau logic middleware mau diubah, edit `app/Http/Middleware/RoleMiddleware.php`

### Ubah redirect setelah login

- Edit match role di `app/Http/Responses/FilamentLoginResponse.php`

### Ubah redirect setelah logout karyawan

- Edit method `logout()` di `app/Http/Controllers/KaryawanMobileController.php`

### Ubah field form resource

- Cari folder resource terkait di `app/Filament/Resources/<NamaResource>/`
- Biasanya edit file `Schemas/*Form.php`

Contoh:
- Karyawan: `app/Filament/Resources/Karyawans/Schemas/KaryawanForm.php`

### Ubah kolom table resource

- Edit file `Tables/*Table.php` kalau ada
- Kalau resource belum dipisah file table-nya, edit method `table()` langsung di `*Resource.php`

### Ubah detail view/infolist

- Edit file `Schemas/*Infolist.php` jika tersedia

### Ubah page action atau tombol list/create/edit

- Edit file page di:
  - `app/Filament/Resources/<Nama>/Pages/*.php`

### Ubah widget dashboard Filament

- Logic widget:
  - `app/Filament/Widgets/AttendanceRealtimeStatsWidget.php`
  - `app/Filament/Widgets/LaporanEvaluasiChartWidget.php`
  - `app/Filament/Widgets/KaryawanQuickAccessWidget.php`
- View widget blade:
  - `resources/views/filament/widgets/karyawan-quick-access-widget.blade.php`

### Ubah role access per Resource

- Edit method permission di file `*Resource.php`:
  - `canViewAny`
  - `canCreate`
  - `canEdit`
  - `canDelete`
  - `shouldRegisterNavigation` (kalau dipakai)

## 5) Struktur Umum Filament Resource di Repo Ini

Pola yang dipakai saat ini:

- `.../<ResourceName>Resource.php`
  - model utama, permission, registration pages
- `.../Schemas/*Form.php`
  - field input create/edit
- `.../Schemas/*Infolist.php`
  - detail view (kalau ada)
- `.../Tables/*Table.php`
  - konfigurasi kolom tabel (kalau ada)
- `.../Pages/*.php`
  - endpoint page list/create/edit/view

Artinya, kalau ada perubahan domain, biasanya editnya tidak cuma satu file.
Minimal cek: Resource + Form + Table + Page.

## 6) Catatan Seeder yang Relevan ke Login/Role

Seeder utama:
- `database/seeders/DatabaseSeeder.php`

Poin penting:
- Menggunakan `updateOrCreate` supaya idempotent.
- Sudah ada user `ekoaryo@example.com` role karyawan.
- Data karyawan Ekoaryo dibuat, tapi tidak dibuat data presensi.

Kalau mau tambah akun default baru:
1. Tambah user di blok USER DATA
2. Tambah entitas turunan sesuai role (admin/supervisor/karyawan)
3. Pastikan tidak menabrak unique key (email, nik, dll)

## 7) Checklist Sebelum Commit

Minimal lakukan ini setelah ubah behavior:

1. Syntax check file PHP yang berubah
   - contoh: `php -l routes/web.php`
2. Cek route login
   - `php artisan route:list --path=login`
3. Kalau ubah role/redirect, uji manual:
   - login admin
   - login supervisor
   - login karyawan
   - logout dari halaman karyawan
4. Kalau ubah seeder:
   - `php artisan db:seed --class=DatabaseSeeder --no-interaction`

## 8) Troubleshooting Cepat

### 403 padahal user sudah login

Cek berurutan:
1. Role user di table `tb_user`
2. Middleware `role:...` di route
3. Permission method di `*Resource.php`
4. `canAccessPanel` di `app/Models/User.php`

### Setelah login nyasar ke halaman yang salah

Cek:
1. `FilamentLoginResponse::toResponse()`
2. route root di `routes/web.php`
3. path panel di provider (`/admin`, `/supervisor`)

### Route /login tidak jalan

Cek:
1. panel admin terdaftar di `bootstrap/providers.php`
2. route `/login` di `routes/web.php`
3. hasil `php artisan route:list --path=login`

## 9) Next Improvement (Opsional)

Kalau mau lebih maintainable ke depan:
- Centralisasi mapping role->redirect di satu config file.
- Tambah test feature untuk login redirect per role.
- Tambah test authorization untuk resource penting (Laporan, Verifikasi, Presensi).

## 10) Diagram + Detail Runtime

Kalau butuh versi yang lebih visual dan teknis (component/sequence/activity), cek:
- `docs/FILAMENT_WORKFLOW_MERMAID.md`

Dokumen itu berisi:
- cara kerja Filament end-to-end
- detail tanggung jawab file kunci
- diagram Mermaid siap pakai
