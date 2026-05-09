# Diagram Sistem (Use Case & ERD)
## Sistem Informasi Kepegawaian — CV Boss Muda Mandiri

Dokumen ini menyajikan visualisasi arsitektur sistem dalam bentuk Use Case Diagram dan Entity Relationship Diagram (ERD) yang telah disesuaikan dengan *source code* terbaru.

---

## 1. Use Case Diagram

Diagram ini menggambarkan interaksi antara aktor (Admin, Supervisor, Karyawan) dengan fitur-fitur utama di dalam sistem.

```mermaid
useCaseDiagram
    actor Admin
    actor Supervisor
    actor Karyawan

    package "Panel Admin & Supervisor (Filament)" {
        usecase UC1 as "Kelola Akun & Data Master"
        usecase UC2 as "Kelola Penugasan (Detail Pekerjaan)"
        usecase UC3 as "Monitoring Presensi Real-Time"
        usecase UC4 as "Verifikasi Presensi Karyawan"
        usecase UC5 as "Review Bukti Pekerjaan"
        usecase UC6 as "Kelola Rekap Potongan"
        usecase UC7 as "Export Laporan (CSV/Excel/PDF)"
    }

    package "Portal Mobile Karyawan (Blade)" {
        usecase UC8 as "Presensi Masuk (GPS + Selfie)"
        usecase UC9 as "Presensi Pulang"
        usecase UC10 as "Terima/Tolak Penugasan"
        usecase UC11 as "Upload Bukti Pekerjaan (Before/After)"
        usecase UC12 as "Lihat Jadwal & Riwayat"
    }

    %% Relasi Admin
    Admin --> UC1
    Admin --> UC2
    Admin --> UC3
    Admin --> UC4
    Admin --> UC5
    Admin --> UC6
    Admin --> UC7

    %% Relasi Supervisor
    Supervisor --> UC3
    Supervisor --> UC5
    Supervisor --> UC7

    %% Relasi Karyawan
    Karyawan --> UC8
    Karyawan --> UC9
    Karyawan --> UC10
    Karyawan --> UC11
    Karyawan --> UC12
```

---

## 2. Entity Relationship Diagram (ERD)

Diagram ini menunjukkan struktur data dan hubungan antar tabel di database `sistem_kepegawaian`.

```mermaid
erDiagram
    tb_user {
        bigint id PK
        string nama
        string email
        string password
        enum role
        boolean is_active
    }

    tb_admin {
        bigint id PK
        bigint user_id FK
        string nip
        string divisi
    }

    tb_supervisor {
        bigint id PK
        bigint user_id FK
        string jabatan
    }

    tb_karyawan {
        bigint id PK
        bigint user_id FK
        string nik UK
        string posisi_karyawan
        decimal gaji_pokok
    }

    tb_detail_pekerjaan {
        bigint id PK
        bigint karyawan_id FK
        date tanggal
        time jam_masuk
        time jam_pulang
        string nama_lokasi
        decimal latitude
        decimal longitude
        enum status "pending/disetujui/ditolak"
        text alasan_tolak
    }

    tb_presensi {
        bigint id PK
        bigint karyawan_id FK
        date tgl_presensi
        datetime jam_masuk
        datetime jam_pulang
        enum status "hadir/terlambat/tidak_hadir/izin"
        string foto_masuk
        integer keterlambatan_menit
        decimal potongan
    }

    tb_bukti_pekerjaan {
        bigint id PK
        bigint detail_pekerjaan_id FK
        bigint karyawan_id FK
        string foto_before
        string foto_after
        text keterangan
        enum status
        datetime uploaded_at
    }

    tb_verifikasi {
        bigint id PK
        bigint presensi_id FK
        bigint supervisor_id FK
        enum status
        text catatan
    }

    tb_rekap_potongan {
        bigint id PK
        bigint karyawan_id FK
        bigint admin_id FK
        string periode
        integer jumlah_hadir
        integer jumlah_terlambat
        decimal total_potongan_keterlambatan
        decimal gaji_pokok
        decimal gaji_bersih
        enum status "draft/final/dibatalkan"
    }

    tb_laporan {
        bigint id PK
        string judul
        string jenis
        string periode
        text filter
        string file_path
        bigint generated_by FK
        datetime tgl_generate
    }

    tb_notifikasi {
        bigint id PK
        bigint user_id FK
        enum tipe "info/peringatan/urgent"
        text pesan
        boolean terbaca
        string channel
    }

    tb_setting {
        bigint id PK
        string key UK
        text value
    }

    tb_user ||--o| tb_admin : "is a"
    tb_user ||--o| tb_supervisor : "is a"
    tb_user ||--o| tb_karyawan : "is a"
    tb_user ||--o{ tb_notifikasi : "receives"
    tb_user ||--o{ tb_laporan : "generates"

    tb_karyawan ||--o{ tb_presensi : "logs"
    tb_karyawan ||--o{ tb_detail_pekerjaan : "assigned to"
    tb_karyawan ||--o{ tb_bukti_pekerjaan : "submits"
    tb_karyawan ||--o{ tb_rekap_potongan : "has salary"

    tb_presensi ||--o| tb_verifikasi : "validated in"
    tb_supervisor ||--o{ tb_verifikasi : "performs"
    tb_admin ||--o{ tb_rekap_potongan : "generates"

    tb_detail_pekerjaan ||--o{ tb_bukti_pekerjaan : "requires proof"
```

---

## 3. Penjelasan Relasi Kunci

1.  **Generalisasi (Role)**: Tabel `tb_user` adalah tabel induk untuk autentikasi, yang terhubung secara One-to-One dengan `tb_admin`, `tb_supervisor`, atau `tb_karyawan`.
2.  **Pemisahan Absensi & Tugas**: 
    - `tb_presensi` mencatat kehadiran harian karyawan di Kantor Pusat.
    - `tb_detail_pekerjaan` mencatat tugas spesifik yang harus diterima/ditolak karyawan.
3.  **Bukti per Tugas**: Relasi antara `tb_detail_pekerjaan` dan `tb_bukti_pekerjaan` memungkinkan karyawan mengunggah banyak laporan hasil kerja dalam satu hari sesuai jumlah tugas yang diberikan.
4.  **Verifikasi Berjenjang**: Setiap log presensi harian diverifikasi oleh Supervisor melalui tabel `tb_verifikasi`.
5.  **Rekap Potongan**: Tabel `tb_rekap_potongan` menghitung gaji bersih per bulan berdasarkan data presensi (hadir/telat/alpa).
6.  **Pengaturan Dinamis**: Tabel `tb_setting` menyimpan konfigurasi sistem secara key-value (lokasi kantor, radius, potongan, dll).
