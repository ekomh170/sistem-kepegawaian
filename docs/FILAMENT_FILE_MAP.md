# Filament File Map

Tanggal update: 2026-04-27
Tujuan: peta file yang bisa kamu sentuh saat maintain aplikasi Filament di repo ini.

## A) Core Auth, Panel, dan Access Control

| File | Fungsi Singkat | Diubah Saat |
|---|---|---|
| `routes/web.php` | Root redirect, route `/login`, route karyawan mobile, middleware role untuk endpoint | Mau ubah flow masuk aplikasi, route login, atau proteksi route |
| `app/Http/Responses/FilamentLoginResponse.php` | Redirect setelah login sukses berdasarkan role | Mau ubah landing page tiap role |
| `app/Providers/AppServiceProvider.php` | Bind contract `LoginResponse` ke custom response | Custom login response tidak kepakai / mau ganti implementasi |
| `app/Models/User.php` | `canAccessPanel` + relasi user | Mau ubah akses panel (`admin`/`supervisor`) |
| `app/Http/Middleware/RoleMiddleware.php` | Middleware role untuk route-level access | Mau ubah respon unauthorized/forbidden |
| `bootstrap/app.php` | Registrasi alias middleware `role` | Mau rename alias middleware atau nambah alias baru |
| `bootstrap/providers.php` | Registrasi provider panel Filament | Nambah/hapus panel provider |
| `app/Providers/Filament/AdminPanelProvider.php` | Konfigurasi panel admin (`/admin`) | Ubah middleware panel, warna panel, path panel admin |
| `app/Providers/Filament/SupervisorPanelProvider.php` | Konfigurasi panel supervisor (`/supervisor`) | Ubah middleware panel supervisor, path, dan setup panel |

## B) Widgets (Filament Dashboard)

| File | Fungsi Singkat | Diubah Saat |
|---|---|---|
| `app/Filament/Widgets/AttendanceRealtimeStatsWidget.php` | Statistik presensi real-time (hadir, terlambat, tidak hadir, pending) | Ubah metrik realtime atau polling interval |
| `app/Filament/Widgets/LaporanEvaluasiChartWidget.php` | Chart evaluasi kehadiran per periode | Ubah query chart, dataset, atau jenis chart |
| `app/Filament/Widgets/KaryawanQuickAccessWidget.php` | Widget quick access khusus role karyawan | Ubah visibilitas role widget |
| `resources/views/filament/widgets/karyawan-quick-access-widget.blade.php` | Tampilan tombol quick access karyawan | Ubah label/link/style tombol quick access |

## C) Resource Map (Semua File Saat Ini)

## 1. Admins

- `app/Filament/Resources/Admins/AdminResource.php`
- `app/Filament/Resources/Admins/Pages/CreateAdmin.php`
- `app/Filament/Resources/Admins/Pages/EditAdmin.php`
- `app/Filament/Resources/Admins/Pages/ListAdmins.php`
- `app/Filament/Resources/Admins/Pages/ViewAdmin.php`
- `app/Filament/Resources/Admins/Schemas/AdminForm.php`
- `app/Filament/Resources/Admins/Schemas/AdminInfolist.php`
- `app/Filament/Resources/Admins/Tables/AdminsTable.php`

Edit cepat:
- izin akses resource -> `AdminResource.php`
- field form create/edit -> `Schemas/AdminForm.php`
- kolom list -> `Tables/AdminsTable.php`
- detail view -> `Schemas/AdminInfolist.php`

## 2. JadwalKerjas

- `app/Filament/Resources/JadwalKerjas/JadwalKerjaResource.php`
- `app/Filament/Resources/JadwalKerjas/Pages/CreateJadwalKerja.php`
- `app/Filament/Resources/JadwalKerjas/Pages/EditJadwalKerja.php`
- `app/Filament/Resources/JadwalKerjas/Pages/ListJadwalKerjas.php`

Edit cepat:
- permission CRUD -> `JadwalKerjaResource.php`
- schema form/table -> biasanya di method dalam resource file

## 3. Karyawans

- `app/Filament/Resources/Karyawans/KaryawanResource.php`
- `app/Filament/Resources/Karyawans/Pages/CreateKaryawan.php`
- `app/Filament/Resources/Karyawans/Pages/EditKaryawan.php`
- `app/Filament/Resources/Karyawans/Pages/ListKaryawans.php`
- `app/Filament/Resources/Karyawans/Pages/ViewKaryawan.php`
- `app/Filament/Resources/Karyawans/Schemas/KaryawanForm.php`
- `app/Filament/Resources/Karyawans/Schemas/KaryawanInfolist.php`
- `app/Filament/Resources/Karyawans/Tables/KaryawansTable.php`

Edit cepat:
- role access -> `KaryawanResource.php`
- field input -> `Schemas/KaryawanForm.php`
- kolom data -> `Tables/KaryawansTable.php`
- detail record -> `Schemas/KaryawanInfolist.php`

## 4. Laporans

- `app/Filament/Resources/Laporans/LaporanResource.php`
- `app/Filament/Resources/Laporans/Pages/CreateLaporan.php`
- `app/Filament/Resources/Laporans/Pages/EditLaporan.php`
- `app/Filament/Resources/Laporans/Pages/ListLaporans.php`

Edit cepat:
- siapa boleh lihat vs CRUD -> `LaporanResource.php`
- filter/list action spesifik list page -> `Pages/ListLaporans.php`

## 5. LokasiGps

- `app/Filament/Resources/LokasiGps/LokasiGpsResource.php`
- `app/Filament/Resources/LokasiGps/Pages/CreateLokasiGps.php`
- `app/Filament/Resources/LokasiGps/Pages/EditLokasiGps.php`
- `app/Filament/Resources/LokasiGps/Pages/ListLokasiGps.php`

Edit cepat:
- logic CRUD + permission -> `LokasiGpsResource.php`

## 6. Notifikasis

- `app/Filament/Resources/Notifikasis/NotifikasiResource.php`
- `app/Filament/Resources/Notifikasis/Pages/CreateNotifikasi.php`
- `app/Filament/Resources/Notifikasis/Pages/EditNotifikasi.php`
- `app/Filament/Resources/Notifikasis/Pages/ListNotifikasis.php`

Edit cepat:
- field dan permission -> `NotifikasiResource.php`

## 7. Presensis

- `app/Filament/Resources/Presensis/PresensiResource.php`
- `app/Filament/Resources/Presensis/Pages/CreatePresensi.php`
- `app/Filament/Resources/Presensis/Pages/EditPresensi.php`
- `app/Filament/Resources/Presensis/Pages/ListPresensis.php`

Edit cepat:
- behavior utama presensi di panel -> `PresensiResource.php`

## 8. Supervisors

- `app/Filament/Resources/Supervisors/SupervisorResource.php`
- `app/Filament/Resources/Supervisors/Pages/CreateSupervisor.php`
- `app/Filament/Resources/Supervisors/Pages/EditSupervisor.php`
- `app/Filament/Resources/Supervisors/Pages/ListSupervisors.php`

Edit cepat:
- akses dan form/table supervisor -> `SupervisorResource.php`

## 9. Verifikasis

- `app/Filament/Resources/Verifikasis/VerifikasiResource.php`
- `app/Filament/Resources/Verifikasis/Pages/CreateVerifikasi.php`
- `app/Filament/Resources/Verifikasis/Pages/EditVerifikasi.php`
- `app/Filament/Resources/Verifikasis/Pages/ListVerifikasis.php`

Edit cepat:
- visibilitas nav supervisor dan permission gabungan admin/supervisor -> `VerifikasiResource.php`

## D) Rule of Thumb Saat Edit Resource

1. Ubah kebutuhan bisnis dulu di Resource (`*Resource.php`):
   - permission
   - relasi
   - registration page
2. Lalu update representasi UI:
   - form (`Schemas/*Form.php`)
   - table (`Tables/*Table.php`)
   - infolist (`Schemas/*Infolist.php`)
3. Kalau action spesifik page (header action, export button, dsb), cek `Pages/*.php`.

## E) File Tambahan di Luar Filament yang Sering Ikut Kena

- `app/Http/Controllers/KaryawanMobileController.php`
  - logout redirect dan flow halaman mobile karyawan
- `database/seeders/DatabaseSeeder.php`
  - akun default, data role, sample data presensi/laporan/verifikasi

Kalau ada perubahan role/flow login, dua file ini sering ikut perlu disesuaikan.
