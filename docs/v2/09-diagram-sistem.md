# Diagram Sistem (Use Case & ERD)

## Sistem Informasi Kepegawaian — CV Boss Muda Mandiri

Dokumen ini menyajikan visualisasi arsitektur sistem dalam bentuk Use Case Diagram dan Entity Relationship Diagram (ERD) yang telah disesuaikan dengan _source code_ terbaru.

---

## 1. Use Case Diagram

Diagram ini menggambarkan interaksi antara aktor (Admin, Supervisor, Karyawan) dengan fitur-fitur utama di dalam sistem. Karena renderer Markdown GitHub tidak konsisten mendukung `usecaseDiagram`, visual berikut ditulis sebagai flowchart Mermaid yang setara.

```mermaid
flowchart LR
    Admin([Admin])
    Supervisor([Supervisor])
    Karyawan([Karyawan])

    subgraph PanelAdminSupervisor["Panel Admin & Supervisor (Filament)"]
        UC1["Kelola Akun & Data Master"]
        UC2["Kelola Penugasan (Detail Pekerjaan)"]
        UC3["Monitoring Presensi Real-Time"]
        UC4["Verifikasi Presensi Karyawan"]
        UC5["Review Bukti Pekerjaan"]
        UC6["Kelola Rekap Presensi Bulanan"]
        UC7["Export Laporan (CSV/Excel/PDF)"]
    end

    subgraph PortalKaryawan["Portal Mobile Karyawan (Blade)"]
        UC8["Presensi Masuk (GPS + Selfie)"]
        UC9["Presensi Pulang"]
        UC10["Terima/Tolak Penugasan"]
        UC11["Upload Bukti Pekerjaan (Before/After)"]
        UC12["Lihat Jadwal & Riwayat"]
    end

    Admin --> UC1
    Admin --> UC2
    Admin --> UC3
    Admin --> UC4
    Admin --> UC5
    Admin --> UC6
    Admin --> UC7

    Supervisor --> UC3
    Supervisor --> UC5
    Supervisor --> UC7

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
        string nik
        string no_hp
    }

    tb_supervisor {
        bigint id PK
        bigint user_id FK
        string nik
        string no_hp
    }

    tb_karyawan {
        bigint id PK
        bigint user_id FK
        string nik UK
        string posisi_karyawan
        decimal gaji_pokok "tidak ditampilkan di UI"
    }

    tb_jadwal {
        bigint id PK
        bigint karyawan_id FK
        bigint admin_id FK
        date tanggal_kerja
        time jam_masuk
        time jam_pulang
        boolean hari_libur
        enum status "aktif/dibatalkan"
    }

    tb_detail_pekerjaan {
        bigint id PK
        bigint jadwal_id FK
        bigint karyawan_id FK
        string nama_lokasi
        decimal latitude
        decimal longitude
        enum status "pending/disetujui/ditolak"
        text alasan_tolak
    }

    tb_presensi {
        bigint id PK
        bigint karyawan_id FK
        bigint jadwal_id FK
        date tanggal
        datetime jam_masuk
        datetime jam_keluar
        enum status_presensi "hadir/terlambat/tidak_hadir/izin"
        enum status_valid "pending/valid/tidak_valid"
        string foto_masuk
        decimal latitude_masuk
        decimal longitude_masuk
        integer menit_terlambat
        decimal potongan_terlambat
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

    tb_rekap_presensi_bulanan {
        bigint id PK
        bigint karyawan_id FK
        bigint admin_id FK
        string periode
        integer jumlah_hadir
        integer jumlah_terlambat
        decimal total_potongan_keterlambatan
        decimal total_potongan_keterlambatan
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

    tb_setting {
        bigint id PK
        string key UK
        text value
    }

    tb_user ||--o| tb_admin : "is a"
    tb_user ||--o| tb_supervisor : "is a"
    tb_user ||--o| tb_karyawan : "is a"
    tb_user ||--o{ tb_laporan : "generates"

    tb_admin ||--o{ tb_jadwal : "creates"

    tb_karyawan ||--o{ tb_jadwal : "has schedule"
    tb_karyawan ||--o{ tb_presensi : "logs"
    tb_karyawan ||--o{ tb_detail_pekerjaan : "assigned to"
    tb_karyawan ||--o{ tb_bukti_pekerjaan : "submits"
    tb_karyawan ||--o{ tb_rekap_presensi_bulanan : "has rekap"

    tb_jadwal ||--o{ tb_detail_pekerjaan : "has tasks"
    tb_jadwal ||--o| tb_presensi : "has presensi"

    tb_presensi ||--o| tb_verifikasi : "validated in"
    tb_supervisor ||--o{ tb_verifikasi : "performs"
    tb_admin ||--o{ tb_rekap_presensi_bulanan : "generates"

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
5.  **Rekap Presensi Bulanan**: Tabel `tb_rekap_presensi_bulanan` merekap potongan keterlambatan per bulan berdasarkan data presensi (hadir/telat/alpa). Kolom `gaji_pokok`/`gaji_bersih` tetap ada di DB namun tidak ditampilkan di UI sejak V2.2.
6.  **Pengaturan Dinamis**: Tabel `tb_setting` menyimpan konfigurasi sistem secara key-value (lokasi kantor, radius, potongan, dll).
