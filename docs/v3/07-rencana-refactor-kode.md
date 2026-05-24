# Rencana Refactor Kode V3

## Sistem Informasi Kepegawaian — CV Boss Muda Mandiri

---

## 1. Ruang Lingkup Refactor

Setelah migrasi DB selesai, kode perlu di-refactor di **5 layer**:

1. **Enum** — Buat PHP enum classes (Role, StatusPresensi, dll)
2. **Model** — Hapus 6 model, buat 1 model baru, update 5 model
3. **Filament Resource** — Hapus 5 resource, buat 1-2 resource baru, update 4 resource
4. **Controller** — Update referensi `karyawan_id` → `user_id`
5. **View (Blade)** — Update relasi `$presensi->karyawan` → `$presensi->user`

---

## 2. Layer 1 — PHP Enums

Buat directory `app/Enums/` dengan 7 file:

**`app/Enums/Role.php`**

```php
namespace App\Enums;

enum Role: string
{
    case Karyawan   = 'karyawan';
    case Admin      = 'admin';
    case Supervisor = 'supervisor';

    public function label(): string
    {
        return match ($this) {
            self::Karyawan   => 'Karyawan',
            self::Admin      => 'Admin',
            self::Supervisor => 'Supervisor',
        };
    }
}
```

Lakukan pola serupa untuk:

- `StatusJadwal` (Aktif, Dibatalkan)
- `StatusTugas` (Pending, Disetujui, Ditolak)
- `StatusPresensi` (Hadir, Terlambat, TidakHadir, Izin)
- `StatusVerifikasi` (Pending, Disetujui, Ditolak)
- `StatusBukti` (Pending, Disetujui, Ditolak)
- `JenisLaporan` (Harian, Mingguan, Bulanan, Tahunan)

---

## 3. Layer 2 — Eloquent Models

### 3.1 Model yang Dihapus

```bash
rm app/Models/Admin.php
rm app/Models/Supervisor.php
rm app/Models/Karyawan.php
rm app/Models/Verifikasi.php
rm app/Models/RekapPresensiBulanan.php
rm app/Models/Laporan.php
```

### 3.2 Model Baru

**`app/Models/JadwalPekerjaan.php`** (rename dari Jadwal.php)

```php
class JadwalPekerjaan extends Model
{
    protected $table = 'tb_jadwal_pekerjaan';
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'dibuat_oleh', 'tanggal_kerja',
        'jam_masuk', 'jam_pulang', 'hari_libur', 'status',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_kerja' => 'date',
            'hari_libur'    => 'boolean',
            'status'        => StatusJadwal::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pembuatJadwal(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function detailPekerjaans(): HasMany
    {
        return $this->hasMany(DetailPekerjaan::class, 'jadwal_id');
    }

    public function presensi(): HasOne
    {
        return $this->hasOne(Presensi::class, 'jadwal_id');
    }

    public function isHariLibur(): bool
    {
        return (bool) $this->hari_libur;
    }
}
```

**`app/Models/LaporanPresensi.php`** (gabungan Laporan + RekapPresensiBulanan)

```php
class LaporanPresensi extends Model
{
    protected $table = 'tb_laporan_presensi';
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'generated_by', 'judul', 'periode', 'jenis',
        'jumlah_hadir', 'jumlah_terlambat', 'jumlah_tidak_hadir',
        'total_potongan', 'file_path', 'tgl_generate',
    ];

    protected function casts(): array
    {
        return [
            'jenis'        => JenisLaporan::class,
            'tgl_generate' => 'datetime',
            'total_potongan' => 'decimal:2',
        ];
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function isRekapPerKaryawan(): bool
    {
        return !is_null($this->user_id);
    }
}
```

### 3.3 Model yang Diupdate

**`app/Models/User.php`** — tambah scope + helper untuk role

```php
class User extends Authenticatable implements FilamentUser
{
    protected $fillable = [
        'nama', 'email', 'password',
        'nik', 'no_hp', 'posisi',
        'role', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'password'  => 'hashed',
            'role'      => Role::class,
            'is_active' => 'boolean',
        ];
    }

    // Helpers
    public function isAdmin(): bool      { return $this->role === Role::Admin; }
    public function isSupervisor(): bool { return $this->role === Role::Supervisor; }
    public function isKaryawan(): bool   { return $this->role === Role::Karyawan; }

    public function hasRole(Role|string $role): bool
    {
        return $this->role === ($role instanceof Role ? $role : Role::from($role));
    }

    // Scopes
    public function scopeKaryawan(Builder $q): Builder    { return $q->where('role', Role::Karyawan); }
    public function scopeAdmin(Builder $q): Builder       { return $q->where('role', Role::Admin); }
    public function scopeSupervisor(Builder $q): Builder  { return $q->where('role', Role::Supervisor); }
    public function scopeAktif(Builder $q): Builder       { return $q->where('is_active', true); }

    // Relasi
    public function jadwalKerja(): HasMany       { return $this->hasMany(JadwalPekerjaan::class, 'user_id'); }
    public function jadwalDibuat(): HasMany      { return $this->hasMany(JadwalPekerjaan::class, 'dibuat_oleh'); }
    public function presensiSaya(): HasMany      { return $this->hasMany(Presensi::class, 'user_id'); }
    public function verifikasiSaya(): HasMany    { return $this->hasMany(Presensi::class, 'diverifikasi_oleh'); }
    public function tugasSaya(): HasMany         { return $this->hasMany(DetailPekerjaan::class, 'user_id'); }
    public function buktiSaya(): HasMany         { return $this->hasMany(BuktiPekerjaan::class, 'user_id'); }
    public function laporanSubjek(): HasMany     { return $this->hasMany(LaporanPresensi::class, 'user_id'); }
    public function laporanDibuat(): HasMany     { return $this->hasMany(LaporanPresensi::class, 'generated_by'); }
}
```

**`app/Models/Presensi.php`** — update relasi karyawan → user + tambah method verifikasi inline

```php
class Presensi extends Model
{
    protected $fillable = [
        'user_id', 'jadwal_id', 'tanggal',
        'jam_masuk', 'jam_keluar',
        'foto_masuk', 'foto_keluar',
        'latitude_masuk', 'longitude_masuk',
        'latitude_keluar', 'longitude_keluar',
        'menit_terlambat', 'potongan_terlambat',
        'status_presensi',
        // V3 verifikasi inline
        'status_verifikasi', 'diverifikasi_oleh',
        'catatan_verifikasi', 'tgl_verifikasi',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function jadwal(): BelongsTo
    {
        return $this->belongsTo(JadwalPekerjaan::class, 'jadwal_id');
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    // Method bisnis
    public function verifikasi(User $supervisor, StatusVerifikasi $status, ?string $catatan = null): self
    {
        $this->update([
            'status_verifikasi'  => $status,
            'diverifikasi_oleh'  => $supervisor->id,
            'catatan_verifikasi' => $catatan,
            'tgl_verifikasi'     => now(),
        ]);
        return $this;
    }

    public function sudahDiverifikasi(): bool
    {
        return $this->status_verifikasi !== StatusVerifikasi::Pending;
    }

    public function sudahCheckOut(): bool
    {
        return !is_null($this->jam_keluar);
    }

    public static function hitungPotongan(int $menitTerlambat): float
    {
        if ($menitTerlambat <= 10) return 0;
        $blok = (int) ceil(($menitTerlambat - 10) / 10);
        return $blok * 10000;
    }
}
```

**`app/Models/DetailPekerjaan.php`**, **`BuktiPekerjaan.php`** — rename FK karyawan_id → user_id, rename relasi karyawan() → user().

---

## 4. Layer 3 — Filament Resources

### 4.1 Resource yang Dihapus

```bash
rm -rf app/Filament/Resources/Karyawans/
rm -rf app/Filament/Resources/Admins/
rm -rf app/Filament/Resources/Supervisors/
rm -rf app/Filament/Resources/Verifikasis/
rm -rf app/Filament/Resources/RekapPresensiBulanans/
rm -rf app/Filament/Resources/Laporans/
```

### 4.2 Resource Baru

**`app/Filament/Resources/Users/UserResource.php`** (gabungan Karyawan + Admin + Supervisor)

Pendekatan terpilih: **3 navigation entries pakai 1 model**.

```php
// app/Filament/Resources/Karyawans/KaryawanResource.php
class KaryawanResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationLabel = 'Karyawan';
    protected static ?string $modelLabel = 'Karyawan';
    protected static ?string $pluralModelLabel = 'Karyawan';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', Role::Karyawan);
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->isAdmin();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama')->required(),
            TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
            TextInput::make('password')->password()->required(fn ($livewire) => $livewire instanceof CreateRecord),
            TextInput::make('nik')->required(),
            TextInput::make('no_hp')->required(),
            TextInput::make('posisi'),
            Hidden::make('role')->default(Role::Karyawan->value),
            Toggle::make('is_active')->default(true),
        ]);
    }
}
```

Sama untuk AdminResource & SupervisorResource (filter ke `Role::Admin` / `Role::Supervisor`, dengan field yang relevan).

**`app/Filament/Resources/LaporanPresensis/LaporanPresensiResource.php`** (gabungan Laporan + RekapPresensiBulanan)

```php
class LaporanPresensiResource extends Resource
{
    protected static ?string $model = LaporanPresensi::class;
    protected static ?string $navigationLabel = 'Laporan Presensi';
    protected static string|\UnitEnum|null $navigationGroup = 'Laporan';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('tipe_laporan')
                ->options([
                    'rekap_per_karyawan' => 'Laporan Jumlah Presensi Per Karyawan (Bulanan)',
                    'presensi_global'    => 'Laporan Presensi (Detail Harian)',
                ])
                ->live()
                ->afterStateUpdated(fn ($set, $get) => $get('tipe_laporan') === 'rekap_per_karyawan'
                    ? $set('jenis', 'Bulanan')
                    : null
                ),

            Select::make('jenis')
                ->options(fn ($get) => $get('tipe_laporan') === 'rekap_per_karyawan'
                    ? ['Bulanan' => 'Bulanan']
                    : ['Harian' => 'Harian', 'Mingguan' => 'Mingguan', 'Bulanan' => 'Bulanan', 'Tahunan' => 'Tahunan']
                )
                ->required(),

            Select::make('user_id')
                ->label('Karyawan (kosongkan untuk laporan global)')
                ->relationship('karyawan', 'nama', fn ($q) => $q->where('role', Role::Karyawan))
                ->searchable(),

            TextInput::make('periode')->required(),

            Hidden::make('generated_by')->default(Auth::id()),
            Hidden::make('tgl_generate')->default(now()),
        ]);
    }
}
```

### 4.3 Resource yang Diupdate

- **PresensiResource** — update relasi `karyawan.user.nama` → `user.nama` (karena tb_user sudah satu)
- Tambah section "Verifikasi" yang `visible` hanya untuk supervisor
- **JadwalResource → JadwalPekerjaanResource** — rename + update FK reference
- **DetailPekerjaanResource** — update relasi
- **BuktiPekerjaanResource** — update relasi

---

## 5. Layer 4 — Controllers

### 5.1 PresensiController

Update method `checkIn`, `checkOut`:

**V2:**

```php
$karyawan = Auth::user()->karyawan;
$presensi = Presensi::firstOrCreate([
    'karyawan_id' => $karyawan->id,
    'tanggal'     => today(),
]);
```

**V3:**

```php
$user = Auth::user();
$presensi = Presensi::firstOrCreate([
    'user_id' => $user->id,
    'tanggal' => today(),
]);
```

### 5.2 LaporanExportController

Update query rekap:

**V2:**

```php
RekapPresensiBulanan::with('karyawan.user')->where('periode', $periode)->get();
```

**V3:**

```php
LaporanPresensi::with('karyawan')
    ->where('jenis', JenisLaporan::Bulanan)
    ->where('periode', $periode)
    ->whereNotNull('user_id')
    ->get();
```

### 5.3 KaryawanMobileController

Update referensi:

- `Auth::user()->karyawan->jadwals` → `Auth::user()->jadwalKerja`
- `$presensi->karyawan->user->nama` → `$presensi->user->nama`

---

## 6. Layer 5 — View (Blade Templates)

### 6.1 Files to Update

| File                                                      | Perubahan                                                                     |
| --------------------------------------------------------- | ----------------------------------------------------------------------------- |
| `resources/views/karyawan/beranda.blade.php`              | `$user = Auth::user();` langsung (tanpa `->karyawan`)                         |
| `resources/views/karyawan/presensi-masuk.blade.php`       | Tetap pakai `$presensiHariIni->foto_masuk` (kolom sama)                       |
| `resources/views/karyawan/presensi-pulang.blade.php`      | Sama                                                                          |
| `resources/views/karyawan/riwayat.blade.php`              | `$item->karyawan->nama` → tidak perlu, pakai `Auth::user()->nama`             |
| `resources/views/karyawan/jadwal.blade.php`               | Update query jadwal                                                           |
| `resources/views/karyawan/tugas.blade.php`                | `$tugas->karyawan` → `$tugas->user` (kalau dipakai)                           |
| `resources/views/exports/laporan-pdf.blade.php`           | `$p->karyawan?->user?->nama` → `$p->karyawan?->nama` (langsung, tb_user satu) |
| `resources/views/exports/laporan-presensi-pdf.blade.php`  | Sama                                                                          |
| `resources/views/exports/laporan-pekerjaan-pdf.blade.php` | Sama                                                                          |

---

## 7. Layer 6 — Tests

V2 tidak punya banyak feature test. V3 kesempatan tambah:

- `tests/Feature/V3MigrationTest.php` — verifikasi data integrity setelah migrasi
- `tests/Feature/PresensiVerifikasiInlineTest.php` — verifikasi flow verifikasi inline
- `tests/Feature/LaporanPresensiMergedTest.php` — verifikasi gabungan laporan+rekap

---

## 8. Files yang Dihapus (Setelah Refactor)

```
app/Models/Admin.php
app/Models/Supervisor.php
app/Models/Karyawan.php
app/Models/Verifikasi.php
app/Models/RekapPresensiBulanan.php
app/Models/Laporan.php
app/Models/Jadwal.php (rename ke JadwalPekerjaan.php)

app/Filament/Resources/Verifikasis/
app/Filament/Resources/RekapPresensiBulanans/

app/Exports/LaporanPresensiExport.php (mungkin perlu rename)

database/migrations/*_create_tb_admin_table.php (lewat migrate:fresh — file tetap ada untuk history)
database/migrations/*_create_tb_supervisor_table.php
database/migrations/*_create_tb_karyawan_table.php
database/migrations/*_create_tb_verifikasi_table.php
database/migrations/*_create_tb_rekap_presensi_bulanan_table.php
database/migrations/*_create_tb_laporan_table.php (akan jadi tb_laporan_presensi)
```

---

## 9. Files yang Direname

| Dari                                                  | Ke                                                                    |
| ----------------------------------------------------- | --------------------------------------------------------------------- |
| `app/Models/Jadwal.php`                               | `app/Models/JadwalPekerjaan.php`                                      |
| `app/Filament/Resources/Jadwals/`                     | `app/Filament/Resources/JadwalPekerjaans/`                            |
| `app/Filament/Resources/Laporans/LaporanResource.php` | `app/Filament/Resources/LaporanPresensis/LaporanPresensiResource.php` |

---

## 10. Estimasi Effort

| Layer             | Effort     | Catatan                                         |
| ----------------- | ---------- | ----------------------------------------------- |
| Enum              | 0.5 hari   | 7 enum class, straightforward                   |
| Model             | 1 hari     | Hapus 6, buat 1 baru, update 5                  |
| Filament Resource | 1.5 hari   | Refactor terbesar                               |
| Controller        | 0.5 hari   | Search & replace                                |
| View              | 0.5 hari   | Update relasi `$x->karyawan->user` → `$x->user` |
| Testing manual    | 1 hari     | E2E test semua fitur masih jalan                |
| **Total**         | **5 hari** | Solo developer                                  |

---

Selanjutnya: [08-trade-off-risiko.md](08-trade-off-risiko.md)
