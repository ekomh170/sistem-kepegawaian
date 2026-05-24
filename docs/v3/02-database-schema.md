# Database Schema V3

## Sistem Informasi Kepegawaian — CV Boss Muda Mandiri

---

## 1. Ringkasan Tabel

| No  | Tabel                 | Tipe      | Jumlah Kolom | Catatan                                                 |
| --- | --------------------- | --------- | ------------ | ------------------------------------------------------- |
| 1   | `tb_user`             | Inti      | 11           | Konsolidasi 4 tabel V2 (user+admin+supervisor+karyawan) |
| 2   | `tb_jadwal_pekerjaan` | Inti      | 10           | Rename dari `tb_jadwal`                                 |
| 3   | `tb_detail_pekerjaan` | Inti      | 12           | Sama struktur V2 (FK karyawan_id → user_id)             |
| 4   | `tb_presensi`         | Inti      | 22           | + 4 kolom verifikasi inline (dari tb_verifikasi)        |
| 5   | `tb_bukti_pekerjaan`  | Inti      | 9            | Sama struktur V2 (FK karyawan_id → user_id)             |
| 6   | `tb_laporan_presensi` | Inti      | 13           | Merger tb_laporan + tb_rekap_presensi_bulanan           |
| 7   | `tb_setting`          | Pendukung | 5            | Tetap dari V2                                           |

**Total:** 6 tabel inti + 1 pendukung = **7 tabel** (turun dari 12 tabel V2)

---

## 2. Detail Skema per Tabel

### 2.1 tb_user

```sql
CREATE TABLE tb_user (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama            VARCHAR(255) NOT NULL,
    email           VARCHAR(255) NOT NULL UNIQUE,
    password        VARCHAR(255) NOT NULL,
    nik             VARCHAR(255) NULL,
    no_hp           VARCHAR(255) NULL,
    posisi          VARCHAR(255) NULL,
    role            ENUM('karyawan', 'admin', 'supervisor') NOT NULL DEFAULT 'karyawan',
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    remember_token  VARCHAR(100) NULL,
    created_at      TIMESTAMP NULL,

    INDEX idx_role (role),
    INDEX idx_email (email)
);
```

**Catatan:**

- `nik` nullable di level DB, **wajib** di level aplikasi untuk role=karyawan
- `posisi` hanya relevan untuk role=karyawan
- Tidak ada `updated_at` (sama dengan V2)

### 2.2 tb_jadwal_pekerjaan

```sql
CREATE TABLE tb_jadwal_pekerjaan (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NOT NULL,
    dibuat_oleh     BIGINT UNSIGNED NULL,
    tanggal_kerja   DATE NOT NULL,
    jam_masuk       TIME NOT NULL DEFAULT '08:00:00',
    jam_pulang      TIME NOT NULL DEFAULT '16:00:00',
    hari_libur      TINYINT(1) NOT NULL DEFAULT 0,
    status          ENUM('aktif', 'dibatalkan') NOT NULL DEFAULT 'aktif',
    created_at      TIMESTAMP NULL,

    UNIQUE KEY uk_user_tanggal (user_id, tanggal_kerja),
    FOREIGN KEY (user_id)     REFERENCES tb_user(id) ON DELETE CASCADE,
    FOREIGN KEY (dibuat_oleh) REFERENCES tb_user(id) ON DELETE SET NULL
);
```

### 2.3 tb_detail_pekerjaan

```sql
CREATE TABLE tb_detail_pekerjaan (
    id                    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    jadwal_id             BIGINT UNSIGNED NOT NULL,
    user_id               BIGINT UNSIGNED NOT NULL,
    nama_lokasi           VARCHAR(255) NOT NULL,
    alamat_lokasi         TEXT NULL,
    latitude              DECIMAL(10,7) NULL,
    longitude             DECIMAL(10,7) NULL,
    radius_meter          INT UNSIGNED NOT NULL DEFAULT 50,
    keterangan_pekerjaan  TEXT NULL,
    status                ENUM('pending', 'disetujui', 'ditolak') NOT NULL DEFAULT 'pending',
    alasan_tolak          TEXT NULL,
    created_at            TIMESTAMP NULL,

    FOREIGN KEY (jadwal_id) REFERENCES tb_jadwal_pekerjaan(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)   REFERENCES tb_user(id) ON DELETE CASCADE
);
```

### 2.4 tb_presensi

```sql
CREATE TABLE tb_presensi (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             BIGINT UNSIGNED NOT NULL,
    jadwal_id           BIGINT UNSIGNED NULL,
    tanggal             DATE NOT NULL,
    jam_masuk           DATETIME NULL,
    jam_keluar          DATETIME NULL,

    -- foto + GPS (dipertahankan dari V2)
    foto_masuk          VARCHAR(255) NULL,
    foto_keluar         VARCHAR(255) NULL,
    latitude_masuk      DECIMAL(10,7) NULL,
    longitude_masuk     DECIMAL(10,7) NULL,
    latitude_keluar     DECIMAL(10,7) NULL,
    longitude_keluar    DECIMAL(10,7) NULL,

    -- kalkulasi keterlambatan
    menit_terlambat     INT UNSIGNED NOT NULL DEFAULT 0,
    potongan_terlambat  DECIMAL(10,2) NOT NULL DEFAULT 0,
    status_presensi     ENUM('hadir', 'terlambat', 'tidak_hadir', 'izin') NOT NULL DEFAULT 'tidak_hadir',

    -- verifikasi inline (BARU di V3 — sebelumnya di tb_verifikasi)
    status_verifikasi   ENUM('pending', 'disetujui', 'ditolak') NOT NULL DEFAULT 'pending',
    diverifikasi_oleh   BIGINT UNSIGNED NULL,
    catatan_verifikasi  TEXT NULL,
    tgl_verifikasi      DATETIME NULL,

    created_at          TIMESTAMP NULL,

    UNIQUE KEY uk_user_tanggal (user_id, tanggal),
    INDEX idx_status_presensi (status_presensi),
    INDEX idx_status_verifikasi (status_verifikasi),
    FOREIGN KEY (user_id)            REFERENCES tb_user(id) ON DELETE CASCADE,
    FOREIGN KEY (jadwal_id)          REFERENCES tb_jadwal_pekerjaan(id) ON DELETE SET NULL,
    FOREIGN KEY (diverifikasi_oleh)  REFERENCES tb_user(id) ON DELETE SET NULL
);
```

### 2.5 tb_bukti_pekerjaan

```sql
CREATE TABLE tb_bukti_pekerjaan (
    id                    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    detail_pekerjaan_id   BIGINT UNSIGNED NOT NULL,
    user_id               BIGINT UNSIGNED NOT NULL,
    foto_before           VARCHAR(255) NOT NULL,
    foto_after            VARCHAR(255) NOT NULL,
    keterangan            TEXT NULL,
    status                ENUM('pending', 'disetujui', 'ditolak') NOT NULL DEFAULT 'pending',
    uploaded_at           DATETIME NOT NULL,
    created_at            TIMESTAMP NULL,

    FOREIGN KEY (detail_pekerjaan_id) REFERENCES tb_detail_pekerjaan(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)             REFERENCES tb_user(id) ON DELETE CASCADE
);
```

### 2.6 tb_laporan_presensi

```sql
CREATE TABLE tb_laporan_presensi (
    id                    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id               BIGINT UNSIGNED NULL,
    generated_by          BIGINT UNSIGNED NULL,
    judul                 VARCHAR(255) NOT NULL,
    periode               VARCHAR(20) NOT NULL,
    jenis                 ENUM('Harian', 'Mingguan', 'Bulanan', 'Tahunan') NOT NULL,

    -- diisi hanya untuk rekap per-karyawan
    jumlah_hadir          INT UNSIGNED NULL,
    jumlah_terlambat      INT UNSIGNED NULL,
    jumlah_tidak_hadir    INT UNSIGNED NULL,
    total_potongan        DECIMAL(12,2) NULL,

    file_path             VARCHAR(255) NULL,
    tgl_generate          DATETIME NOT NULL,
    created_at            TIMESTAMP NULL,

    INDEX idx_jenis (jenis),
    INDEX idx_periode (periode),
    FOREIGN KEY (user_id)      REFERENCES tb_user(id) ON DELETE CASCADE,
    FOREIGN KEY (generated_by) REFERENCES tb_user(id) ON DELETE SET NULL
);
```

**Catatan polimorfik:**

- `user_id = NULL` → laporan agregat (e.g. semua karyawan periode X)
- `user_id != NULL` → rekap per-karyawan (`jumlah_*` + `total_potongan` wajib diisi)
- `file_path = NULL` → laporan belum di-generate ke file; diisi setelah export PDF/Excel/CSV

### 2.7 tb_setting (Pendukung)

```sql
CREATE TABLE tb_setting (
    id      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key`   VARCHAR(100) NOT NULL UNIQUE,
    value   TEXT NULL,
    `group` VARCHAR(50) NULL,
    label   VARCHAR(255) NULL
);
```

**Key yang dipakai:**

- Geofence: `kantor_lat`, `kantor_lng`, `kantor_radius`, `toleransi_menit`
- Identitas: `nama_perusahaan`, `alamat_perusahaan`
- Kalkulasi: `potongan_terlambat` (Rp per blok 10 menit, default 10000)

---

## 3. Aturan Foreign Key

| FK Column                                | Reference                | On Delete |
| ---------------------------------------- | ------------------------ | --------- |
| `tb_jadwal_pekerjaan.user_id`            | `tb_user.id`             | CASCADE   |
| `tb_jadwal_pekerjaan.dibuat_oleh`        | `tb_user.id`             | SET NULL  |
| `tb_detail_pekerjaan.jadwal_id`          | `tb_jadwal_pekerjaan.id` | CASCADE   |
| `tb_detail_pekerjaan.user_id`            | `tb_user.id`             | CASCADE   |
| `tb_presensi.user_id`                    | `tb_user.id`             | CASCADE   |
| `tb_presensi.jadwal_id`                  | `tb_jadwal_pekerjaan.id` | SET NULL  |
| `tb_presensi.diverifikasi_oleh`          | `tb_user.id`             | SET NULL  |
| `tb_bukti_pekerjaan.detail_pekerjaan_id` | `tb_detail_pekerjaan.id` | CASCADE   |
| `tb_bukti_pekerjaan.user_id`             | `tb_user.id`             | CASCADE   |
| `tb_laporan_presensi.user_id`            | `tb_user.id`             | CASCADE   |
| `tb_laporan_presensi.generated_by`       | `tb_user.id`             | SET NULL  |

**Pola umum:**

- Data karyawan yang owner-nya hilang → ikut terhapus (CASCADE)
- Data audit/generated yang creator-nya hilang → tetap tapi referensi creator jadi NULL (SET NULL)

---

## 4. Unique Constraints

| Tabel                                          | Constraint | Tujuan                              |
| ---------------------------------------------- | ---------- | ----------------------------------- |
| `tb_user.email`                                | UNIQUE     | Login identifier                    |
| `tb_jadwal_pekerjaan (user_id, tanggal_kerja)` | UNIQUE     | 1 karyawan = 1 jadwal per tanggal   |
| `tb_presensi (user_id, tanggal)`               | UNIQUE     | 1 karyawan = 1 presensi per tanggal |
| `tb_setting.key`                               | UNIQUE     | Key-value uniqueness                |

> **Catatan:** V2 tidak punya unique `tb_presensi(user_id, tanggal)` secara eksplisit. V3 menambahkan ini untuk mencegah double check-in.

---

## 5. Indexes (Performance)

| Tabel                             | Index | Alasan                              |
| --------------------------------- | ----- | ----------------------------------- |
| `tb_user (role)`                  | INDEX | Query by role (e.g. semua karyawan) |
| `tb_user (email)`                 | INDEX | Login lookup                        |
| `tb_presensi (status_presensi)`   | INDEX | Filter dashboard widget             |
| `tb_presensi (status_verifikasi)` | INDEX | Filter verifikasi pending           |
| `tb_laporan_presensi (jenis)`     | INDEX | Filter by jenis laporan             |
| `tb_laporan_presensi (periode)`   | INDEX | Search by periode                   |

---

## 6. Diff Tabel V2 ke V3

### Dihapus

- `tb_admin`
- `tb_supervisor`
- `tb_karyawan`
- `tb_verifikasi`
- `tb_laporan` (di-merge ke tb_laporan_presensi)
- `tb_rekap_presensi_bulanan` (di-merge ke tb_laporan_presensi)

### Direname

- `tb_jadwal` → `tb_jadwal_pekerjaan`

### Tetap (struktur sama, FK direname)

- `tb_user` (+ kolom nik, no_hp, posisi)
- `tb_detail_pekerjaan` (karyawan_id → user_id)
- `tb_presensi` (karyawan_id → user_id, + 4 kolom verifikasi inline)
- `tb_bukti_pekerjaan` (karyawan_id → user_id)
- `tb_setting` (tidak berubah)

### Baru

- `tb_laporan_presensi` (gabungan tb_laporan + tb_rekap_presensi_bulanan)

---

Selanjutnya: [03-class-diagram-oop.md](03-class-diagram-oop.md)
