# Trade-off & Analisis Risiko V3

## Sistem Informasi Kepegawaian — CV Boss Muda Mandiri

---

## 1. Kelebihan Desain V3

### 1.1 Schema Lebih Ramping (12 → 7 tabel)

**Manfaat:**

- Mudah dipahami developer baru
- ER diagram tidak overwhelming
- Backup/dump DB lebih cepat (lebih sedikit metadata)

**Quantifiable:** ~42% pengurangan jumlah tabel.

### 1.2 Konsolidasi User (1 source of truth)

**Sebelum (V2):**

```sql
SELECT u.nama, k.nik, k.posisi_karyawan
FROM tb_user u
JOIN tb_karyawan k ON k.user_id = u.id
WHERE u.id = ?
```

**Sesudah (V3):**

```sql
SELECT nama, nik, posisi FROM tb_user WHERE id = ?
```

**Manfaat:**

- Hapus 1 JOIN dari setiap query yang butuh profil user
- Lebih cepat di N+1 scenarios
- Tidak ada risiko orphan record (admin/supervisor/karyawan tanpa user)

### 1.3 Naming Konsisten (semua user_id)

**Sebelum:** developer harus ingat `karyawan_id` vs `admin_id` vs `supervisor_id`. Sering bingung saat copy-paste query.

**Sesudah:** semua FK ke user adalah `user_id`. Hanya beda nama relasi semantik (`karyawan`, `pembuatJadwal`, `verifikator`) — tapi schema-level uniform.

### 1.4 Verifikasi Inline (Hapus 1 Tabel)

**Manfaat:**

- 1 JOIN lebih sedikit untuk query "presensi yang sudah diverifikasi"
- Relasi 1:1 di-encode di schema-level (bukan implicit via aplikasi)
- Mudah filter di tabel Filament: `status_verifikasi = 'pending'` langsung di tb_presensi

### 1.5 Unified Laporan & Rekap

**Sebelum:** 2 endpoint export (`/laporan/export/*` untuk rekap bulanan vs `/laporan/export-presensi/*` untuk harian). Logic dispatch via judul.

**Sesudah:** 1 tabel `tb_laporan_presensi` dengan kolom `jenis` membedakan. Logic dispatch berdasarkan kolom `jenis` + `user_id IS NULL` (agregat) vs `user_id IS NOT NULL` (rekap per-karyawan).

### 1.6 Type Safety via PHP Enum

**Sebelum:**

```php
if ($user->role === 'admin') { ... }  // typo 'admni' jadi bug silent
```

**Sesudah:**

```php
if ($user->role === Role::Admin) { ... }  // IDE autocomplete, compile-time check
```

---

## 2. Kekurangan / Risiko Desain V3

### 2.1 tb_user Polimorfik & Nullable

**Risiko:** kolom `nik`, `no_hp`, `posisi` nullable di DB tapi semantically required untuk role tertentu.

| Field    | Wajib untuk                          | Nullable untuk       |
| -------- | ------------------------------------ | -------------------- |
| `nik`    | karyawan (unique), admin, supervisor | — (semua role butuh) |
| `no_hp`  | karyawan, supervisor                 | admin (opsional)     |
| `posisi` | karyawan                             | admin, supervisor    |

**Mitigasi:**

- Validation di aplikasi (FormRequest, Filament form validation)
- Custom rule: "kalau role=karyawan, nik+posisi wajib"
- Atau: bikin constraint di DB level dengan CHECK constraint (MySQL 8+)

### 2.2 Migrasi Data Berat & Tidak Reversible

**Risiko:** setelah Phase 3 (drop tb_admin/supervisor/karyawan/verifikasi), tidak ada cara restore tanpa backup.

**Mitigasi:**

- WAJIB backup DB sebelum Phase 3
- Run staging environment dulu untuk validasi
- Phase 1+2 selesai → run aplikasi 1-2 hari di dual-mode (kode lama + kolom baru) → confirm tidak ada bug → baru jalankan Phase 3

### 2.3 Polimorfik Laporan (Sparse Table)

`tb_laporan_presensi` punya 2 mode:

- **Agregat:** `user_id = NULL`, `jumlah_* = NULL`
- **Rekap per-karyawan:** `user_id != NULL`, `jumlah_* != NULL`

**Risiko:**

- Query yang select kolom statistik dari laporan agregat akan return NULL → harus null-safe
- Schema kurang strict, gampang typo (mis. lupa isi `jumlah_hadir` saat insert rekap)

**Mitigasi:**

- Application-level validation di Model (mis. `LaporanPresensi::booted` event)
- Atau: tetap 2 tabel terpisah (rekap + laporan) — trade off antara DRY dan schema strict

### 2.4 FK Self-Referential ke tb_user

Tabel `tb_jadwal_pekerjaan`, `tb_presensi`, `tb_laporan_presensi` semua punya 2 FK ke `tb_user`:

```
tb_jadwal_pekerjaan: user_id (karyawan), dibuat_oleh (admin)
tb_presensi: user_id (karyawan), diverifikasi_oleh (supervisor)
tb_laporan_presensi: user_id (karyawan), generated_by (admin)
```

**Risiko:** query JOIN dua kali ke tb_user untuk satu row:

```sql
SELECT p.*, k.nama as karyawan_nama, s.nama as supervisor_nama
FROM tb_presensi p
JOIN tb_user k ON k.id = p.user_id
LEFT JOIN tb_user s ON s.id = p.diverifikasi_oleh
```

Tidak fatal, tapi performa lebih lambat dari V2 yang punya FK ke tabel role-specific yang lebih kecil.

**Mitigasi:**

- Index `tb_user.role` agar JOIN cepat
- Cache user data di Redis (kalau perlu, untuk dashboard real-time)

### 2.5 Audit Trail Verifikasi Hilang

V2 punya `tb_verifikasi` sebagai append-only log. Kalau supervisor ubah keputusan, record baru ditambah.

V3 inline ke `tb_presensi` — kalau supervisor ubah dari `disetujui` ke `ditolak`, **record asli overwritten**, tidak ada history.

**Mitigasi:**

- Untuk audit trail, tambah tabel terpisah `tb_audit_verifikasi` (append-only) **kalau diperlukan**
- Atau: pakai Laravel package seperti `spatie/laravel-activitylog` untuk audit otomatis
- Untuk sistem internal CV ini, audit trail mungkin tidak critical → diterima trade-off

### 2.6 Eloquent Relasi Multi-Role di User

V2: `$user->karyawan` jelas relasinya. V3: `$user` adalah karyawan/admin/supervisor sekaligus model.

**Risiko developer error:**

```php
// Bisa loop relasi yang tidak relevan
$user->jadwalDibuat // hanya relevan kalau user role=admin
$user->verifikasiSaya // hanya relevan kalau user role=supervisor
```

**Mitigasi:**

- Konvensi naming yang jelas (jadwalDibuat vs jadwalKerja sudah cukup deskriptif)
- Documentation di model docblock
- Atau: bikin trait/interface untuk role-specific behavior

---

## 3. Risiko Implementasi

### 3.1 Downtime Saat Migrasi Production

**Risiko:** migrasi schema bisa butuh 10-30 menit kalau data presensi sudah ratusan ribu rows. Selama Phase 3 (drop tabel + rename), aplikasi harus down.

**Mitigasi:**

- Lakukan migrasi di jam off (malam, weekend)
- Pre-announce maintenance window
- Phase 1+2 bisa dijalankan tanpa downtime (additive only)

### 3.2 Bugs di Code Refactor (5 layer)

**Risiko:** refactor besar = banyak titik kegagalan. Khususnya:

- View blade yang melibatkan relasi `$x->karyawan->user->nama` → typo bisa silent fail
- Filament Resource yang pakai filter by role — kalau salah scope, data karyawan bisa tercampur

**Mitigasi:**

- Manual E2E testing per fitur sebelum merge
- Bikin checklist visual: buka tiap halaman, cek tiap form, cek tiap tabel
- Feature flag (kalau memungkinkan): jalankan V2 dan V3 berdampingan di staging

### 3.3 Filament Resource Konflik di Sidebar

V3 punya 3 navigation entries untuk User (KaryawanResource, AdminResource, SupervisorResource) dengan model yang sama. Filament v5 mungkin tidak nyaman dengan multiple resource share same model.

**Mitigasi:**

- Test di Filament v5 sebelum implementasi
- Alternatif: 1 UserResource dengan filter by role + 3 quick-link di dashboard

### 3.4 Backward Compatibility URL

Beberapa URL Filament punya pattern `/admin/karyawans/{id}/edit`. Kalau resource dihapus dan diganti dengan UserResource, URL berubah.

**Risiko:** bookmark user / link di email lama tidak berfungsi.

**Mitigasi:**

- Kalau pakai pendekatan 3 navigation entries (KaryawanResource pakai User model), URL tetap `/admin/karyawans/{id}/edit` — kompatibel.

---

## 4. Risiko Bisnis

### 4.1 Konsekuensi Lost Field

Field V2 yang dihapus di V3:

| Field V2                     | Risiko Bisnis                                    |
| ---------------------------- | ------------------------------------------------ |
| `tb_karyawan.no_ktp`         | Compliance KYC kalau dipakai untuk laporan pajak |
| `tb_karyawan.alamat`         | Tidak bisa kirim surat resmi                     |
| `tb_karyawan.foto`           | Tidak ada foto profil di kartu identitas         |
| `tb_karyawan.tgl_masuk`      | Tidak bisa hitung masa kerja                     |
| `tb_karyawan.status_kontrak` | Tidak bisa filter karyawan tetap vs kontrak      |
| `tb_karyawan.bidang_tugas`   | Tidak bisa kategorisasi                          |

**Mitigasi:**

- Konfirmasi ke owner: apakah field-field ini benar-benar tidak dipakai?
- Kalau ada yang masih dipakai → keep di tb_user (nullable) atau bikin tabel `tb_karyawan_detail` (1:1 dengan tb_user, hanya untuk role=karyawan)
- Audit fitur saat ini (V2.3): cek apakah ada Filament Resource / view / report yang menampilkan field ini

### 4.2 Konsekuensi Hapus Audit Verifikasi

Kalau bisnis butuh trail "siapa yang ubah verifikasi kapan", V3 inline kehilangan ini.

**Mitigasi:**

- Tanya ke supervisor: apakah pernah revisi verifikasi?
- Kalau iya: bikin `tb_audit_verifikasi` terpisah dari awal

---

## 5. Decision Matrix

| Aspek               | Pertahankan V2       | Migrasi ke V3                   |
| ------------------- | -------------------- | ------------------------------- |
| Effort migrasi      | 0 hari               | ~5 hari                         |
| Kompleksitas schema | Tinggi (12 tabel)    | Rendah (7 tabel)                |
| Performance query   | Sedang (banyak JOIN) | Lebih baik (lebih sedikit JOIN) |
| Maintainability     | Sedang               | Tinggi                          |
| Risiko bug refactor | 0                    | Sedang                          |
| Reversibility       | N/A                  | Sulit (perlu backup)            |

**Rekomendasi:** migrasi ke V3 worth kalau:

- Sistem masih awal (belum banyak data live)
- Tim developer berencana long-term maintenance
- Ada kebutuhan extend fitur (mis. multi-cabang, multi-perusahaan)

**Skip V3 kalau:**

- Sistem sudah stabil di production dengan data lama yang banyak
- Tidak ada developer untuk maintenance long-term
- Fitur V2.3 sudah cukup untuk kebutuhan bisnis

---

## 6. Open Questions untuk Stakeholder

Sebelum implementasi, konfirmasi ke owner:

1. ❓ Field karyawan yang akan dihapus (no_ktp, alamat, foto, tgl_masuk, status_kontrak, bidang_tugas) — apakah masih dipakai untuk fitur lain?
2. ❓ Audit trail verifikasi (siapa ubah kapan) — apakah diperlukan untuk compliance / dispute resolution?
3. ❓ Multi-cabang / multi-perusahaan — apakah dalam roadmap? Kalau iya, V3 sebaiknya juga tambah `tb_cabang` / `cabang_id`.
4. ❓ Sistem absensi mobile native (Android/iOS) — apakah dalam roadmap? Akan butuh API endpoint stabil — refactor V3 berdampak ke API contract.
5. ❓ Reporting yang lebih kompleks (e.g. analytics, dashboard BI) — apakah perlu data warehouse terpisah?

---

Selanjutnya: [09-checklist-implementasi.md](09-checklist-implementasi.md)
