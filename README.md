# Sistem Informasi Kepegawaian

Sistem informasi kepegawaian untuk CV Boss Muda Mandiri yang dibangun dengan Laravel 12, Filament v5, dan MySQL. Aplikasi ini dipakai untuk mengelola data karyawan, presensi harian (GPS + foto), penugasan lapangan, bukti pekerjaan, rekap potongan keterlambatan, laporan (Harian/Mingguan/Bulanan/Tahunan), dan pengaturan lokasi kantor.

Dokumentasi utama ada di [docs/v2](docs/v2), sedangkan referensi dokumentasi lama tetap disimpan di [docs/v1](docs/v1).

## Gambaran Singkat

- Admin bekerja lewat panel Filament untuk CRUD penuh modul operasional.
- Supervisor memakai panel Filament dengan akses view only pada modul operasional dan akses laporan.
- Karyawan memakai portal mobile berbasis Blade untuk presensi masuk/pulang, daftar tugas, upload bukti pekerjaan, riwayat, dan logout.

## Fitur Utama

- Manajemen akun, karyawan, jadwal, presensi, penugasan lapangan, bukti pekerjaan, rekap presensi bulanan, laporan, verifikasi, dan setting.
- Presensi harian dengan validasi GPS (Haversine), foto selfie, dan kalkulasi potongan keterlambatan otomatis.
- Portal mobile karyawan: beranda, presensi masuk/pulang, daftar tugas (terima/tolak), upload bukti, jadwal mingguan, riwayat + potongan.
- Laporan Harian, **Mingguan**, Bulanan, Tahunan — export CSV, Excel, PDF per record.
- Pengaturan lokasi kantor berbasis map picker dan geofence radius (disimpan di `tb_setting`).
- RBAC tiga lapis: panel middleware, route middleware, dan Filament resource policy.

> **V2.2 (Fase 3):** Fitur tampilan gaji (Gaji Pokok, Gaji Bersih, Estimasi Gaji) dihapus dari seluruh UI. Kolom tetap ada di database untuk keperluan histori. Laporan Mingguan diperbaiki.

## Teknologi

- PHP 8.2+
- Laravel 12
- Filament v5
- MySQL
- maatwebsite/excel untuk export Excel/CSV
- barryvdh/laravel-dompdf untuk export PDF
- dotswan/filament-map-picker untuk pengaturan lokasi kantor

## Kebutuhan Awal

- PHP 8.2 atau lebih baru
- Composer
- Node.js dan npm
- Database MySQL

## Instalasi

```bash
git clone <url-repository>
cd sistem-kepegawaian
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Sesuaikan konfigurasi database di file `.env`, lalu jalankan migrasi dan seeder:

```bash
php artisan migrate --seed
```

Untuk build asset produksi:

```bash
npm run build
```

## Menjalankan Aplikasi

Mode development paling praktis:

```bash
composer run dev
```

Atau jalankan manual:

```bash
php artisan serve
npm run dev
```

## Perintah Berguna

- `composer run setup` untuk instalasi awal yang lengkap.
- `composer test` untuk menjalankan test suite Laravel.
- `php artisan filament:upgrade` bila ada penyesuaian Filament setelah update dependensi.

## Rujukan Dokumentasi

| File                                                                             | Isi                                             |
| -------------------------------------------------------------------------------- | ----------------------------------------------- |
| [docs/v2/01-ringkasan-sistem.md](docs/v2/01-ringkasan-sistem.md)                 | Ringkasan sistem, teknologi, struktur direktori |
| [docs/v2/02-database-schema.md](docs/v2/02-database-schema.md)                   | Skema tabel, kolom, dan ERD                     |
| [docs/v2/03-hak-akses-rbac.md](docs/v2/03-hak-akses-rbac.md)                     | Matriks RBAC per modul                          |
| [docs/v2/04-fitur-alur-bisnis.md](docs/v2/04-fitur-alur-bisnis.md)               | Alur bisnis per aktor dan logika kalkulasi      |
| [docs/v2/05-api-routes.md](docs/v2/05-api-routes.md)                             | Daftar route web, API, dan export               |
| [docs/v2/06-seeder-skenario-demo.md](docs/v2/06-seeder-skenario-demo.md)         | Skenario demo dan akun uji                      |
| [docs/v2/07-antarmuka-ui-ux.md](docs/v2/07-antarmuka-ui-ux.md)                   | Deskripsi UI per halaman                        |
| [docs/v2/10-maintenance-future-plan.md](docs/v2/10-maintenance-future-plan.md)   | TODO list dan rencana pengembangan              |
| [docs/v1/FILAMENT_DEVELOPER_PLAYBOOK.md](docs/v1/FILAMENT_DEVELOPER_PLAYBOOK.md) | Referensi arsitektur Filament (masih valid)     |

## Struktur Ringkas

```text
app/
database/
docs/
resources/views/
routes/
```

## Lisensi

Proyek ini mengikuti lisensi MIT.
