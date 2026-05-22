# Data Seeder & Skenario Demo

## Sistem Informasi Kepegawaian — CV Boss Muda Mandiri

---

## 1. Skenario Demo untuk BAB 4 (Refactored)

Skenario ini mencakup alur lengkap dari penugasan hingga verifikasi akhir.

### Skenario 1: Admin Memberikan Tugas

1. Login sebagai `admin@example.com`.
2. Buka menu **Detail Pekerjaan**.
3. Klik **Create New**, pilih karyawan "Rizky Pratama", tentukan lokasi "Kantor Pusat" (Pagi), "Proyek B" (Siang), dan "Gudang" (Sore).
4. Klik **Save**. Tugas terkirim ke portal karyawan dengan status **Pending**.

### Skenario 2: Karyawan Merespon Tugas

1. Login sebagai `karyawan@example.com`.
2. Buka menu **Tugas** di navigasi bawah.
3. Karyawan melihat 3 tugas baru.
4. Klik **Terima Tugas** pada tugas "Kantor Pusat".
5. Klik **Tolak Tugas** pada tugas "Proyek B", isi alasan: "Kendaraan sedang dalam perbaikan".
6. Status tugas diperbarui secara real-time.

### Skenario 3: Presensi Harian di Kantor Pusat

1. Karyawan berada di Kantor Pusat.
2. Klik **Presensi Masuk** di Beranda.
3. Klik **Ambil Lokasi** (Sistem memvalidasi jarak ke Kantor Pusat).
4. Ambil foto selfie dan klik **Kirim**.
5. Jika jam menunjukkan pukul 08:15, sistem otomatis mencatat status **Terlambat** dan mencatat potongan Rp 10.000.

### Skenario 4: Karyawan Melaporkan Hasil Kerja (Upload Bukti)

1. Setelah menyelesaikan tugas "Kantor Pusat", karyawan kembali ke menu **Tugas**.
2. Klik **Upload Bukti Pekerjaan**.
3. Ambil **Foto Before** (kondisi awal) dan **Foto After** (hasil akhir).
4. Isi keterangan: "Pemasangan kabel selesai dan area dibersihkan".
5. Klik **Kirim**.

### Skenario 5: Supervisor Memverifikasi

1. Login sebagai `supervisor@example.com`.
2. Buka menu **Verifikasi** untuk menyetujui absensi harian karyawan.
3. Buka menu **Bukti Pekerjaan** untuk me-review hasil kerja (foto before/after).
4. Supervisor memberikan status **Disetujui** pada bukti kerja yang valid.

---

## 2. Distribusi Data Historis (Seeder)

Seeder menghasilkan data 15 hari terakhir dengan logika:

- **Presensi**: 1 baris per hari per karyawan (Check-in/out Kantor Pusat).
- **Detail Pekerjaan**: 3 baris per hari (Pagi, Siang, Sore).
- **Bukti Pekerjaan**: Dihasilkan secara acak untuk tugas yang berstatus "Disetujui".
- **Verifikasi**: Mayoritas sudah disetujui oleh supervisor Andi Gunawan.

---

## 3. Akun Login Demo

| Role       | Email                  | Password |
| ---------- | ---------------------- | -------- |
| Admin      | admin@example.com      | password |
| Supervisor | supervisor@example.com | password |
| Karyawan   | karyawan@example.com   | password |
