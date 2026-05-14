# Panduan Quick Fix & Adjustment (Sat-Set Guide)
## Sistem Informasi Kepegawaian — CV Boss Muda Mandiri

Dokumen ini adalah "Cheat Sheet" untuk melakukan perubahan cepat pada parameter sistem tanpa harus mencari-cari di ribuan baris kode.

---

## 1. Penyesuaian Parameter Bisnis (VIA ADMIN PANEL)
Kini Anda tidak perlu lagi membuka kodingan untuk mengubah data penting. Masuk ke Panel Admin → Menu **Pengaturan (Sistem)**.

| Pengaturan | Cara Ubah | Kegunaan |
|------------|-----------|----------|
| **Nama Perusahaan** | Edit "Nama Perusahaan" | Mengubah identitas di seluruh aplikasi & laporan |
| **Lokasi Kantor** | Edit "Latitude/Longitude Kantor" | Mengubah titik pusat absen GPS |
| **Radius Absen** | Edit "Radius Presensi" | Mengatur seberapa jauh karyawan boleh absen (meter) |
| **Nominal Potongan** | Edit "Potongan per 10 Menit" | Mengatur denda otomatis keterlambatan |
| **Toleransi Waktu** | Edit "Toleransi Terlambat" | Mengatur batas menit sebelum dianggap telat (Contoh: 10) |
| **Alamat Kantor** | Edit "Alamat Perusahaan" | Mengubah alamat di footer/laporan |

---

## 2. Cara Update Manual Jika Mendesak (FILES)
Jika ingin melihat kodingan aslinya atau mengubah logika perhitungan:
- **Logic Presensi**: `app/Http/Controllers/PresensiController.php`
- **Logic Setting**: `app/Models/Setting.php` (Gunakan `Setting::get('key')`)


---

## 2. Penyesuaian Tampilan (UI) Mobile
Jika ingin mengubah warna atau font portal karyawan, edit file: `resources/views/karyawan/layout.blade.php`

Cari bagian `<style>` di dalam file tersebut, Anda bisa langsung mengganti variabel CSS berikut:
- `--brand-a`: Warna Teal (Utama).
- `--brand-b`: Warna Amber (Sekunder).
- `--bg`: Warna background aplikasi.

---

## 3. Penyesuaian Data Demo (Seeder)
Jika ingin menambah user baru atau mengubah data awal, edit file: `database/seeders/DatabaseSeeder.php`

Setelah mengedit, jalankan perintah:
```bash
php artisan migrate:fresh --seed
```

---

## 4. Setup Storage Link di Windows (Wajib Saat Instalasi Baru)

`php artisan storage:link` di **Windows** kadang membuat folder kosong biasa di `public/storage` alih-alih junction ke `storage/app/public`. Akibatnya foto presensi & bukti pekerjaan yang diupload karyawan **tidak tampil** di browser.

### Cara Cek

Buka PowerShell di folder project, jalankan:

```powershell
(Get-Item public\storage -Force).Attributes
```

- ✅ **Benar:** output mengandung `ReparsePoint` (artinya junction aktif)
- ❌ **Salah:** output hanya `Directory` (folder kosong biasa)

### Cara Perbaiki (jika salah)

```powershell
# Hapus folder kosong lama
Remove-Item public\storage -Force -Recurse

# Buat junction yang benar
New-Item -ItemType Junction -Path public\storage -Target storage\app\public
```

Setelah ini verifikasi:

```powershell
(Get-Item public\storage -Force).Attributes
# Output harus: Directory, ReparsePoint
```

> Lakukan langkah ini **sekali** setelah instalasi awal (`composer run setup`). Tidak perlu diulang kecuali folder `public/storage` terhapus atau project dipindah.

---

## 5. Reset & Optimasi Cepat (Cache Bersih)
Jika sistem terasa berat atau perubahan kode tidak muncul, jalankan perintah "Sakti" ini di terminal:

```powershell
# Bersihkan semua cache & optimasi ulang
php artisan optimize:clear; php artisan config:cache; php artisan view:cache
```

---

## 6. Mengubah Struktur Form Admin (Filament)
Jika ingin menambah/menghapus inputan di panel Admin, cari file di folder:
`app/Filament/Resources/[NamaModul]/Schemas/`

Contoh: Ingin tambah field di Penugasan? Edit `DetailPekerjaanForm.php`.

---

## 7. Buat Jadwal Massal (Range Tanggal)

Di form **Buat Jadwal**, isi field **Tanggal Mulai** dan **Tanggal Akhir** sekaligus. Sistem akan membuat satu jadwal per hari di rentang tersebut, melewati tanggal yang sudah ada (tidak duplikat).

---

## 8. Nama Perusahaan di Halaman Login

Heading halaman `/login` diambil langsung dari `Setting::get('nama_perusahaan')`. Untuk mengubahnya: Panel Admin → **Pengaturan (Sistem)** → edit "Nama Perusahaan". Perubahan langsung berlaku tanpa restart.

---

> **Tips Sat-Set:** Gunakan `CTRL + P` di VS Code lalu ketik nama filenya untuk berpindah antar file dengan cepat.
