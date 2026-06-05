<?php

namespace Tests\Feature;

use App\Filament\Resources\LaporanPresensis\Pages\CreateLaporan;
use App\Filament\Resources\Verifikasis\Pages\ListVerifikasis;
use App\Models\JadwalPekerjaan;
use App\Models\Presensi;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    public function test_supervisor_render_antrian_verifikasi_dengan_baris_pending(): void
    {
        $kar = $this->user('karyawan', 'verif-kar@example.com');
        $jadwal = JadwalPekerjaan::create([
            'user_id' => $kar->id,
            'tanggal_kerja' => Carbon::today()->toDateString(),
            'jam_masuk' => '08:00:00', 'jam_pulang' => '16:00:00', 'status' => 'aktif',
        ]);
        Presensi::create([
            'user_id' => $kar->id,
            'jadwal_id' => $jadwal->id,
            'tanggal' => Carbon::today()->toDateString(),
            'jam_masuk' => Carbon::today()->setTime(8, 0), // check-in nyata → masuk antrian
            'status_presensi' => 'hadir',
            'status_verifikasi' => 'pending',
        ]);

        // Antrian (difilter pending) ter-render + kolom & closure aksi dievaluasi tanpa fatal.
        $this->actingAs($this->user('supervisor', 'spv-verif@example.com'))
            ->get('/supervisor/verifikasis')
            ->assertSuccessful()
            ->assertSee($kar->nama);
    }

    public function test_admin_tidak_bisa_akses_antrian_verifikasi(): void
    {
        // Verifikasi khusus supervisor — admin tidak punya menu/akses (RBAC: admin ❌).
        $this->actingAs($this->user('admin', 'admin-noverif@example.com'))
            ->get('/admin/verifikasis')
            ->assertForbidden();
    }

    public function test_admin_render_form_buat_laporan(): void
    {
        // Form "Buat Laporan" ter-render (closure type/label/helper periode dievaluasi tanpa fatal).
        $this->actingAs($this->user('admin', 'admin-laporan@example.com'))
            ->get('/admin/laporan-presensis/create')
            ->assertSuccessful();
    }

    private function presensiPending(string $email): Presensi
    {
        $kar = $this->user('karyawan', $email);
        $jadwal = JadwalPekerjaan::create([
            'user_id' => $kar->id,
            'tanggal_kerja' => Carbon::today()->toDateString(),
            'jam_masuk' => '08:00:00', 'jam_pulang' => '16:00:00', 'status' => 'aktif',
        ]);

        return Presensi::create([
            'user_id' => $kar->id,
            'jadwal_id' => $jadwal->id,
            'tanggal' => Carbon::today()->toDateString(),
            'jam_masuk' => Carbon::today()->setTime(8, 0),
            'status_presensi' => 'hadir',
            'status_verifikasi' => 'pending',
        ]);
    }

    public function test_supervisor_setujui_presensi_via_aksi_cepat(): void
    {
        $presensi = $this->presensiPending('setuju-kar@example.com');
        $supervisor = $this->user('supervisor', 'spv-setuju@example.com');

        $this->actingAs($supervisor);
        Filament::setCurrentPanel('supervisor');
        Livewire::test(ListVerifikasis::class)
            ->callTableAction('setujui', $presensi);

        $fresh = $presensi->fresh();
        $this->assertSame('disetujui', $fresh->status_verifikasi);
        $this->assertSame($supervisor->id, $fresh->diverifikasi_oleh);
        $this->assertNotNull($fresh->tgl_verifikasi);
    }

    public function test_supervisor_tolak_presensi_via_aksi_cepat(): void
    {
        $presensi = $this->presensiPending('tolak-kar@example.com');
        $supervisor = $this->user('supervisor', 'spv-tolak@example.com');

        $this->actingAs($supervisor);
        Filament::setCurrentPanel('supervisor');
        Livewire::test(ListVerifikasis::class)
            ->callTableAction('tolak', $presensi, ['catatan_verifikasi' => 'Lokasi check-in di luar area kantor']);

        $fresh = $presensi->fresh();
        $this->assertSame('ditolak', $fresh->status_verifikasi);
        $this->assertSame('Lokasi check-in di luar area kantor', $fresh->catatan_verifikasi);
        $this->assertSame($supervisor->id, $fresh->diverifikasi_oleh);
    }

    public function test_admin_submit_buat_laporan_tersimpan(): void
    {
        $admin = $this->user('admin', 'admin-submit-laporan@example.com');

        $this->actingAs($admin);
        Filament::setCurrentPanel('admin');
        Livewire::test(CreateLaporan::class)
            ->fillForm([
                'tipe_laporan' => 'presensi',
                'jenis' => 'Bulanan',
                'periode' => '2026-04',
                'judul' => 'Laporan Presensi Bulanan 2026-04',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // generated_by & tgl_generate terisi otomatis (Hidden default), bukan dari input user.
        $this->assertDatabaseHas('tb_laporan_presensi', [
            'judul' => 'Laporan Presensi Bulanan 2026-04',
            'periode' => '2026-04',
            'jenis' => 'Bulanan',
            'generated_by' => $admin->id,
        ]);
    }
}
