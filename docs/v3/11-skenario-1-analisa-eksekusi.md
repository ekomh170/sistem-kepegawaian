# Analisa Eksekusi Skenario 1 — V3 Strict

## Sistem Informasi Kepegawaian — CV Boss Muda Mandiri

> **Dokumen ini menjawab:** "Kalau saya benar-benar eksekusi **Skenario 1 (V3 Strict)**, apa hasil akhirnya, dan penyesuaian/perbaikan kode apa saja yang harus saya lakukan?"
>
> Analisa di bawah **bukan asumsi** — sudah dicek langsung ke kode aktual di branch `main-dev-erd-v3` (kondisi per 2026-05-30, masih 100% struktur V2.3).

---

## 1. Apa Itu Skenario 1 (V3 Strict)

Skenario 1 = jalankan ERD V3 **persis sesuai gambar asli**, tanpa kompromi:

| Aturan Skenario 1                                                      | Konsekuensi                                                                              |
| ---------------------------------------------------------------------- | ---------------------------------------------------------------------------------------- |
| Hanya **7 tabel** (6 inti + `tb_setting`)                              | Turun dari 12 tabel V2                                                                   |
| 4 tabel user → **1 `tb_user`**                                         | `tb_admin`, `tb_supervisor`, `tb_karyawan` dihapus                                       |
| **6 field karyawan DIHAPUS**                                           | `no_ktp`, `alamat`, `foto`, `tgl_masuk`, `status_kontrak`, `bidang_tugas` + `gaji_pokok` |
| `posisi_karyawan` → `posisi` di `tb_user`                              | rename kolom                                                                             |
| Verifikasi **inline** ke `tb_presensi`                                 | `tb_verifikasi` dihapus, **tanpa audit trail**                                           |
| `tb_laporan` + `tb_rekap_presensi_bulanan` → **`tb_laporan_presensi`** | rekap & laporan jadi satu tabel polimorfik                                               |
| `tb_jadwal` → `tb_jadwal_pekerjaan`                                    | rename tabel                                                                             |
| Semua FK ke user pakai `user_id`                                       | `karyawan_id`, `admin_id` dibuang                                                        |

**Inti Skenario 1:** prioritaskan skema ramping di atas preservasi fitur. Field & fitur yang menghalangi keramping­an **direlakan hilang**.

---

## 2. Hasil Akhir — Yang HILANG vs Yang TETAP

### 2.1 Yang HILANG PERMANEN (konsekuensi yang harus diterima)

| #   | Yang hilang                       | Dari mana                             | Dampak ke user                                                                                    |
| --- | --------------------------------- | ------------------------------------- | ------------------------------------------------------------------------------------------------- |
| 1   | Field **No. KTP** karyawan        | `tb_karyawan.no_ktp`                  | Tidak ada nomor KTP di profil; tidak bisa KYC/pajak nanti                                         |
| 2   | Field **Alamat** karyawan         | `tb_karyawan.alamat`                  | Profil karyawan tanpa alamat (24 referensi di kode)                                               |
| 3   | Field **Tanggal Masuk**           | `tb_karyawan.tgl_masuk`               | Tidak tahu kapan karyawan mulai kerja; fitur masa kerja mustahil                                  |
| 4   | Field **Status Kontrak**          | `tb_karyawan.status_kontrak`          | **Filter tabel karyawan by kontrak/tetap rusak**                                                  |
| 5   | Field **Bidang Tugas**            | `tb_karyawan.bidang_tugas`            | Tidak bisa kategorisasi/search karyawan by bidang                                                 |
| 6   | Field **Foto profil**             | `tb_karyawan.foto`                    | Aman (tidak terbukti dipakai)                                                                     |
| 7   | **Gaji pokok & gaji bersih**      | `tb_karyawan.gaji_pokok`, rekap       | Sudah tidak ditampilkan sejak V2.2 — aman dibuang                                                 |
| 8   | **Audit trail verifikasi**        | `tb_verifikasi` (1 row per perubahan) | Tidak ada history "siapa ubah keputusan kapan"; tidak bisa rollback; tidak ada bukti saat dispute |
| 9   | Kolom **`status_valid`** presensi | `tb_presensi.status_valid`            | Lihat [§6 Jebakan](#6-jebakan-gotcha-yang-mudah-terlewat) — perlu keputusan                       |

> ⚠️ **Field #1–#5 TERBUKTI dipakai aktif** di form input akun, tabel karyawan, infolist, dan seeder (lihat [10-audit-fitur-terdampak.md](10-audit-fitur-terdampak.md)). Skenario 1 = sengaja merelakannya. Pastikan owner setuju **secara tertulis** sebelum eksekusi.

### 2.2 Yang TETAP JALAN (tidak terdampak)

- ✅ Presensi GPS check-in/check-out (Haversine + radius)
- ✅ Upload foto selfie masuk/keluar (`foto_masuk`/`foto_keluar` dipertahankan)
- ✅ Foto masuk hanya tampil setelah check-out
- ✅ Aturan keterlambatan Rp 10.000/10 menit tanpa cap
- ✅ Workflow tugas terima/tolak + alasan
- ✅ Upload bukti before/after
- ✅ Filter default hari ini di Jadwal & Detail Pekerjaan
- ✅ Laporan PDF/Excel/CSV (tetap, tapi query-nya berubah)
- ✅ RBAC 3 role + login redirect
- ✅ Setting runtime via `tb_setting`

---

## 3. Peta Dampak ke Kode (Skala Sebenarnya)

Grep relasi/FK `karyawan` di luar `docs/`: **±183 kemunculan di 46 file.** Skenario 1 menyentuh **5 layer** sekaligus. Ini bukan refactor kecil.

| Layer              | Jumlah file | Jenis perubahan                                      |
| ------------------ | ----------- | ---------------------------------------------------- |
| Migrations         | 8 file      | rewrite struktur tabel                               |
| Models             | 12 file     | hapus 6, rename 1, buat 1, update 5                  |
| Filament Resources | ±15 file    | hapus 5 resource, rewrite Akun, update 5             |
| Controllers        | 3 file      | buang `resolveKaryawan()`, `karyawan_id` → `user_id` |
| Exports + Views    | ±9 file     | `karyawan.user.nama` → `user.nama`                   |
| Seeders            | 2 file      | rewrite total (tanpa tabel anak)                     |

---

## 4. Penyesuaian & Perbaikan per Layer (Langkah Konkret)

### Layer 0 — Enums (baru)

Buat `app/Enums/` (belum ada sama sekali). Minimal: `Role`, `StatusPresensi`, `StatusVerifikasi`, `StatusJadwal`, `StatusTugas`, `StatusBukti`, `JenisLaporan`. Pola lihat [07-rencana-refactor-kode.md §2](07-rencana-refactor-kode.md).

### Layer 1 — Database / Migrations

Karena branch ini **dev dan belum ada data production**, Skenario 1 paling bersih dikerjakan dengan **rewrite migrasi dasar + `migrate:fresh`**, bukan pola additive-backfill 3-fase (pola itu untuk menyelamatkan data production).

Yang harus diubah:

1. **`...create_users_table.php`** → tambah kolom `nik`, `no_hp`, `posisi` (semua nullable) ke `tb_user`.
2. **`...create_karyawans_table.php`** → **HAPUS** file (atau kosongkan `up()`).
3. **`...create_admins_table.php`** → **HAPUS**.
4. **`...create_supervisors_table.php`** → **HAPUS**.
5. **`...create_verifikasis_table.php`** → **HAPUS** (verifikasi jadi inline).
6. **`...create_jadwals_table.php`** → rename tabel jadi `tb_jadwal_pekerjaan`; `karyawan_id` → `user_id`, `admin_id` → `dibuat_oleh`; FK ke `tb_user`.
7. **`...create_detail_pekerjaans_table.php`** → `karyawan_id` → `user_id` (FK `tb_user`); `jadwal_id` FK ke `tb_jadwal_pekerjaan`.
8. **`...create_presensis_table.php`** → `karyawan_id` → `user_id`; **tambah 4 kolom inline** `status_verifikasi`, `diverifikasi_oleh`, `catatan_verifikasi`, `tgl_verifikasi`; putuskan nasib `status_valid` (lihat §6); FK `jadwal_id` ke `tb_jadwal_pekerjaan`.
9. **`...create_bukti_pekerjaans_table.php`** → `karyawan_id` → `user_id`.
10. **`...create_rekap_presensi_bulanans_table.php`** → **HAPUS**, ganti **`create_laporan_presensi_table.php`** baru (gabungan, lihat skema [02 §2.6](02-database-schema.md)).
11. **`...create_laporans_table.php`** → **HAPUS** (dimerge ke `tb_laporan_presensi`).

> **Catatan unique constraint baru:** V3 menambah `UNIQUE(user_id, tanggal)` di presensi & `UNIQUE(user_id, tanggal_kerja)` di jadwal. Seeder sudah pakai `updateOrCreate` berbasis kunci itu, jadi aman.

### Layer 2 — Models

| File                                                                                                       | Aksi                                                                                                                                                                                                                                                                                                                                                                    |
| ---------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `Admin.php`, `Supervisor.php`, `Karyawan.php`, `Verifikasi.php`, `Laporan.php`, `RekapPresensiBulanan.php` | **HAPUS (6 file)**                                                                                                                                                                                                                                                                                                                                                      |
| `Jadwal.php`                                                                                               | rename → `JadwalPekerjaan.php`, `$table='tb_jadwal_pekerjaan'`, relasi `karyawan()`→`user()`, `admin()`→`pembuatJadwal()`                                                                                                                                                                                                                                               |
| `LaporanPresensi.php`                                                                                      | **BUAT BARU** (gabungan), relasi `karyawan()` (user_id) + `generator()` (generated_by)                                                                                                                                                                                                                                                                                  |
| `User.php`                                                                                                 | tambah `nik/no_hp/posisi` ke `$fillable`; cast `role`→`Role::class`; hapus relasi `karyawan()/admin()/supervisor()` (HasOne); tambah relasi langsung `presensiSaya`, `jadwalKerja`, `tugasSaya`, `buktiSaya`, dll; tambah helper `isKaryawan()/isAdmin()/isSupervisor()` + scope `karyawan()/admin()/supervisor()`. **Pertahankan accessor `name`** (dipakai Filament). |
| `Presensi.php`                                                                                             | relasi `karyawan()`→`user()`; **hapus** `verifikasi()` HasOne; tambah `verifikator()` BelongsTo + 4 field inline di `$fillable`; pertahankan `hitungPotongan()` (sudah benar).                                                                                                                                                                                          |
| `DetailPekerjaan.php`, `BuktiPekerjaan.php`                                                                | relasi `karyawan()`→`user()` (FK `user_id`)                                                                                                                                                                                                                                                                                                                             |

### Layer 3 — Filament Resources

**3a. Hapus 5 folder resource:**
`Verifikasis/`, `RekapPresensiBulanans/`, `Admins/`, `Supervisors/`, `Laporans/` (Laporans diganti `LaporanPresensis/`).

**3b. `KaryawanResource` + Schemas — refactor besar:**

- Model `Karyawan::class` → `User::class` + `getEloquentQuery()` scope `role=karyawan`.
- **`KaryawanForm.php`**: buang field `no_ktp`, `alamat`, `tgl_masuk`, `status_kontrak`, `bidang_tugas`; `posisi_karyawan` → `posisi`.
- **`KaryawansTable.php`**: buang kolom + **filter `status_kontrak`** (akan error kalau dibiarkan).
- **`KaryawanInfolist.php`**: buang 5 field dari section identitas.

**3c. `AkunResource` (form akun multi-role) — paling rawan:**
File `Akuns/Schemas/AkunForm.php`, `Pages/CreateAkun.php`, `Pages/EditAkun.php` adalah **titik tunggal pembuatan akun semua role** dan sangat bergantung tabel anak:

- **`AkunForm.php`**: section "Data Karyawan" punya 8 field — buang `karyawan_no_ktp`, `karyawan_tgl_masuk`, `karyawan_status_kontrak`, `karyawan_bidang_tugas`, `karyawan_alamat`. Sisakan `nik`, `posisi` (eks `posisi_karyawan`), `no_hp`. `afterStateUpdated` yang me-reset field juga diperbarui.
- **`CreateAkun.php`**: blok `match($role)` yang `Admin::create/Supervisor::create/Karyawan::create` **dibongkar total** — semua data (`nik`, `no_hp`, `posisi`) langsung masuk `User::create([...])`. Tidak ada lagi tabel anak.
- **`EditAkun.php`**: `mutateFormDataBeforeFill()` yang baca `$user->karyawan->...` → baca `$user->...` langsung; `handleRecordUpdate()` `updateOrCreate` tabel anak → `$record->update([...])` saja.

**3d. Update resource operasional:**

- `JadwalResource` → rename `JadwalPekerjaanResource`; referensi `karyawan` → `user`.
- `PresensiResource`: `karyawan.user.nama` → `user.nama`; **tambah section Verifikasi** (visible supervisor) yang isi `status_verifikasi`/`catatan_verifikasi`; putuskan nasib field `status_valid` di form/infolist/table (3 tempat).
- `DetailPekerjaanResource`, `BuktiPekerjaanResource`: referensi `karyawan` → `user`.

**3e. Buat `LaporanPresensiResource`** (gabungan rekap + laporan) — lihat [07 §4.2](07-rencana-refactor-kode.md).

### Layer 4 — Controllers (3 file)

Pola umum: hilangkan langkah `resolveKaryawan()` / `Karyawan::where('user_id', …)` dan pakai `Auth::id()` langsung sebagai `user_id`.

- **`PresensiController.php`**: `checkIn`, `checkOut`, `submitBuktiPekerjaan`, `viewSchedule`, `viewHistory`, `uploadFoto` semuanya buang baris `$karyawan = Karyawan::where(...)` lalu ganti `karyawan_id` → `user_id`. Di `getCurrentAttendance()`: `with(['karyawan',...])` → `with(['user'])` dan **`whereNull('verifikasi')` → `where('status_verifikasi', 'pending')`** (relasi verifikasi sudah tidak ada).
- **`KaryawanMobileController.php`**: hapus method `resolveKaryawan()`; ±19 referensi `$karyawan->id` → `Auth::id()`; `with('verifikasi')` → tidak perlu (kolom inline); query join `tb_jadwal` → `tb_jadwal_pekerjaan`.
- **`LaporanExportController.php`**: `buildRekapPresensi()` ganti sumber `RekapPresensiBulanan` → `LaporanPresensi` (filter `jenis=Bulanan`, `whereNotNull('user_id')`); relasi eager `karyawan.user` → `user`; validasi `exists:tb_karyawan,id` → `exists:tb_user,id`; query agregat `Presensi` ganti `karyawan_id` → `user_id`; join `tb_jadwal` → `tb_jadwal_pekerjaan`.

### Layer 5 — Exports + Views

- **Exports** (`LaporanPresensiExport.php`, `LaporanPekerjaanExport.php`, `LaporanPresensiDetailExport.php`): relasi `karyawan.user` → `user`; `exists:tb_karyawan,id` → `exists:tb_user,id`.
- **Views export** (`exports/laporan-pdf.blade.php`, `laporan-presensi-pdf.blade.php`, `laporan-pekerjaan-pdf.blade.php`): `$p->karyawan?->user?->nama` → `$p->user?->nama`; `$p->karyawan?->nik` → `$p->user?->nik`.
- **Views karyawan** (`karyawan/layout.blade.php`, `tugas.blade.php`, `detail-bukti.blade.php`, `upload-bukti.blade.php`, dll): referensi relasi `karyawan` → `user`; field karyawan yang dihapus (mis. `status_kontrak`) yang muncul di view harus dibuang.

### Layer 6 — Seeders (2 file, rewrite total)

- **`DatabaseSeeder.php`**: buang semua `Admin::create/Supervisor::create/Karyawan::create` — `nik`/`no_hp`/`posisi` masuk `User::updateOrCreate` langsung. Buang `gaji_pokok` (3 tempat) dan blok rekap `gaji_pokok/gaji_bersih`. `Verifikasi::create` → `$presensi->update(['status_verifikasi'=>'disetujui', 'diverifikasi_oleh'=>$spv->id, ...])`. `Laporan::` & `RekapPresensiBulanan::` → `LaporanPresensi::`. Buang field karyawan yang dihapus dari semua array seed.
- **`ProductionSeeder.php`**: sama — `Karyawan::updateOrCreate` (dengan `gaji_pokok`, `status_kontrak`, `tgl_masuk`, `bidang_tugas`) dibongkar jadi `User::updateOrCreate` saja.

---

## 5. Urutan Eksekusi yang Disarankan

Untuk Skenario 1 di **branch dev (boleh `migrate:fresh`)** — urutan teraman supaya app tidak "setengah rusak" di tengah jalan:

1. **Enums dulu** (tidak memutus apa pun).
2. **Migrations** (rewrite struktur 7 tabel).
3. **Models** (hapus 6 → rename → buat baru → update 5).
4. **Seeders** (biar bisa `migrate:fresh --seed` untuk uji cepat tiap langkah).
5. **Controllers** (mobile + presensi + export).
6. **Filament Resources** (Akun, Karyawan, Presensi, Jadwal, dst).
7. **Views + Exports**.
8. `php artisan migrate:fresh --seed` → cek `storage/logs/laravel.log` bersih.
9. **E2E manual 3 role** (lihat checklist [09 §11](09-checklist-implementasi.md)).

> **Kenapa Model & Resource jangan diubah sebelum Migration jadi?** Filament boot semua Resource saat panel diakses; satu referensi `Karyawan::class` yang sudah dihapus = panel error total. Lebih aman ubah DB+Model dulu, baru sapu Resource sekaligus.

---

## 6. Jebakan (Gotcha) yang Mudah Terlewat

1. **`status_valid` ≠ `status_verifikasi`.** V2 punya DUA hal: kolom `status_valid` (pending/valid/tidak_valid, dipakai di 3 tempat `PresensiResource` + di-set `checkIn`) **dan** `tb_verifikasi` terpisah. Skema V3 (doc 02) **tidak mencantumkan `status_valid`**. Keputusan wajib: gabungkan `status_valid` ke `status_verifikasi`, atau buang. Kalau lupa, `PresensiResource` & seeder akan error kolom tidak ada.

2. **Relasi dua tingkat `karyawan.user`.** Banyak kode pakai `$p->karyawan?->user?->nama` (dua hop, karena nama ada di `tb_user`, profil di `tb_karyawan`). Di V3 jadi **satu hop** `$p->user?->nama`. Cari semua pola `->karyawan?->user` dan `karyawan.user`.

3. **Default `status_presensi` berubah.** V2 default `'hadir'`, skema V3 default `'tidak_hadir'`. Pastikan `checkIn` tetap set eksplisit (sudah, jadi aman) tapi cek seeder/asumsi lain.

4. **Accessor `name` di `User` wajib tetap ada.** Filament & Notifications memanggil `$user->name`, sedangkan kolom DB `nama`. Jangan ikut terhapus saat rapikan model.

5. **Rename kolom `posisi_karyawan` → `posisi`.** Muncul di `AkunForm`, `CreateAkun`, `EditAkun`, `KaryawanForm`, seeder, dan `RekapPresensiBulanan::generateSlipRekapPresensiBulanan()`. Semua harus ikut.

6. **`RekapPresensiBulanan` punya field yang tidak ada di `tb_laporan_presensi`.** Yaitu `gaji_pokok`, `gaji_bersih`, `total_potongan_keterlambatan`, `catatan`, `status`. Saat merge: `total_potongan_keterlambatan` → `total_potongan`; `gaji_*` dibuang; `catatan`/`status` tidak punya kolom tujuan → relakan atau mapping manual. `buildRekapPresensi()` di controller membaca shape lama ini — ikut disesuaikan.

7. **Validasi `exists:tb_karyawan,id` & `exists:tb_detail_pekerjaan`.** Beberapa request rule masih menunjuk `tb_karyawan` — ganti `tb_user`. Kalau lupa, export by karyawan akan selalu gagal validasi.

8. **Hard-coded nama tabel di query join.** `KaryawanMobileController` & `LaporanExportController` join `tb_jadwal` literal. Setelah rename → `tb_jadwal_pekerjaan`.

---

## 7. Checklist Verifikasi Setelah Selesai

- [ ] `php artisan migrate:fresh --seed` sukses tanpa error
- [ ] `storage/logs/laravel.log` bersih (tidak ada `Class "App\Models\Karyawan" not found` dsb)
- [ ] Login admin → menu Akun: create/edit karyawan **tanpa** field yang dihapus, tersimpan ke `tb_user`
- [ ] Menu Karyawan: list/filter jalan (tidak ada filter `status_kontrak` yang nyangkut)
- [ ] Karyawan: check-in → check-out → foto masuk muncul setelah pulang
- [ ] Supervisor: verifikasi presensi (set `status_verifikasi`) tersimpan inline
- [ ] Export PDF/Excel/CSV rekap + presensi + pekerjaan tetap keluar, kolom Karyawan & NIK terisi
- [ ] `grep -rn "karyawan_id\|->karyawan\|Karyawan::\|tb_jadwal\b\|tb_verifikasi\|RekapPresensiBulanan\|status_valid" app/ resources/ database/` → **0 sisa** (kecuali yang sengaja)

---

## 8. Kesimpulan

| Aspek                                           | Hasil Skenario 1                                                                                 |
| ----------------------------------------------- | ------------------------------------------------------------------------------------------------ |
| Total tabel                                     | **7** (sesuai gambar ERD asli)                                                                   |
| Fitur Kategori A (5 field karyawan)             | ❌ Hilang — form/tabel/infolist disederhanakan                                                   |
| Fitur Kategori B (audit verifikasi)             | ❌ Hilang permanen                                                                               |
| Fitur Kategori C (presensi, GPS, foto, laporan) | ✅ Tetap jalan                                                                                   |
| File tersentuh                                  | ±46 file, 5 layer                                                                                |
| Estimasi effort                                 | **5–7 hari** solo dev                                                                            |
| Risiko utama                                    | Filament panel error kalau urutan refactor salah; data karyawan lama hilang saat `migrate:fresh` |

**Rekomendasi pemakaian Skenario 1:** cocok kalau tujuannya **belajar/latihan schema design** atau owner **benar-benar konfirmasi** 5 field karyawan + audit verifikasi tidak dibutuhkan. Kalau salah satu field itu masih dipakai bisnis, pertimbangkan **Skenario 2 (Opsi A)** — schema tetap 7 tabel, field karyawan disimpan nullable di `tb_user`, fitur tidak rusak (lihat [10-audit-fitur-terdampak.md §5](10-audit-fitur-terdampak.md)).

---

**Lihat juga:** [10-audit-fitur-terdampak.md](10-audit-fitur-terdampak.md) · [07-rencana-refactor-kode.md](07-rencana-refactor-kode.md) · [09-checklist-implementasi.md](09-checklist-implementasi.md)
