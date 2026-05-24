# Ringkasan Perubahan V2 → V3

## Sistem Informasi Kepegawaian — CV Boss Muda Mandiri

---

## 1. Latar Belakang

V2 (terakhir di V2.3) memiliki **12 tabel** dengan beberapa kelemahan struktural:

- **4 tabel untuk user** — `tb_user`, `tb_admin`, `tb_supervisor`, `tb_karyawan` membutuhkan JOIN setiap kali butuh data profil
- **Penamaan FK tidak konsisten** — kadang `karyawan_id`, kadang `admin_id`, kadang `supervisor_id`
- **tb_verifikasi terpisah padahal 1:1** dengan tb_presensi — JOIN tambahan tanpa benefit
- **tb_laporan + tb_rekap_presensi_bulanan duplikatif** — overlap konseptual

V3 menyederhanakan struktur menjadi **6 tabel inti + 1 pendukung** tanpa mengubah fitur fungsional.

---

## 2. Perubahan Struktural

### 2.1 Konsolidasi User (4 tabel → 1 tabel)

**V2:**

```
tb_user (id, nama, email, password, role)
tb_admin (user_id, nik, no_hp)
tb_supervisor (user_id, nik, no_hp)
tb_karyawan (user_id, nik, posisi, no_ktp, alamat, foto, tgl_masuk, status_kontrak, ...)
```

**V3:**

```
tb_user (id, nama, email, password, nik, no_hp, posisi, role, is_active)
```

Atribut role-specific dimerge ke `tb_user`. Beberapa atribut karyawan-only (no_ktp, alamat, foto, tgl_masuk, status_kontrak, bidang_tugas) **disederhanakan** — sebagian dihapus, sebagian jadi nullable di tb_user, atau pindah ke kolom JSON kalau diperlukan.

> **Catatan:** kolom `gaji_pokok` di tb_karyawan **tidak dibawa** karena fitur tampilan gaji sudah dihapus sejak V2.2.

### 2.2 Verifikasi Inline ke Presensi

**V2:**

```
tb_presensi (id, karyawan_id, ...)
tb_verifikasi (id, presensi_id, supervisor_id, status, catatan, tgl_verifikasi)
```

**V3:**

```
tb_presensi (id, user_id, ..., status_verifikasi, diverifikasi_oleh, catatan_verifikasi, tgl_verifikasi)
```

Karena relasi presensi↔verifikasi memang **1:1**, kolom verifikasi di-inline. Mengurangi 1 JOIN dan 1 tabel.

**Konsekuensi:** kehilangan audit trail kalau supervisor ubah keputusan. Trade-off ini diterima (lihat [08-trade-off-risiko.md](08-trade-off-risiko.md)).

### 2.3 Rename Jadwal

`tb_jadwal` → `tb_jadwal_pekerjaan`. Lebih deskriptif tentang isi tabel.

### 2.4 Penyatuan Laporan & Rekap

**V2:**

```
tb_laporan (id, judul, jenis, periode, generated_by, tgl_generate)
tb_rekap_presensi_bulanan (id, karyawan_id, admin_id, periode, jumlah_hadir, jumlah_terlambat, jumlah_tidak_hadir, total_potongan, status)
```

**V3:**

```
tb_laporan_presensi (id, user_id, generated_by, judul, periode, jenis, jumlah_hadir, jumlah_terlambat, jumlah_tidak_hadir, total_potongan, file_path, tgl_generate)
```

Satu tabel polimorfik:

- `user_id = NULL` → laporan agregat (semua karyawan)
- `user_id != NULL` → rekap per-karyawan
- `jumlah_*` dan `total_potongan` nullable, diisi hanya untuk rekap

### 2.5 Penamaan FK Konsisten

Semua FK ke user pakai `user_id`. Untuk relasi multi-peran, dipakai nama semantik:

- `tb_jadwal_pekerjaan.user_id` (karyawan)
- `tb_jadwal_pekerjaan.dibuat_oleh` (admin pembuat)
- `tb_presensi.user_id` (karyawan)
- `tb_presensi.diverifikasi_oleh` (supervisor)
- `tb_laporan_presensi.user_id` (subjek rekap)
- `tb_laporan_presensi.generated_by` (admin/supervisor pembuat)

---

## 3. Apa yang TIDAK Berubah

Tidak ada perubahan fitur fungsional. Semua perilaku V2.3 dipertahankan:

| Fitur                                                   | Status                                               |
| ------------------------------------------------------- | ---------------------------------------------------- |
| Login 3 role (admin/supervisor/karyawan)                | ✅ Sama                                              |
| RBAC supervisor (hanya Presensi/Laporan/Verifikasi)     | ✅ Sama                                              |
| Presensi GPS Haversine + radius                         | ✅ Sama                                              |
| Upload foto selfie check-in/check-out                   | ✅ Sama (field foto_masuk/foto_keluar dipertahankan) |
| Foto masuk tampil setelah check-out                     | ✅ Sama                                              |
| Aturan keterlambatan Rp 10.000 / 10 menit (tanpa cap)   | ✅ Sama                                              |
| Workflow tugas pending → diterima/ditolak + alasan      | ✅ Sama                                              |
| Upload bukti before/after + keterangan                  | ✅ Sama                                              |
| Filter default hari ini untuk Jadwal & Detail Pekerjaan | ✅ Sama                                              |
| Tipe Laporan dikunci Bulanan untuk rekap                | ✅ Sama                                              |
| Header PDF tampilkan jenis laporan                      | ✅ Sama                                              |
| Setting konfigurasi runtime via Filament                | ✅ Sama (tb_setting dipertahankan)                   |

---

## 4. Tabel Mapping V2 → V3

| Tabel V2                  | Tabel V3            | Catatan                                                         |
| ------------------------- | ------------------- | --------------------------------------------------------------- |
| tb_user                   | tb_user             | + kolom nik, no_hp, posisi (dari role-tables)                   |
| tb_admin                  | (dihapus)           | nik, no_hp dimerge ke tb_user                                   |
| tb_supervisor             | (dihapus)           | nik, no_hp dimerge ke tb_user                                   |
| tb_karyawan               | (dihapus)           | nik, posisi dimerge ke tb_user; atribut detail sebagian dihapus |
| tb_jadwal                 | tb_jadwal_pekerjaan | rename + karyawan_id → user_id, admin_id → dibuat_oleh          |
| tb_detail_pekerjaan       | tb_detail_pekerjaan | karyawan_id → user_id                                           |
| tb_presensi               | tb_presensi         | karyawan_id → user_id, + 4 kolom verifikasi inline              |
| tb_verifikasi             | (dihapus)           | merged ke tb_presensi                                           |
| tb_bukti_pekerjaan        | tb_bukti_pekerjaan  | karyawan_id → user_id                                           |
| tb_rekap_presensi_bulanan | tb_laporan_presensi | merged dengan tb_laporan                                        |
| tb_laporan                | tb_laporan_presensi | merged dengan tb_rekap, karyawan_id → user_id                   |
| tb_setting                | tb_setting          | tetap (tabel pendukung)                                         |

---

## 5. Field Mapping (Critical Fields)

| Field V2                     | Lokasi V3                        | Catatan                           |
| ---------------------------- | -------------------------------- | --------------------------------- |
| tb_admin.nik                 | tb_user.nik                      | (nullable)                        |
| tb_admin.no_hp               | tb_user.no_hp                    | (nullable)                        |
| tb_supervisor.nik            | tb_user.nik                      | (nullable)                        |
| tb_supervisor.no_hp          | tb_user.no_hp                    | (nullable)                        |
| tb_karyawan.nik              | tb_user.nik                      | (nullable, unique untuk karyawan) |
| tb_karyawan.posisi_karyawan  | tb_user.posisi                   | (nullable)                        |
| tb_karyawan.no_hp            | tb_user.no_hp                    | (nullable)                        |
| tb_karyawan.no_ktp           | (dihapus)                        | tidak dipakai fitur saat ini      |
| tb_karyawan.alamat           | (dihapus)                        | tidak dipakai fitur saat ini      |
| tb_karyawan.foto             | (dihapus)                        | tidak dipakai fitur saat ini      |
| tb_karyawan.tgl_masuk        | (dihapus)                        | tidak dipakai fitur saat ini      |
| tb_karyawan.status_kontrak   | (dihapus)                        | tidak dipakai fitur saat ini      |
| tb_karyawan.bidang_tugas     | (dihapus)                        | tidak dipakai fitur saat ini      |
| tb_karyawan.gaji_pokok       | (dihapus)                        | sudah dihapus di V2.2             |
| tb_verifikasi.status         | tb_presensi.status_verifikasi    | inline                            |
| tb_verifikasi.supervisor_id  | tb_presensi.diverifikasi_oleh    | inline                            |
| tb_verifikasi.catatan        | tb_presensi.catatan_verifikasi   | inline                            |
| tb_verifikasi.tgl_verifikasi | tb_presensi.tgl_verifikasi       | inline                            |
| tb_verifikasi.alasan_tolak   | (digabung ke catatan_verifikasi) | reuse field                       |

> **Penting:** field foto_masuk/foto_keluar, latitude_masuk/longitude_masuk/keluar di tb_presensi **tetap dipertahankan** (tidak terlihat di gambar ERD V3 tapi wajib ada untuk fitur upload selfie + audit GPS).

---

## 6. Dampak ke Kode

Refactor berikut diperlukan setelah migrasi DB (detail di [07-rencana-refactor-kode.md](07-rencana-refactor-kode.md)):

- **Model:** hapus `Admin`, `Supervisor`, `Karyawan`, `Verifikasi`, `Laporan`, `RekapPresensiBulanan` → buat `LaporanPresensi`. Update `User`, `Jadwal` (→ `JadwalPekerjaan`), `Presensi`, `DetailPekerjaan`, `BuktiPekerjaan`.
- **Filament Resources:** hapus `KaryawanResource`, `AdminResource`, `SupervisorResource`, `VerifikasiResource`, `RekapPresensiBulananResource`. Buat `UserResource` dengan filter by role. Rename `LaporanResource` → `LaporanPresensiResource`.
- **Controllers:** update `PresensiController`, `LaporanExportController`, `KaryawanMobileController` untuk pakai `user_id`.
- **Views (Blade):** update referensi `$presensi->karyawan` → `$presensi->user`.
- **Routes:** path/middleware tetap, hanya rename internal.

---

## 7. Branch & Timeline

- Branch: `main-dev-erd-v3` (saat ini)
- Branch sumber: `main-dev-tanpa-gaji` (V2.3, merged ke `main-dev`)
- **Estimasi effort:** 3-5 hari kerja
    - 1 hari: migration script + seeder
    - 1-2 hari: refactor Model + Resource
    - 1 hari: refactor Controller + View
    - 1 hari: testing + bugfix

---

Selanjutnya: [02-database-schema.md](02-database-schema.md)
