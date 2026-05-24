# Hak Akses & RBAC V3

## Sistem Informasi Kepegawaian — CV Boss Muda Mandiri

---

## 1. Konsep RBAC V3

RBAC di V3 **secara konseptual sama dengan V2.3** — 3 role (admin, supervisor, karyawan) dengan akses sesuai matriks. Perbedaan utama di **implementasi**:

| Aspek                 | V2                                                    | V3                                         |
| --------------------- | ----------------------------------------------------- | ------------------------------------------ |
| Identitas user        | 4 tabel (user + admin/supervisor/karyawan)            | 1 tabel (tb_user)                          |
| Cara dapat karyawan   | `Karyawan::all()`                                     | `User::where('role', 'karyawan')->get()`   |
| Cara dapat supervisor | `Supervisor::all()`                                   | `User::where('role', 'supervisor')->get()` |
| FK ke user role       | `karyawan_id`, `admin_id`, `supervisor_id`            | semuanya `user_id`                         |
| Resource Filament     | KaryawanResource + AdminResource + SupervisorResource | UserResource tunggal dengan filter by role |

---

## 2. Matriks Hak Akses (Tidak Berubah dari V2.3)

### 2.1 Panel Admin

| Modul / Resource                          |                     Admin                      |         Supervisor         | Karyawan |
| ----------------------------------------- | :--------------------------------------------: | :------------------------: | :------: |
| **User (semua role)**                     |                    ✅ CRUD                     |             ❌             |    ❌    |
| **Jadwal Pekerjaan**                      |       ✅ CRUD (default filter hari ini)        |             ❌             |    ❌    |
| **Detail Pekerjaan**                      | ✅ CRUD + Map Picker (default filter hari ini) |             ❌             |    ❌    |
| **Bukti Pekerjaan**                       |                    ✅ CRUD                     |             ❌             |    ❌    |
| **Presensi**                              |                    ✅ CRUD                     |   👁️ View Only (kelola)    |    ❌    |
| **Verifikasi (inline di Presensi)**       |                       ❌                       | ✅ Update kolom verifikasi |    ❌    |
| **Laporan Presensi (multi-jenis)**        |                ✅ CRUD + Export                |    ✅ Generate + Export    |    ❌    |
| **Laporan Presensi (rekap per-karyawan)** |                    ✅ CRUD                     |     👁️ View Only tabel     |    ❌    |
| **Pengaturan (Setting)**                  |                    ✅ CRUD                     |             ❌             |    ❌    |
| **Lokasi Kantor**                         |                    ✅ Edit                     |             ❌             |    ❌    |

### 2.2 Portal Karyawan (Mobile Web)

| Halaman         | Karyawan | Admin | Supervisor |
| --------------- | :------: | :---: | :--------: |
| Beranda         |    ✅    |  ❌   |     ❌     |
| Presensi Masuk  |    ✅    |  ❌   |     ❌     |
| Presensi Pulang |    ✅    |  ❌   |     ❌     |
| Daftar Tugas    |    ✅    |  ❌   |     ❌     |
| Upload Bukti    |    ✅    |  ❌   |     ❌     |
| Jadwal Mingguan |    ✅    |  ❌   |     ❌     |
| Riwayat         |    ✅    |  ❌   |     ❌     |

### 2.3 Export Laporan

| Format                       | Admin | Supervisor | Karyawan |
| ---------------------------- | :---: | :--------: | :------: |
| CSV                          |  ✅   |     ✅     |    ❌    |
| Excel                        |  ✅   |     ✅     |    ❌    |
| PDF (header tampilkan jenis) |  ✅   |     ✅     |    ❌    |

---

## 3. Implementasi 3-Layer RBAC

### Layer 1 — Panel Access (`User::canAccessPanel`)

```php
// app/Models/User.php
public function canAccessPanel(Panel $panel): bool
{
    return match ($panel->getId()) {
        'admin'      => in_array($this->role, [Role::Admin, Role::Karyawan]),
        // ^ Karyawan boleh login lewat /admin (untuk /login bersama), nanti di-redirect keluar
        'supervisor' => $this->role === Role::Supervisor,
        default      => false,
    };
}
```

### Layer 2 — Route Middleware

```php
// routes/web.php tetap pakai middleware 'role:'
Route::middleware('role:karyawan')->prefix('karyawan')->group(...);
Route::middleware('role:admin,supervisor')->prefix('laporan')->group(...);
```

### Layer 3 — Filament Resource Policy

```php
// app/Filament/Resources/UserResource.php (BARU di V3)
public static function canViewAny(): bool
{
    return Auth::user()?->role === Role::Admin;
}

// app/Filament/Resources/PresensiResource.php
public static function canViewAny(): bool
{
    return in_array(Auth::user()?->role, [Role::Admin, Role::Supervisor]);
}

public static function canEdit(Model $record): bool
{
    return Auth::user()?->role === Role::Admin;
}
```

> **Perubahan utama V3:** policy method tetap sama, tapi check role pakai PHP enum (`Role::Admin`) bukan string `'admin'`. Hanya cosmetic + type safety.

---

## 4. Resource Filament Baru: `UserResource`

V2 punya 3 resource terpisah (KaryawanResource, AdminResource, SupervisorResource). V3 menggabungkan jadi 1 `UserResource` dengan **filter by role**:

```php
class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationLabel = 'Pengguna';
    protected static string|\UnitEnum|null $navigationGroup = 'Manajemen Akun';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('nik'),
                TextColumn::make('role')->badge(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options([
                        'karyawan'   => 'Karyawan',
                        'admin'      => 'Admin',
                        'supervisor' => 'Supervisor',
                    ]),
                TernaryFilter::make('is_active')->label('Status Akun'),
            ])
            // ... actions
        ;
    }
}
```

**Alternatif:** buat 3 resource yang sama-sama pakai model `User` tapi dengan `getEloquentQuery()` di-scope by role. Pendekatan ini menyimpan navigasi terpisah di sidebar (Karyawan, Admin, Supervisor) untuk kemudahan akses admin.

```php
class KaryawanResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationLabel = 'Karyawan';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', 'karyawan');
    }
}
```

Pilih salah satu pendekatan di [07-rencana-refactor-kode.md](07-rencana-refactor-kode.md).

---

## 5. Akses ke Laporan Jumlah Presensi Per Karyawan

Sesuai V2.3, supervisor dapat **view-only tabel rekap** (tidak boleh edit/delete/create):

```php
// app/Filament/Resources/LaporanPresensiResource.php

public static function canViewAny(): bool
{
    // Admin + Supervisor bisa lihat tabel
    return in_array(Auth::user()?->role, [Role::Admin, Role::Supervisor]);
}

public static function canCreate(): bool
{
    // Admin + Supervisor bisa generate laporan baru
    return in_array(Auth::user()?->role, [Role::Admin, Role::Supervisor]);
}

public static function canEdit(Model $record): bool
{
    return Auth::user()?->role === Role::Admin;
}

public static function canDelete(Model $record): bool
{
    return Auth::user()?->role === Role::Admin;
}
```

Filter jenis:

- Supervisor & Admin sama-sama bisa lihat semua jenis (Harian/Mingguan/Bulanan/Tahunan)
- Saat generate baru, tipe rekap per-karyawan tetap dikunci ke jenis Bulanan saja

---

## 6. Verifikasi Inline (V3)

V2 punya `VerifikasiResource` terpisah. V3 **menghapus VerifikasiResource** karena field verifikasi inline di tb_presensi. Cara supervisor verifikasi:

- Buka **PresensiResource** (admin/supervisor sama-sama bisa view tabel)
- Klik baris presensi → buka edit form
- Untuk supervisor: muncul section khusus **"Verifikasi"** (visible hanya untuk supervisor)
- Set `status_verifikasi`, `catatan_verifikasi`, `tgl_verifikasi` otomatis `now()`, `diverifikasi_oleh` otomatis `Auth::id()`
- Submit

```php
// app/Filament/Resources/PresensiResource.php
public static function form(Schema $schema): Schema
{
    return $schema->components([
        Section::make('Data Presensi')
            ->visible(fn () => true)
            ->schema([...]),

        Section::make('Verifikasi')
            ->description('Hanya supervisor yang boleh mengisi.')
            ->visible(fn () => Auth::user()?->role === Role::Supervisor)
            ->schema([
                Select::make('status_verifikasi')->options(...)->required(),
                Textarea::make('catatan_verifikasi'),
                Hidden::make('diverifikasi_oleh')->default(Auth::id()),
                Hidden::make('tgl_verifikasi')->default(now()),
            ]),
    ]);
}

public static function canEdit(Model $record): bool
{
    // Admin: full edit; Supervisor: hanya edit verifikasi (handle via field-level disabled di form)
    return in_array(Auth::user()?->role, [Role::Admin, Role::Supervisor]);
}
```

Untuk supervisor, field non-verifikasi di-disabled lewat `disabled(fn () => Auth::user()->role === Role::Supervisor)` di setiap field non-verifikasi.

---

## 7. Login & Redirect (Tidak Berubah)

Redirect by role sama persis V2.3:

```
/login → success login
       ├── role = admin      → /admin
       ├── role = supervisor → /supervisor
       └── role = karyawan   → karyawan.beranda
```

`FilamentLoginResponse` dan `FilamentLogoutResponse` tetap dari V2.

---

## 8. Migrasi Hak Akses dari V2 ke V3

Tidak ada perubahan logika hak akses. Yang berubah hanya:

- ✏️ String role check → PHP enum check (`'admin'` → `Role::Admin`)
- ✏️ Hapus referensi tabel role-specific (Karyawan, Admin, Supervisor) di Resource
- ✏️ Hapus `VerifikasiResource` (inline ke Presensi)
- ✏️ Rename `RekapPresensiBulananResource` + `LaporanResource` → `LaporanPresensiResource`

Detail di [07-rencana-refactor-kode.md](07-rencana-refactor-kode.md).

---

Selanjutnya: [05-fitur-alur-bisnis.md](05-fitur-alur-bisnis.md)
