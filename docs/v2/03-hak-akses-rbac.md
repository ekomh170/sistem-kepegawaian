# Hak Akses & Role-Based Access Control (RBAC)

## Sistem Informasi Kepegawaian — CV Boss Muda Mandiri

---

## 1. Mekanisme RBAC

Sistem menggunakan **dua lapis** kontrol akses:

1. **Route-Level Middleware** (`RoleMiddleware.php`): Memeriksa role user pada setiap HTTP request. Diterapkan pada route group di `web.php`.
2. **Resource-Level Policy** (Filament `canViewAny`, `canCreate`, `canEdit`, `canDelete`): Mengatur hak akses granular per Resource di panel admin.

### Login & Redirect Flow

```
User login → /login (Custom Login Page — App\Filament\Pages\Auth\Login)
    ├── role = 'admin'      → redirect /admin      (Panel Filament Admin)
    ├── role = 'supervisor'  → redirect /supervisor (Panel Filament Supervisor)
    └── role = 'karyawan'    → redirect /karyawan   (Portal Mobile Blade)
```

Ditangani oleh `FilamentLoginResponse` (custom). Setelah logout, `FilamentLogoutResponse` redirect ke `/login` dengan flash notifikasi.

---

## 2. Matriks Hak Akses per Modul

### 2.1 Panel Admin (Filament Resources)

| Modul / Resource                         |                     Admin                      |          Supervisor           | Karyawan |
| ---------------------------------------- | :--------------------------------------------: | :---------------------------: | :------: |
| **Akun (User)**                          |                    ✅ CRUD                     |              ❌               |    ❌    |
| **Admin**                                |                    ✅ CRUD                     |              ❌               |    ❌    |
| **Supervisor**                           |                    ✅ CRUD                     |              ❌               |    ❌    |
| **Karyawan**                             |                    ✅ CRUD                     |              ❌               |    ❌    |
| **Jadwal**                               |       ✅ CRUD (default filter hari ini)        |              ❌               |    ❌    |
| **Detail Pekerjaan**                     | ✅ CRUD + Map Picker (default filter hari ini) |              ❌               |    ❌    |
| **Bukti Pekerjaan**                      |                    ✅ CRUD                     |              ❌               |    ❌    |
| **Presensi**                             |                    ✅ CRUD                     |     👁️ View Only (kelola)     |    ❌    |
| **Verifikasi**                           |                       ❌                       | ✅ CRUD (verifikasi presensi) |    ❌    |
| **Laporan**                              |                ✅ CRUD + Export                |     ✅ Generate + Export      |    ❌    |
| **Laporan Jumlah Presensi Per Karyawan** |                    ✅ CRUD                     |     👁️ View Only (tabel)      |    ❌    |
| **Pengaturan (Setting)**                 |                    ✅ CRUD                     |              ❌               |    ❌    |
| **Lokasi Kantor**                        |                    ✅ Edit                     |              ❌               |    ❌    |

> **Catatan V2.3:** Hak akses Supervisor dirampingkan menjadi 4 modul: **Presensi (view)**, **Laporan (generate & export)**, **Verifikasi (kelola)**, dan **Laporan Jumlah Presensi Per Karyawan (view-only tabel)**. Modul Karyawan, Jadwal, Detail Pekerjaan, Bukti Pekerjaan, dan Setting tidak tampil di sidebar supervisor.

### 2.2 Portal Karyawan (Mobile Web)

| Halaman                                | Karyawan | Admin | Supervisor |
| -------------------------------------- | :------: | :---: | :--------: |
| Beranda (Presensi + Tugas)             |    ✅    |  ❌   |     ❌     |
| Presensi Masuk (Check-in Kantor Pusat) |    ✅    |  ❌   |     ❌     |
| Presensi Pulang (Check-out)            |    ✅    |  ❌   |     ❌     |
| Daftar Tugas (Terima/Tolak)            |    ✅    |  ❌   |     ❌     |
| Upload Bukti Pekerjaan (per Tugas)     |    ✅    |  ❌   |     ❌     |
| Jadwal Mingguan                        |    ✅    |  ❌   |     ❌     |
| Riwayat Presensi                       |    ✅    |  ❌   |     ❌     |

### 2.3 Export Laporan

| Format Export        | Admin | Supervisor | Karyawan |
| -------------------- | :---: | :--------: | :------: |
| Export CSV           |  ✅   |     ✅     |    ❌    |
| Export Excel (.xlsx) |  ✅   |     ✅     |    ❌    |
| Export PDF           |  ✅   |     ✅     |    ❌    |

---

## 3. Dashboard Widgets (Hak Akses)

| Widget                                                        | Admin | Supervisor | Karyawan |
| ------------------------------------------------------------- | :---: | :--------: | :------: |
| `AttendanceRealtimeStatsWidget` (stat card hadir/telat/total) |  ✅   |     ✅     |    ❌    |
| `RekapKehadiranHariIniWidget` (tabel rekap hari ini)          |  ✅   |     ✅     |    ❌    |
| `ProgressPekerjaanHariIniWidget` (progress tugas hari ini)    |  ✅   |     ✅     |    ❌    |
| `LaporanEvaluasiChartWidget` (grafik kehadiran per bulan)     |  ✅   |     ✅     |    ❌    |
| `KaryawanQuickAccessWidget` (akses cepat menu karyawan)       |  ✅   |     ❌     |    ❌    |

---

## 4. Implementasi Middleware

### Route Protection (`web.php`)

```php
// Karyawan mobile portal - hanya role karyawan
Route::middleware('role:karyawan')->group(function () { ... });

// Export laporan - admin & supervisor
Route::middleware('role:admin,supervisor')->group(function () { ... });
```

### Filament Resource Protection (contoh PresensiResource)

```php
public static function canViewAny(): bool {
    return in_array(Auth::user()?->role, ['admin', 'supervisor'], true);
}

public static function canCreate(): bool {
    return Auth::user()?->role === 'admin'; // Supervisor tidak bisa create
}

public static function canEdit(Model $record): bool {
    return Auth::user()?->role === 'admin'; // Supervisor tidak bisa edit
}

public static function canDelete(Model $record): bool {
    return Auth::user()?->role === 'admin'; // Hanya admin bisa hapus
}
```

### Table Action Visibility (contoh)

```php
->recordActions([
    EditAction::make()
        ->visible(fn () => Auth::user()?->role === 'admin'),
    DeleteAction::make()
        ->visible(fn () => Auth::user()?->role === 'admin'),
])
```

---

## 5. Keamanan Tambahan

| Aspek                   | Implementasi                                                   |
| ----------------------- | -------------------------------------------------------------- |
| Password Hashing        | Menggunakan `bcrypt` via Laravel `'password' => 'hashed'` cast |
| Session Management      | Database-backed sessions                                       |
| CSRF Protection         | Token otomatis pada semua form POST                            |
| File Upload Validation  | Max 10MB (foto presensi), hanya format image                   |
| GPS Spoofing Prevention | Validasi jarak (Haversine Formula) vs radius_meter             |
