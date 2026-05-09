# Kesesuaian dengan Class Diagram & Catatan BAB 4
## Sistem Informasi Kepegawaian — CV Boss Muda Mandiri

---

## 1. Matriks Kesesuaian Class Diagram (Updated)

| No | Kelas (Class Diagram) | Model Laravel | Status | Perubahan Refactoring |
|----|----------------------|---------------|--------|----------------------|
| 1 | Presensi | `App\Models\Presensi` | ✅ Sesuai | Kini bersifat harian (1 baris per hari). Validasi GPS ke Kantor Pusat. |
| 2 | DetailPekerjaan | `App\Models\DetailPekerjaan` | ✅ Sesuai | Kini berfungsi sebagai Task List (banyak tugas per hari). Tambah atribut `status` & `alasan_tolak`. |
| 3 | BuktiPekerjaan | `App\Models\BuktiPekerjaan` | ✅ Sesuai | Relasi dipindah ke `DetailPekerjaan` (per tugas), bukan lagi per sesi absensi. |

---

## 2. Kesesuaian Use Case (Refactored)

| No | Use Case | Status | Implementasi Baru |
|----|----------|--------|-------------------|
| UC-05 | Kelola Detail Pekerjaan | ✅ | Admin membuat tugas; Karyawan bisa **Menerima** atau **Menolak**. |
| UC-07 | Presensi Masuk (Check-in) | ✅ | Validasi GPS ketat ke **Kantor Pusat** (Radius 500m). |
| UC-08 | Pelaporan Hasil Kerja | ✅ | Upload foto Before/After per Tugas (DetailPekerjaan). |

---

## 3. Kesesuaian dengan Kebutuhan Lapangan (R-XX)

| No | Kebutuhan (Wawancara) | Status | Penjelasan Implementasi |
|----|-----------------------|--------|-------------------------|
| R-05 | Foto before/after hasil kerja | ✅ | Diimplementasikan per tugas melalui model `BuktiPekerjaan`. |
| R-06 | Multi-lokasi kerja per hari | ✅ | Karyawan bisa memiliki banyak `DetailPekerjaan` namun hanya 1 `Presensi` harian. |
| R-11 | Konfirmasi penerimaan tugas | ✅ | **Fitur Baru**: Karyawan wajib menekan "Terima" atau "Tolak" pada setiap penugasan. |

---

## 4. Checklist Teknis Akhir

- [x] Pemisahan Logika: Presensi (Kehadiran) != Detail Pekerjaan (Tugas).
- [x] Validasi GPS Kantor Pusat pada Check-in.
- [x] Mekanisme Accept/Reject Tugas oleh Karyawan.
- [x] Upload Bukti Pekerjaan (Before/After) terikat ke ID Tugas.
- [x] UI Beranda Karyawan: Blok Presensi Harian + Blok Daftar Tugas.
- [x] Seeder: Menghasilkan tugas harian dan data presensi secara terpisah.

---

## 5. Narasi Kesimpulan untuk Skripsi (BAB 4)

> Dalam tahap implementasi ini, dilakukan pengembangan fitur yang memisahkan antara pencatatan kehadiran (presensi) dengan pelaporan tugas lapangan (detail pekerjaan). Hal ini bertujuan untuk mengakomodasi alur bisnis CV Boss Muda Mandiri di mana karyawan melakukan absensi di kantor pusat sebelum berangkat ke lokasi-lokasi tugas yang berbeda.
>
> Sistem memvalidasi lokasi karyawan menggunakan **Haversine Formula** terhadap koordinat kantor pusat saat check-in. Selanjutnya, karyawan dapat mengelola daftar tugas harian melalui fitur **Terima/Tolak Tugas**. Setiap tugas yang diselesaikan wajib disertai dengan unggahan **Bukti Pekerjaan** berupa foto kondisi sebelum (before) dan sesudah (after) pengerjaan. Arsitektur ini memastikan data kehadiran tetap akurat sementara laporan teknis pekerjaan terdokumentasi secara detail per lokasi tugas.
