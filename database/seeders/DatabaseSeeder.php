<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\DetailPekerjaan;
use App\Models\Karyawan;
use App\Models\Laporan;
use App\Models\Notifikasi;
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
                'nip' => 'ADM-001',
                'divisi' => 'Sumber Daya Manusia',
                'level_akses' => 'penuh',
            ]
        );

        // ========== SUPERVISOR DATA ==========
        $supervisor = Supervisor::updateOrCreate(
            ['user_id' => $supervisorUser->id],
            [
                'jabatan' => 'Direktur Utama',
                'level_akses' => 'menengah',
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

        // ========== DETAIL PEKERJAAN & PRESENSI DATA (3 BULAN TERAKHIR) ==========
        $semuaKaryawan = Karyawan::all();
        $tanggalAcuan = \Carbon\Carbon::parse('2026-05-08');

        // Generate presensi 3 bulan terakhir sampai 8 Mei 2026
        $startDate = $tanggalAcuan->copy()->subMonths(2)->startOfMonth(); // Awal bulan 2 bulan lalu
        $endDate = $tanggalAcuan->copy(); // 8 Mei 2026

        foreach ($semuaKaryawan as $kr) {
            // Set gaji pokok random per karyawan jika belum ada
            if (!$kr->gaji_pokok || $kr->gaji_pokok == 0) {
                $kr->update([
                    'gaji_pokok' => fake()->randomElement([4500000, 5000000, 5500000, 6000000, 6500000, 7000000]),
                ]);
            }

            // Generate presensi per hari kerja selama periode seed
            $currentDate = $startDate->copy();
            while ($currentDate->lte($endDate)) {
                if ($currentDate->isWeekend()) {
                    $currentDate->addDay();
                    continue;
                }

                $tgl = $currentDate->copy();

                // Buat jadwal kerja
                $jadwal = DetailPekerjaan::updateOrCreate(
                    [
                        'karyawan_id' => $kr->id,
                        'tanggal' => $tgl->toDateString(),
                    ],
                    [
                        'jam_masuk' => '08:00:00',
                        'jam_pulang' => '17:00:00',
                        'nama_lokasi' => 'Kantor CV Boss Muda Mandiri',
                        'alamat_lokasi' => 'Jl. Jend. Sudirman No. 45, Jakarta Pusat',
                        'latitude' => -6.2087634,
                        'longitude' => 106.8222568,
                        'radius_meter' => 500,
                        'keterangan_pekerjaan' => 'Pekerjaan harian sesuai SOP',
                    ]
                );

                // Buat presensi jika belum ada
                if (!Presensi::where('karyawan_id', $kr->id)->where('tgl_presensi', $tgl->toDateString())->exists()) {
                    $rand = rand(1, 100);
                    $potongan = 0;

                    if ($rand <= 80) { // 80% hadir tepat waktu
                        $jamMasukTime = '07:' . str_pad(rand(30, 59), 2, '0', STR_PAD_LEFT) . ':00';
                        $jamKeluarTime = '17:' . str_pad(rand(0, 30), 2, '0', STR_PAD_LEFT) . ':00';
                        $status = 'hadir';
                    } elseif ($rand <= 92) { // 12% terlambat
                        $menitTerlambat = rand(5, 60);
                        $jamMasukTime = '08:' . str_pad(rand(11, 59), 2, '0', STR_PAD_LEFT) . ':00';
                        $jamKeluarTime = '17:' . str_pad(rand(0, 30), 2, '0', STR_PAD_LEFT) . ':00';
                        $status = 'terlambat';
                        // Hitung potongan sesuai logika: per 10 menit Rp10.000
                        $potongan = ceil($menitTerlambat / 10) * 10000;
                    } elseif ($rand <= 97) { // 5% izin
                        $status = 'izin';
                        $jamMasukTime = null;
                        $jamKeluarTime = null;
                    } else { // 3% tidak hadir (alpa)
                        $status = 'tidak_hadir';
                        $jamMasukTime = null;
                        $jamKeluarTime = null;
                    }

                    $jamMasuk = $jamMasukTime ? $tgl->toDateString() . ' ' . $jamMasukTime : null;
                    $jamKeluar = $jamKeluarTime ? $tgl->toDateString() . ' ' . $jamKeluarTime : null;
                    $durasi = ($jamMasuk && $jamKeluar) ? \Carbon\Carbon::parse($jamMasuk)->diffInMinutes(\Carbon\Carbon::parse($jamKeluar)) : 0;

                    $presensi = Presensi::create([
                        'karyawan_id' => $kr->id,
                        'tgl_presensi' => $tgl->toDateString(),
                        'jam_masuk' => $jamMasuk,
                        'jam_pulang' => $jamKeluar,
                        'status' => $status,
                        'foto_masuk' => in_array($status, ['hadir', 'terlambat']) ? 'presensi/sample-masuk.jpg' : null,
                        'foto_keluar' => in_array($status, ['hadir', 'terlambat']) ? 'presensi/sample-keluar.jpg' : null,
                        'durasi_menit' => $durasi,
                        'potongan' => $potongan,
                    ]);

                    // Bukti pekerjaan hanya untuk yang hadir
                    if (in_array($status, ['hadir', 'terlambat'])) {
                        \App\Models\BuktiPekerjaan::create([
                            'detail_pekerjaan_id' => $jadwal->id,
                            'karyawan_id' => $kr->id,
                            'foto_before' => 'bukti_pekerjaan/sample-before.jpg',
                            'foto_after' => 'bukti_pekerjaan/sample-after.jpg',
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
                ->whereRaw("DATE_FORMAT(tgl_presensi, '%Y-%m') = ?", [$periodeLaporan])
                ->get();

            $jumlahHadir = $presensis->whereIn('status', ['hadir', 'terlambat'])->count();
            $jumlahTidakHadir = $presensis->where('status', 'tidak_hadir')->count();
            $jumlahTerlambat = $presensis->where('status', 'terlambat')->count();
            $totalPotongan = (float) $presensis->where('status', 'terlambat')->sum('potongan');
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

        // ========== NOTIFIKASI DATA ==========
        Notifikasi::updateOrCreate(
            [
                'user_id' => $karyawanUser->id,
                'pesan' => 'Jadwal kerja reguler dimulai pukul 08:00 di Kantor CV Boss Muda Mandiri.',
            ],
            [
                'tipe' => 'info',
                'terbaca' => true,
                'tgl_kirim' => '2026-04-27 07:00:00',
                'channel' => 'in_app',
            ]
        );

        Notifikasi::updateOrCreate(
            [
                'user_id' => $supervisorUser->id,
                'pesan' => 'Ada 1 presensi menunggu verifikasi dari staf lapangan.',
            ],
            [
                'tipe' => 'urgent',
                'terbaca' => false,
                'tgl_kirim' => '2026-04-27 17:10:00',
                'channel' => 'email',
            ]
        );

        Notifikasi::updateOrCreate(
            [
                'user_id' => $adminUser->id,
                'pesan' => 'Laporan presensi bulan Maret sudah siap untuk diekspor.',
            ],
            [
                'tipe' => 'info',
                'terbaca' => false,
                'tgl_kirim' => \Carbon\Carbon::parse($periodeLaporan . '-28 18:00:00')->toDateTimeString(),
                'channel' => 'in_app',
            ]
        );

        Notifikasi::updateOrCreate(
            [
                'user_id' => $karyawanUser->id,
                'pesan' => 'Peringatan: Presensi Anda pada 2026-04-26 menunjukkan keterlambatan 55 menit.',
            ],
            [
                'tipe' => 'peringatan',
                'terbaca' => true,
                'tgl_kirim' => '2026-04-26 09:00:00',
                'channel' => 'email',
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
