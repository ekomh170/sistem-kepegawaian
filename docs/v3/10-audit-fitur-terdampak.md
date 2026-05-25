# Audit Fitur Terdampak Migrasi V2 → V3

## Sistem Informasi Kepegawaian — CV Boss Muda Mandiri

---

## 1. Tujuan Dokumen

Dokumen ini mengaudit **fitur V2.3 mana yang tidak bisa diikutkan** ke V3 kalau schema V3 dijalankan sesuai gambar ERD asli (6 tabel inti + 1 pendukung). Audit dilakukan dengan **grep langsung di codebase** — bukan asumsi.

Dokumen ini melengkapi [08-trade-off-risiko.md](08-trade-off-risiko.md) dengan data konkret tentang dependency fitur ke field/tabel yang akan dihapus.

> ⚠️ **Penting:** Doc 08 awalnya menganggap beberapa field karyawan "kemungkinan tidak dipakai" — audit ini membuktikan **anggapan itu salah**. Field-field tersebut aktif dipakai di banyak Filament Resource. Dokumen ini menjelaskan opsi mitigasi-nya.

---

## 2. Metodologi Audit

Dilakukan via grep di folder `app/`, `resources/`, dan `database/`:

```bash
grep -rn "<field_name>" app/ resources/ database/ \
  | grep -v vendor \
  | grep -v node_modules
```

Field yang dicek:

- Field tb_karyawan: `no_ktp`, `alamat`, `foto`, `tgl_masuk`, `status_kontrak`, `bidang_tugas`
- Audit trail di tb_verifikasi (overwrite vs append-only)

---

## 3. Hasil Audit Field-by-Field

### 3.1 `status_kontrak` (tetap / kontrak / freelance)

| Lokasi                                                             | Pemakaian                    |
| ------------------------------------------------------------------ | ---------------------------- |
| `app/Models/Karyawan.php`                                          | Cast enum                    |
| `app/Filament/Resources/Karyawans/Schemas/KaryawanForm.php`        | Form input (Select dropdown) |
| `app/Filament/Resources/Karyawans/Schemas/KaryawanInfolist.php`    | Display di view page         |
| `app/Filament/Resources/Karyawans/Tables/KaryawansTable.php`       | Column + filter              |
| `app/Filament/Resources/Akuns/Schemas/AkunForm.php`                | Form input                   |
| `app/Filament/Resources/Akuns/Pages/CreateAkun.php`                | Logic create                 |
| `app/Filament/Resources/Akuns/Pages/EditAkun.php`                  | Logic edit                   |
| `database/seeders/DatabaseSeeder.php`                              | Seed data demo               |
| `database/migrations/2026_04_24_140135_create_karyawans_table.php` | Schema                       |

**Fitur yang bergantung:**

- ✅ Filter tabel karyawan by status kontrak (lihat semua kontrak vs tetap vs freelance)
- ✅ Input data status saat tambah/edit karyawan
- ✅ Distribusi data realistis di seeder

**Kalau dihapus:** Filter tabel rusak, form tambah karyawan kehilangan field penting.

---

### 3.2 `bidang_tugas`

Pola pemakaian sama persis seperti `status_kontrak` (10 file).

**Fitur yang bergantung:**

- ✅ Input bidang tugas saat tambah/edit karyawan
- ✅ Kategorisasi karyawan by bidang (search di tabel)
- ✅ Display di profil karyawan

**Kalau dihapus:** Tidak bisa kategorisasi karyawan, search/filter rusak.

---

### 3.3 `tgl_masuk` (tanggal mulai kerja)

Pola pemakaian sama (10 file).

**Fitur yang bergantung:**

- ✅ Input tanggal mulai kerja
- ✅ Display di profil
- 🔜 Potensi fitur masa kerja, eligibility kontrak (kalau di-extend)

**Kalau dihapus:** Tidak bisa tahu kapan karyawan mulai kerja, fitur masa kerja masa depan tidak mungkin.

---

### 3.4 `alamat`

24 references di codebase (terbanyak dari semua field).

**Fitur yang bergantung:**

- ✅ Input alamat karyawan
- ✅ Display di profil + infolist
- 🔜 Surat resmi / dokumen yang butuh alamat

**Kalau dihapus:** Tidak ada alamat di profil karyawan.

---

### 3.5 `no_ktp` (Nomor KTP)

9 references.

**Fitur yang bergantung:**

- ✅ Input KTP karyawan
- ✅ Display di profil
- 🔜 Compliance KYC / laporan pajak (kalau di-extend)

**Kalau dihapus:** Tidak ada nomor KTP karyawan, compliance ke depan tidak mungkin.

---

### 3.6 `foto` (foto profil karyawan)

Tidak ditemukan referensi `karyawan->foto` di codebase grep (berbeda dari `foto_masuk`/`foto_keluar` di presensi).

**Fitur yang bergantung:** Sepertinya tidak aktif dipakai saat ini. Tapi field ada di DB schema dan migration.

**Kalau dihapus:** Sepertinya aman (perlu konfirmasi visual oleh user).

---

### 3.7 Audit Trail Verifikasi (`tb_verifikasi` history)

V2 punya `tb_verifikasi` sebagai tabel terpisah. Setiap verifikasi = 1 row baru. Kalau supervisor revisi keputusan, row baru ditambah.

V3 design **inline** ke `tb_presensi` — kolom `status_verifikasi`, `catatan_verifikasi`, `tgl_verifikasi` diubah langsung. **Overwrite, no history.**

**Fitur yang hilang:**

- ❌ Tidak ada history "siapa ubah keputusan kapan"
- ❌ Tidak bisa rollback verifikasi
- ❌ Kalau ada dispute (karyawan klaim sudah verified tapi sekarang ditolak), tidak ada bukti

---

## 4. Klasifikasi Fitur Terdampak

### Kategori A: Pasti RUSAK kalau V3 strict (tanpa mitigasi)

| #   | Fitur V2.3                          | Field Dependency                                                  | Impact                                  |
| --- | ----------------------------------- | ----------------------------------------------------------------- | --------------------------------------- |
| 1   | Input data karyawan lengkap         | `status_kontrak`, `tgl_masuk`, `bidang_tugas`, `alamat`, `no_ktp` | Form jadi seadanya, banyak field hilang |
| 2   | Filter karyawan by status_kontrak   | `status_kontrak`                                                  | Filter di KaryawansTable rusak          |
| 3   | Kategorisasi karyawan by bidang     | `bidang_tugas`                                                    | Search/column rusak                     |
| 4   | Tampilan profil karyawan (infolist) | 5 field                                                           | Section identitas jadi minimal          |
| 5   | Distribusi data realistis di seeder | 5 field                                                           | Seeder demo simplistic                  |

### Kategori B: HILANG PERMANENT (tidak ada mitigasi tanpa redesign V3)

| #   | Fitur V2.3                    | Alasan                                   |
| --- | ----------------------------- | ---------------------------------------- |
| 1   | Audit trail verifikasi        | V3 inline overwrite, tidak punya history |
| 2   | Rollback keputusan verifikasi | Tidak ada row history untuk rollback     |

### Kategori C: AMAN (jalan tanpa perubahan di V3)

Semua fitur lain di V2.3:

- ✅ Presensi GPS check-in/check-out
- ✅ Upload foto selfie (foto_masuk, foto_keluar di tb_presensi — dipertahankan sebagai "KEEP" field)
- ✅ Validasi radius geofence
- ✅ Aturan keterlambatan Rp 10.000/10 menit (V2.3)
- ✅ Foto masuk hanya tampil setelah check-out (V2.3)
- ✅ Workflow tugas (terima/tolak dengan alasan) — `alasan_tolak` dipertahankan
- ✅ Upload bukti pekerjaan before/after + keterangan
- ✅ Default filter hari ini di Jadwal & Detail Pekerjaan (V2.3)
- ✅ Tipe Laporan dikunci Bulanan untuk rekap (V2.3)
- ✅ PDF tampilkan jenis di header (V2.3)
- ✅ RBAC 3 role (admin / supervisor / karyawan)
- ✅ Setting runtime (kantor_lat, kantor_lng, dll) via tb_setting
- ✅ Login + redirect by role

---

## 5. Opsi Mitigasi

### Opsi A — Keep 5 Field di `tb_user` (Nullable)

```sql
ALTER TABLE tb_user
    ADD COLUMN no_ktp VARCHAR(255) NULL,
    ADD COLUMN alamat TEXT NULL,
    ADD COLUMN foto VARCHAR(255) NULL,
    ADD COLUMN tgl_masuk DATE NULL,
    ADD COLUMN status_kontrak ENUM('kontrak','tetap','freelance') NULL,
    ADD COLUMN bidang_tugas VARCHAR(255) NULL;
```

**Konsekuensi:**

- ✅ Semua fitur Kategori A tetap jalan
- ⚠️ `tb_user` jadi 16 kolom (dari 11 di rencana V3)
- ⚠️ 5 kolom hanya relevan untuk role=karyawan → **polimorfik nullable smell**
- ⚠️ Validation harus dilakukan di aplikasi (Filament FormRequest)
- ⚠️ Schema "ramping" V3 jadi tidak sesuai gambar ERD asli

**Total tabel V3:** 7 tabel (sesuai rencana). Tapi `tb_user` gemuk.

---

### Opsi B — Bikin `tb_karyawan_detail` (1:1 dengan tb_user)

```sql
CREATE TABLE tb_karyawan_detail (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED UNIQUE,
    no_ktp VARCHAR(255) NULL,
    alamat TEXT NULL,
    foto VARCHAR(255) NULL,
    tgl_masuk DATE NULL,
    status_kontrak ENUM('kontrak','tetap','freelance') NULL,
    bidang_tugas VARCHAR(255) NULL,
    FOREIGN KEY (user_id) REFERENCES tb_user(id) ON DELETE CASCADE
);
```

**Konsekuensi:**

- ✅ Semua fitur Kategori A tetap jalan
- ✅ `tb_user` tetap ramping (11 kolom)
- ✅ Schema strict: detail karyawan terpisah dari identitas
- ⚠️ Butuh JOIN saat butuh profil lengkap karyawan
- ⚠️ Total tabel V3 jadi **8 (6 inti + 2 pendukung)** — bukan 7
- ⚠️ Mirip struktur V2 (tb_user + tb_karyawan)

**Manfaat V3 "konsolidasi user" hilang sebagian** karena karyawan-detail dipisah.

---

### Opsi C — `tb_audit_verifikasi` untuk Audit Trail

```sql
CREATE TABLE tb_audit_verifikasi (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    presensi_id BIGINT UNSIGNED,
    supervisor_id BIGINT UNSIGNED,
    status_lama VARCHAR(20),
    status_baru VARCHAR(20),
    catatan TEXT NULL,
    tgl_perubahan DATETIME,
    FOREIGN KEY (presensi_id) REFERENCES tb_presensi(id) ON DELETE CASCADE,
    FOREIGN KEY (supervisor_id) REFERENCES tb_user(id) ON DELETE SET NULL
);
```

Trigger otomatis (atau Laravel Model Event) insert ke tabel ini setiap kali `status_verifikasi` berubah di `tb_presensi`.

**Konsekuensi:**

- ✅ Fitur Kategori B (audit trail) terpenuhi
- ⚠️ Total tabel V3 bertambah 1 (jadi 8 atau 9 tergantung Opsi A/B)
- ⚠️ Manfaat "verifikasi inline" jadi kontradiksi (data inline tapi history terpisah)
- ⚠️ Butuh implementasi observer/trigger di Laravel

---

## 6. Skenario Realistis

### Skenario 1 — V3 Strict (sesuai gambar ERD asli, 7 tabel)

| Aspek                         | Status             |
| ----------------------------- | ------------------ |
| Schema tabel                  | 7 (sesuai rencana) |
| Fitur Kategori A              | ❌ RUSAK           |
| Fitur Kategori B              | ❌ HILANG          |
| Fitur Kategori C              | ✅ AMAN            |
| Effort                        | 5-7 hari           |
| Sesuai dengan gambar ERD asli | ✅                 |

**Use case:** Kalau owner benar-benar tidak butuh 5 field karyawan dan tidak butuh audit verifikasi.

---

### Skenario 2 — V3 Pragmatis dengan Opsi A (tb_user gemuk)

| Aspek                         | Status                            |
| ----------------------------- | --------------------------------- |
| Schema tabel                  | 7 (sama dengan rencana)           |
| Fitur Kategori A              | ✅ JALAN                          |
| Fitur Kategori B              | ❌ HILANG                         |
| Fitur Kategori C              | ✅ AMAN                           |
| Effort                        | 5-7 hari                          |
| Sesuai dengan gambar ERD asli | ⚠️ Sebagian (tb_user lebih gemuk) |

**Trade-off:** Schema "ramping" 7 tabel tetap, tapi `tb_user` polimorfik nullable.

---

### Skenario 3 — V3 Pragmatis dengan Opsi A + B + C (full preservation)

| Aspek                         | Status                                           |
| ----------------------------- | ------------------------------------------------ |
| Schema tabel                  | 8 (6 inti + 2 pendukung + 1 audit) = **9 tabel** |
| Fitur Kategori A              | ✅ JALAN                                         |
| Fitur Kategori B              | ✅ JALAN                                         |
| Fitur Kategori C              | ✅ AMAN                                          |
| Effort                        | 7-10 hari                                        |
| Sesuai dengan gambar ERD asli | ❌ Schema bertambah 2 tabel dari rencana         |

**Trade-off:** Semua fitur V2.3 dipertahankan, tapi V3 "ramping" jadi mitos — total 9 tabel vs 12 V2 (cuma turun 3).

---

## 7. Decision Matrix Final

| Skenario            | Fitur Karyawan | Audit Verifikasi       | Total Tabel | Effort    | Risiko               |
| ------------------- | -------------- | ---------------------- | ----------- | --------- | -------------------- |
| **V2.3 Stay**       | ✅ Aman        | ✅ Ada (tb_verifikasi) | 12          | 0 hari    | Rendah               |
| **V3 Strict**       | ❌ Rusak       | ❌ Hilang              | 7           | 5-7 hari  | Tinggi (fitur rusak) |
| **V3 + Opsi A**     | ✅ Aman        | ❌ Hilang              | 7           | 5-7 hari  | Sedang               |
| **V3 + Opsi A+B+C** | ✅ Aman        | ✅ Ada                 | 9           | 7-10 hari | Sedang               |

---

## 8. Pertanyaan Strategis untuk Stakeholder

Sebelum decide skenario, jawab:

### Q1: Field karyawan (no_ktp, alamat, foto, tgl_masuk, status_kontrak, bidang_tugas) — dipakai bisnis?

- **Ya, semua dipakai** → Pilih Skenario 2 atau 3 (Opsi A atau B)
- **Tidak, bisa dihapus** → Konfirmasi tertulis ke owner → Skenario 1 (V3 strict)
- **Sebagian dipakai** → Identifikasi mana, mungkin keep sebagian di tb_user

### Q2: Supervisor pernah revisi keputusan verifikasi (disetujui ↔ ditolak)?

- **Ya pernah / mungkin akan** → Pilih Opsi C (`tb_audit_verifikasi`)
- **Tidak pernah** → Inline V3 OK, skip Opsi C

### Q3: Apa manfaat utama V3 yang Anda incar?

| Goal                                 | Skenario Cocok                            |
| ------------------------------------ | ----------------------------------------- |
| Schema benar-benar ramping (7 tabel) | Skenario 1 (V3 Strict) — tapi rusak fitur |
| Konsolidasi user (1 source of truth) | Skenario 2 (Opsi A)                       |
| Naming konsisten (semua user_id)     | Semua skenario V3                         |
| Verifikasi inline (1:1 jadi kolom)   | Skenario 1 atau 2 (skip Opsi C)           |
| Laporan + rekap unified              | Semua skenario V3                         |
| Audit trail history                  | Skenario 3 (Opsi C)                       |

---

## 9. Rekomendasi Berdasarkan Audit

### Kalau Sistem Masih Awal (data < 50 karyawan, belum production)

→ **Skenario 2 (V3 + Opsi A)**: Field karyawan tetap di tb_user (nullable). Schema 7 tabel sesuai rencana, fitur tidak rusak.

### Kalau Sistem Sudah Production (data live, user aktif)

→ **V2.3 Stay**: Refactor V3 tidak worth risiko vs benefit. V2.3 sudah stabil dan punya semua fitur.

### Kalau Owner Konfirm Audit Verifikasi WAJIB

→ **Skenario 3 (Opsi A + C)**: Semua fitur dipertahankan, tapi accept schema jadi 8 tabel (bukan 7).

### Kalau Pure Refactor Latihan / Belajar

→ **Skenario 1 (V3 Strict)**: Jalankan di branch terpisah, tidak deploy ke production. Tujuan belajar OOP / schema design saja.

---

## 10. Update yang Harus Dilakukan di Doc Lain

Setelah audit ini, file V3 lain perlu di-revisi untuk konsistensi:

| File                                                         | Update                                                                                                                                                                |
| ------------------------------------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| [08-trade-off-risiko.md](08-trade-off-risiko.md)             | Tambah link ke dokumen ini di section 4.1 (Konsekuensi Lost Field). Update "kemungkinan tidak dipakai" → "TERBUKTI dipakai aktif (lihat 10-audit-fitur-terdampak.md)" |
| [02-database-schema.md](02-database-schema.md)               | Tambah note: "Schema ini asumsi V3 Strict. Untuk preservasi fitur, lihat opsi mitigasi di 10-audit-fitur-terdampak.md"                                                |
| [09-checklist-implementasi.md](09-checklist-implementasi.md) | Tambah Fase 0.5: "Audit fitur terdampak — putuskan skenario (1/2/3)" sebelum Fase 1                                                                                   |
| [00-README.md](00-README.md)                                 | Tambah entry untuk dokumen ini                                                                                                                                        |

---

**Kesimpulan:** Doc 08-trade-off-risiko terlalu optimis di section 4.1 ("kemungkinan tidak dipakai"). Audit aktual menunjukkan **5 dari 6 field karyawan dipakai aktif** di Filament Resources. Tanpa mitigasi (Opsi A/B), V3 Strict akan merusak fitur input/edit data karyawan, filter tabel, dan tampilan profil. Pilih skenario yang sesuai dengan kebutuhan bisnis sebelum mulai implementasi.
