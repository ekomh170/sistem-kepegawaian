# Sistem Informasi Kepegawaian

Sistem informasi kepegawaian untuk CV Boss Muda Mandiri yang dibangun dengan Laravel 12, Filament v5, dan MySQL. Aplikasi ini dipakai untuk mengelola data karyawan, presensi harian, penugasan, bukti pekerjaan, rekap potongan, laporan, dan pengaturan lokasi kantor.

Dokumentasi utama ada di [docs/v2](docs/v2), sedangkan referensi dokumentasi lama tetap disimpan di [docs/v1](docs/v1).

## Gambaran Singkat

- Admin bekerja lewat panel Filament untuk CRUD penuh modul operasional.
- Supervisor memakai panel Filament dengan akses view only pada modul operasional dan akses laporan.
- Karyawan memakai portal mobile berbasis Blade untuk presensi masuk/pulang, daftar tugas, upload bukti pekerjaan, riwayat, dan logout.

## Fitur Utama

- Manajemen akun, karyawan, presensi, detail pekerjaan, bukti pekerjaan, rekap potongan, laporan, verifikasi, dan setting.
- Presensi harian dengan validasi lokasi dan dukungan capture kamera/base64.
- Portal mobile karyawan untuk beranda, tugas, jadwal, riwayat, dan upload bukti.
- Export laporan ke CSV, Excel, dan PDF.
- Pengaturan lokasi kantor berbasis map picker dan geofence radius.
- Struktur dokumentasi yang sudah dirapikan untuk handoff dev-to-dev.

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

- [docs/progress_report.md](docs/progress_report.md)
- [docs/v1/FILAMENT_DEVELOPER_PLAYBOOK.md](docs/v1/FILAMENT_DEVELOPER_PLAYBOOK.md)
- [docs/v2/01-ringkasan-sistem.md](docs/v2/01-ringkasan-sistem.md)
- [docs/v2/02-database-schema.md](docs/v2/02-database-schema.md)
- [docs/v2/03-hak-akses-rbac.md](docs/v2/03-hak-akses-rbac.md)

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
