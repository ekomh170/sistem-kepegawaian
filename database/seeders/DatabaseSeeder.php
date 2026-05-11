<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\DetailPekerjaan;
use App\Models\Jadwal;
use App\Models\Karyawan;
use App\Models\Laporan;
use App\Models\Presensi;
use App\Models\Supervisor;
use App\Models\User;
use App\Models\Verifikasi;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
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
        // ========== USER DATA ==========
        // Akun Admin
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'nama' => 'Budi Santoso',
                'password' => 'password',
                'role' => 'admin',
            ]
        );

        // Akun Supervisor (Direktur Utama - CV Boss Muda Mandiri)
        $supervisorUser = User::updateOrCreate(
            ['email' => 'supervisor@example.com'],
            [
                'nama' => 'Andi Gunawan',
                'password' => 'password',
                'role' => 'supervisor',
            ]
        );

        // Akun Karyawan
        $karyawanUser = User::updateOrCreate(
            ['email' => 'karyawan@example.com'],
            [
                'nama' => 'Rizky Pratama',
                'password' => 'password',
                'role' => 'karyawan',
            ]
        );

        // Akun Karyawan Ekoaryo
        $ekoaryoUser = User::updateOrCreate(
            ['email' => 'ekoaryo@example.com'],
            [
                'nama' => 'Eko Aryo',
                'password' => 'password',
                'role' => 'karyawan',
            ]
        );

        // Akun Contoh Lama
        User::updateOrCreate([
            'email' => 'dela@example.com',
        ], [
            'nama' => 'Dela Maharani',
            'password' => 'password',
            'role' => 'admin',
        ]);

        // ========== ADMIN DATA ==========
        $admin = Admin::updateOrCreate(
            ['user_id' => $adminUser->id],
            [
                'nik' => 'ADM-001',
                'no_hp' => '081200000001',
            ]
        );

        // ========== SUPERVISOR DATA ==========
        $supervisor = Supervisor::updateOrCreate(
            ['user_id' => $supervisorUser->id],
            [
                'nik' => 'SPV-001',
                'no_hp' => '081200000002',
            ]
        );

        // ========== KARYAWAN DATA ==========
        $karyawan = Karyawan::updateOrCreate(
            ['user_id' => $karyawanUser->id],
            [
                'nik' => 'KRY-001',
                'posisi_karyawan' => 'Staf Administrasi',
                'tgl_masuk' => '2025-01-15',
                'status_kontrak' => 'tetap',
                'no_hp' => '081234567890',
                'bidang_tugas' => 'Administrasi dan Pelaporan',
            ]
        );

        Karyawan::updateOrCreate(
            ['user_id' => $ekoaryoUser->id],
            [
                'nik' => 'KRY-002',
                'posisi_karyawan' => 'Staf Operasional',
                'tgl_masuk' => '2025-02-10',
                'status_kontrak' => 'kontrak',
                'no_hp' => '081298765432',
                'bidang_tugas' => 'Operasional Lapangan',
            ]
        );

        // KARYAWAN DUMMY BANYAK
        $faker = \Faker\Factory::create('id_ID');
        for ($i = 1; $i <= 10; $i++) {
            $dummyUser = User::firstOrCreate(
                ['email' => "staf{$i}@example.com"],
                [
                    'nama' => $faker->name,
                    'password' => 'password',
                    'role' => 'karyawan',
                ]
            );

            Karyawan::firstOrCreate(
                ['user_id' => $dummyUser->id],
                [
                    'nik' => 'KRY-' . str_pad($i + 2, 3, '0', STR_PAD_LEFT),
                    'posisi_karyawan' => $faker->randomElement(['Staf IT', 'Pemasaran', 'Keuangan', 'Operasional', 'Layanan Pelanggan', 'Teknisi']),
                    'tgl_masuk' => $faker->dateTimeBetween('-2 years', '-1 month')->format('Y-m-d'),
                    'status_kontrak' => $faker->randomElement(['tetap', 'kontrak', 'freelance']),
                    'no_hp' => $faker->phoneNumber,
                    'bidang_tugas' => $faker->randomElement(['Penyusunan Laporan', 'Pengecekan Stok', 'Pelayanan Klien', 'Maintenance Server', 'Audit Internal', 'Pengelolaan Dokumen']),
                ]
            );
        }

        // ========== JADWAL + TUGAS + PRESENSI DATA (3 BULAN TERAKHIR) ==========
        // Sesuai BAB 4: 6 hari kerja per minggu, 1 hari libur (Minggu) ditentukan admin,
        // jam kerja 08:00–16:00.
        $semuaKaryawan = Karyawan::all();
        $tanggalAcuan = \Carbon\Carbon::parse('2026-05-11');

        // Generate jadwal & presensi sampai 11 Mei 2026
        $startDate = $tanggalAcuan->copy()->subMonths(2)->startOfMonth();
        $endDate = $tanggalAcuan->copy();

        foreach ($semuaKaryawan as $kr) {
            // Set gaji pokok random per karyawan jika belum ada
            if (!$kr->gaji_pokok || $kr->gaji_pokok == 0) {
                $kr->update([
                    'gaji_pokok' => 2827593,
                ]);
            }

            // Generate jadwal harian: Sabtu = 6 hari kerja menurut praktik banyak UMKM, Minggu libur.
            $currentDate = $startDate->copy();
            while ($currentDate->lte($endDate)) {
                $tgl = $currentDate->copy();
                $isLibur = $tgl->isSunday(); // Minggu = hari libur

                $jadwal = Jadwal::updateOrCreate(
                    [
                        'karyawan_id' => $kr->id,
                        'tanggal_kerja' => $tgl->toDateString(),
                    ],
                    [
                        'admin_id' => $admin->id,
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

                // Buat 1 detail pekerjaan default per jadwal
                $tugas = DetailPekerjaan::firstOrCreate(
                    [
                        'jadwal_id' => $jadwal->id,
                    ],
                    [
                        'karyawan_id' => $kr->id,
                        'nama_lokasi' => 'Kantor CV Boss Muda Mandiri',
                        'alamat_lokasi' => 'Jl. Jend. Sudirman No. 45, Jakarta Pusat',
                        'latitude' => -6.2087634,
                        'longitude' => 106.8222568,
                        'keterangan_pekerjaan' => 'Pekerjaan harian sesuai SOP',
                        'status' => 'disetujui',
                    ]
                );

                // Buat presensi jika belum ada (skip hari ini agar karyawan bisa test check-in asli)
                $isToday = $tgl->isToday();
                if (!$isToday && !Presensi::where('karyawan_id', $kr->id)->where('tanggal', $tgl->toDateString())->exists()) {
                    $rand = rand(1, 100);
                    $potonganTerlambat = 0;
                    $menitTerlambat = 0;

                    if ($rand <= 80) { // 80% hadir tepat waktu
                        $jamMasukTime = '07:' . str_pad(rand(30, 59), 2, '0', STR_PAD_LEFT) . ':00';
                        // Hari ini: masih di kantor, belum pulang
                        $jamKeluarTime = $isToday ? null : '16:' . str_pad(rand(0, 30), 2, '0', STR_PAD_LEFT) . ':00';
                        $statusPresensi = 'hadir';
                    } elseif ($rand <= 92) { // 12% terlambat
                        $menitTerlambat = rand(11, 60);
                        $jamMasukTime = '08:' . str_pad(rand(11, 59), 2, '0', STR_PAD_LEFT) . ':00';
                        // Hari ini: masih di kantor, belum pulang
                        $jamKeluarTime = $isToday ? null : '16:' . str_pad(rand(0, 30), 2, '0', STR_PAD_LEFT) . ':00';
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

                    $presensi = Presensi::create([
                        'karyawan_id' => $kr->id,
                        'jadwal_id' => $jadwal->id,
                        'tanggal' => $tgl->toDateString(),
                        'jam_masuk' => $jamMasuk,
                        'jam_keluar' => $jamKeluar,
                        'foto_masuk' => null,
                        'foto_keluar' => null,
                        'latitude_masuk' => $sudahHadir ? -6.2087634 : null,
                        'longitude_masuk' => $sudahHadir ? 106.8222568 : null,
                        'latitude_keluar' => ($sudahHadir && !$isToday) ? -6.2087634 : null,
                        'longitude_keluar' => ($sudahHadir && !$isToday) ? 106.8222568 : null,
                        'menit_terlambat' => $menitTerlambat,
                        'potongan_terlambat' => $potonganTerlambat,
                        'status_presensi' => $statusPresensi,
                        // Hari ini masih pending (belum pulang/diverifikasi)
                        'status_valid' => ($sudahHadir && !$isToday) ? 'valid' : 'pending',
                    ]);

                    // Bukti pekerjaan dan verifikasi hanya untuk hari sebelumnya yang sudah selesai
                    if ($sudahHadir && !$isToday) {
                        \App\Models\BuktiPekerjaan::create([
                            'detail_pekerjaan_id' => $tugas->id,
                            'karyawan_id' => $kr->id,
                            'foto_before' => null,
                            'foto_after' => null,
                            'keterangan' => 'Tugas selesai, area bersih.',
                            'status' => 'disetujui',
                            'uploaded_at' => $jamKeluar,
                        ]);

                        Verifikasi::create([
                            'presensi_id' => $presensi->id,
                            'supervisor_id' => $supervisor->id,
                            'status' => 'disetujui',
                            'catatan' => 'Terverifikasi dengan baik',
                            'tgl_verifikasi' => $tgl->toDateString() . ' 18:00:00',
                        ]);
                    }
                }

                $currentDate->addDay();
            }
        }

        // ========== AUTO-GENERATE REKAP PRESENSI BULANAN PERIODE MARET SAJA ==========
        $periodeLaporan = $tanggalAcuan->copy()->subMonths(2)->format('Y-m');

        foreach ($semuaKaryawan as $kr) {
            $presensis = Presensi::where('karyawan_id', $kr->id)
                ->whereRaw("DATE_FORMAT(tanggal, '%Y-%m') = ?", [$periodeLaporan])
                ->get();

            $jumlahHadir = $presensis->whereIn('status_presensi', ['hadir', 'terlambat'])->count();
            $jumlahTidakHadir = $presensis->where('status_presensi', 'tidak_hadir')->count();
            $jumlahTerlambat = $presensis->where('status_presensi', 'terlambat')->count();
            $totalPotongan = (float) $presensis->where('status_presensi', 'terlambat')->sum('potongan_terlambat');
            $gajiPokok = (float) ($kr->gaji_pokok ?? 5000000);
            $gajiBersih = max(0, $gajiPokok - $totalPotongan);

            \App\Models\RekapPresensiBulanan::updateOrCreate(
                [
                    'karyawan_id' => $kr->id,
                    'periode' => $periodeLaporan,
                ],
                [
                    'admin_id' => $admin->id,
                    'jumlah_hadir' => $jumlahHadir,
                    'jumlah_tidak_hadir' => $jumlahTidakHadir,
                    'jumlah_terlambat' => $jumlahTerlambat,
                    'total_potongan_keterlambatan' => $totalPotongan,
                    'gaji_pokok' => $gajiPokok,
                    'gaji_bersih' => $gajiBersih,
                    'catatan' => "Rekap presensi bulan {$periodeLaporan}",
                    'status' => 'final',
                ]
            );
        }

        // ========== LAPORAN DATA (AUTO-GENERATE PERIODE MARET SAJA) ==========
        Laporan::updateOrCreate(
            [
                'judul' => "Laporan Presensi Bulanan {$periodeLaporan}",
                'periode' => $periodeLaporan,
            ],
            [
                'jenis' => 'Bulanan',
                'filter' => json_encode(['karyawan_id' => 'all']),
                'file_path' => "laporan/{$periodeLaporan}-presensi.pdf",
                'generated_by' => $adminUser->id,
                'tgl_generate' => \Carbon\Carbon::parse($periodeLaporan . '-28 18:00:00'),
            ]
        );

        Laporan::updateOrCreate(
            [
                'judul' => "Laporan Rekap Presensi Bulanan {$periodeLaporan}",
                'periode' => $periodeLaporan,
            ],
            [
                'jenis' => 'Bulanan',
                'filter' => json_encode(['karyawan_id' => 'all']),
                'file_path' => "laporan/{$periodeLaporan}-rekap-presensi-bulanan.pdf",
                'generated_by' => $adminUser->id,
                'tgl_generate' => \Carbon\Carbon::parse($periodeLaporan . '-28 19:00:00'),
            ]
        );

        // ========== SETTING DATA ==========
        $settings = [
            [
                'key' => 'nama_perusahaan',
                'value' => 'CV Boss Muda Mandiri',
                'group' => 'identitas',
                'label' => 'Nama Perusahaan',
                'type' => 'text'
            ],
            [
                'key' => 'alamat_perusahaan',
                'value' => 'Jl. Jend. Sudirman No. 45, Jakarta Pusat',
                'group' => 'identitas',
                'label' => 'Alamat Perusahaan',
                'type' => 'textarea'
            ],
            [
                'key' => 'kantor_lat',
                'value' => '-6.2087634',
                'group' => 'lokasi',
                'label' => 'Latitude Kantor Pusat',
                'type' => 'text'
            ],
            [
                'key' => 'kantor_lng',
                'value' => '106.8222568',
                'group' => 'lokasi',
                'label' => 'Longitude Kantor Pusat',
                'type' => 'text'
            ],
            [
                'key' => 'kantor_radius',
                'value' => '500',
                'group' => 'lokasi',
                'label' => 'Radius Presensi (Meter)',
                'type' => 'number'
            ],
            [
                'key' => 'potongan_terlambat',
                'value' => '10000',
                'group' => 'penggajian',
                'label' => 'Potongan per 10 Menit Terlambat (Rp)',
                'type' => 'number'
            ],
            [
                'key' => 'toleransi_menit',
                'value' => '10',
                'group' => 'kehadiran',
                'label' => 'Toleransi Terlambat (Menit)',
                'type' => 'number'
            ],
        ];

        foreach ($settings as $s) {
            \App\Models\Setting::updateOrCreate(['key' => $s['key']], $s);
        }

        \App\Models\Setting::clearCache();
    }
}
