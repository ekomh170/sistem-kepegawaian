# 13 — Panduan Pengembangan V3 (Konvensi Kode & Kontribusi)

> **Kenapa doc ini ada.** File `CLAUDE.md` di root repo adalah panduan kerja untuk AI assistant
> (Claude Code), tetapi **di-`.gitignore`** (pola `claude.md`) sehingga tidak ikut ter-commit dan
> tidak terlihat oleh tim. Dokumen ini adalah **versi ter-track** dari konvensi tersebut — supaya
> seluruh kontributor (manusia maupun AI) punya rujukan yang sama. **Jika mengubah konvensi,
> sinkronkan `CLAUDE.md` lokal dan dokumen ini.**

Sumber kebenaran implementasi: [12-hasil-akhir-implementasi.md](12-hasil-akhir-implementasi.md).
Skema rinci: [02-database-schema.md](02-database-schema.md).

---

## 1. Tentang Proyek

Sistem Informasi Kepegawaian **CV Boss Muda Mandiri** — **Laravel 12 + Filament v5 + MySQL**.

Tiga role: **admin**, **supervisor**, **karyawan**. Fitur inti: presensi GPS + foto, penugasan
lapangan, bukti pekerjaan, laporan presensi, verifikasi, pengaturan lokasi kantor.

---

## 2. Arsitektur Database — V3 Strict (7 tabel)

Sejak **30 Mei 2026** schema memakai **V3 Strict**. 6 tabel inti + 1 pendukung:

| Tabel                 | Catatan                                                                                                         |
| --------------------- | --------------------------------------------------------------------------------------------------------------- |
| `tb_user`             | **Konsolidasi semua role.** Kolom: `nama, email, password, nik, no_hp, posisi, role, is_active`                 |
| `tb_jadwal_pekerjaan` | dulu `tb_jadwal`. FK `user_id` (karyawan) + `dibuat_oleh` (admin)                                               |
| `tb_detail_pekerjaan` | FK `user_id`. Tugas lapangan per jadwal                                                                         |
| `tb_presensi`         | FK `user_id`. **Verifikasi inline**: `status_verifikasi, diverifikasi_oleh, catatan_verifikasi, tgl_verifikasi` |
| `tb_bukti_pekerjaan`  | FK `user_id`. Foto before/after                                                                                 |
| `tb_laporan_presensi` | dulu `tb_laporan` + `tb_rekap_presensi_bulanan`. `user_id` NULL = laporan agregat, terisi = rekap per-karyawan  |
| `tb_setting`          | key-value config (geofence, identitas perusahaan)                                                               |

**Tabel V2 yang sudah DIHAPUS** (jangan referensikan lagi): `tb_admin`, `tb_supervisor`,
`tb_karyawan`, `tb_verifikasi`, `tb_laporan`, `tb_rekap_presensi_bulanan`.

---

## 3. Konvensi Kode

- **Model & FK.** Semua relasi ke user pakai `user_id` (bukan `karyawan_id`/`admin_id`). Model
  user-anak lama (`Karyawan`, `Admin`, `Supervisor`, `Verifikasi`, `Laporan`, `RekapPresensiBulanan`)
  **sudah dihapus**. Pakai `User` + scope.

- **Ambil karyawan.** Gunakan scope, bukan model anak:

    ```php
    User::karyawan()->get();   // scope role = karyawan
    User::admin();             // role = admin
    User::supervisor();        // role = supervisor
    User::aktif();             // is_active = true
    ```

- **Cek role — kolom `role` di-cast ke `App\Enums\Role`.** Cara mengecek:

    ```php
    $user->isAdmin();  $user->isSupervisor();  $user->isKaryawan();   // cara utama
    $user->role === Role::Admin;                                       // setara
    $user->hasRole('admin');  $user->hasRole(Role::Admin);            // terima string atau enum
    ```

    **Jangan** bandingkan ke string mentah: `$user->role === 'admin'` **selalu `false`** karena
    `$user->role` adalah objek enum, bukan string.
    Untuk **query builder** tetap pakai string — binding ke nilai string di DB, tetap benar:

    ```php
    User::where('role', 'admin')->count();   // ✅ benar
    ```

- **Status lain tetap string.** `status_presensi`, `status_verifikasi`, `status`, `jenis`
  **belum di-cast** ke enum (lihat [§4](#4-enum-appenums)). Perlakukan sebagai string biasa.

- **Relasi `User`** (semua via `user_id`):
  `jadwalKerja`, `jadwalDibuat`, `presensiSaya`, `verifikasiSaya`, `tugasSaya`, `buktiSaya`,
  `laporanSubjek`, `laporanDibuat`.

- **Accessor `name`.** `tb_user` kolomnya `nama`; accessor `$user->name` (→ `nama`) **dipertahankan**
  karena dipakai Filament & Notifications. Jangan hapus.

- **Nama tabel.** Model tetap `protected $table = 'tb_*'` (snake plural Indonesia, prefix `tb_`).

- **Perbandingan tanggal.** Gunakan `whereDate('tanggal', $hari)` untuk equality tanggal — robust di
  **MySQL dan SQLite**. Hindari `where('tanggal', ...)` polos (SQLite menyimpan datetime penuh →
  equality string gagal saat testing).

- **Aturan keterlambatan.** `Presensi::hitungPotongan($menit)` — **Rp 10.000 per blok 10 menit**,
  toleransi 10 menit pertama, **tanpa batas atas**. Jangan tambahkan cap.

- **Foto masuk** hanya ditampilkan setelah check-out:
    ```php
    ->visible(fn ($r) => $r?->jam_keluar !== null)
    ```

---

## 4. Enum (`app/Enums/`)

Tersedia **7 enum** yang sudah mengimplementasikan kontrak Filament `HasLabel` + `HasColor`:
`Role`, `StatusPresensi`, `StatusVerifikasi`, `StatusJadwal`, `StatusTugas`, `StatusBukti`, `JenisLaporan`.

| Enum                                                                                               | Di-cast di model?    | Alasan                                                                                                                                                                                                                                                       |
| -------------------------------------------------------------------------------------------------- | -------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `Role` (di `User`)                                                                                 | ✅ **Ya**            | Domain tertutup (3 nilai), dipakai di banyak gate RBAC — manfaat tinggi, risiko rendah.                                                                                                                                                                      |
| `StatusPresensi`, `StatusVerifikasi`, `StatusJadwal`, `StatusTugas`, `StatusBukti`, `JenisLaporan` | ⏳ **Sengaja belum** | Casting menyebar ke banyak titik (mis. `$collection->where('status', 'hadir')` jadi **salah hitung tanpa error**, `@switch` blade, closure Filament). Manfaat ~nol karena nilai string = nilai enum. Enum tetap siap dipakai sebagai sumber **label/warna**. |

> Kalau suatu saat status/jenis di-cast penuh, **buat dulu jaring pengaman test** (render + RBAC),
> lalu cast, biar regressi tertangkap — bukan sebaliknya.

---

## 5. Filament

- **Lokasi resource:** `app/Filament/Resources/<Nama>s/`.
- **Folder hasil rename V3** (folder **dan** class sudah konsisten):
    - `JadwalPekerjaans/` → class `JadwalPekerjaanResource` (model `JadwalPekerjaan`)
    - `LaporanPresensis/` → class `LaporanPresensiResource` (model `LaporanPresensi`)
- **Resource user:** **`AkunResource`** (kelola semua role) + **`KaryawanResource`** (scope `role = karyawan`).
- **Resource yang DIHAPUS** (konsolidasi): `AdminResource`, `SupervisorResource`, `VerifikasiResource`,
  `RekapPresensiBulananResource`. Verifikasi kini **inline** di `PresensiResource`.
- **Kolom role** memakai `->badge()` polos — label & warna otomatis dari `Role` (`HasLabel`+`HasColor`).
- **Dua panel:** `admin` (`/admin`) & `supervisor` (`/supervisor`); auto-discover resources/pages/widgets.
- **RBAC berlapis:** `canViewAny()`/`canCreate()` per resource **+** middleware `role:` di route **+**
  `canAccessPanel()` di `User`. Detail matriks: [04-hak-akses-rbac.md](04-hak-akses-rbac.md).

---

## 6. Perintah Penting

```bash
php artisan migrate:fresh --seed   # reset + seed data demo (butuh MySQL jalan)
php artisan test                   # PHPUnit (SQLite :memory:). Suite V3: tests/Feature/V3Test.php + V3RbacTest.php
php artisan optimize:clear         # clear semua cache
composer dump-autoload -o          # WAJIB setelah hapus/rename class/model
php artisan route:list             # smoke test boot Filament (loads semua resource)
```

- **Test DB = SQLite memory.** **Hindari** `DATE_FORMAT` (MySQL-only) di kode yang ikut diuji
  unit/feature; pakai `whereDate()` untuk perbandingan tanggal.
- **Akun demo:** `admin@example.com`, `supervisor@example.com`, `karyawan@example.com` — password `password`.

---

## 7. Git & Lingkungan

- **Commit message:** **jangan** tambahkan trailer `Co-Authored-By: Claude`.
- **Author commit** mengikuti konvensi repo: `Eko Muchamad Haryono <66320809+ekomh170@users.noreply.github.com>`.
- **Lingkungan:** Windows + **Laragon MySQL** (`C:\laragon\bin\mysql\...\mysqld.exe`,
  DB `db_sistem_kepegawaian`).
- `CLAUDE.md` di root **di-`.gitignore`** — itu disengaja. Konvensi versi ter-track ada di dokumen ini.

---

## 8. Dokumentasi Terkait

- **Hasil akhir implementasi:** [12-hasil-akhir-implementasi.md](12-hasil-akhir-implementasi.md)
- **Skema database rinci:** [02-database-schema.md](02-database-schema.md)
- **Class diagram OOP:** [03-class-diagram-oop.md](03-class-diagram-oop.md)
- **Matriks RBAC:** [04-hak-akses-rbac.md](04-hak-akses-rbac.md)
- **Alur bisnis (masih relevan dari V2.3):** `docs/v2/`
- **Index folder ini:** [00-README.md](00-README.md)
