<?php

namespace Tests\Feature;

use App\Models\JadwalPekerjaan;
use App\Models\Presensi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Jaring pengaman RBAC + render panel Filament.
 * Memastikan gate role tetap benar dan tabel Filament ter-render (closure badge jalan).
 */
class V3RbacTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $email): User
    {
        return User::create([
            'nama' => ucfirst($role) . ' Uji',
            'email' => $email,
            'password' => 'password',
            'role' => $role,
            'nik' => strtoupper(substr($role, 0, 3)) . '-' . substr(md5($email), 0, 4),
            'no_hp' => '0812',
            'posisi' => $role === 'karyawan' ? 'Staf' : null,
        ]);
    }

    private function seedPresensiRow(): void
    {
        $kar = $this->user('karyawan', 'datakar@example.com');
        $jadwal = JadwalPekerjaan::create([
            'user_id' => $kar->id,
            'tanggal_kerja' => Carbon::today()->toDateString(),
            'jam_masuk' => '08:00:00', 'jam_pulang' => '16:00:00', 'status' => 'aktif',
        ]);
        Presensi::create([
            'user_id' => $kar->id,
            'jadwal_id' => $jadwal->id,
            'tanggal' => Carbon::today()->toDateString(),
            'status_presensi' => 'terlambat',
            'status_verifikasi' => 'disetujui',
            'menit_terlambat' => 15,
            'potongan_terlambat' => 10000,
        ]);
    }

    // ===================== RBAC =====================

    public function test_karyawan_ditolak_dari_panel_admin(): void
    {
        $this->actingAs($this->user('karyawan', 'k@example.com'))
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_admin_bisa_akses_panel_admin(): void
    {
        $this->actingAs($this->user('admin', 'a@example.com'))
            ->get('/admin')
            ->assertSuccessful();
    }

    public function test_supervisor_bisa_akses_panel_supervisor(): void
    {
        $this->actingAs($this->user('supervisor', 's@example.com'))
            ->get('/supervisor')
            ->assertSuccessful();
    }

    public function test_karyawan_ditolak_dari_panel_supervisor(): void
    {
        $this->actingAs($this->user('karyawan', 'k2@example.com'))
            ->get('/supervisor')
            ->assertForbidden();
    }

    // ===================== RENDER FILAMENT (closure badge jalan) =====================

    /** @dataProvider resourceUrls */
    public function test_admin_render_resource_list(string $url): void
    {
        $this->seedPresensiRow();

        $this->actingAs($this->user('admin', 'admin-render@example.com'))
            ->get($url)
            ->assertSuccessful();
    }

    public static function resourceUrls(): array
    {
        return [
            'presensi' => ['/admin/presensis'],
            'karyawan' => ['/admin/karyawans'],
            'jadwal'   => ['/admin/jadwal-pekerjaans'],
            'laporan'  => ['/admin/laporan-presensis'],
            'akun'     => ['/admin/akuns'],
        ];
    }

    public function test_admin_render_form_edit_akun_dengan_role_enum(): void
    {
        $target = $this->user('supervisor', 'target@example.com');

        $this->actingAs($this->user('admin', 'admin-edit@example.com'))
            ->get("/admin/akuns/{$target->id}/edit")
            ->assertSuccessful()
            ->assertSee('Supervisor'); // label role ter-render (badge/preselect)
    }
}
