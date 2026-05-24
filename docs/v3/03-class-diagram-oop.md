# Class Diagram OOP V3

## Sistem Informasi Kepegawaian — CV Boss Muda Mandiri

---

## 1. Perbedaan Class Diagram vs ERD

| Aspek       | ERD (Tabel 2 di HTML)                            | Class Diagram (Tabel 1 di HTML)                   |
| ----------- | ------------------------------------------------ | ------------------------------------------------- |
| Fokus       | Struktur database                                | Objek & perilaku bisnis                           |
| Notasi tipe | SQL: `bigint`, `varchar`, `decimal`, `timestamp` | OOP: `int`, `string`, `float`, `DateTime`         |
| Hubungan    | Foreign Key (PK/FK badge)                        | Asosiasi object reference                         |
| Method      | (tidak ada)                                      | Operasi bisnis (`checkIn()`, `verifikasi()`, dll) |
| Visibility  | (tidak ada)                                      | `+` public, `-` private                           |
| Enum        | ENUM SQL string                                  | Class enum terpisah dengan `<<enumeration>>`      |

Class diagram V3 ditulis dengan fokus OOP — bukan sekedar mirror dari skema database, tapi memodelkan **objek dan perilaku** sesuai prinsip domain-driven design.

---

## 2. Daftar Class

| No  | Class             | Tipe    | Tanggung Jawab                           |
| --- | ----------------- | ------- | ---------------------------------------- |
| 1   | `User`            | Entity  | Autentikasi, profil, otorisasi panel     |
| 2   | `JadwalPekerjaan` | Entity  | Jadwal kerja harian per karyawan         |
| 3   | `DetailPekerjaan` | Entity  | Tugas lapangan dalam satu jadwal         |
| 4   | `Presensi`        | Entity  | Kehadiran + verifikasi inline            |
| 5   | `BuktiPekerjaan`  | Entity  | Bukti hasil kerja per tugas              |
| 6   | `LaporanPresensi` | Entity  | Laporan multi-jenis + rekap per-karyawan |
| 7   | `Setting`         | Service | Konfigurasi runtime (singleton-like)     |

### Enum Classes

| Enum               | Nilai                              | Dipakai di      |
| ------------------ | ---------------------------------- | --------------- |
| `Role`             | Karyawan, Admin, Supervisor        | User            |
| `StatusJadwal`     | Aktif, Dibatalkan                  | JadwalPekerjaan |
| `StatusTugas`      | Pending, Disetujui, Ditolak        | DetailPekerjaan |
| `StatusPresensi`   | Hadir, Terlambat, TidakHadir, Izin | Presensi        |
| `StatusVerifikasi` | Pending, Disetujui, Ditolak        | Presensi        |
| `StatusBukti`      | Pending, Disetujui, Ditolak        | BuktiPekerjaan  |
| `JenisLaporan`     | Harian, Mingguan, Bulanan, Tahunan | LaporanPresensi |

---

## 3. Method Bisnis per Class

### 3.1 User

**Autentikasi & Otorisasi:**

- `login(email, password): bool`
- `logout(): void`
- `hasRole(role): bool`
- `isAdmin(): bool`
- `isSupervisor(): bool`
- `isKaryawan(): bool`
- `canAccessPanel(panel): bool`

**Pengelolaan akun:**

- `ubahPassword(passwordBaru): bool`
- `aktifkan(): void`
- `nonaktifkan(): void`

### 3.2 JadwalPekerjaan

**Lifecycle:**

- `buat(karyawan, tanggal, pembuat): self`
- `batalkan(): void`
- `tandaiHariLibur(): void`
- `tambahPekerjaan(detail): void`

**Query:**

- `isHariLibur(): bool`
- `sudahDiPresensi(): bool`
- `getJadwalHarian(userId, tanggal): self?` (static)
- `getJadwalBulanan(userId, periode): Collection` (static)

### 3.3 DetailPekerjaan

**Workflow tugas:**

- `terima(): void`
- `tolak(alasan): void`
- `tambahkanBukti(fotoBefore, fotoAfter, keterangan): BuktiPekerjaan`

**Validasi GPS:**

- `ubahLokasi(lat, lng, radius): void`
- `cekJarakDari(lat, lng): float` (return jarak meter)
- `dalamRadius(lat, lng): bool`

**Predikat:**

- `isDiterima(): bool`
- `isDitolak(): bool`

### 3.4 Presensi

**Check-in/out:**

- `checkIn(lat, lng, foto): bool`
- `checkOut(lat, lng, foto): bool`

**Verifikasi (inline, V3):**

- `verifikasi(supervisor, status, catatan): self`
- `tolakVerifikasi(supervisor, alasan): self`

**Kalkulasi (static):**

- `hitungKeterlambatan(jamMasuk, jamJadwal): int` (menit)
- `hitungPotongan(menitTerlambat): float` (Rp)
- `validasiGPS(lat, lng, kantorLat, kantorLng, radius): bool`

**Query:**

- `getStatusPresensi(): string`
- `sudahCheckOut(): bool`
- `sudahDiverifikasi(): bool`
- `getRiwayatBulanan(userId, periode): Collection` (static)

### 3.5 BuktiPekerjaan

- `upload(fotoBefore, fotoAfter, keterangan): self`
- `setujui(): void`
- `tolak(): void`
- `isDisetujui(): bool`
- `isDitolak(): bool`

### 3.6 LaporanPresensi

**Generate & Export:**

- `generate(): self`
- `hitungRekap(periode): self`
- `exportPdf(): string` (path file)
- `exportExcel(): string`
- `exportCsv(): string`
- `regenerate(): self`

### 3.7 Setting (Service)

**Static helpers:**

- `get(key, default): mixed`
- `set(key, value): self`
- `clearCache(): void`
- `getGeofence(): array` (return `[lat, lng, radius]`)
- `getIdentitasPerusahaan(): array` (return `[nama, alamat]`)

---

## 4. Asosiasi (Relasi Object Reference)

```
User 1 ──< * JadwalPekerjaan          (karyawan)
User 1 ──< * JadwalPekerjaan          (pembuatJadwal)
User 1 ──< * DetailPekerjaan          (ditugaskan)
User 1 ──< * Presensi                  (melakukan)
User 1 ──< * Presensi                  (memverifikasi)
User 1 ──< * BuktiPekerjaan            (mengunggah)
User 1 ──< * LaporanPresensi           (subjek)
User 1 ──< * LaporanPresensi           (generator)
JadwalPekerjaan 1 ──< * DetailPekerjaan  (memuat tugas)
JadwalPekerjaan 1 ──< 0..1 Presensi      (dihadiri)
DetailPekerjaan 1 ──< * BuktiPekerjaan   (memiliki bukti)
```

`Setting` tidak punya asosiasi — service standalone.

---

## 5. Prinsip OOP yang Dipakai

### Single Responsibility

Setiap class punya tanggung jawab tunggal yang jelas:

- `User` = identitas + otorisasi
- `Presensi` = kehadiran + verifikasi (1 lifecycle)
- `LaporanPresensi` = generate + export laporan

### Encapsulation

- Field `password` private (`-password: string`)
- Akses ke konfigurasi sistem via `Setting::get()` (bukan akses langsung tabel)
- State transition lewat method (e.g. `terima()`, `tolak()`) bukan setter langsung ke `status`

### Polymorphic Behavior via Role

User berperilaku berbeda tergantung `role`:

- `Karyawan` → bisa `checkIn()`, `checkOut()`, `tambahkanBukti()`
- `Admin` → bisa `buat()` jadwal, `generate()` laporan
- `Supervisor` → bisa `verifikasi()` presensi

Implementasi: guard dengan `hasRole()` di method-level (di Laravel: Policy + middleware).

### Static Methods untuk Utility & Factory

- `Presensi::hitungPotongan(menit)` — kalkulasi murni, tidak butuh instance
- `JadwalPekerjaan::getJadwalHarian(userId, tanggal)` — factory/finder
- `Setting::get(key)` — global accessor

---

## 6. Mapping Class ↔ Tabel ↔ Eloquent Model

| Class (OOP)       | Tabel (DB)            | Eloquent Model               | File                             |
| ----------------- | --------------------- | ---------------------------- | -------------------------------- |
| `User`            | `tb_user`             | `App\Models\User`            | `app/Models/User.php`            |
| `JadwalPekerjaan` | `tb_jadwal_pekerjaan` | `App\Models\JadwalPekerjaan` | `app/Models/JadwalPekerjaan.php` |
| `DetailPekerjaan` | `tb_detail_pekerjaan` | `App\Models\DetailPekerjaan` | `app/Models/DetailPekerjaan.php` |
| `Presensi`        | `tb_presensi`         | `App\Models\Presensi`        | `app/Models/Presensi.php`        |
| `BuktiPekerjaan`  | `tb_bukti_pekerjaan`  | `App\Models\BuktiPekerjaan`  | `app/Models/BuktiPekerjaan.php`  |
| `LaporanPresensi` | `tb_laporan_presensi` | `App\Models\LaporanPresensi` | `app/Models/LaporanPresensi.php` |
| `Setting`         | `tb_setting`          | `App\Models\Setting`         | `app/Models/Setting.php`         |

Enum classes (PHP 8.1+ native enum):

| Enum               | File                             |
| ------------------ | -------------------------------- |
| `Role`             | `app/Enums/Role.php`             |
| `StatusJadwal`     | `app/Enums/StatusJadwal.php`     |
| `StatusTugas`      | `app/Enums/StatusTugas.php`      |
| `StatusPresensi`   | `app/Enums/StatusPresensi.php`   |
| `StatusVerifikasi` | `app/Enums/StatusVerifikasi.php` |
| `StatusBukti`      | `app/Enums/StatusBukti.php`      |
| `JenisLaporan`     | `app/Enums/JenisLaporan.php`     |

> **V2 saat ini:** semua status dideklarasikan sebagai string `ENUM` di migration + string biasa di model. **V3 disarankan migrasi ke PHP enum class** untuk type safety.

---

## 7. Contoh Skenario OOP (Bukan SQL)

### Skenario: Karyawan Check-in

```php
// Bukan: $presensi = new Presensi(['karyawan_id' => ...]); $presensi->save();
// Tapi:
$presensi = $karyawan->checkIn(
    lat: -6.2087,
    lng: 106.8222,
    foto: $fotoSelfie
);

if ($presensi->statusPresensi === StatusPresensi::Terlambat) {
    // Auto-kalkulasi sudah jalan di dalam checkIn()
    echo "Telat {$presensi->menitTerlambat} menit, potongan Rp {$presensi->potonganTerlambat}";
}
```

### Skenario: Supervisor Verifikasi

```php
// Bukan: Verifikasi::create([...])
// Tapi:
$presensi->verifikasi(
    supervisor: $userSupervisor,
    status: StatusVerifikasi::Disetujui,
    catatan: 'Foto valid, lokasi di radius kantor'
);
```

### Skenario: Generate Laporan PDF

```php
$laporan = LaporanPresensi::generate([
    'user_id' => $karyawan->id,
    'periode' => '2026-05',
    'jenis'   => JenisLaporan::Bulanan,
]);

$pdfPath = $laporan->exportPdf();
return response()->download($pdfPath);
```

---

Selanjutnya: [04-hak-akses-rbac.md](04-hak-akses-rbac.md)
