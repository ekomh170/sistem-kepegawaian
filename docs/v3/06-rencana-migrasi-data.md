# Rencana Migrasi Data V2 → V3

## Sistem Informasi Kepegawaian — CV Boss Muda Mandiri

---

## 1. Strategi Migrasi

Karena V3 mengubah skema (drop 6 tabel, rename 1, merge 2), migrasi data **tidak bisa pakai `migrate:fresh` di production** kalau ada data live. Strategi:

### 1.1 Untuk Dev / Demo (Reset Total)

```bash
php artisan migrate:fresh --seed
```

- Drop semua tabel V2
- Buat tabel V3 baru
- Seeder generate data demo sesuai struktur V3

### 1.2 Untuk Production (Migrasi In-Place)

3-step approach (hindari downtime):

1. **Phase 1: Add new columns** (additive only, backward compatible)
    - Tambah `nik`, `no_hp`, `posisi` di `tb_user`
    - Tambah `status_verifikasi`, `diverifikasi_oleh`, `catatan_verifikasi`, `tgl_verifikasi` di `tb_presensi`
    - Tambah `dibuat_oleh` di `tb_jadwal`
    - Tambah `user_id`, `generated_by`, `jumlah_*`, `total_potongan`, `file_path` di `tb_laporan`

2. **Phase 2: Backfill data** (jalankan migration script)
    - Copy data dari tb_admin/tb_supervisor/tb_karyawan ke tb_user
    - Copy data tb_verifikasi ke kolom inline tb_presensi
    - Copy data tb_rekap_presensi_bulanan ke tb_laporan_presensi
    - Rename FK column: `karyawan_id` → `user_id` (di semua tabel)

3. **Phase 3: Cleanup** (setelah verifikasi backfill berhasil)
    - Drop tb_admin, tb_supervisor, tb_karyawan, tb_verifikasi
    - Drop tb_rekap_presensi_bulanan
    - Rename tb_jadwal → tb_jadwal_pekerjaan
    - Rename tb_laporan → tb_laporan_presensi

---

## 2. Migration Files (Urutan Eksekusi)

Buat di `database/migrations/` dengan timestamp setelah migrasi V2 terakhir:

### Phase 1 — Add Columns

**`2026_05_25_000001_add_v3_columns_to_tb_user.php`**

```php
return new class extends Migration {
    public function up(): void
    {
        Schema::table('tb_user', function (Blueprint $table) {
            $table->string('nik')->nullable()->after('password');
            $table->string('no_hp')->nullable()->after('nik');
            $table->string('posisi')->nullable()->after('no_hp');
        });
    }

    public function down(): void
    {
        Schema::table('tb_user', function (Blueprint $table) {
            $table->dropColumn(['nik', 'no_hp', 'posisi']);
        });
    }
};
```

**`2026_05_25_000002_add_verifikasi_columns_to_tb_presensi.php`**

```php
return new class extends Migration {
    public function up(): void
    {
        Schema::table('tb_presensi', function (Blueprint $table) {
            $table->enum('status_verifikasi', ['pending', 'disetujui', 'ditolak'])
                  ->default('pending')->after('status_presensi');
            $table->foreignId('diverifikasi_oleh')->nullable()->after('status_verifikasi')
                  ->constrained('tb_user')->nullOnDelete();
            $table->text('catatan_verifikasi')->nullable()->after('diverifikasi_oleh');
            $table->dateTime('tgl_verifikasi')->nullable()->after('catatan_verifikasi');
        });
    }

    public function down(): void
    {
        Schema::table('tb_presensi', function (Blueprint $table) {
            $table->dropForeign(['diverifikasi_oleh']);
            $table->dropColumn(['status_verifikasi', 'diverifikasi_oleh', 'catatan_verifikasi', 'tgl_verifikasi']);
        });
    }
};
```

**`2026_05_25_000003_add_dibuat_oleh_to_tb_jadwal.php`**

```php
return new class extends Migration {
    public function up(): void
    {
        Schema::table('tb_jadwal', function (Blueprint $table) {
            $table->foreignId('dibuat_oleh')->nullable()->after('admin_id')
                  ->constrained('tb_user')->nullOnDelete();
        });
    }
};
```

**`2026_05_25_000004_add_user_id_to_tb_jadwal.php`**

```php
return new class extends Migration {
    public function up(): void
    {
        Schema::table('tb_jadwal', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('karyawan_id')
                  ->constrained('tb_user')->cascadeOnDelete();
        });
    }
};
```

(Lakukan pola serupa untuk `tb_detail_pekerjaan`, `tb_presensi`, `tb_bukti_pekerjaan`, `tb_laporan`)

### Phase 2 — Backfill Data

**`2026_05_25_000010_backfill_user_profiles.php`**

```php
return new class extends Migration {
    public function up(): void
    {
        // Backfill nik, no_hp dari tb_admin
        DB::statement("
            UPDATE tb_user u
            JOIN tb_admin a ON a.user_id = u.id
            SET u.nik = a.nik, u.no_hp = a.no_hp
        ");

        // Backfill nik, no_hp dari tb_supervisor
        DB::statement("
            UPDATE tb_user u
            JOIN tb_supervisor s ON s.user_id = u.id
            SET u.nik = s.nik, u.no_hp = s.no_hp
        ");

        // Backfill nik, no_hp, posisi dari tb_karyawan
        DB::statement("
            UPDATE tb_user u
            JOIN tb_karyawan k ON k.user_id = u.id
            SET u.nik = k.nik, u.no_hp = k.no_hp, u.posisi = k.posisi_karyawan
        ");
    }
};
```

**`2026_05_25_000011_backfill_verifikasi_inline.php`**

```php
return new class extends Migration {
    public function up(): void
    {
        DB::statement("
            UPDATE tb_presensi p
            JOIN tb_verifikasi v ON v.presensi_id = p.id
            JOIN tb_supervisor s ON s.id = v.supervisor_id
            SET p.status_verifikasi = CASE
                    WHEN v.status = 'disetujui' THEN 'disetujui'
                    WHEN v.status = 'ditolak'   THEN 'ditolak'
                    ELSE 'pending'
                END,
                p.diverifikasi_oleh  = s.user_id,
                p.catatan_verifikasi = COALESCE(v.catatan, v.alasan_tolak),
                p.tgl_verifikasi     = v.tgl_verifikasi
        ");
    }
};
```

**`2026_05_25_000012_backfill_user_id_on_relations.php`**

```php
return new class extends Migration {
    public function up(): void
    {
        // tb_jadwal: karyawan_id (FK ke tb_karyawan) → user_id (FK ke tb_user)
        DB::statement("
            UPDATE tb_jadwal j
            JOIN tb_karyawan k ON k.id = j.karyawan_id
            SET j.user_id = k.user_id
        ");

        // tb_jadwal: admin_id (FK ke tb_admin) → dibuat_oleh (FK ke tb_user)
        DB::statement("
            UPDATE tb_jadwal j
            JOIN tb_admin a ON a.id = j.admin_id
            SET j.dibuat_oleh = a.user_id
        ");

        // tb_detail_pekerjaan
        DB::statement("
            UPDATE tb_detail_pekerjaan dp
            JOIN tb_karyawan k ON k.id = dp.karyawan_id
            SET dp.user_id = k.user_id
        ");

        // tb_presensi
        DB::statement("
            UPDATE tb_presensi p
            JOIN tb_karyawan k ON k.id = p.karyawan_id
            SET p.user_id = k.user_id
        ");

        // tb_bukti_pekerjaan
        DB::statement("
            UPDATE tb_bukti_pekerjaan b
            JOIN tb_karyawan k ON k.id = b.karyawan_id
            SET b.user_id = k.user_id
        ");
    }
};
```

**`2026_05_25_000013_backfill_laporan_presensi_merged.php`**

```php
return new class extends Migration {
    public function up(): void
    {
        // Step 1: Migrate tb_rekap_presensi_bulanan → tb_laporan (sebagai jenis Bulanan)
        DB::statement("
            INSERT INTO tb_laporan (judul, jenis, periode, generated_by, user_id, jumlah_hadir, jumlah_terlambat, jumlah_tidak_hadir, total_potongan, tgl_generate, created_at)
            SELECT
                CONCAT('Laporan Jumlah Presensi Per Karyawan Bulanan ', r.periode),
                'Bulanan',
                r.periode,
                a.user_id,
                k.user_id,
                r.jumlah_hadir,
                r.jumlah_terlambat,
                r.jumlah_tidak_hadir,
                r.total_potongan_keterlambatan,
                r.created_at,
                r.created_at
            FROM tb_rekap_presensi_bulanan r
            LEFT JOIN tb_admin a ON a.id = r.admin_id
            JOIN tb_karyawan k ON k.id = r.karyawan_id
        ");
    }
};
```

### Phase 3 — Cleanup (Drop + Rename)

**`2026_05_25_000020_drop_old_user_tables.php`**

```php
return new class extends Migration {
    public function up(): void
    {
        // Hapus FK constraint lama dulu
        Schema::table('tb_jadwal', function (Blueprint $table) {
            $table->dropForeign(['karyawan_id']);
            $table->dropForeign(['admin_id']);
            $table->dropColumn(['karyawan_id', 'admin_id']);
        });

        Schema::table('tb_detail_pekerjaan', function (Blueprint $table) {
            $table->dropForeign(['karyawan_id']);
            $table->dropColumn('karyawan_id');
        });

        Schema::table('tb_presensi', function (Blueprint $table) {
            $table->dropForeign(['karyawan_id']);
            $table->dropColumn('karyawan_id');
        });

        Schema::table('tb_bukti_pekerjaan', function (Blueprint $table) {
            $table->dropForeign(['karyawan_id']);
            $table->dropColumn('karyawan_id');
        });

        // Drop tabel
        Schema::dropIfExists('tb_verifikasi');
        Schema::dropIfExists('tb_rekap_presensi_bulanan');
        Schema::dropIfExists('tb_karyawan');
        Schema::dropIfExists('tb_admin');
        Schema::dropIfExists('tb_supervisor');
    }
};
```

**`2026_05_25_000021_rename_jadwal_to_jadwal_pekerjaan.php`**

```php
return new class extends Migration {
    public function up(): void
    {
        Schema::rename('tb_jadwal', 'tb_jadwal_pekerjaan');
    }

    public function down(): void
    {
        Schema::rename('tb_jadwal_pekerjaan', 'tb_jadwal');
    }
};
```

**`2026_05_25_000022_rename_laporan_to_laporan_presensi.php`**

```php
return new class extends Migration {
    public function up(): void
    {
        Schema::rename('tb_laporan', 'tb_laporan_presensi');
    }
};
```

---

## 3. Verifikasi Backfill (Sanity Check SQL)

Setelah Phase 2, jalankan queries berikut untuk verifikasi data lengkap:

```sql
-- 1. Semua karyawan punya nik di tb_user
SELECT COUNT(*) as missing_nik
FROM tb_user
WHERE role = 'karyawan' AND nik IS NULL;
-- Harus = 0

-- 2. Semua presensi yang punya verifikasi V2 sudah ter-inline
SELECT COUNT(*) as missing_verifikasi
FROM tb_verifikasi v
LEFT JOIN tb_presensi p ON p.id = v.presensi_id
WHERE p.status_verifikasi = 'pending';
-- Harus = 0 (semua sudah tidak pending)

-- 3. Semua jadwal punya user_id
SELECT COUNT(*) as missing_user_id
FROM tb_jadwal
WHERE user_id IS NULL;
-- Harus = 0

-- 4. Jumlah rekap V2 = jumlah laporan tipe Bulanan rekap di V3
SELECT
    (SELECT COUNT(*) FROM tb_rekap_presensi_bulanan) as v2_count,
    (SELECT COUNT(*) FROM tb_laporan WHERE jenis = 'Bulanan' AND user_id IS NOT NULL) as v3_count;
-- Harus sama
```

---

## 4. Rollback Plan

Setiap migration WAJIB punya `down()` method yang reversible. Untuk rollback:

```bash
# Rollback satu batch
php artisan migrate:rollback

# Rollback semua V3 migration (kembali ke V2)
php artisan migrate:rollback --step=12
```

**Catatan:** Setelah Phase 3 cleanup (drop tabel), rollback tidak bisa restore data — wajib **backup DB sebelum Phase 3**.

---

## 5. Seeder V3

Seeder V2 (`DatabaseSeeder.php`) perlu di-refactor:

**V2 (sekarang):**

```php
User::factory()->create(['role' => 'admin']);
Admin::create(['user_id' => $user->id, 'nik' => '...', 'no_hp' => '...']);
```

**V3:**

```php
User::factory()->create([
    'role'   => Role::Admin,
    'nik'    => '...',
    'no_hp'  => '...',
]);
```

Data demo yang harus tetap ada:

- 2 admin (Budi, Dela)
- 1 supervisor (Andi)
- 12 karyawan (Rizky, Eko, staf1..staf10)
- ~90 hari presensi historis (sampai 2026-05-08)
- Sample tugas + bukti pekerjaan
- Sample laporan + rekap bulanan

---

## 6. Backup Sebelum Migrasi (Wajib)

```bash
# Export DB V2 full
mysqldump -u root -p sistem_kepegawaian > backup_v2_pre_migrasi_$(date +%Y%m%d).sql

# Atau via Laravel artisan (jika tersedia)
php artisan db:backup
```

Simpan backup di lokasi aman sebelum eksekusi Phase 3 (cleanup).

---

## 7. Risiko Migrasi Data

| Risiko                                                                | Mitigasi                                                                                                   |
| --------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------- |
| Data karyawan tidak punya nik unik                                    | Cek duplikat nik sebelum migrasi: `SELECT nik, COUNT(*) FROM tb_karyawan GROUP BY nik HAVING COUNT(*) > 1` |
| Verifikasi double (1 presensi punya 2 verifikasi di tb_verifikasi V2) | Ambil verifikasi terbaru: `SELECT MAX(id) FROM tb_verifikasi GROUP BY presensi_id`                         |
| Admin/Supervisor punya user_id duplikat                               | Pre-check: `SELECT user_id, COUNT(*) FROM tb_admin GROUP BY user_id HAVING COUNT(*) > 1`                   |
| FK violation saat drop tb_karyawan                                    | Pastikan semua child tabel sudah migrasi karyawan_id → user_id sebelum drop                                |

---

Selanjutnya: [07-rencana-refactor-kode.md](07-rencana-refactor-kode.md)
