<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Karyawan;
use App\Models\Setting;
use App\Models\Supervisor;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder untuk environment production.
 *
 * Isi minimal: 1 admin, 1 supervisor, 8 karyawan + settings dasar perusahaan.
 * Tidak generate data dummy (jadwal/presensi/laporan).
 *
 * Cara jalankan:
 *   php artisan db:seed --class=ProductionSeeder
 *
 * Semua akun password default: "password" (wajib diubah setelah login pertama).
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        // ========== ADMIN ==========
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@kepegawaianjog.com'],
            [
                'nama' => 'Administrator',
                'password' => 'password',
                'role' => 'admin',
            ]
        );

        Admin::updateOrCreate(
            ['user_id' => $adminUser->id],
            [
                'nik' => 'ADM-001',
                'no_hp' => '081200000001',
            ]
        );

        // ========== SUPERVISOR ==========
        $supervisorUser = User::updateOrCreate(
            ['email' => 'supervisor@kepegawaianjog.com'],
            [
                'nama' => 'Supervisor',
                'password' => 'password',
                'role' => 'supervisor',
            ]
        );

        Supervisor::updateOrCreate(
            ['user_id' => $supervisorUser->id],
            [
                'nik' => 'SPV-001',
                'no_hp' => '081200000002',
            ]
        );

        // ========== KARYAWAN (8 akun) ==========
        for ($i = 1; $i <= 8; $i++) {
            $karyawanUser = User::updateOrCreate(
                ['email' => "karyawan{$i}@kepegawaianjog.com"],
                [
                    'nama' => "Karyawan {$i}",
                    'password' => 'password',
                    'role' => 'karyawan',
                ]
            );

            Karyawan::updateOrCreate(
                ['user_id' => $karyawanUser->id],
                [
                    'nik' => 'KRY-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                    'posisi_karyawan' => 'Staf',
                    'tgl_masuk' => now()->toDateString(),
                    'status_kontrak' => 'tetap',
                    'no_hp' => '0812000000' . str_pad($i + 2, 2, '0', STR_PAD_LEFT),
                    'bidang_tugas' => 'Operasional',
                    'gaji_pokok' => 2827593,
                ]
            );
        }

        // ========== SETTINGS DASAR ==========
        $settings = [
            ['key' => 'nama_perusahaan',    'value' => 'CV Boss Muda Mandiri',                  'group' => 'identitas',  'label' => 'Nama Perusahaan',                       'type' => 'text'],
            ['key' => 'alamat_perusahaan',  'value' => 'Jl. Jend. Sudirman No. 45, Jakarta',    'group' => 'identitas',  'label' => 'Alamat Perusahaan',                     'type' => 'textarea'],
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

        $this->command->info('');
        $this->command->info('========================================');
        $this->command->info('  SEEDING PRODUCTION SELESAI');
        $this->command->info('========================================');
        $this->command->info('  Admin       : admin@kepegawaianjog.com');
        $this->command->info('  Supervisor  : supervisor@kepegawaianjog.com');
        $this->command->info('  Karyawan 1-8: karyawan{1..8}@kepegawaianjog.com');
        $this->command->info('  Password    : password');
        $this->command->warn('  WAJIB UBAH PASSWORD SETELAH LOGIN PERTAMA!');
        $this->command->info('========================================');
    }
}
