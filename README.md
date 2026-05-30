# Sistem Informasi Kepegawaian

Sistem informasi kepegawaian untuk CV Boss Muda Mandiri yang dibangun dengan Laravel 12, Filament v5, dan MySQL. Aplikasi ini dipakai untuk mengelola data karyawan, presensi harian (GPS + foto), penugasan lapangan, bukti pekerjaan, rekap potongan keterlambatan, laporan (Harian/Mingguan/Bulanan/Tahunan), dan pengaturan lokasi kantor.

**Arsitektur database: V3 (7 tabel)** — lihat [docs/v3](docs/v3). Empat tabel user (user/admin/supervisor/karyawan) dikonsolidasi jadi satu `tb_user`; verifikasi presensi inline di `tb_presensi`; laporan & rekap digabung jadi `tb_laporan_presensi`. Dokumentasi fitur V2 tetap relevan di [docs/v2](docs/v2), referensi lama di [docs/v1](docs/v1).

## Gambaran Singkat

- Admin bekerja lewat panel Filament untuk CRUD penuh modul operasional.
- Supervisor memakai panel Filament dengan akses terbatas: **Presensi (termasuk verifikasi inline)** dan **Laporan Presensi (generate + export)**.
- Karyawan memakai portal mobile berbasis Blade untuk presensi masuk/pulang, daftar tugas, upload bukti pekerjaan, riwayat, dan logout.

## Fitur Utama

- Manajemen akun (semua role lewat `tb_user`), karyawan, jadwal pekerjaan, presensi (dengan verifikasi inline), penugasan lapangan, bukti pekerjaan, **Laporan Presensi** (gabungan laporan + rekap), dan setting.
- Presensi harian dengan validasi GPS (Haversine), foto selfie, dan kalkulasi potongan keterlambatan otomatis (**Rp 10.000 / 10 menit tanpa batas atas**).
- Portal mobile karyawan: beranda, presensi masuk/pulang, daftar tugas (terima/tolak), upload bukti, jadwal mingguan, riwayat + potongan. Foto masuk **hanya tampil setelah check-out**.
- Laporan Harian, **Mingguan**, Bulanan, Tahunan — export CSV, Excel, PDF per record. **PDF menampilkan spesifikasi jenis laporan di header**.
- Jadwal & Detail Pekerjaan di admin **default filter hari ini** agar tidak menumpuk data lama.
- Pengaturan lokasi kantor berbasis map picker dan geofence radius (disimpan di `tb_setting`).
- RBAC tiga lapis: panel middleware, route middleware, dan Filament resource policy.

> **V3 (30 Mei 2026) — restrukturisasi database (Skenario 1 / V3 Strict):**
>
> 1. Konsolidasi 4 tabel user → satu `tb_user` (kolom `nik`, `no_hp`, `posisi`). Helper `User::karyawan()`, `isAdmin()/isSupervisor()/isKaryawan()`.
> 2. Verifikasi presensi **inline** ke `tb_presensi` (`status_verifikasi`, `diverifikasi_oleh`, `catatan_verifikasi`, `tgl_verifikasi`) — `tb_verifikasi` dihapus.
> 3. `tb_laporan` + `tb_rekap_presensi_bulanan` digabung jadi `tb_laporan_presensi` (polimorfik: `user_id` NULL = agregat, terisi = rekap per-karyawan).
> 4. `tb_jadwal` → `tb_jadwal_pekerjaan`. Semua FK ke user pakai `user_id`/`dibuat_oleh`/`generated_by`.
> 5. Field karyawan yang tidak dipakai dihapus (no_ktp, alamat, foto, tgl_masuk, status_kontrak, bidang_tugas, gaji). Total tabel 12 → **7**.
> 6. Aturan keterlambatan **tetap** Rp 10.000 / 10 menit tanpa cap; foto masuk **tetap** tampil setelah check-out.
>
> Detail & analisa hasil: [docs/v3/12-hasil-akhir-implementasi.md](docs/v3/12-hasil-akhir-implementasi.md).

> **V2.3 (24 Mei 2026):**
>
> 1. Hak akses Supervisor dirampingkan ke 4 modul (Presensi view, Laporan generate+export, Verifikasi CRUD, Laporan Jumlah Presensi Per Karyawan view-only).
> 2. Tabel Jadwal & Detail Pekerjaan default filter hari ini.
> 3. Tabel Laporan default sort `tgl_generate` desc (terbaru paling atas).
> 4. Tipe "Laporan Jumlah Presensi Per Karyawan" dikunci jenis Bulanan.
> 5. Rename: Rekap Presensi Bulanan → Laporan Jumlah Presensi Per Karyawan.
> 6. PDF Laporan tampilkan spesifikasi jenis di header.
> 7. Foto masuk hanya tampil setelah karyawan check-out.
> 8. Potongan keterlambatan: hapus flat cap Rp 20.000, murni Rp 10.000 / 10 menit tanpa batas atas.
>
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

| File                                                                             | Isi                                                   |
| -------------------------------------------------------------------------------- | ----------------------------------------------------- |
| [docs/v3/00-README.md](docs/v3/00-README.md)                                     | **V3 (aktif)** — index dokumentasi restrukturisasi DB |
| [docs/v3/12-hasil-akhir-implementasi.md](docs/v3/12-hasil-akhir-implementasi.md) | **V3** — laporan hasil + analisa kesesuaian           |
| [docs/v2/01-ringkasan-sistem.md](docs/v2/01-ringkasan-sistem.md)                 | Ringkasan sistem, teknologi, struktur direktori       |
| [docs/v2/02-database-schema.md](docs/v2/02-database-schema.md)                   | Skema tabel, kolom, dan ERD                           |
| [docs/v2/03-hak-akses-rbac.md](docs/v2/03-hak-akses-rbac.md)                     | Matriks RBAC per modul                                |
| [docs/v2/04-fitur-alur-bisnis.md](docs/v2/04-fitur-alur-bisnis.md)               | Alur bisnis per aktor dan logika kalkulasi            |
| [docs/v2/05-api-routes.md](docs/v2/05-api-routes.md)                             | Daftar route web, API, dan export                     |
| [docs/v2/06-seeder-skenario-demo.md](docs/v2/06-seeder-skenario-demo.md)         | Skenario demo dan akun uji                            |
| [docs/v2/07-antarmuka-ui-ux.md](docs/v2/07-antarmuka-ui-ux.md)                   | Deskripsi UI per halaman                              |
| [docs/v2/08-kesesuaian-bab4.md](docs/v2/08-kesesuaian-bab4.md)                   | Matriks kesesuaian Class Diagram & BAB 4              |
| [docs/v2/09-diagram-sistem.md](docs/v2/09-diagram-sistem.md)                     | Use Case Diagram & ERD                                |
| [docs/v2/10-maintenance-future-plan.md](docs/v2/10-maintenance-future-plan.md)   | TODO list dan rencana pengembangan                    |
| [docs/v2/11-quick-fix-sat-set.md](docs/v2/11-quick-fix-sat-set.md)               | Cheat sheet quick-fix parameter sistem                |
| [docs/v2/12-flow-penggunaan-karyawan.md](docs/v2/12-flow-penggunaan-karyawan.md) | Panduan penggunaan portal karyawan                    |
| [docs/progress_report.md](docs/progress_report.md)                               | Progress report per rilis (V2.1 → V2.3)               |
| [docs/v1/FILAMENT_DEVELOPER_PLAYBOOK.md](docs/v1/FILAMENT_DEVELOPER_PLAYBOOK.md) | Referensi arsitektur Filament (masih valid)           |

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
