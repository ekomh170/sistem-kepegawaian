<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Jadwal_kerja;
use App\Models\Karyawan;
use App\Models\Laporan;
use App\Models\Lokasi_gps;
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
     * - Contoh lama: dela@example.com / password
     */
    public function run(): void
    {
        // ========== USER DATA ==========
        // Akun Admin
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'nama' => 'Admin Sistem',
                'password' => 'password',
                'role' => 'admin',
            ]
        );

        // Akun Supervisor
        $supervisorUser = User::updateOrCreate(
            ['email' => 'supervisor@example.com'],
            [
                'nama' => 'Supervisor Tim',
                'password' => 'password',
                'role' => 'supervisor',
            ]
        );

        // Akun Karyawan
        $karyawanUser = User::updateOrCreate(
            ['email' => 'karyawan@example.com'],
            [
                'nama' => 'Karyawan Contoh',
                'password' => 'password',
                'role' => 'karyawan',
            ]
        );

        // Akun Karyawan Ekoaryo (tanpa data presensi)
        $ekoaryoUser = User::updateOrCreate(
            ['email' => 'ekoaryo@example.com'],
            [
                'nama' => 'Ekoaryo',
                'password' => 'password',
                'role' => 'karyawan',
            ]
        );

        // Akun Contoh Lama
        User::updateOrCreate([
            'email' => 'dela@example.com',
        ], [
            'nama' => 'Dela',
            'password' => 'password',
            'role' => 'admin',
        ]);

        // ========== ADMIN DATA ==========
        $admin = Admin::updateOrCreate(
            ['user_id' => $adminUser->id],
            [
                'nip' => 'ADM-001',
                'divisi' => 'Human Resource',
                'level_akses' => 'penuh',
            ]
        );

        // ========== SUPERVISOR DATA ==========
        $supervisor = Supervisor::updateOrCreate(
            ['user_id' => $supervisorUser->id],
            [
                'jabatan' => 'Supervisor Operasional',
                'level_akses' => 'menengah',
            ]
        );

        // ========== KARYAWAN DATA ==========
        $karyawan = Karyawan::updateOrCreate(
            ['user_id' => $karyawanUser->id],
            [
                'nik' => 'KRY-001',
                'posisi_karyawan' => 'Staff Administrasi',
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
                'posisi_karyawan' => 'Staff Operasional',
                'tgl_masuk' => '2025-02-10',
                'status_kontrak' => 'kontrak',
                'no_hp' => '081298765432',
                'bidang_tugas' => 'Operasional Lapangan',
            ]
        );

        // ========== LOKASI GPS DATA ==========
        $lokasiKantor = Lokasi_gps::updateOrCreate(
            ['nama_lokasi' => 'Kantor Pusat'],
            [
                'latitude' => -7.2574720,
                'longitude' => 112.7520880,
                'radius_meter' => 150,
                'timestamp' => '2026-04-27 07:00:00',
                'akurasi' => 5.5,
            ]
        );

        $lokasiCabang = Lokasi_gps::updateOrCreate(
            ['nama_lokasi' => 'Kantor Cabang'],
            [
                'latitude' => -7.2650000,
                'longitude' => 112.7600000,
                'radius_meter' => 200,
                'timestamp' => '2026-04-27 07:30:00',
                'akurasi' => 4.2,
            ]
        );

        // ========== JADWAL KERJA DATA ==========
        $jadwalKerja = Jadwal_kerja::updateOrCreate(
            [
                'karyawan_id' => $karyawan->id,
                'tanggal' => '2026-04-27',
            ],
            [
                'hari' => 'Senin',
                'jam_masuk' => '08:00:00',
                'jam_keluar' => '17:00:00',
                'lokasi_kerja' => 'Kantor Pusat',
            ]
        );

        $jadwalKerjaKemarin = Jadwal_kerja::updateOrCreate(
            [
                'karyawan_id' => $karyawan->id,
                'tanggal' => '2026-04-26',
            ],
            [
                'hari' => 'Minggu',
                'jam_masuk' => '09:00:00',
                'jam_keluar' => '18:00:00',
                'lokasi_kerja' => 'Kantor Pusat',
            ]
        );

        // ========== PRESENSI DATA ==========
        $presensi = Presensi::updateOrCreate(
            [
                'karyawan_id' => $karyawan->id,
                'jadwal_kerja_id' => $jadwalKerja->id,
                'tgl_presensi' => '2026-04-27',
            ],
            [
                'lokasi_gps_id' => $lokasiKantor->id,
                'jam_masuk' => '2026-04-27 08:03:00',
                'jam_keluar' => '2026-04-27 17:02:00',
                'status' => 'hadir',
                'foto_masuk' => 'presensi/sample-masuk.jpg',
                'foto_keluar' => 'presensi/sample-keluar.jpg',
                'durasi_menit' => 539,
            ]
        );

        $presensiKemarin = Presensi::updateOrCreate(
            [
                'karyawan_id' => $karyawan->id,
                'jadwal_kerja_id' => $jadwalKerjaKemarin->id,
                'tgl_presensi' => '2026-04-26',
            ],
            [
                'lokasi_gps_id' => $lokasiCabang->id,
                'jam_masuk' => '2026-04-26 08:55:00',
                'jam_keluar' => '2026-04-26 17:30:00',
                'status' => 'terlambat',
                'foto_masuk' => 'presensi/sample-masuk-2.jpg',
                'foto_keluar' => 'presensi/sample-keluar-2.jpg',
                'durasi_menit' => 575,
            ]
        );

        // ========== VERIFIKASI DATA ==========
        Verifikasi::updateOrCreate(
            ['presensi_id' => $presensi->id],
            [
                'supervisor_id' => $supervisor->id,
                'status' => 'disetujui',
                'catatan' => 'Presensi sesuai jadwal kerja.',
                'tgl_verifikasi' => '2026-04-27 17:30:00',
                'alasan_tolak' => null,
            ]
        );

        Verifikasi::updateOrCreate(
            ['presensi_id' => $presensiKemarin->id],
            [
                'supervisor_id' => $supervisor->id,
                'status' => 'disetujui',
                'catatan' => 'Presensi terlambat tetapi diketahui, ada pemberitahuan sebelumnya.',
                'tgl_verifikasi' => '2026-04-26 18:00:00',
                'alasan_tolak' => null,
            ]
        );

        // ========== LAPORAN DATA ==========
        Laporan::updateOrCreate(
            [
                'karyawan_id' => $karyawan->id,
                'admin_id' => $admin->id,
                'periode' => '2026-04',
            ],
            [
                'total_hadir' => 22,
                'total_terlambat' => 1,
                'total_tidak_hadir' => 0,
                'estimasi_gaji' => 5500000,
                'tgl_generate' => '2026-04-27 18:00:00',
            ]
        );

        Laporan::updateOrCreate(
            [
                'karyawan_id' => $karyawan->id,
                'admin_id' => $admin->id,
                'periode' => '2026-03',
            ],
            [
                'total_hadir' => 21,
                'total_terlambat' => 0,
                'total_tidak_hadir' => 1,
                'estimasi_gaji' => 5450000,
                'tgl_generate' => '2026-03-31 18:00:00',
            ]
        );

        // ========== NOTIFIKASI DATA ==========
        Notifikasi::updateOrCreate(
            [
                'user_id' => $karyawanUser->id,
                'pesan' => 'Jadwal kerja hari ini dimulai pukul 08:00 di Kantor Pusat.',
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
                'pesan' => 'Ada 1 presensi menunggu verifikasi dari karyawan.',
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
                'pesan' => 'Laporan presensi bulan April sudah siap untuk di-export.',
            ],
            [
                'tipe' => 'info',
                'terbaca' => false,
                'tgl_kirim' => '2026-04-27 18:00:00',
                'channel' => 'in_app',
            ]
        );

        Notifikasi::updateOrCreate(
            [
                'user_id' => $karyawanUser->id,
                'pesan' => 'Perhatian: Presensi Anda pada 2026-04-26 menunjukkan keterlambatan 55 menit.',
            ],
            [
                'tipe' => 'peringatan',
                'terbaca' => true,
                'tgl_kirim' => '2026-04-26 09:00:00',
                'channel' => 'email',
            ]
        );
    }
}
