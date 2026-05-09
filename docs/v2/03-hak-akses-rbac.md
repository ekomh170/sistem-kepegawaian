# Hak Akses & Role-Based Access Control (RBAC)
## Sistem Informasi Kepegawaian — CV Boss Muda Mandiri

---

## 1. Mekanisme RBAC

Sistem menggunakan **dua lapis** kontrol akses:

1. **Route-Level Middleware** (`RoleMiddleware.php`): Memeriksa role user pada setiap HTTP request. Diterapkan pada route group di `web.php`.
2. **Resource-Level Policy** (Filament `canViewAny`, `canCreate`, `canEdit`, `canDelete`): Mengatur hak akses granular per Resource di panel admin.

### Login & Redirect Flow
```
User login → /login (Filament Login Page)
    ├── role = 'admin'      → redirect /admin (Panel Filament)
    ├── role = 'supervisor'  → redirect /admin (Panel Filament, akses terbatas)
    └── role = 'karyawan'    → redirect /karyawan (Portal Mobile Blade)
```

---

## 2. Matriks Hak Akses per Modul

### 2.1 Panel Admin (Filament Resources)

| Modul / Resource | Admin | Supervisor | Karyawan |
|-----------------|:-----:|:----------:|:--------:|
| **Akun (User)** | ✅ CRUD | ❌ | ❌ |
| **Admin** | ✅ CRUD | ❌ | ❌ |
| **Supervisor** | ✅ CRUD | 👁️ View Only | ❌ |
| **Karyawan** | ✅ CRUD | 👁️ View Only | ❌ |
| **Detail Pekerjaan** | ✅ CRUD + Map Picker | 👁️ View Only | ❌ |
| **Presensi** | ✅ CRUD | 👁️ View Only | ❌ |
| **Verifikasi** | ✅ CRUD | 👁️ View Only | ❌ |
| **Bukti Pekerjaan** | ✅ CRUD | 👁️ View Only | ❌ |
| **Rekap Potongan** | ✅ CRUD | 👁️ View Only | ❌ |
| **Laporan** | ✅ CRUD + Export | 👁️ View + Export | ❌ |
| **Notifikasi** | ✅ CRUD | 👁️ View Only | ❌ |
| **Pengaturan (Setting)** | ✅ CRUD | ❌ | ❌ |
| **Lokasi Kantor** | ✅ Edit | ❌ | ❌ |

### 2.2 Portal Karyawan (Mobile Web)

| Halaman | Karyawan | Admin | Supervisor |
|---------|:--------:|:-----:|:----------:|
| Beranda (Presensi + Tugas) | ✅ | ❌ | ❌ |
| Presensi Masuk (Check-in Kantor Pusat) | ✅ | ❌ | ❌ |
| Presensi Pulang (Check-out) | ✅ | ❌ | ❌ |
| Daftar Tugas (Terima/Tolak) | ✅ | ❌ | ❌ |
| Upload Bukti Pekerjaan (per Tugas) | ✅ | ❌ | ❌ |
| Jadwal Mingguan | ✅ | ❌ | ❌ |
| Riwayat Presensi | ✅ | ❌ | ❌ |

### 2.3 Export Laporan

| Format Export | Admin | Supervisor | Karyawan |
|--------------|:-----:|:----------:|:--------:|
| Export CSV | ✅ | ✅ | ❌ |
| Export Excel (.xlsx) | ✅ | ✅ | ❌ |
| Export PDF | ✅ | ✅ | ❌ |

---

## 3. Dashboard Widgets (Hak Akses)

| Widget | Admin | Supervisor | Karyawan |
|--------|:-----:|:----------:|:--------:|
| `AttendanceRealtimeStatsWidget` (Statistik real-time hadir/telat/alpa) | ✅ | ✅ | ❌ |
| `LaporanEvaluasiChartWidget` (Grafik evaluasi kehadiran) | ✅ | ✅ | ❌ |
| `KaryawanQuickAccessWidget` | ✅ | ❌ | ❌ |

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

| Aspek | Implementasi |
|-------|-------------|
| Password Hashing | Menggunakan `bcrypt` via Laravel `'password' => 'hashed'` cast |
| Session Management | Database-backed sessions |
| CSRF Protection | Token otomatis pada semua form POST |
| File Upload Validation | Max 5MB, hanya format image |
| GPS Spoofing Prevention | Validasi jarak (Haversine Formula) vs radius_meter |
