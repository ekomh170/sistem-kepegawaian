# Dokumentasi V3 — Persiapan Migrasi ERD

## Sistem Informasi Kepegawaian — CV Boss Muda Mandiri

**Status:** 📋 PERSIAPAN — belum diimplementasikan di kode  
**Branch target:** `main-dev-erd-v3`  
**Branch sumber:** `main-dev-tanpa-gaji` (V2.3)  
**Tanggal mulai:** 24 Mei 2026

---

## Tentang Folder Ini

Folder `docs/v3/` berisi **dokumen perencanaan migrasi** dari arsitektur V2 (12 tabel) ke V3 (6 tabel inti + 1 pendukung). Semua dokumen di sini adalah **rencana**, bukan dokumentasi sistem yang sudah jalan. Untuk dokumentasi sistem live, lihat [docs/v2/](../v2/).

ERD V3 lahir dari kebutuhan **menyederhanakan skema database** tanpa mengubah fitur — bukan rewrite total, hanya restrukturisasi tabel agar konsisten dan ramping.

---

## Daftar Dokumen

| File                                                         | Isi                                                      |
| ------------------------------------------------------------ | -------------------------------------------------------- |
| [00-README.md](00-README.md)                                 | Index folder (file ini)                                  |
| [01-ringkasan-perubahan.md](01-ringkasan-perubahan.md)       | Ringkasan eksekutif: apa yang berubah V2 → V3            |
| [02-database-schema.md](02-database-schema.md)               | Skema database V3 (7 tabel: 6 inti + 1 pendukung)        |
| [03-class-diagram-oop.md](03-class-diagram-oop.md)           | Class diagram OOP (objek + perilaku bisnis, bukan tabel) |
| [04-hak-akses-rbac.md](04-hak-akses-rbac.md)                 | Matriks hak akses V3 dengan unified User table           |
| [05-fitur-alur-bisnis.md](05-fitur-alur-bisnis.md)           | Alur bisnis per aktor — tidak berubah dari V2.3          |
| [06-rencana-migrasi-data.md](06-rencana-migrasi-data.md)     | Migration script + data mapping V2 → V3                  |
| [07-rencana-refactor-kode.md](07-rencana-refactor-kode.md)   | Refactor checklist (Model, Resource, Controller, View)   |
| [08-trade-off-risiko.md](08-trade-off-risiko.md)             | Trade-off analysis + identifikasi risiko                 |
| [09-checklist-implementasi.md](09-checklist-implementasi.md) | Step-by-step checklist eksekusi migrasi                  |

---

## Diagram Visual

Lihat juga `docs/tabel-class-diagram-erd-v3.html` untuk visualisasi:

- **Tabel 1** — Class Diagram (OOP focus)
- **Diagram 1** — Class Diagram Mermaid
- **Tabel 2** — ERD (DB focus)
- **Diagram 2** — ERD Mermaid
- **Tabel 3** — Analisis Trade-off

---

## Ringkasan Cepat

### Yang Berubah Struktural

| Aspek           | V2 (12 tabel)                               | V3 (7 tabel)                     |
| --------------- | ------------------------------------------- | -------------------------------- |
| User tables     | 4 tabel (user, admin, supervisor, karyawan) | 1 tabel (tb_user terkonsolidasi) |
| Verifikasi      | tb_verifikasi terpisah                      | Inline ke tb_presensi            |
| Jadwal          | tb_jadwal                                   | tb_jadwal_pekerjaan (rename)     |
| Laporan & Rekap | 2 tabel                                     | tb_laporan_presensi (merged)     |
| Setting         | tb_setting                                  | tetap dipertahankan              |

### Yang TIDAK Berubah

- ✅ Semua fitur V2.3 tetap berjalan (presensi GPS, foto selfie, verifikasi, laporan, dll)
- ✅ Hak akses 3 role (admin/supervisor/karyawan) — logika RBAC sama
- ✅ Aturan keterlambatan Rp 10.000 / 10 menit tanpa batas atas (V2.3)
- ✅ Foto masuk hanya tampil setelah check-out (V2.3)
- ✅ Filter default hari ini untuk Jadwal & Detail Pekerjaan (V2.3)

### Status Implementasi

⚠️ **Kode belum diubah.** Folder ini hanya perencanaan. Lihat [09-checklist-implementasi.md](09-checklist-implementasi.md) untuk urutan eksekusi yang aman.

---

## Cara Memakai Dokumen Ini

1. **Mulai dari** [01-ringkasan-perubahan.md](01-ringkasan-perubahan.md) untuk gambaran besar
2. **Lalu** [02-database-schema.md](02-database-schema.md) untuk struktur tabel
3. **Lihat** [03-class-diagram-oop.md](03-class-diagram-oop.md) untuk model OOP
4. **Cek** [08-trade-off-risiko.md](08-trade-off-risiko.md) sebelum memutuskan implementasi
5. **Ikuti** [09-checklist-implementasi.md](09-checklist-implementasi.md) saat eksekusi
