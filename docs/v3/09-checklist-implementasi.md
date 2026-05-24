# Checklist Implementasi V3

## Sistem Informasi Kepegawaian — CV Boss Muda Mandiri

---

## 1. Cara Memakai Checklist

Setiap item harus selesai sebelum lanjut ke item berikutnya. Tandai dengan `[x]` setelah selesai. Untuk skip item, tulis alasan di kolom catatan.

Branch kerja: `main-dev-erd-v3`

---

## 2. Fase 0 — Persiapan

- [ ] Backup database V2 lengkap
    ```bash
    mysqldump -u root -p sistem_kepegawaian > backup_v2_pre_migrasi_$(date +%Y%m%d).sql
    ```
- [ ] Backup folder `storage/app/public/presensi/` (foto selfie)
- [ ] Konfirmasi 5 open questions di [08-trade-off-risiko.md#6](08-trade-off-risiko.md) ke stakeholder
- [ ] Setup staging environment yang mirror production
- [ ] Pull branch `main-dev-erd-v3` dari `main-dev-tanpa-gaji`
- [ ] Review semua dokumen di folder `docs/v3/` sebagai briefing

---

## 3. Fase 1 — Setup Enum

- [ ] Buat folder `app/Enums/`
- [ ] Buat `app/Enums/Role.php`
- [ ] Buat `app/Enums/StatusJadwal.php`
- [ ] Buat `app/Enums/StatusTugas.php`
- [ ] Buat `app/Enums/StatusPresensi.php`
- [ ] Buat `app/Enums/StatusVerifikasi.php`
- [ ] Buat `app/Enums/StatusBukti.php`
- [ ] Buat `app/Enums/JenisLaporan.php`
- [ ] Test loading enum: `php artisan tinker` → `App\Enums\Role::Admin->value`

---

## 4. Fase 2 — Migration Phase 1 (Additive)

- [ ] Buat migration `add_v3_columns_to_tb_user` (nik, no_hp, posisi)
- [ ] Buat migration `add_verifikasi_columns_to_tb_presensi` (4 kolom inline)
- [ ] Buat migration `add_dibuat_oleh_to_tb_jadwal`
- [ ] Buat migration `add_user_id_to_tb_jadwal` (sementara, sebelum drop karyawan_id)
- [ ] Buat migration `add_user_id_to_tb_detail_pekerjaan`
- [ ] Buat migration `add_user_id_to_tb_presensi`
- [ ] Buat migration `add_user_id_to_tb_bukti_pekerjaan`
- [ ] Buat migration `add_v3_columns_to_tb_laporan` (user*id, jumlah*\*, total_potongan, file_path)
- [ ] Run: `php artisan migrate`
- [ ] Verifikasi schema: `php artisan db:show`
- [ ] Sanity check: pastikan kolom lama (karyawan_id) masih ada (untuk Phase 2)

---

## 5. Fase 3 — Migration Phase 2 (Backfill)

- [ ] Buat migration `backfill_user_profiles` (nik/no_hp dari admin/supervisor/karyawan)
- [ ] Buat migration `backfill_verifikasi_inline` (dari tb_verifikasi)
- [ ] Buat migration `backfill_user_id_on_relations` (karyawan_id → user_id semua tabel)
- [ ] Buat migration `backfill_laporan_presensi_merged` (rekap → laporan)
- [ ] Run: `php artisan migrate`
- [ ] Sanity check SQL (lihat [06-rencana-migrasi-data.md#3](06-rencana-migrasi-data.md))
    - [ ] Semua karyawan punya nik
    - [ ] Semua presensi yang punya verifikasi V2 sudah ter-inline
    - [ ] Semua tb_jadwal punya user_id
    - [ ] Jumlah rekap V2 = jumlah laporan tipe Bulanan di V3

---

## 6. Fase 4 — Refactor Model

- [ ] Update `app/Models/User.php`:
    - [ ] Tambah cast role ke `Role::class`
    - [ ] Tambah scope `karyawan()`, `admin()`, `supervisor()`, `aktif()`
    - [ ] Tambah helper `isAdmin()`, `isSupervisor()`, `isKaryawan()`, `hasRole()`
    - [ ] Tambah relasi `jadwalKerja`, `jadwalDibuat`, `presensiSaya`, `verifikasiSaya`, `tugasSaya`, `buktiSaya`, `laporanSubjek`, `laporanDibuat`
- [ ] Rename `app/Models/Jadwal.php` → `app/Models/JadwalPekerjaan.php`
    - [ ] Update `$table` ke `tb_jadwal_pekerjaan`
    - [ ] Update relasi `karyawan` → `user`
    - [ ] Update relasi `admin` → `pembuatJadwal`
- [ ] Update `app/Models/DetailPekerjaan.php`:
    - [ ] Relasi `karyawan()` → `user()` (FK user_id)
- [ ] Update `app/Models/Presensi.php`:
    - [ ] Relasi `karyawan()` → `user()` (FK user_id)
    - [ ] Hapus relasi `verifikasi()` (HasOne)
    - [ ] Tambah relasi `verifikator()` (BelongsTo User)
    - [ ] Tambah field verifikasi inline di `$fillable`
    - [ ] Tambah method `verifikasi(User, StatusVerifikasi, string)`, `sudahDiverifikasi()`, `sudahCheckOut()`
- [ ] Update `app/Models/BuktiPekerjaan.php`:
    - [ ] Relasi `karyawan()` → `user()`
- [ ] Buat `app/Models/LaporanPresensi.php` baru (gabungan)
- [ ] Hapus model lama:
    - [ ] `app/Models/Admin.php`
    - [ ] `app/Models/Supervisor.php`
    - [ ] `app/Models/Karyawan.php`
    - [ ] `app/Models/Verifikasi.php`
    - [ ] `app/Models/RekapPresensiBulanan.php`
    - [ ] `app/Models/Laporan.php`
- [ ] Test loading model: `php artisan tinker` → `User::first()->presensiSaya`

---

## 7. Fase 5 — Refactor Filament Resources

### 7.1 User Resources

Pilih pendekatan: **3 navigation entries** (rekomendasi).

- [ ] Update `KaryawanResource` pakai model `User` + scope role karyawan
- [ ] Update `AdminResource` pakai model `User` + scope role admin
- [ ] Update `SupervisorResource` pakai model `User` + scope role supervisor (sebelumnya disabled — re-enable kalau perlu)
- [ ] Update form Schema masing-masing dengan field role-specific

### 7.2 Operasional Resources

- [ ] Rename `JadwalResource` → `JadwalPekerjaanResource`
- [ ] Update `JadwalPekerjaanResource` referensi `karyawan` → `user`
- [ ] Update `DetailPekerjaanResource` referensi
- [ ] Update `PresensiResource`:
    - [ ] Referensi `karyawan.user.nama` → `user.nama`
    - [ ] Tambah section "Verifikasi" di form (visible untuk supervisor)
    - [ ] Update tabel: tampilkan kolom `status_verifikasi`
- [ ] Update `BuktiPekerjaanResource` referensi

### 7.3 Hapus Resources

- [ ] Hapus `app/Filament/Resources/Verifikasis/` (full folder)
- [ ] Hapus `app/Filament/Resources/RekapPresensiBulanans/` (full folder)

### 7.4 Buat Resource Baru

- [ ] Buat `app/Filament/Resources/LaporanPresensis/LaporanPresensiResource.php`
    - [ ] Form: Select tipe (rekap vs presensi global), jenis (Harian/Mingguan/Bulanan/Tahunan)
    - [ ] Logic: tipe = rekap → kunci jenis ke Bulanan
    - [ ] Tabel: kolom judul, jenis, periode, karyawan, generator, tgl_generate
    - [ ] Action export PDF/Excel/CSV
- [ ] Hapus `app/Filament/Resources/Laporans/` (resource lama)

### 7.5 Visibility & RBAC

- [ ] Test semua resource: login sebagai admin, supervisor, karyawan
- [ ] Sidebar admin: muncul Karyawan, Admin, Supervisor, Jadwal Pekerjaan, Detail Pekerjaan, Bukti Pekerjaan, Presensi, Laporan Presensi, Setting
- [ ] Sidebar supervisor: hanya Presensi, Laporan Presensi
- [ ] Karyawan: redirect ke `/karyawan` (portal mobile)

---

## 8. Fase 6 — Refactor Controller

- [ ] Update `PresensiController.php`:
    - [ ] `checkIn`: `karyawan_id` → `user_id`
    - [ ] `checkOut`: idem
    - [ ] `viewHistory`: query `Presensi::where('user_id', ...)`
- [ ] Update `KaryawanMobileController.php`:
    - [ ] `beranda`: `Auth::user()->karyawan->...` → `Auth::user()->...`
    - [ ] `riwayat`: query langsung pakai user_id
    - [ ] `daftarTugas`: relasi sesuaikan
- [ ] Update `LaporanExportController.php`:
    - [ ] Query rekap: `LaporanPresensi` instead of `RekapPresensiBulanan`
    - [ ] Pass jenis tetap (sudah ada di V2.3)
- [ ] Update `app/Exports/LaporanPresensiExport.php` kalau perlu rename

---

## 9. Fase 7 — Refactor Views

- [ ] `resources/views/karyawan/beranda.blade.php` — hapus `$karyawan->` references
- [ ] `resources/views/karyawan/presensi-masuk.blade.php` — relasi
- [ ] `resources/views/karyawan/presensi-pulang.blade.php` — relasi
- [ ] `resources/views/karyawan/riwayat.blade.php` — relasi
- [ ] `resources/views/karyawan/jadwal.blade.php` — query update
- [ ] `resources/views/karyawan/tugas.blade.php` — relasi
- [ ] `resources/views/exports/laporan-pdf.blade.php` — `$p->karyawan?->user?->nama` → `$p->karyawan?->nama`
- [ ] `resources/views/exports/laporan-presensi-pdf.blade.php` — sama
- [ ] `resources/views/exports/laporan-pekerjaan-pdf.blade.php` — sama
- [ ] Search global di `resources/views/`: `grep -r "karyawan->user" resources/views/`

---

## 10. Fase 8 — Refactor Seeder

- [ ] Update `database/seeders/DatabaseSeeder.php`:
    - [ ] Hapus `Admin::create`, `Supervisor::create`, `Karyawan::create`
    - [ ] Tambah `User::create([role, nik, no_hp, posisi, ...])` langsung
    - [ ] Update seeder Jadwal: `user_id` + `dibuat_oleh`
    - [ ] Update seeder Presensi: `user_id`
    - [ ] Update seeder rekap → laporan_presensi
- [ ] Run: `php artisan db:seed --class=DatabaseSeeder` (di dev DB)
- [ ] Verifikasi data demo lengkap

---

## 11. Fase 9 — Testing

### 11.1 Manual E2E Test

Login sebagai 3 role, lakukan operasi end-to-end:

#### Admin

- [ ] Login `admin@example.com` → masuk `/admin`
- [ ] Menu Karyawan: list, view, create, edit, delete
- [ ] Menu Admin: list, view, create, edit, delete (untuk akun admin baru)
- [ ] Menu Supervisor: list, view, create, edit, delete
- [ ] Menu Jadwal Pekerjaan: filter hari ini default aktif, create jadwal range tanggal, batalkan jadwal
- [ ] Menu Detail Pekerjaan: filter hari ini, create dengan map picker
- [ ] Menu Presensi: view list, view detail (foto masuk visible hanya kalau sudah check-out)
- [ ] Menu Bukti Pekerjaan: review, approve, reject
- [ ] Menu Laporan Presensi: create tipe rekap (jenis dikunci Bulanan), create tipe presensi global (semua jenis), export PDF (header jenis terisi)
- [ ] Menu Setting: edit kantor_lat, kantor_lng, radius, toleransi_menit

#### Supervisor

- [ ] Login `supervisor@example.com` → masuk `/supervisor`
- [ ] Sidebar hanya: Presensi, Laporan Presensi
- [ ] Menu Presensi: view list, edit untuk verifikasi (section verifikasi muncul)
    - [ ] Set status_verifikasi = disetujui → save → diverifikasi_oleh otomatis terisi
    - [ ] Set status_verifikasi = ditolak + catatan → save
- [ ] Menu Laporan Presensi: create rekap, export PDF

#### Karyawan

- [ ] Login `karyawan@example.com` → masuk `/karyawan`
- [ ] Beranda: tampil presensi hari ini + ringkasan tugas
- [ ] Presensi Masuk: GPS valid → ambil foto → submit → record terbuat
- [ ] Presensi Pulang: GPS + foto → submit → jam_keluar terisi → foto_masuk muncul di halaman
- [ ] Tugas: terima 1 tugas + tolak 1 tugas dengan alasan
- [ ] Upload Bukti: foto before + after + keterangan
- [ ] Jadwal Mingguan: list 7 hari ke depan
- [ ] Riwayat: 30 hari terakhir, total potongan bulan berjalan

### 11.2 Automated Test (kalau ada)

- [ ] Run: `composer test`
- [ ] Pastikan tidak ada test fail karena referensi model lama

---

## 12. Fase 10 — Migration Phase 3 (Cleanup)

⚠️ **WAJIB BACKUP DB SEBELUM FASE INI**

- [ ] Backup ulang DB (post-refactor, pre-cleanup):
    ```bash
    mysqldump -u root -p sistem_kepegawaian > backup_v3_pre_cleanup_$(date +%Y%m%d).sql
    ```
- [ ] Buat migration `drop_old_user_tables`:
    - [ ] Drop FK karyawan_id, admin_id di semua tabel anak
    - [ ] Drop kolom karyawan_id, admin_id
    - [ ] Drop tb_verifikasi, tb_rekap_presensi_bulanan, tb_karyawan, tb_admin, tb_supervisor
- [ ] Buat migration `rename_jadwal_to_jadwal_pekerjaan`:
    - [ ] `Schema::rename('tb_jadwal', 'tb_jadwal_pekerjaan')`
- [ ] Buat migration `rename_laporan_to_laporan_presensi`:
    - [ ] `Schema::rename('tb_laporan', 'tb_laporan_presensi')`
- [ ] Run: `php artisan migrate`
- [ ] Sanity check: semua fitur masih jalan
- [ ] Final E2E test ulang (Fase 9 ringkas)

---

## 13. Fase 11 — Update Dokumentasi

- [ ] Update [docs/v2/01-ringkasan-sistem.md](../v2/01-ringkasan-sistem.md) → tandai versi sebagai "superseded by V3"
- [ ] Update [README.md](../../README.md):
    - [ ] Bump versi ke V3
    - [ ] Update deskripsi 12 tabel → 7 tabel
    - [ ] Tambah link ke docs/v3/
- [ ] Update [CLAUDE.md](../../CLAUDE.md):
    - [ ] Konvensi database: tabel V3 (7 tabel)
    - [ ] Helper: `User::karyawan()->get()` instead of `Karyawan::all()`
    - [ ] Aturan keterlambatan: sama (Rp 10.000/10 menit tanpa cap)
- [ ] Buat `docs/progress_report.md` section V3 dengan:
    - [ ] List 8 perubahan struktural
    - [ ] List file dimodifikasi
    - [ ] Migration steps
- [ ] Update [docs/tabel-class-diagram-erd-v3.html](../tabel-class-diagram-erd-v3.html) kalau ada perubahan terakhir

---

## 14. Fase 12 — Commit & Push

- [ ] Group commits per fase (jangan 1 mega-commit):
    - [ ] `feat(enums): tambah PHP enum classes V3`
    - [ ] `feat(db): migration phase 1 — add v3 columns`
    - [ ] `feat(db): migration phase 2 — backfill data V2 → V3`
    - [ ] `refactor(models): konsolidasi User + hapus Admin/Supervisor/Karyawan/Verifikasi`
    - [ ] `refactor(models): rename Jadwal → JadwalPekerjaan + buat LaporanPresensi`
    - [ ] `refactor(filament): UserResource konsolidasi + hapus VerifikasiResource`
    - [ ] `refactor(filament): LaporanPresensiResource gabungan rekap+laporan`
    - [ ] `refactor(controllers): update referensi karyawan_id → user_id`
    - [ ] `refactor(views): update relasi karyawan → user di blade`
    - [ ] `feat(db): migration phase 3 — cleanup drop+rename`
    - [ ] `docs(v3): finalisasi dokumentasi V3`
- [ ] Push ke `ekomh/main-dev-erd-v3`
- [ ] Buat PR ke `main-dev` untuk review

---

## 15. Fase 13 — Production Rollout

- [ ] Stakeholder approval terkait perubahan (especially: open questions diselesaikan)
- [ ] Schedule maintenance window (akhir pekan ideal)
- [ ] Pre-rollout: backup production DB
- [ ] Deploy code ke production
- [ ] Run migration di production
- [ ] Smoke test production:
    - [ ] Login 3 role
    - [ ] 1 presensi end-to-end
    - [ ] 1 generate laporan
- [ ] Monitor 24 jam: error log, user reports

---

## 16. Rollback Plan

Kalau ada blocker di fase 1-10 (sebelum cleanup):

- Rollback migration: `php artisan migrate:rollback --step=N`
- Revert code: `git revert HEAD`

Kalau ada blocker setelah fase 10 (cleanup):

- Restore DB dari backup: `mysql -u root -p sistem_kepegawaian < backup_v3_pre_cleanup_*.sql`
- Revert code: checkout branch sebelum V3

**WAJIB:** sebelum fase 10, pastikan ada minimal 2 backup DB di lokasi terpisah.

---

## 17. Kriteria Sukses (Definition of Done)

V3 dianggap **siap merge ke main** jika:

- [ ] Semua checklist fase 0-9 selesai (Phase 3 cleanup boleh ditunda)
- [ ] Tidak ada error log saat E2E test
- [ ] 3 role bisa login & operasi normal
- [ ] Foto masuk hanya tampil setelah check-out (V2.3 — tetap)
- [ ] Rumus keterlambatan Rp 10.000/10 menit tanpa cap (V2.3 — tetap)
- [ ] Verifikasi inline berfungsi (status_verifikasi terupdate)
- [ ] Laporan PDF tetap menampilkan jenis di header
- [ ] Tidak ada PHP error di `storage/logs/laravel.log`

---

**Selamat mengimplementasi V3!** 🚀
