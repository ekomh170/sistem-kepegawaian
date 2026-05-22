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

### 2.1 Halaman Login (Custom)

- **Heading**: Nama perusahaan dinamis dari `Setting::get('nama_perusahaan')` — berubah otomatis saat diubah di panel admin.
- **Subheading**: "Masuk ke akun Anda".
- **Label form**: Email / Kata Sandi / Ingat saya (ringkas, tanpa "Alamat").
- **Notifikasi logout**: Muncul toast info "Berhasil keluar" jika user baru saja logout.
- **Notifikasi login sukses**: Muncul di dashboard setelah login, menyebutkan nama role + nama user.

### 2.2 Dashboard

- **AttendanceRealtimeStatsWidget** (sort=1, polling 15s): 5 stat card — Total Karyawan, Hadir Hari Ini (hijau), Terlambat Hari Ini (kuning), Hadir Bulan Ini, Terlambat Bulan Ini. Query cross-DB (`whereYear`/`whereMonth`).
- **RekapKehadiranHariIniWidget** (sort=2, polling 30s): Tabel rekap kehadiran hari ini.
- **ProgressPekerjaanHariIniWidget** (sort=3, polling 30s): Progress tugas hari ini.
- **LaporanEvaluasiChartWidget** (sort=4): Grafik line smooth kehadiran per bulan.

### 2.3 Form Akun (AkunResource)

- **Create**: Form dinamis berdasarkan role yang dipilih.
    - Role Admin → muncul section: NIP, Divisi.
    - Role Supervisor → muncul section: Jabatan.
    - Role Karyawan → muncul section: NIK, No KTP, Posisi, Bidang Tugas, Tgl Masuk, Status Kontrak, No HP, Alamat.
- **Edit**: Form yang sama, password opsional (kosongkan jika tidak diubah).
- **Tabel**: Badge role berwarna (🔴 Admin, 🟡 Supervisor, 🟢 Karyawan).

### 2.4 Form Karyawan (KaryawanResource)

- **Create**: 3 section (Data Akun + Identitas + Kepegawaian) — otomatis buat User + Karyawan dalam 1 transaksi.
- **Edit**: Section Data Akun berubah jadi Select (disabled) menampilkan user terhubung.
- **View**: Infolist 3 section dengan avatar, badge, icon, copyable fields (tanpa Gaji Pokok).

### 2.5 Riwayat Karyawan (`riwayat.blade.php`)

- **Potongan Keterlambatan Bulan Ini**: Menampilkan total potongan + jumlah hari hadir di bulan berjalan.
- **Ringkasan 30 Hari**: Statistik hadir, terlambat, tidak hadir, izin.
- **Tab Presensi**: Riwayat presensi harian dengan detail jam & potongan per item.
- **Tab Pekerjaan**: Riwayat tugas 30 hari terakhir beserta status (diterima/ditolak/pending).
- ⚠️ Fitur **Estimasi Gaji** dan **Gaji Pokok** dihapus sejak V2.2.

### 2.6 Form Laporan (LaporanResource)

- Tipe: Presensi Harian / Rekap Presensi Bulanan / Rekap Pekerjaan.
- Jenis: Harian / **Mingguan** / Bulanan / Tahunan → format periode otomatis berubah.
    - Mingguan: input tanggal awal minggu (YYYY-MM-DD), data diambil 7 hari ke depan.
- Judul auto-generated (hidden field).
- Export per record: 3 tombol (CSV, Excel, PDF) di tiap baris tabel.
- Kolom **Gaji Pokok** dan **Gaji Bersih** dihapus dari semua format export sejak V2.2.

### 2.6 Form Detail Pekerjaan

- **Map Picker**: Penentuan lokasi visual menggunakan OpenStreetMap.
- **View**: Infolist 4 section (Karyawan, Jadwal, Lokasi, Detail Tugas) dengan badge status.
- **Alasan Penolakan**: Hanya muncul jika status = ditolak.

### 2.7 Presensi (Auto-kalkulasi)

- Live hitung durasi, keterlambatan, dan potongan saat form diisi.
- Map Picker untuk lokasi GPS check-in.

### 2.8 Verifikasi & Review

- **Menu Verifikasi**: Fokus pada validasi kehadiran harian (jam masuk & lokasi kantor pusat).
- **Menu Bukti Pekerjaan**: Fokus pada review hasil kerja teknis (foto before/after dan keterangan kerja).

### 2.9 Pengaturan Lokasi Kantor

- Halaman custom `LokasiKantorPage` dengan peta interaktif (MapPicker).
- Set koordinat kantor pusat + radius geofence.
- Tersimpan di `tb_setting`: `kantor_lat`, `kantor_lng`, `kantor_radius`.

### 2.10 Hak Akses Supervisor (View Only)

- Seluruh modul Operasional: tombol Buat, Edit, Hapus, dan Bulk Delete **disembunyikan**.
- Supervisor hanya bisa melihat data tanpa melakukan perubahan.

---

## 3. Komponen Visual & Warna

| Elemen               | Warna (Hex)                  | Kegunaan                           |
| -------------------- | ---------------------------- | ---------------------------------- |
| **Brand Primary**    | `#0f766e` (Teal)             | Tombol Utama, Header, Status Hadir |
| **Brand Secondary**  | `#f59e0b` (Amber)            | Tombol Pulang, Status Menunggu     |
| **Danger**           | `#ef4444` (Red)              | Tombol Tolak, Status Terlambat     |
| **Background**       | `#f5f7fb`                    | Latar belakang aplikasi mobile     |
| **Card Shadow**      | `0 4px 6px rgba(0,0,0,0.05)` | Memberikan efek kedalaman (depth)  |
| **Badge Admin**      | `danger` (Merah)             | Role admin di tabel                |
| **Badge Supervisor** | `warning` (Kuning)           | Role supervisor di tabel           |
| **Badge Karyawan**   | `success` (Hijau)            | Role karyawan di tabel             |
