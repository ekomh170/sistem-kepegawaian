# Panduan Maintenance & Pengembangan Mendatang (V2.1+)
## Sistem Informasi Kepegawaian — CV Boss Muda Mandiri

Dokumen ini berfungsi sebagai *roadmap* dan catatan teknis bagi pengembang untuk memelihara serta meningkatkan sistem di masa depan.

---

## 1. Daftar Tugas Pemeliharaan (TODO List)

Berikut adalah aspek-aspek yang masih bisa ditingkatkan dari versi saat ini:

### ⚙️ Otomatisasi Sistem
- [ ] **Pemicu Notifikasi (Triggers)**: Mengintegrasikan notifikasi otomatis saat Admin membuat `DetailPekerjaan` baru, agar Karyawan langsung menerima pesan di portal mereka.
- [ ] **Kalkulasi Gaji Otomatis**: Membuat *Command* atau *Job* bulanan yang menarik data dari `tb_presensi` (total hadir & total potongan) untuk mengisi tabel `tb_rekap_potongan` secara otomatis.
- [ ] **Reminder Absensi**: Mengirimkan notifikasi ke Karyawan yang belum check-in pada pukul 08:10 WIB.

### 📊 Laporan & Monitoring
- [ ] **Filter Export Global**: Menambahkan form modal pada tombol Export di menu Laporan agar Admin bisa memilih rentang tanggal (`start_date` & `end_date`) sebelum men-download file.
- [ ] **Laravel Reverb (WebSockets)**: Mengganti mekanisme *polling* 15 detik pada Dashboard Admin dengan WebSockets agar pembaruan data terjadi secara instan tanpa membebani server (real real-time).

### 🛡️ Keamanan & Validasi
- [ ] **Anti-GPS Spoofing**: Menambahkan validasi sisi server untuk mendeteksi penggunaan aplikasi *Fake GPS* pada perangkat mobile.
- [ ] **Audit Log**: Mencatat setiap perubahan data penting (seperti perubahan status verifikasi atau penghapusan jadwal) ke dalam tabel `activity_log`.

---

## 2. Lokasi File Kunci untuk Maintenance

Jika ingin melakukan perubahan logika, berikut adalah file yang perlu Anda buka:

| Fungsi | Lokasi File |
|--------|-------------|
| **Logika GPS & Potongan** | `app/Http/Controllers/PresensiController.php` |
| **Alur Mobile Karyawan** | `app/Http/Controllers/KaryawanMobileController.php` |
| **Tampilan Mobile UI** | `resources/views/karyawan/` |
| **Konfigurasi Admin Panel** | `app/Filament/Resources/` |
| **Style / CSS Mobile** | `resources/views/karyawan/layout.blade.php` (Bagian `<style>`) |

---

## 3. Prosedur Update Sistem

Jika ada perubahan pada database atau seeder, ikuti langkah berikut:

1. **Modifikasi Migration**: Edit file di `database/migrations/`.
2. **Refresh Database** (Hanya saat dev): 
   ```bash
   php artisan migrate:fresh --seed
   ```
3. **Clear Cache**:
   ```bash
   php artisan optimize:clear
   ```

---

## 4. Pengembangan Versi 3.0 (Ide Mendatang)

1. **Modul Pengajuan Izin/Cuti**: Karyawan bisa upload surat dokter atau formulir izin langsung dari portal mobile.
2. **Dashboard Supervisor Mobile**: Supervisor bisa melakukan verifikasi langsung lewat handphone tanpa harus membuka laptop.
3. **Pencetakan Slip Gaji**: Fitur bagi karyawan untuk men-download slip gaji mereka dalam format PDF.

---

> **Catatan:** Selalu lakukan backup database sebelum menjalankan `migrate:fresh` di lingkungan produksi (jika ada data asli).
