# Panduan Maintenance & Pengembangan Mendatang (V2.1+)

## Sistem Informasi Kepegawaian — CV Boss Muda Mandiri

Dokumen ini berfungsi sebagai _roadmap_ dan catatan teknis bagi pengembang untuk memelihara serta meningkatkan sistem di masa depan.

---

## 1. Daftar Tugas Pemeliharaan (TODO List)

Berikut adalah aspek-aspek yang masih bisa ditingkatkan dari versi saat ini:

### ⚙️ Otomatisasi Sistem

- [ ] **Rekap Potongan Otomatis**: Membuat _Command_ atau _Job_ bulanan yang menarik data dari `tb_presensi` (total hadir & total potongan keterlambatan) untuk mengisi tabel `tb_rekap_presensi_bulanan` secara otomatis.

### 📊 Laporan & Monitoring

- [ ] **Filter Export Global**: Menambahkan form modal pada tombol Export di menu Laporan agar Admin bisa memilih rentang tanggal (`start_date` & `end_date`) sebelum men-download file.
- [ ] **Laravel Reverb (WebSockets)**: Mengganti mekanisme _polling_ 15 detik pada Dashboard Admin dengan WebSockets agar pembaruan data terjadi secara instan tanpa membebani server (real real-time).

### 🛡️ Keamanan & Validasi

- [ ] **Anti-GPS Spoofing**: Menambahkan validasi sisi server untuk mendeteksi penggunaan aplikasi _Fake GPS_ pada perangkat mobile.
- [ ] **Audit Log**: Mencatat setiap perubahan data penting (seperti perubahan status verifikasi atau penghapusan jadwal) ke dalam tabel `activity_log`.

---

## 2. Lokasi File Kunci untuk Maintenance

Jika ingin melakukan perubahan logika, berikut adalah file yang perlu Anda buka:

| Fungsi                           | Lokasi File                                                    |
| -------------------------------- | -------------------------------------------------------------- |
| **Logika GPS & Potongan**        | `app/Http/Controllers/PresensiController.php`                  |
| **Alur Mobile Karyawan**         | `app/Http/Controllers/KaryawanMobileController.php`            |
| **Tampilan Mobile UI**           | `resources/views/karyawan/`                                    |
| **Konfigurasi Admin Panel**      | `app/Filament/Resources/`                                      |
| **Style / CSS Mobile**           | `resources/views/karyawan/layout.blade.php` (Bagian `<style>`) |
| **Redirect setelah login**       | `app/Http/Responses/FilamentLoginResponse.php`                 |
| **Redirect setelah logout**      | `app/Http/Responses/FilamentLogoutResponse.php`                |
| **Halaman login (custom)**       | `app/Filament/Pages/Auth/Login.php`                            |
| **Dashboard (notifikasi login)** | `app/Filament/Pages/Dashboard.php`                             |
| **Rumus keterlambatan**          | `app/Models/Presensi.php` → `hitungPotongan()`                 |

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
3. **Notifikasi Push**: Notifikasi real-time ke karyawan saat ada tugas baru atau status verifikasi berubah.

---

> **Catatan:** Selalu lakukan backup database sebelum menjalankan `migrate:fresh` di lingkungan produksi (jika ada data asli).
