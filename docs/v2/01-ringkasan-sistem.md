# Dokumentasi Sistem Informasi Kepegawaian

## CV Boss Muda Mandiri — Versi 2.2

**Tanggal Generate:** 22 Mei 2026 (V2.2)  
**Framework:** Laravel 12 + Filament v5 + MySQL  
**Status:** ✅ Stabil — V2.2 Fase 3 (Hapus Fitur Gaji, Fix Laporan Mingguan)

---

## 1. Ringkasan Umum Sistem

Sistem Informasi Kepegawaian CV Boss Muda Mandiri adalah aplikasi web berbasis Laravel yang dirancang untuk mengelola data karyawan, jadwal kerja, presensi harian (termasuk validasi GPS), verifikasi oleh supervisor, dan pelaporan/penggajian. Sistem ini mendukung **3 aktor utama** dengan antarmuka yang berbeda:

| Aktor          | Antarmuka                | Akses                                        |
| -------------- | ------------------------ | -------------------------------------------- |
| **Admin**      | Panel Filament (Desktop) | CRUD penuh semua modul                       |
| **Supervisor** | Panel Filament (Desktop) | View only operasional, view + export laporan |
| **Karyawan**   | Portal Mobile (Blade)    | Presensi masuk/pulang, jadwal, riwayat       |

---

## 2. Teknologi & Dependensi

| Komponen          | Teknologi                   | Versi |
| ----------------- | --------------------------- | ----- |
| Backend Framework | Laravel                     | 12.x  |
| Admin Panel       | Filament                    | 5.x   |
| Database          | MySQL                       | 8.x   |
| PHP               | PHP                         | 8.2+  |
| Export Excel      | maatwebsite/excel           | 3.1   |
| Export PDF        | barryvdh/laravel-dompdf     | 3.1   |
| Map Picker        | dotswan/filament-map-picker | 2.3   |
| Testing           | PHPUnit                     | 11.x  |

---

## 3. Akun Demo (Seeder)

| Role       | Email                                    | Password | Nama               |
| ---------- | ---------------------------------------- | -------- | ------------------ |
| Admin      | admin@example.com                        | password | Budi Santoso       |
| Admin      | dela@example.com                         | password | Dela Maharani      |
| Supervisor | supervisor@example.com                   | password | Andi Gunawan       |
| Karyawan   | karyawan@example.com                     | password | Rizky Pratama      |
| Karyawan   | ekoaryo@example.com                      | password | Eko Aryo           |
| Karyawan   | staf1@example.com s/d staf10@example.com | password | (Random Indonesia) |

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
│   │   │   ├── Auth/
│   │   │   │   └── Login.php               # Custom login — heading dari Setting, flash logout
│   │   │   ├── Dashboard.php               # Custom dashboard — flash login notification
│   │   │   └── LokasiKantorPage.php        # Pengaturan lokasi kantor via peta
│   │   ├── Resources/                  # 12 Filament Resources (CRUD)
│   │   │   ├── Admins/
│   │   │   ├── Akuns/
│   │   │   ├── BuktiPekerjaans/
│   │   │   ├── DetailPekerjaans/       # + Map Picker
│   │   │   ├── Jadwals/
│   │   │   ├── Karyawans/
│   │   │   ├── Laporans/              # + Export CSV/Excel/PDF
│   │   │   ├── Presensis/             # + Auto-kalkulasi + MapPicker
│   │   │   ├── RekapPresensiBulanans/        # Rekap presensi bulanan (potongan, tanpa gaji)
│   │   │   ├── Supervisors/
│   │   │   └── Verifikasis/
│   │   └── Widgets/                    # 5 Dashboard Widgets
│   │       ├── AttendanceRealtimeStatsWidget.php   # sort=1, poll 15s
│   │       ├── RekapKehadiranHariIniWidget.php     # sort=2, poll 30s
│   │       ├── ProgressPekerjaanHariIniWidget.php  # sort=3, poll 30s
│   │       ├── LaporanEvaluasiChartWidget.php      # sort=4
│   │       └── KaryawanQuickAccessWidget.php       # admin only
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── KaryawanMobileController.php   # Portal karyawan
│   │   │   ├── PresensiController.php         # API presensi + GPS
│   │   │   └── LaporanExportController.php    # Export CSV/Excel/PDF
│   │   ├── Middleware/
│   │   │   └── RoleMiddleware.php             # RBAC middleware
│   │   └── Responses/
│   │       ├── FilamentLoginResponse.php      # Redirect by role + flash login
│   │       └── FilamentLogoutResponse.php     # Redirect ke /login + flash logout
│   └── Models/                         # 12 Eloquent Models
│       ├── User.php
│       ├── Admin.php
│       ├── Supervisor.php
│       ├── Karyawan.php
│       ├── Jadwal.php
│       ├── DetailPekerjaan.php
│       ├── Presensi.php
│       ├── Verifikasi.php
│       ├── BuktiPekerjaan.php
│       ├── RekapPresensiBulanan.php
│       ├── Setting.php
│       └── Laporan.php
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
