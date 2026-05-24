# Daftar API Endpoint & Route

## Sistem Informasi Kepegawaian — CV Boss Muda Mandiri

---

## 1. Route Web (Portal Karyawan)

| Method | URI                         | Name                              | Controller                                      | Deskripsi                            |
| ------ | --------------------------- | --------------------------------- | ----------------------------------------------- | ------------------------------------ |
| GET    | `/`                         | -                                 | Closure                                         | Redirect berdasarkan role            |
| GET    | `/login`                    | `login`                           | Filament Login                                  | Halaman login tunggal                |
| GET    | `/karyawan`                 | `karyawan.beranda`                | `KaryawanMobileController@beranda`              | Beranda (Presensi + Ringkasan Tugas) |
| GET    | `/karyawan/presensi/masuk`  | `karyawan.presensi.masuk`         | `KaryawanMobileController@formPresensiMasuk`    | Form check-in (Kantor Pusat)         |
| POST   | `/karyawan/presensi/masuk`  | `karyawan.presensi.masuk.submit`  | `KaryawanMobileController@submitPresensiMasuk`  | Submit data check-in                 |
| GET    | `/karyawan/presensi/pulang` | `karyawan.presensi.pulang`        | `KaryawanMobileController@formPresensiPulang`   | Form check-out                       |
| POST   | `/karyawan/presensi/pulang` | `karyawan.presensi.pulang.submit` | `KaryawanMobileController@submitPresensiPulang` | Submit data check-out                |
| GET    | `/karyawan/tugas`           | `karyawan.tugas`                  | `KaryawanMobileController@daftarTugas`          | Daftar tugas (terima/tolak/bukti)    |
| POST   | `/karyawan/tugas/terima`    | `karyawan.tugas.terima`           | `KaryawanMobileController@terimaTugas`          | Aksi menerima tugas                  |
| POST   | `/karyawan/tugas/tolak`     | `karyawan.tugas.tolak`            | `KaryawanMobileController@tolakTugas`           | Aksi menolak tugas (+ alasan)        |
| GET    | `/karyawan/tugas/upload`    | `karyawan.tugas.upload`           | `KaryawanMobileController@formUploadBukti`      | Form upload foto before/after        |
| POST   | `/karyawan/tugas/upload`    | `karyawan.tugas.upload.submit`    | `KaryawanMobileController@submitUploadBukti`    | Submit bukti pekerjaan               |
| GET    | `/karyawan/jadwal`          | `karyawan.jadwal`                 | `KaryawanMobileController@jadwalMingguan`       | Jadwal tugas 7 hari ke depan         |
| GET    | `/karyawan/riwayat`         | `karyawan.riwayat`                | `KaryawanMobileController@riwayat`              | Riwayat absensi harian               |

---

## 2. Route Export (Admin & Supervisor)

Semua endpoint export menerima query param `?periode=...&jenis=...` — `jenis` (Harian/Mingguan/Bulanan/Tahunan) ditampilkan di header PDF sejak V2.3.

| Method | URI                               | Name                             | Controller                                     | Deskripsi                                                |
| ------ | --------------------------------- | -------------------------------- | ---------------------------------------------- | -------------------------------------------------------- |
| GET    | `/laporan/export/csv`             | `laporan.export.csv`             | `LaporanExportController@exportCsv`            | Export Laporan Jumlah Presensi Per Karyawan ke CSV       |
| GET    | `/laporan/export/excel`           | `laporan.export.excel`           | `LaporanExportController@exportExcel`          | Export Laporan Jumlah Presensi Per Karyawan ke Excel     |
| GET    | `/laporan/export/pdf`             | `laporan.export.pdf`             | `LaporanExportController@exportPdf`            | Export Laporan Jumlah Presensi Per Karyawan ke PDF       |
| GET    | `/laporan/export-presensi/csv`    | `laporan.export-presensi.csv`    | `LaporanExportController@exportPresensiCsv`    | Export presensi harian ke CSV                            |
| GET    | `/laporan/export-presensi/excel`  | `laporan.export-presensi.excel`  | `LaporanExportController@exportPresensiExcel`  | Export presensi harian ke Excel                          |
| GET    | `/laporan/export-presensi/pdf`    | `laporan.export-presensi.pdf`    | `LaporanExportController@exportPresensiPdf`    | Export presensi harian ke PDF (header tampilkan `jenis`) |
| GET    | `/laporan/export-pekerjaan/csv`   | `laporan.export-pekerjaan.csv`   | `LaporanExportController@exportPekerjaanCsv`   | Export rekap pekerjaan ke CSV                            |
| GET    | `/laporan/export-pekerjaan/excel` | `laporan.export-pekerjaan.excel` | `LaporanExportController@exportPekerjaanExcel` | Export rekap pekerjaan ke Excel                          |
| GET    | `/laporan/export-pekerjaan/pdf`   | `laporan.export-pekerjaan.pdf`   | `LaporanExportController@exportPekerjaanPdf`   | Export rekap pekerjaan ke PDF (header tampilkan `jenis`) |

---

## 3. Route API (JSON Service)

| Method | URI                             | Name                       | Controller                                | Deskripsi                   |
| ------ | ------------------------------- | -------------------------- | ----------------------------------------- | --------------------------- |
| POST   | `/api/validasi-gps`             | `presensi.validasi-gps`    | `PresensiController@validateGPS`          | Cek radius ke Kantor Pusat  |
| POST   | `/api/presensi/check-in`        | `presensi.check-in`        | `PresensiController@checkIn`              | Logika backend check-in     |
| POST   | `/api/presensi/check-out`       | `presensi.check-out`       | `PresensiController@checkOut`             | Logika backend check-out    |
| POST   | `/api/presensi/bukti-pekerjaan` | `presensi.bukti-pekerjaan` | `PresensiController@submitBuktiPekerjaan` | Simpan bukti before/after   |
| GET    | `/api/presensi/history`         | `presensi.history`         | `PresensiController@viewHistory`          | Ambil data riwayat via JSON |
| GET    | `/api/presensi/current`         | `presensi.current`         | `PresensiController@getCurrentAttendance` | Data statistik real-time    |

---

## 4. Filament Resource Routes

Akses melalui `/admin/...` dikendalikan secara otomatis oleh Filament untuk model-model berikut:

- `AkunResource` (Users) — Create + Edit
- `KaryawanResource` — Create (auto buat User) + Edit + View
- `DetailPekerjaanResource` (Penugasan)
- `PresensiResource` (Log Harian + Auto-kalkulasi)
- `VerifikasiResource` (Review)
- `BuktiPekerjaanResource` (Review Hasil Kerja)
- `RekapPresensiBulananResource` (Navigation label: **"Laporan Jumlah Presensi Per Karyawan"** sejak V2.3 — potongan keterlambatan)
- `LaporanResource` (+ Export per record)
- `SettingResource` (Key-Value)
- `LokasiKantorPage` (Custom Page + MapPicker)
