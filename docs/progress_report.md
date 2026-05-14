# Progress Report — V2.1 (Bug Fix & Polish)

**Tanggal:** 15 Mei 2026  
**Branch:** `main-dev`  
**Status:** ✅ Stabil

---

## Ringkasan Sesi V2.1

Sesi ini fokus pada **perbaikan bug, peningkatan UX, dan penambahan notifikasi** di atas fondasi V2 yang sudah berjalan. Tidak ada perubahan skema database.

---

## ✅ Bug Fix

| # | Bug | File | Keterangan |
|---|-----|------|------------|
| 1 | **Rumus keterlambatan salah** | `Models/Presensi.php` | `hitungPotongan()`: toleransi tidak dikurangi sebelum `ceil`. Fix: `ceil(($menit - toleransi) / 10)` |
| 2 | **Foto check-in tidak terkirim** | `views/karyawan/presensi-masuk.blade.php` | Canvas kamera dikompresi: max 1280px, quality 0.75 JPEG |
| 3 | **Notifikasi double "sudah absen"** | `Http/Controllers/KaryawanMobileController.php` | Redirect langsung ke `presensi-pulang` jika sudah check-in; ke `beranda` jika sudah check-out |
| 4 | **Format Rp tidak real-time di form** | `Resources/Karyawans/Schemas/KaryawanForm.php`, `Resources/Akuns/Schemas/AkunForm.php` | Ganti `formatStateUsing` + live ke Alpine `$money` mask via `RawJs` |
| 5 | **Format Rp disabled field** | `Resources/Presensis/PresensiResource.php`, `Resources/RekapPresensiBulanans/Schemas/RekapPresensiBulananForm.php` | Hapus `->numeric()`, tambah `formatStateUsing` + `dehydrateStateUsing` |

---

## ✅ Peningkatan Fitur Admin

| # | Fitur | File | Keterangan |
|---|-------|------|------------|
| 1 | **Widget dashboard dirapikan** | `Widgets/AttendanceRealtimeStatsWidget.php` | Hapus "rekap bulan lalu", kurangi stat card, perbaiki query cross-DB (`whereYear`/`whereMonth` — tidak lagi MySQL-only) |
| 2 | **Urutan widget konsisten** | Semua widget | Tambah `protected static ?int $sort` (1–4) agar urutan tetap |
| 3 | **Pencarian karyawan by nama** | `Resources/Jadwals/Schemas/JadwalForm.php` | Select `karyawan_id` pakai `getSearchResultsUsing` + `orWhereHas('user', nama)` |
| 4 | **Buat jadwal range tanggal** | `Resources/Jadwals/Schemas/JadwalForm.php`, `Pages/CreateJadwal.php` | Tambah field `tanggal_akhir` (visible saat create); `handleRecordCreation()` loop & skip duplikat |
| 5 | **Global search karyawan by nama** | `Resources/Karyawans/KaryawanResource.php` | Tambah `getGloballySearchableAttributes()` dengan `user.nama` |
| 6 | **Upload foto maks 10 MB** | `Http/Controllers/PresensiController.php` | Naik dari 5 MB |

---

## ✅ Notifikasi Login & Logout

| # | Fitur | File |
|---|-------|------|
| 1 | **Notifikasi selamat datang setelah login** | `Http/Responses/FilamentLoginResponse.php` → flash `login_success` |
| 2 | **Custom Dashboard membaca flash** | `Filament/Pages/Dashboard.php` → `mount()` → Filament Notification |
| 3 | **Notifikasi berhasil logout** | `Http/Responses/FilamentLogoutResponse.php` → flash `logout_success` |
| 4 | **Custom Login membaca flash logout** | `Filament/Pages/Auth/Login.php` → `mount()` → Filament Notification |
| 5 | **Login page custom** | `Filament/Pages/Auth/Login.php` | Heading = nama perusahaan dari `Setting`, label form dirapikan |

| 7 | **Tombol ubah & hapus muncul untuk supervisor** di Laporan & Rekap Presensi Bulanan | `Resources/Laporans/LaporanResource.php`, `Resources/RekapPresensiBulanans/Tables/RekapPresensiBulanansTable.php` | Tambah `->visible(fn () => Auth::user()?->role === 'admin')` pada `EditAction`, `DeleteAction`, dan `DeleteBulkAction` |

---

## File Baru (V2.1)

| File | Peran |
|------|-------|
| `app/Filament/Pages/Dashboard.php` | Custom Dashboard — baca session flash login |
| `app/Filament/Pages/Auth/Login.php` | Custom Login — flash logout + nama perusahaan dari Setting |
| `app/Http/Responses/FilamentLogoutResponse.php` | Logout redirect + flash session |

---

## File Dimodifikasi (V2.1)

| File | Perubahan |
|------|-----------|
| `app/Http/Responses/FilamentLoginResponse.php` | Tambah role label + flash `login_success` |
| `app/Providers/AppServiceProvider.php` | Bind `LogoutResponse` contract |
| `app/Providers/Filament/AdminPanelProvider.php` | `->login(Login::class)` custom, import Dashboard custom |
| `app/Providers/Filament/SupervisorPanelProvider.php` | Sama dengan admin |
| `routes/web.php` | Route `/login` pakai `App\Filament\Pages\Auth\Login` |
| `app/Models/Presensi.php` | Fix `hitungPotongan()` |
| `app/Http/Controllers/PresensiController.php` | Max foto 10 MB |
| `app/Http/Controllers/KaryawanMobileController.php` | Redirect if already checked-in |
| `app/Filament/Widgets/AttendanceRealtimeStatsWidget.php` | Cross-DB queries, hapus potongan bulan lalu, sort=1 |
| `app/Filament/Widgets/LaporanEvaluasiChartWidget.php` | Hapus dead code, sort=4 |
| `app/Filament/Widgets/RekapKehadiranHariIniWidget.php` | sort=2 |
| `app/Filament/Widgets/ProgressPekerjaanHariIniWidget.php` | sort=3 |
| `app/Filament/Resources/Karyawans/Schemas/KaryawanForm.php` | Alpine `$money` mask untuk gaji_pokok |
| `app/Filament/Resources/Akuns/Schemas/AkunForm.php` | Sama |
| `app/Filament/Resources/Jadwals/Schemas/JadwalForm.php` | Search by nama, field tanggal_akhir |
| `app/Filament/Resources/Jadwals/Pages/CreateJadwal.php` | `handleRecordCreation()` bulk date range |
| `app/Filament/Resources/Karyawans/KaryawanResource.php` | Global search by `user.nama` |
| `app/Filament/Resources/Presensis/PresensiResource.php` | Format Rp disable field |
| `app/Filament/Resources/RekapPresensiBulanans/Schemas/RekapPresensiBulananForm.php` | Format Rp 3 field money |
| `resources/views/karyawan/presensi-masuk.blade.php` | Kompresi kamera 1280px / q0.75 |

---

## Catatan Teknis Penting

### Alpine `$money` mask (currency field yang bisa diedit)
```php
->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
->dehydrateStateUsing(fn ($state) => (int) str_replace('.', '', $state ?? '0'))
```
Gunakan pola ini untuk TextInput numerik bertitik ribuan yang **aktif (tidak disabled)**.

### Format Rp untuk field disabled
```php
->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state, 0, ',', '.') : '0')
->dehydrateStateUsing(fn ($state) => (float) str_replace('.', '', $state ?? '0'))
// Jangan pakai ->numeric() karena akan override format
```

### Rumus keterlambatan (final)
```
≤ toleransi (10 mnt) → Rp 0
> toleransi s.d. 20 mnt → Rp 10.000
> 20 mnt s.d. 30 mnt  → Rp 20.000
> 30 mnt              → Rp 20.000 (flat cap)
```
