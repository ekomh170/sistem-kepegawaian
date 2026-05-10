# Dokumentasi Sistem Informasi Kepegawaian
## CV Boss Muda Mandiri — Versi 2.0

**Tanggal Generate:** 9 Mei 2026 (Updated)  
**Framework:** Laravel 12 + Filament v5 + MySQL  
**Status:** ✅ Stabil — Siap untuk BAB 4

---

## 1. Ringkasan Umum Sistem

Sistem Informasi Kepegawaian CV Boss Muda Mandiri adalah aplikasi web berbasis Laravel yang dirancang untuk mengelola data karyawan, jadwal kerja, presensi harian (termasuk validasi GPS), verifikasi oleh supervisor, dan pelaporan/penggajian. Sistem ini mendukung **3 aktor utama** dengan antarmuka yang berbeda:

| Aktor | Antarmuka | Akses |
|-------|-----------|-------|
| **Admin** | Panel Filament (Desktop) | CRUD penuh semua modul |
| **Supervisor** | Panel Filament (Desktop) | View only operasional, view + export laporan |
| **Karyawan** | Portal Mobile (Blade) | Presensi masuk/pulang, jadwal, riwayat |

---

## 2. Teknologi & Dependensi

| Komponen | Teknologi | Versi |
|----------|-----------|-------|
| Backend Framework | Laravel | 12.x |
| Admin Panel | Filament | 5.x |
| Database | MySQL | 8.x |
| PHP | PHP | 8.2+ |
| Export Excel | maatwebsite/excel | 3.1 |
| Export PDF | barryvdh/laravel-dompdf | 3.1 |
| Map Picker | dotswan/filament-map-picker | 2.3 |
| Testing | PHPUnit | 11.x |

---

## 3. Akun Demo (Seeder)

| Role | Email | Password | Nama |
|------|-------|----------|------|
| Admin | admin@example.com | password | Budi Santoso |
| Admin | dela@example.com | password | Dela Maharani |
| Supervisor | supervisor@example.com | password | Andi Gunawan |
| Karyawan | karyawan@example.com | password | Rizky Pratama |
| Karyawan | ekoaryo@example.com | password | Eko Aryo |
| Karyawan | staf1@example.com s/d staf10@example.com | password | (Random Indonesia) |

---

## 4. Struktur Direktori Utama

```
sistem-kepegawaian/
├── app/
│   ├── Exports/                        # Class export Excel/CSV
│   │   ├── LaporanPresensiExport.php      # Export rekap presensi bulanan
│   │   └── LaporanPresensiDetailExport.php # Export presensi harian
│   ├── Filament/
│   │   ├── Pages/
│   │   │   └── LokasiKantorPage.php        # Pengaturan lokasi kantor via peta
│   │   ├── Resources/                  # 12 Filament Resources (CRUD)
│   │   │   ├── Admins/
│   │   │   ├── Akuns/
│   │   │   ├── BuktiPekerjaans/
│   │   │   ├── DetailPekerjaans/       # + Map Picker
│   │   │   ├── Karyawans/
│   │   │   ├── Laporans/              # + Export CSV/Excel/PDF
│   │   │   ├── Notifikasis/
│   │   │   ├── Presensis/             # + Auto-kalkulasi + MapPicker
│   │   │   ├── RekapPresensiBulanans/        # Rekap presensi bulanan gaji
│   │   │   ├── Supervisors/
│   │   │   └── Verifikasis/
│   │   └── Widgets/                    # 3 Dashboard Widgets
│   │       ├── AttendanceRealtimeStatsWidget.php
│   │       ├── KaryawanQuickAccessWidget.php
│   │       └── LaporanEvaluasiChartWidget.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── KaryawanMobileController.php   # Portal karyawan
│   │   │   ├── PresensiController.php         # API presensi + GPS
│   │   │   └── LaporanExportController.php    # Export CSV/Excel/PDF
│   │   └── Middleware/
│   │       └── RoleMiddleware.php             # RBAC middleware
│   └── Models/                         # 12 Eloquent Models
│       ├── User.php
│       ├── Admin.php
│       ├── Supervisor.php
│       ├── Karyawan.php
│       ├── DetailPekerjaan.php
│       ├── Presensi.php
│       ├── Verifikasi.php
│       ├── BuktiPekerjaan.php
│       ├── RekapPresensiBulanan.php
│       ├── Setting.php
│       ├── Laporan.php
│       └── Notifikasi.php
├── database/
│   ├── migrations/                     # 14 Migration files
│   └── seeders/
│       └── DatabaseSeeder.php          # Data realistis Indonesia
├── resources/views/
│   ├── karyawan/                       # 8 Blade views (mobile UI)
│   │   ├── layout.blade.php
│   │   ├── beranda.blade.php
│   │   ├── presensi-masuk.blade.php
│   │   ├── presensi-pulang.blade.php
│   │   ├── tugas.blade.php
│   │   ├── upload-bukti.blade.php
│   │   ├── jadwal.blade.php
│   │   └── riwayat.blade.php
│   └── exports/
│       ├── laporan-pdf.blade.php          # Template PDF rekap presensi bulanan
│       └── laporan-presensi-pdf.blade.php # Template PDF presensi harian
└── routes/
    └── web.php                         # Semua route definisi
```
