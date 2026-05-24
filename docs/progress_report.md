# Progress Report — V2.3 (Perampingan RBAC Supervisor, Rename Rekap, Foto Masuk Conditional, Potongan Tanpa Cap)

**Tanggal:** 24 Mei 2026  
**Branch:** `main-dev-jogja`  
**Status:** ✅ Stabil

---

## 🔥 Perubahan V2.3

### 1. Hak Akses Supervisor Dirampingkan

Sebelumnya Supervisor melihat hampir semua modul operasional (view only). Sekarang Supervisor **hanya akses 4 modul**:

| Modul                                                        | Akses Supervisor V2.3              |
| ------------------------------------------------------------ | ---------------------------------- |
| Presensi                                                     | 👁️ View Only                       |
| Laporan                                                      | ✅ Generate + Export               |
| Verifikasi                                                   | ✅ Full CRUD                       |
| Laporan Jumlah Presensi Per Karyawan                         | 👁️ View Only (tabel) — revisi V2.3 |
| Karyawan, Jadwal, Detail Pekerjaan, Bukti Pekerjaan, Setting | ❌ Disembunyikan                   |

**File ubah:** `canViewAny()` di `KaryawanResource`, `JadwalResource`, `DetailPekerjaanResource`, `BuktiPekerjaanResource`, `SettingResource` → admin-only. `RekapPresensiBulananResource` → admin + supervisor (view), action Create/Edit/Delete tetap admin-only via `->visible(fn () => Auth::user()?->role === 'admin')` di table actions.

### 2. Jadwal & Detail Pekerjaan: Default Filter Hari Ini

Agar tabel jadwal admin tidak menumpuk data lama, ditambah filter default `today()`:

- `JadwalsTable.php` — Filter `tanggal_kerja = today()` (default, dapat di-clear).
- `DetailPekerjaansTable.php` — Filter `jadwal.tanggal_kerja = today()` via `whereHas`.

### 3. Tabel Laporan: Default Sort Tanggal Generate Terbaru

`LaporanResource` sudah memiliki `defaultSort('tgl_generate', 'desc')` — laporan terbaru tampil paling atas (sudah ada sebelumnya, dikonfirmasi V2.3).

### 4. Rekap Presensi Bulanan: Restrict Jenis ke Bulanan

Saat tipe `rekap_presensi_bulanan` dipilih di form Laporan:

- Opsi jenis hanya menampilkan **Bulanan** (Harian/Mingguan/Tahunan disembunyikan).
- Auto-set ke `Bulanan` saat tipe dipilih.

### 5. Rename: Rekap Presensi Bulanan → Laporan Jumlah Presensi Per Karyawan

| Lokasi                                           | Status |
| ------------------------------------------------ | ------ |
| `RekapPresensiBulananResource::$navigationLabel` | ✅     |
| `LaporanResource` form options (tipe_laporan)    | ✅     |
| `generateJudul()`                                | ✅     |
| `resolveTipeLabel()` + badge color match         | ✅     |
| Filter tabel label                               | ✅     |
| Filter query (`%jumlah presensi%`)               | ✅     |
| `afterStateHydrated` detection                   | ✅     |
| PDF template heading                             | ✅     |
| PDF filename                                     | ✅     |

> Nama tabel database `tb_rekap_presensi_bulanan` tetap (tidak di-rename).

### 6. PDF Laporan: Tambah Spesifikasi Jenis

`LaporanExportController` (3 metode: `exportPdf`, `exportPresensiPdf`, `exportPekerjaanPdf`) menerima dan meneruskan parameter `jenis` ke blade. Header PDF sekarang menampilkan:

- `laporan-pdf.blade.php`: `Jenis Laporan: {{ $jenis }}`
- `laporan-presensi-pdf.blade.php`: subtitle `Laporan Presensi {{ $jenis }}`
- `laporan-pekerjaan-pdf.blade.php`: subtitle `Rekap Pekerjaan {{ $jenis }}`

`resolveExportRoute` di `LaporanResource` pass `jenis` ke URL: `?periode=...&jenis=...`.

### 7. Foto Masuk: Conditional Visibility (Setelah Check-out)

Foto masuk hanya tampil setelah karyawan check-out (`jam_keluar` terisi):

| Lokasi                                        | Implementasi                                                                             |
| --------------------------------------------- | ---------------------------------------------------------------------------------------- |
| `PresensiResource` infolist (admin)           | `ImageEntry::make('foto_masuk')->visible(fn ($record) => $record?->jam_keluar !== null)` |
| `presensi-masuk.blade.php` (karyawan)         | `@if ($presensiHariIni->jam_keluar && $presensiHariIni->foto_masuk)`                     |
| `presensi-pulang.blade.php` form check-out    | Blok preview foto_masuk **dihapus**                                                      |
| `presensi-pulang.blade.php` setelah check-out | Tetap tampilkan foto_masuk + foto_keluar                                                 |

### 8. Potongan Keterlambatan: Hapus Flat Cap

`Presensi::hitungPotongan()` — flat cap Rp 20.000 untuk >30 menit **dihapus**. Rumus murni Rp 10.000 / 10 menit tanpa batas atas.

---

## File Dimodifikasi (V2.3)

| File                                                                            | Perubahan                                                                                       |
| ------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------- |
| `app/Models/Presensi.php`                                                       | Hapus flat cap `if ($menitTerlambat > 30) return 20000;`                                        |
| `app/Filament/Resources/Karyawans/KaryawanResource.php`                         | `canViewAny()` → admin only                                                                     |
| `app/Filament/Resources/Jadwals/JadwalResource.php`                             | `canViewAny()` → admin only                                                                     |
| `app/Filament/Resources/Jadwals/Tables/JadwalsTable.php`                        | Tambah Filter tanggal default today()                                                           |
| `app/Filament/Resources/DetailPekerjaans/DetailPekerjaanResource.php`           | `canViewAny()` → admin only                                                                     |
| `app/Filament/Resources/DetailPekerjaans/Tables/DetailPekerjaansTable.php`      | Tambah Filter tanggal jadwal default today()                                                    |
| `app/Filament/Resources/BuktiPekerjaans/BuktiPekerjaanResource.php`             | `canViewAny()` → admin only                                                                     |
| `app/Filament/Resources/RekapPresensiBulanans/RekapPresensiBulananResource.php` | `canViewAny()` → admin only, navigationLabel diganti                                            |
| `app/Filament/Resources/SettingResource.php`                                    | `canViewAny()` → admin only                                                                     |
| `app/Filament/Resources/Presensis/PresensiResource.php`                         | foto_masuk visible saat jam_keluar terisi, update helper text                                   |
| `app/Filament/Resources/Laporans/LaporanResource.php`                           | jenis restriction utk rekap, rename, fix afterStateHydrated, fix filter, fix resolveExportRoute |
| `app/Http/Controllers/LaporanExportController.php`                              | Pass `$jenis` ke 3 PDF view, validasi param                                                     |
| `resources/views/exports/laporan-pdf.blade.php`                                 | Tambah jenis di header, rename heading                                                          |
| `resources/views/exports/laporan-presensi-pdf.blade.php`                        | Subtitle tampilkan jenis                                                                        |
| `resources/views/exports/laporan-pekerjaan-pdf.blade.php`                       | Subtitle tampilkan jenis                                                                        |
| `resources/views/karyawan/presensi-masuk.blade.php`                             | foto_masuk hanya tampil setelah check-out                                                       |
| `resources/views/karyawan/presensi-pulang.blade.php`                            | Hapus preview foto_masuk di form check-out                                                      |

---

## Catatan Teknis V2.3

### Rumus Keterlambatan (Final V2.3)

```
0 – 10 menit  → Rp 0      (toleransi)
11 – 20 menit → Rp 10.000  (1 blok)
21 – 30 menit → Rp 20.000  (2 blok)
31 – 40 menit → Rp 30.000  (3 blok)
41 – 50 menit → Rp 40.000  (4 blok)
... dan seterusnya tanpa batas atas (cap dihapus)
```

### Filter Default Today di Filament v5

```php
Filter::make('tanggal_kerja')
    ->form([DatePicker::make('tanggal')->default(today())])
    ->query(fn ($q, $data) => $q->when($data['tanggal'], fn ($q, $tgl) => $q->whereDate('tanggal_kerja', $tgl)))
    ->default(['tanggal' => today()->toDateString()])  // <-- penting agar default aktif sejak page load
    ->indicateUsing(fn ($data) => $data['tanggal'] ? 'Tanggal: ' . Carbon::parse($data['tanggal'])->format('d M Y') : null);
```

### URL Export Sekarang Mengandung `jenis`

```php
$params = ['periode' => $record->periode, 'jenis' => $record->jenis];
return route("laporan.export.{$format}", $params);
// hasilkan: /laporan/export/pdf?periode=2026-05&jenis=Bulanan
```

---

# Progress Report — V2.1 (Bug Fix & Polish) [Histori Lama]

**Tanggal:** 15 Mei 2026  
**Branch:** `main-dev`  
**Status:** ✅ Stabil

---

## Ringkasan Sesi V2.1

Sesi ini fokus pada **perbaikan bug, peningkatan UX, dan penambahan notifikasi** di atas fondasi V2 yang sudah berjalan. Tidak ada perubahan skema database.

---

## ✅ Bug Fix

| #   | Bug                                   | File                                                                                                               | Keterangan                                                                                           |
| --- | ------------------------------------- | ------------------------------------------------------------------------------------------------------------------ | ---------------------------------------------------------------------------------------------------- |
| 1   | **Rumus keterlambatan salah**         | `Models/Presensi.php`                                                                                              | `hitungPotongan()`: toleransi tidak dikurangi sebelum `ceil`. Fix: `ceil(($menit - toleransi) / 10)` |
| 2   | **Foto check-in tidak terkirim**      | `views/karyawan/presensi-masuk.blade.php`                                                                          | Canvas kamera dikompresi: max 1280px, quality 0.75 JPEG                                              |
| 3   | **Notifikasi double "sudah absen"**   | `Http/Controllers/KaryawanMobileController.php`                                                                    | Redirect langsung ke `presensi-pulang` jika sudah check-in; ke `beranda` jika sudah check-out        |
| 4   | **Format Rp tidak real-time di form** | `Resources/Karyawans/Schemas/KaryawanForm.php`, `Resources/Akuns/Schemas/AkunForm.php`                             | Ganti `formatStateUsing` + live ke Alpine `$money` mask via `RawJs`                                  |
| 5   | **Format Rp disabled field**          | `Resources/Presensis/PresensiResource.php`, `Resources/RekapPresensiBulanans/Schemas/RekapPresensiBulananForm.php` | Hapus `->numeric()`, tambah `formatStateUsing` + `dehydrateStateUsing`                               |

---

## ✅ Peningkatan Fitur Admin

| #   | Fitur                              | File                                                                 | Keterangan                                                                                                              |
| --- | ---------------------------------- | -------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------- |
| 1   | **Widget dashboard dirapikan**     | `Widgets/AttendanceRealtimeStatsWidget.php`                          | Hapus "rekap bulan lalu", kurangi stat card, perbaiki query cross-DB (`whereYear`/`whereMonth` — tidak lagi MySQL-only) |
| 2   | **Urutan widget konsisten**        | Semua widget                                                         | Tambah `protected static ?int $sort` (1–4) agar urutan tetap                                                            |
| 3   | **Pencarian karyawan by nama**     | `Resources/Jadwals/Schemas/JadwalForm.php`                           | Select `karyawan_id` pakai `getSearchResultsUsing` + `orWhereHas('user', nama)`                                         |
| 4   | **Buat jadwal range tanggal**      | `Resources/Jadwals/Schemas/JadwalForm.php`, `Pages/CreateJadwal.php` | Tambah field `tanggal_akhir` (visible saat create); `handleRecordCreation()` loop & skip duplikat                       |
| 5   | **Global search karyawan by nama** | `Resources/Karyawans/KaryawanResource.php`                           | Tambah `getGloballySearchableAttributes()` dengan `user.nama`                                                           |
| 6   | **Upload foto maks 10 MB**         | `Http/Controllers/PresensiController.php`                            | Naik dari 5 MB                                                                                                          |

---

## ✅ Notifikasi Login & Logout

| #   | Fitur                                       | File                                                                 |
| --- | ------------------------------------------- | -------------------------------------------------------------------- | -------------------------------------------------------------- |
| 1   | **Notifikasi selamat datang setelah login** | `Http/Responses/FilamentLoginResponse.php` → flash `login_success`   |
| 2   | **Custom Dashboard membaca flash**          | `Filament/Pages/Dashboard.php` → `mount()` → Filament Notification   |
| 3   | **Notifikasi berhasil logout**              | `Http/Responses/FilamentLogoutResponse.php` → flash `logout_success` |
| 4   | **Custom Login membaca flash logout**       | `Filament/Pages/Auth/Login.php` → `mount()` → Filament Notification  |
| 5   | **Login page custom**                       | `Filament/Pages/Auth/Login.php`                                      | Heading = nama perusahaan dari `Setting`, label form dirapikan |

| 7 | **Tombol ubah & hapus muncul untuk supervisor** di Laporan & Rekap Presensi Bulanan | `Resources/Laporans/LaporanResource.php`, `Resources/RekapPresensiBulanans/Tables/RekapPresensiBulanansTable.php` | Tambah `->visible(fn () => Auth::user()?->role === 'admin')` pada `EditAction`, `DeleteAction`, dan `DeleteBulkAction` |

---

## File Baru (V2.1)

| File                                            | Peran                                                      |
| ----------------------------------------------- | ---------------------------------------------------------- |
| `app/Filament/Pages/Dashboard.php`              | Custom Dashboard — baca session flash login                |
| `app/Filament/Pages/Auth/Login.php`             | Custom Login — flash logout + nama perusahaan dari Setting |
| `app/Http/Responses/FilamentLogoutResponse.php` | Logout redirect + flash session                            |

---

## File Dimodifikasi (V2.1)

| File                                                                                | Perubahan                                               |
| ----------------------------------------------------------------------------------- | ------------------------------------------------------- |
| `app/Http/Responses/FilamentLoginResponse.php`                                      | Tambah role label + flash `login_success`               |
| `app/Providers/AppServiceProvider.php`                                              | Bind `LogoutResponse` contract                          |
| `app/Providers/Filament/AdminPanelProvider.php`                                     | `->login(Login::class)` custom, import Dashboard custom |
| `app/Providers/Filament/SupervisorPanelProvider.php`                                | Sama dengan admin                                       |
| `routes/web.php`                                                                    | Route `/login` pakai `App\Filament\Pages\Auth\Login`    |
| `app/Models/Presensi.php`                                                           | Fix `hitungPotongan()`                                  |
| `app/Http/Controllers/PresensiController.php`                                       | Max foto 10 MB                                          |
| `app/Http/Controllers/KaryawanMobileController.php`                                 | Redirect if already checked-in                          |
| `app/Filament/Widgets/AttendanceRealtimeStatsWidget.php`                            | Cross-DB queries, hapus potongan bulan lalu, sort=1     |
| `app/Filament/Widgets/LaporanEvaluasiChartWidget.php`                               | Hapus dead code, sort=4                                 |
| `app/Filament/Widgets/RekapKehadiranHariIniWidget.php`                              | sort=2                                                  |
| `app/Filament/Widgets/ProgressPekerjaanHariIniWidget.php`                           | sort=3                                                  |
| `app/Filament/Resources/Karyawans/Schemas/KaryawanForm.php`                         | Alpine `$money` mask untuk gaji_pokok                   |
| `app/Filament/Resources/Akuns/Schemas/AkunForm.php`                                 | Sama                                                    |
| `app/Filament/Resources/Jadwals/Schemas/JadwalForm.php`                             | Search by nama, field tanggal_akhir                     |
| `app/Filament/Resources/Jadwals/Pages/CreateJadwal.php`                             | `handleRecordCreation()` bulk date range                |
| `app/Filament/Resources/Karyawans/KaryawanResource.php`                             | Global search by `user.nama`                            |
| `app/Filament/Resources/Presensis/PresensiResource.php`                             | Format Rp disable field                                 |
| `app/Filament/Resources/RekapPresensiBulanans/Schemas/RekapPresensiBulananForm.php` | Format Rp 3 field money                                 |
| `resources/views/karyawan/presensi-masuk.blade.php`                                 | Kompresi kamera 1280px / q0.75                          |

---

## Catatan Teknis Penting

### Alpine `$money` mask (currency field yang bisa diedit)

```php
->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
->dehydrateStateUsing(fn ($state) => (int) str_replace('.', '', $state ?? '0'))
```

Gunakan pola ini untuk TextInput numerik bertitik ribuan yang **aktif (tidak disabled)**.

### Format Rp untuk field disabled

```php
->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state, 0, ',', '.') : '0')
->dehydrateStateUsing(fn ($state) => (float) str_replace('.', '', $state ?? '0'))
// Jangan pakai ->numeric() karena akan override format
```

### Rumus keterlambatan (V2.1, **superseded** oleh V2.3 — lihat section atas)

```
≤ toleransi (10 mnt) → Rp 0
> toleransi s.d. 20 mnt → Rp 10.000
> 20 mnt s.d. 30 mnt  → Rp 20.000
> 30 mnt              → Rp 20.000 (flat cap)   ← DIHAPUS di V2.3
```

> ⚠️ **V2.3:** flat cap Rp 20.000 untuk >30 menit dihapus. Rumus final: Rp 10.000 per 10 menit tanpa batas atas (lihat section V2.3 di atas).
