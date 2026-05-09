# Progress Report — Refactoring V2

## ✅ Perubahan yang Sudah Selesai

### 1. Pemisahan Presensi & Detail Pekerjaan
| Modul | Sebelum | Sesudah |
|-------|---------|---------|
| **Presensi** | 1 per detail_pekerjaan (multi per hari) | **1 per hari** (check-in/out di kantor pusat) |
| **Bukti Pekerjaan** | Terikat `presensi_id` | Terikat **`detail_pekerjaan_id` + `karyawan_id`** |
| **GPS Validation** | Per lokasi tugas | **Kantor pusat saja** (saat check-in) |

### 2. Fitur Terima/Tolak Tugas (Karyawan)
- ✅ Kolom `status` (`pending` → `disetujui` / `ditolak`) di tabel `tb_detail_pekerjaan`
- ✅ Kolom `alasan_tolak` untuk alasan penolakan
- ✅ Tombol **Terima Tugas** dan **Tolak Tugas** di portal karyawan
- ✅ Validasi: alasan tolak wajib diisi (min. 10 karakter)
- ✅ Guard: hanya bisa upload bukti jika tugas sudah **diterima**

### 3. Portal Karyawan (Mobile View)
| Halaman | Status | Keterangan |
|---------|--------|------------|
| Beranda | ✅ | Presensi + ringkasan tugas (terpisah) |
| Presensi Masuk | ✅ | Check-in di kantor pusat + GPS + foto |
| Presensi Pulang | ✅ | Check-out sederhana (foto opsional) |
| **Tugas** | ✅ **BARU** | Daftar tugas + terima/tolak + upload bukti |
| **Upload Bukti** | ✅ **BARU** | Foto before/after + keterangan per tugas |
| Jadwal | ✅ | Status badge per tugas |
| Riwayat | ✅ | Riwayat presensi 30 hari |

### 4. Admin Panel (Filament)
| Resource | Status | Keterangan |
|----------|--------|------------|
| Detail Pekerjaan | ✅ | Kolom status badge (pending/disetujui/ditolak) |
| Bukti Pekerjaan | ✅ | Relasi ke detail_pekerjaan + karyawan |
| Presensi | ✅ | Tidak lagi terikat detail_pekerjaan |
| Verifikasi | ✅ | Label disesuaikan |

## Alur Bisnis Terkini

```
Admin buat tugas (Detail Pekerjaan) → status = "pending"
        ↓
Karyawan lihat tugas di portal mobile
        ↓
    ┌──────────────┐     ┌──────────────┐
    │ TERIMA TUGAS │     │ TOLAK TUGAS  │
    │ status=      │     │ status=      │
    │ "disetujui"  │     │ "ditolak"    │
    └──────┬───────┘     │ + alasan     │
           ↓             └──────────────┘
    Upload Bukti
    (foto before/after)
           ↓
    Admin/Supervisor
    review bukti →
    disetujui/ditolak
```

## Screenshots Test

````carousel
![Tugas page setelah terima & tolak](tugas_final_state_1778136352714.png)
<!-- slide -->
![Admin Detail Pekerjaan dengan status](admin_detail_pekerjaan_scrolled_1778136431422.png)
<!-- slide -->
![Admin Bukti Pekerjaan](admin_bukti_pekerjaan_1778136448713.png)
<!-- slide -->
![Admin Presensi (tanpa detail_pekerjaan)](C:/Users/Lenovo_Ideapad_G_3/.gemini/antigravity/brain/132f47ca-0f79-4d75-bfda-9dd546e699ae/.system_generated/click_feedback/click_feedback_1778135591658.png)
````

## Files yang Dimodifikasi (17 files)

| File | Perubahan |
|------|-----------|
| `migrations/create_detail_pekerjaans_table.php` | + `status`, `alasan_tolak` |
| `migrations/create_presensis_table.php` | - `detail_pekerjaan_id` |
| `migrations/create_bukti_pekerjaans_table.php` | `presensi_id` → `detail_pekerjaan_id` + `karyawan_id` |
| `Models/Presensi.php` | Hapus relasi detailPekerjaan & buktiPekerjaan |
| `Models/BuktiPekerjaan.php` | Relasi: detailPekerjaan + karyawan |
| `Models/DetailPekerjaan.php` | + status, alasan_tolak, hasBuktiPekerjaans |
| `Controllers/PresensiController.php` | Check-in kantor pusat, + submitBuktiPekerjaan |
| `Controllers/KaryawanMobileController.php` | + daftarTugas, formUploadBukti, terimaTugas, tolakTugas |
| `routes/web.php` | + 5 route baru |
| `views/karyawan/beranda.blade.php` | Presensi + tugas terpisah |
| `views/karyawan/tugas.blade.php` | **BARU** — daftar tugas + terima/tolak |
| `views/karyawan/upload-bukti.blade.php` | **BARU** — upload foto before/after |
| `views/karyawan/presensi-pulang.blade.php` | Disederhanakan |
| `views/karyawan/jadwal.blade.php` | + status badge |
| `views/karyawan/layout.blade.php` | Nav: Pulang → Tugas |
| `Filament Resources` | Updated form/table/resource 5 files |
| `DatabaseSeeder.php` | Disesuaikan skema baru |
