<?php

namespace Database\Seeders;

use App\Models\BuktiPekerjaan;
use App\Models\DetailPekerjaan;
use App\Models\JadwalPekerjaan;
use App\Models\LaporanPresensi;
use App\Models\Presensi;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database (V3 — skema 7 tabel, user terkonsolidasi).
     *
     * Contoh akun untuk login:
     * - Admin: admin@example.com / password
     * - Supervisor: supervisor@example.com / password
     * - Karyawan: karyawan@example.com / password
     * - Karyawan: ekoaryo@example.com / password
     * - Admin Tambahan: dela@example.com / password
     */
    public function run(): void
    {
        // ========== USER DATA (konsolidasi: nik/no_hp/posisi langsung di tb_user) ==========
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'nama' => 'Budi Santoso',
                'password' => 'password',
                'role' => 'admin',
                'nik' => 'ADM-001',
                'no_hp' => '081200000001',
            ]
        );

        $supervisorUser = User::updateOrCreate(
            ['email' => 'supervisor@example.com'],
            [
                'nama' => 'Andi Gunawan',
                'password' => 'password',
                'role' => 'supervisor',
                'nik' => 'SPV-001',
                'no_hp' => '081200000002',
            ]
        );

        User::updateOrCreate(
            ['email' => 'dela@example.com'],
            [
                'nama' => 'Dela Maharani',
                'password' => 'password',
                'role' => 'admin',
                'nik' => 'ADM-002',
                'no_hp' => '081200000003',
            ]
        );

        User::updateOrCreate(
            ['email' => 'karyawan@example.com'],
            [
                'nama' => 'Rizky Pratama',
                'password' => 'password',
                'role' => 'karyawan',
                'nik' => 'KRY-001',
                'posisi' => 'Staf Administrasi',
                'no_hp' => '081234567890',
            ]
        );

        User::updateOrCreate(
            ['email' => 'ekoaryo@example.com'],
            [
                'nama' => 'Eko Aryo',
                'password' => 'password',
                'role' => 'karyawan',
                'nik' => 'KRY-002',
                'posisi' => 'Staf Operasional',
                'no_hp' => '081298765432',
            ]
        );

        // KARYAWAN DUMMY BANYAK
        $faker = \Faker\Factory::create('id_ID');
        for ($i = 1; $i <= 10; $i++) {
            User::updateOrCreate(
                ['email' => "staf{$i}@example.com"],
                [
                    'nama' => $faker->name,
                    'password' => 'password',
                    'role' => 'karyawan',
                    'nik' => 'KRY-' . str_pad($i + 2, 3, '0', STR_PAD_LEFT),
                    'posisi' => $faker->randomElement(['Staf IT', 'Pemasaran', 'Keuangan', 'Operasional', 'Layanan Pelanggan', 'Teknisi']),
                    'no_hp' => $faker->phoneNumber,
                ]
            );
        }

        // ========== JADWAL + TUGAS + PRESENSI DATA (3 BULAN TERAKHIR) ==========
        // Sesuai BAB 4: 6 hari kerja per minggu, 1 hari libur (Minggu) ditentukan admin,
        // jam kerja 08:00–16:00.
        $semuaKaryawan = User::where('role', 'karyawan')->get();
        $tanggalAcuan = \Carbon\Carbon::parse('2026-06-05'); // Jumat

        $startDate = $tanggalAcuan->copy()->subMonths(2)->startOfMonth();
        $endDate = $tanggalAcuan->copy();

        foreach ($semuaKaryawan as $kr) {
            $currentDate = $startDate->copy();
            while ($currentDate->lte($endDate)) {
                $tgl = $currentDate->copy();
                $isLibur = $tgl->isSunday(); // Minggu = hari libur

                $jadwal = JadwalPekerjaan::updateOrCreate(
                    [
                        'user_id' => $kr->id,
                        'tanggal_kerja' => $tgl->toDateString(),
                    ],
                    [
                        'dibuat_oleh' => $adminUser->id,
                        'jam_masuk' => '08:00:00',
                        'jam_pulang' => '16:00:00',
                        'hari_libur' => $isLibur,
                        'status' => 'aktif',
                    ]
                );

                if ($isLibur) {
                    $currentDate->addDay();
                    continue;
                }

                $isToday = $tgl->isToday();

                // Buat 1 detail pekerjaan default per jadwal
                $tugas = DetailPekerjaan::firstOrCreate(
                    [
                        'jadwal_id' => $jadwal->id,
                    ],
                    [
                        'user_id' => $kr->id,
                        'nama_lokasi' => 'Kantor CV Boss Muda Mandiri',
                        'alamat_lokasi' => 'Jl. Jend. Sudirman No. 45, Jakarta Pusat',
                        'latitude' => -6.2087634,
                        'longitude' => 106.8222568,
                        'keterangan_pekerjaan' => 'Pekerjaan harian sesuai SOP',
                        'status' => $isToday ? 'pending' : 'disetujui',
                    ]
                );

                if (! $isToday && ! Presensi::where('user_id', $kr->id)->where('tanggal', $tgl->toDateString())->exists()) {
                    $rand = rand(1, 100);
                    $potonganTerlambat = 0;
                    $menitTerlambat = 0;

                    if ($rand <= 80) { // 80% hadir tepat waktu
                        $jamMasukTime = '07:' . str_pad(rand(30, 59), 2, '0', STR_PAD_LEFT) . ':00';
                        $jamKeluarTime = '16:' . str_pad(rand(0, 30), 2, '0', STR_PAD_LEFT) . ':00';
                        $statusPresensi = 'hadir';
                    } elseif ($rand <= 92) { // 12% terlambat
                        $menitTerlambat = rand(11, 60);
                        $jamMasukTime = '08:' . str_pad(rand(11, 59), 2, '0', STR_PAD_LEFT) . ':00';
                        $jamKeluarTime = '16:' . str_pad(rand(0, 30), 2, '0', STR_PAD_LEFT) . ':00';
                        $statusPresensi = 'terlambat';
                        $potonganTerlambat = Presensi::hitungPotongan($menitTerlambat);
                    } elseif ($rand <= 97) { // 5% izin
                        $statusPresensi = 'izin';
                        $jamMasukTime = null;
                        $jamKeluarTime = null;
                    } else { // 3% tidak hadir (alpa)
                        $statusPresensi = 'tidak_hadir';
                        $jamMasukTime = null;
                        $jamKeluarTime = null;
                    }

                    $jamMasuk = $jamMasukTime ? $tgl->toDateString() . ' ' . $jamMasukTime : null;
                    $jamKeluar = $jamKeluarTime ? $tgl->toDateString() . ' ' . $jamKeluarTime : null;
                    $sudahHadir = in_array($statusPresensi, ['hadir', 'terlambat']);
                    // Check-in lama sudah diverifikasi supervisor; check-in 7 hari terakhir dari
                    // rentang data ($endDate) dibiarkan "pending" agar antrian Verifikasi terisi realistis.
                    // (Pakai $endDate, bukan now(), karena data seeded berada di masa lampau.)
                    $checkinTerverifikasi = $sudahHadir && $tgl->lt($endDate->copy()->subDays(7));

                    $presensi = Presensi::create([
                        'user_id' => $kr->id,
                        'jadwal_id' => $jadwal->id,
                        'tanggal' => $tgl->toDateString(),
                        'jam_masuk' => $jamMasuk,
                        'jam_keluar' => $jamKeluar,
                        'foto_masuk' => null,
                        'foto_keluar' => null,
                        'latitude_masuk' => $sudahHadir ? -6.2087634 : null,
                        'longitude_masuk' => $sudahHadir ? 106.8222568 : null,
                        'latitude_keluar' => $sudahHadir ? -6.2087634 : null,
                        'longitude_keluar' => $sudahHadir ? 106.8222568 : null,
                        'menit_terlambat' => $menitTerlambat,
                        'potongan_terlambat' => $potonganTerlambat,
                        'status_presensi' => $statusPresensi,
                        // V3: verifikasi inline. Hari yang sudah selesai → disetujui supervisor.
                        'status_verifikasi' => $checkinTerverifikasi ? 'disetujui' : 'pending',
                        'diverifikasi_oleh' => $checkinTerverifikasi ? $supervisorUser->id : null,
                        'catatan_verifikasi' => $checkinTerverifikasi ? 'Terverifikasi dengan baik' : null,
                        'tgl_verifikasi' => $checkinTerverifikasi ? $tgl->toDateString() . ' 18:00:00' : null,
                    ]);

                    // Bukti pekerjaan hanya untuk hari yang sudah selesai
                    if ($sudahHadir) {
                        BuktiPekerjaan::create([
                            'detail_pekerjaan_id' => $tugas->id,
                            'user_id' => $kr->id,
                            'foto_before' => null,
                            'foto_after' => null,
                            'keterangan' => 'Tugas selesai, area bersih.',
                            'status' => 'disetujui',
                            'uploaded_at' => $jamKeluar,
                        ]);
                    }
                }

                $currentDate->addDay();
            }
        }

        // ========== LAPORAN PRESENSI (metadata agregat, user_id NULL) ==========
        // Kumpulkan semua bulan yang sudah selesai (tidak termasuk bulan berjalan)
        $periodeSelesai = [];
        $tempBulan = $startDate->copy()->startOfMonth();
        while ($tempBulan->lt($tanggalAcuan->copy()->startOfMonth())) {
            $periodeSelesai[] = $tempBulan->format('Y-m');
            $tempBulan->addMonth();
        }

        // -- 1. Laporan Bulanan: semua bulan selesai + bulan berjalan --
        $semuaPeriodeBulanan = array_merge($periodeSelesai, [$tanggalAcuan->format('Y-m')]);

        foreach ($semuaPeriodeBulanan as $periodeLaporan) {
            $isBulanBerjalan = $periodeLaporan === $tanggalAcuan->format('Y-m');
            $tglBase = $isBulanBerjalan
                ? $tanggalAcuan->copy()->subDays(1)->format('Y-m-d')
                : $periodeLaporan . '-28';

            LaporanPresensi::updateOrCreate(
                ['judul' => "Laporan Presensi Bulanan {$periodeLaporan}", 'periode' => $periodeLaporan],
                ['jenis' => 'Bulanan', 'generated_by' => $adminUser->id, 'tgl_generate' => $tglBase . ' 17:00:00']
            );

            LaporanPresensi::updateOrCreate(
                ['judul' => "Laporan Rekap Presensi Bulanan {$periodeLaporan}", 'periode' => $periodeLaporan],
                ['jenis' => 'Bulanan', 'generated_by' => $adminUser->id, 'tgl_generate' => $tglBase . ' 18:00:00']
            );

            LaporanPresensi::updateOrCreate(
                ['judul' => "Laporan Rekap Pekerjaan Bulanan {$periodeLaporan}", 'periode' => $periodeLaporan],
                ['jenis' => 'Bulanan', 'generated_by' => $adminUser->id, 'tgl_generate' => $tglBase . ' 19:00:00']
            );
        }

        // -- 2. Laporan Mingguan: 4 minggu terakhir --
        $weekStart = $tanggalAcuan->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
        for ($w = 0; $w < 4; $w++) {
            $ws = $weekStart->copy()->subWeeks($w);
            $periodeMingguan = $ws->toDateString();

            LaporanPresensi::updateOrCreate(
                ['judul' => "Laporan Presensi Mingguan {$periodeMingguan}", 'periode' => $periodeMingguan],
                ['jenis' => 'Mingguan', 'generated_by' => $adminUser->id, 'tgl_generate' => $ws->copy()->addDays(6)->format('Y-m-d') . ' 17:00:00']
            );
        }

        // -- 3. Laporan Harian: 5 hari kerja terakhir (skip Minggu) --
        $hariCheck = $tanggalAcuan->copy();
        $countHari = 0;
        while ($countHari < 5) {
            if (! $hariCheck->isSunday()) {
                $periodeHarian = $hariCheck->toDateString();

                LaporanPresensi::updateOrCreate(
                    ['judul' => "Laporan Presensi Harian {$periodeHarian}", 'periode' => $periodeHarian],
                    ['jenis' => 'Harian', 'generated_by' => $adminUser->id, 'tgl_generate' => $periodeHarian . ' 17:00:00']
                );
                $countHari++;
            }
            $hariCheck->subDay();
        }

        // ========== SETTING DATA ==========
        $settings = [
            ['key' => 'nama_perusahaan',    'value' => 'CV Boss Muda Mandiri',                  'group' => 'identitas',  'label' => 'Nama Perusahaan',                       'type' => 'text'],
            ['key' => 'alamat_perusahaan',  'value' => 'Jl. Jend. Sudirman No. 45, Jakarta Pusat', 'group' => 'identitas', 'label' => 'Alamat Perusahaan',                  'type' => 'textarea'],
            ['key' => 'wa_admin',           'value' => '081200000002',                          'group' => 'kontak',     'label' => 'Nomor WhatsApp Admin (Konfirmasi Tugas)', 'type' => 'text'],
            ['key' => 'kantor_lat',         'value' => '-6.2087634',                            'group' => 'lokasi',     'label' => 'Latitude Kantor Pusat',                 'type' => 'text'],
            ['key' => 'kantor_lng',         'value' => '106.8222568',                           'group' => 'lokasi',     'label' => 'Longitude Kantor Pusat',                'type' => 'text'],
            ['key' => 'kantor_radius',      'value' => '500',                                   'group' => 'lokasi',     'label' => 'Radius Presensi (Meter)',               'type' => 'number'],
            ['key' => 'potongan_terlambat', 'value' => '10000',                                 'group' => 'penggajian', 'label' => 'Potongan per 10 Menit Terlambat (Rp)',  'type' => 'number'],
            ['key' => 'toleransi_menit',    'value' => '10',                                    'group' => 'kehadiran',  'label' => 'Toleransi Terlambat (Menit)',           'type' => 'number'],
        ];

        foreach ($settings as $s) {
            Setting::updateOrCreate(['key' => $s['key']], $s);
        }

        Setting::clearCache();
    }
}
