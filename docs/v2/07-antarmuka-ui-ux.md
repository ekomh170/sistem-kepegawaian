# Antarmuka Pengguna (UI/UX)
## Sistem Informasi Kepegawaian — CV Boss Muda Mandiri

---

## 1. Portal Karyawan (Mobile-First Design)

Portal karyawan menggunakan desain responsif dengan visual premium (vibrant colors, glassmorphism elements, dynamic buttons).

### 1.1 Beranda (`beranda.blade.php`)
Beranda kini berfungsi sebagai pusat informasi harian yang terbagi menjadi dua blok utama:
1. **Presensi Hari Ini**: 
   - Status absensi harian (Belum Absen / Hadir / Terlambat).
   - Tombol cepat **Presensi Masuk** dan **Presensi Pulang**.
   - Menampilkan jam masuk dan durasi kerja secara real-time.
2. **Ringkasan Tugas**:
   - Menampilkan daftar lokasi tugas yang harus dikerjakan hari ini.
   - Badge status tugas (Menunggu / Diterima / Ditolak).

### 1.2 Halaman Tugas (`tugas.blade.php`)
Halaman khusus untuk mengelola penugasan lapangan:
- **Interaksi Respon**: Tombol "Terima Tugas" (Hijau) dan "Tolak Tugas" (Merah).
- **Form Penolakan**: Muncul secara dinamis jika karyawan menolak tugas, mewajibkan pengisian alasan.
- **Akses Upload**: Tombol "Upload Bukti" muncul hanya pada tugas yang telah diterima.

### 1.3 Form Upload Bukti (`upload-bukti.blade.php`)
- **Dual Camera/File**: Pilihan untuk mengambil foto langsung via kamera (WebRTC) atau upload file.
- **Before/After Box**: Visualisasi kartu terpisah untuk kondisi awal (Before) dan kondisi akhir (After).
- **Auto-Compress**: Gambar dikompresi di sisi klien sebelum dikirim untuk menghemat kuota data.

---

## 2. Panel Administrasi (Filament v5)

### 2.1 Dashboard
- **AttendanceRealtimeStatsWidget**: 4 stat card — Hadir (hijau), Terlambat (kuning), Tidak Hadir (merah), Menunggu Verifikasi (biru).
- **LaporanEvaluasiChartWidget**: Grafik line smooth kehadiran per bulan.
- **KaryawanQuickAccessWidget**: Akses cepat menu karyawan (admin only).

### 2.2 Form Akun (AkunResource)
- **Create**: Form dinamis berdasarkan role yang dipilih.
  - Role Admin → muncul section: NIP, Divisi.
  - Role Supervisor → muncul section: Jabatan.
  - Role Karyawan → muncul section: NIK, No KTP, Posisi, Bidang Tugas, Tgl Masuk, Status Kontrak, No HP, Gaji Pokok, Alamat.
- **Edit**: Form yang sama, password opsional (kosongkan jika tidak diubah).
- **Tabel**: Badge role berwarna (🔴 Admin, 🟡 Supervisor, 🟢 Karyawan).

### 2.3 Form Karyawan (KaryawanResource)
- **Create**: 3 section (Data Akun + Identitas + Kepegawaian) — otomatis buat User + Karyawan dalam 1 transaksi.
- **Edit**: Section Data Akun berubah jadi Select (disabled) menampilkan user terhubung.
- **View**: Infolist 3 section dengan avatar, badge, icon, copyable fields, format Rp.

### 2.4 Form Laporan (LaporanResource)
- Tipe: Presensi / Rekap Presensi Bulanan (dengan icon).
- Jenis: Harian / Bulanan / Tahunan → format periode otomatis berubah.
- Judul auto-generated (hidden field).
- Export per record: 3 tombol (CSV, Excel, PDF) di tiap baris tabel.

### 2.5 Form Detail Pekerjaan
- **Map Picker**: Penentuan lokasi visual menggunakan OpenStreetMap.
- **View**: Infolist 4 section (Karyawan, Jadwal, Lokasi, Detail Tugas) dengan badge status.
- **Alasan Penolakan**: Hanya muncul jika status = ditolak.

### 2.6 Presensi (Auto-kalkulasi)
- Live hitung durasi, keterlambatan, dan potongan saat form diisi.
- Map Picker untuk lokasi GPS check-in.

### 2.7 Verifikasi & Review
- **Menu Verifikasi**: Fokus pada validasi kehadiran harian (jam masuk & lokasi kantor pusat).
- **Menu Bukti Pekerjaan**: Fokus pada review hasil kerja teknis (foto before/after dan keterangan kerja).

### 2.8 Pengaturan Lokasi Kantor
- Halaman custom `LokasiKantorPage` dengan peta interaktif (MapPicker).
- Set koordinat kantor pusat + radius geofence.
- Tersimpan di `tb_setting`: `kantor_lat`, `kantor_lng`, `kantor_radius`.

### 2.9 Hak Akses Supervisor (View Only)
- Seluruh modul Operasional: tombol Buat, Edit, Hapus, dan Bulk Delete **disembunyikan**.
- Supervisor hanya bisa melihat data tanpa melakukan perubahan.

---

## 3. Komponen Visual & Warna

| Elemen | Warna (Hex) | Kegunaan |
|--------|-------------|----------|
| **Brand Primary** | `#0f766e` (Teal) | Tombol Utama, Header, Status Hadir |
| **Brand Secondary** | `#f59e0b` (Amber) | Tombol Pulang, Status Menunggu |
| **Danger** | `#ef4444` (Red) | Tombol Tolak, Status Terlambat |
| **Background** | `#f5f7fb` | Latar belakang aplikasi mobile |
| **Card Shadow** | `0 4px 6px rgba(0,0,0,0.05)` | Memberikan efek kedalaman (depth) |
| **Badge Admin** | `danger` (Merah) | Role admin di tabel |
| **Badge Supervisor** | `warning` (Kuning) | Role supervisor di tabel |
| **Badge Karyawan** | `success` (Hijau) | Role karyawan di tabel |
