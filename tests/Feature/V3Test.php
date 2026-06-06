<?php

namespace Tests\Feature;

use App\Models\BuktiPekerjaan;
use App\Models\DetailPekerjaan;
use App\Models\JadwalPekerjaan;
use App\Models\LaporanPresensi;
use App\Models\Presensi;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Test E2E untuk skema V3 (Skenario 1 / V3 Strict).
 * Memverifikasi konsolidasi user, FK user_id, verifikasi inline, dan alur presensi/tugas.
 */
class V3Test extends TestCase
{
    use RefreshDatabase;

    private function seedSettings(): void
    {
        foreach ([
            ['key' => 'kantor_lat', 'value' => '-6.2', 'group' => 'lokasi', 'label' => 'Lat', 'type' => 'text'],
            ['key' => 'kantor_lng', 'value' => '106.8', 'group' => 'lokasi', 'label' => 'Lng', 'type' => 'text'],
            ['key' => 'kantor_radius', 'value' => '500', 'group' => 'lokasi', 'label' => 'Radius', 'type' => 'number'],
            ['key' => 'toleransi_menit', 'value' => '10', 'group' => 'kehadiran', 'label' => 'Toleransi', 'type' => 'number'],
            ['key' => 'nama_perusahaan', 'value' => 'CV Boss Muda Mandiri', 'group' => 'identitas', 'label' => 'Nama', 'type' => 'text'],
        ] as $s) {
            Setting::updateOrCreate(['key' => $s['key']], $s);
        }
        Setting::clearCache();
    }

    private function karyawan(string $email = 'kar@example.com'): User
    {
        return User::create([
            'nama' => 'Karyawan Uji',
            'email' => $email,
            'password' => 'password',
            'role' => 'karyawan',
            'nik' => 'KRY-' . substr(md5($email), 0, 6),
            'no_hp' => '0812',
            'posisi' => 'Staf',
        ]);
    }

    private function supervisor(string $email = 'spv@example.com'): User
    {
        return User::create([
            'nama' => 'Supervisor Uji',
            'email' => $email,
            'password' => 'password',
            'role' => 'supervisor',
            'nik' => 'SPV-' . substr(md5($email), 0, 6),
            'no_hp' => '0813',
        ]);
    }

    // ===================== SCHEMA / MODEL =====================

    public function test_user_terkonsolidasi_punya_kolom_v3(): void
    {
        $u = $this->karyawan();

        $this->assertStringStartsWith('KRY-', $u->nik);
        $this->assertSame('Staf', $u->posisi);
        $this->assertSame('Karyawan Uji', $u->name); // accessor name -> nama
    }

    public function test_scope_dan_helper_role(): void
    {
        $kar = $this->karyawan();
        $spv = $this->supervisor();

        $this->assertSame(1, User::karyawan()->count());
        $this->assertSame(1, User::supervisor()->count());
        $this->assertTrue($kar->isKaryawan());
        $this->assertFalse($kar->isAdmin());
        $this->assertTrue($spv->isSupervisor());
    }

    public function test_role_dicast_ke_enum(): void
    {
        $kar = $this->karyawan();

        $this->assertInstanceOf(\App\Enums\Role::class, $kar->role);
        $this->assertSame(\App\Enums\Role::Karyawan, $kar->role);
        $this->assertTrue($kar->hasRole('karyawan'));
        $this->assertTrue($kar->hasRole(\App\Enums\Role::Karyawan));
        // attributesToArray men-serialize enum -> string (penting utk fill form Filament & JSON)
        $this->assertSame('karyawan', $kar->attributesToArray()['role']);
        // query builder tetap pakai string
        $this->assertSame(1, User::where('role', 'karyawan')->count());
    }

    public function test_relasi_v3_user_id(): void
    {
        $kar = $this->karyawan();
        $jadwal = JadwalPekerjaan::create([
            'user_id' => $kar->id,
            'tanggal_kerja' => Carbon::today()->toDateString(),
            'jam_masuk' => '08:00:00',
            'jam_pulang' => '16:00:00',
            'status' => 'aktif',
        ]);
        Presensi::create([
            'user_id' => $kar->id,
            'jadwal_id' => $jadwal->id,
            'tanggal' => Carbon::today()->toDateString(),
            'status_presensi' => 'hadir',
        ]);

        $this->assertSame(1, $kar->presensiSaya()->count());
        $this->assertSame(1, $kar->jadwalKerja()->count());
    }

    public function test_hitung_potongan_rule_10rb_per_10menit(): void
    {
        $this->assertSame(0.0, Presensi::hitungPotongan(10));
        $this->assertSame(10000.0, Presensi::hitungPotongan(11));
        $this->assertSame(10000.0, Presensi::hitungPotongan(20));
        $this->assertSame(20000.0, Presensi::hitungPotongan(21));
    }

    public function test_verifikasi_inline_method(): void
    {
        $kar = $this->karyawan();
        $spv = $this->supervisor();
        $presensi = Presensi::create([
            'user_id' => $kar->id,
            'tanggal' => Carbon::today()->toDateString(),
            'status_presensi' => 'hadir',
        ]);

        $this->assertFalse($presensi->sudahDiverifikasi());

        $presensi->verifikasi($spv, 'disetujui', 'Oke');

        $this->assertTrue($presensi->fresh()->sudahDiverifikasi());
        $this->assertSame('disetujui', $presensi->fresh()->status_verifikasi);
        $this->assertSame($spv->id, $presensi->fresh()->diverifikasi_oleh);
        $this->assertSame('Supervisor Uji', $presensi->fresh()->verifikator->nama);
    }

    public function test_laporan_presensi_agregat_scope(): void
    {
        $admin = User::create(['nama' => 'A', 'email' => 'a@e.com', 'password' => 'p', 'role' => 'admin', 'nik' => 'ADM']);
        LaporanPresensi::create([
            'judul' => 'Laporan Presensi Bulanan 2026-04',
            'periode' => '2026-04',
            'jenis' => 'Bulanan',
            'generated_by' => $admin->id,
            'tgl_generate' => now(),
        ]);

        $this->assertSame(1, LaporanPresensi::whereNull('user_id')->count());
    }

    // ===================== HTTP / ALUR =====================

    public function test_root_redirect_tanpa_login_ke_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_halaman_login_tidak_di_cache_browser(): void
    {
        // Cegah token CSRF basi setelah logout (anti "login dua kali" / 419 saat ganti role).
        $resp = $this->get('/login');
        $resp->assertSuccessful();
        $this->assertStringContainsString('no-store', (string) $resp->headers->get('Cache-Control'));
    }

    public function test_root_redirect_karyawan_ke_beranda(): void
    {
        $this->actingAs($this->karyawan())
            ->get('/')
            ->assertRedirect(route('karyawan.beranda'));
    }

    public function test_checkin_membuat_presensi_dengan_user_id(): void
    {
        Storage::fake('public');
        $this->seedSettings();
        $kar = $this->karyawan();

        $this->travelTo(Carbon::today()->setTime(7, 30));

        $resp = $this->actingAs($kar)->postJson(route('presensi.check-in'), [
            'latitude' => -6.2,
            'longitude' => 106.8,
            'foto_masuk_base64' => 'data:image/jpeg;base64,' . base64_encode('img'),
        ]);

        $resp->assertOk()->assertJson(['success' => true, 'status' => 'hadir']);

        $this->assertDatabaseHas('tb_presensi', [
            'user_id' => $kar->id,
            'status_presensi' => 'hadir',
            'status_verifikasi' => 'pending', // default V3
        ]);
    }

    public function test_checkin_ditolak_saat_hari_libur(): void
    {
        Storage::fake('public');
        $this->seedSettings();
        $kar = $this->karyawan();

        JadwalPekerjaan::create([
            'user_id' => $kar->id,
            'tanggal_kerja' => Carbon::today()->toDateString(),
            'jam_masuk' => '08:00:00',
            'jam_pulang' => '16:00:00',
            'hari_libur' => true,
            'status' => 'aktif',
        ]);

        $this->actingAs($kar)->postJson(route('presensi.check-in'), [
            'latitude' => -6.2,
            'longitude' => 106.8,
            'foto_masuk_base64' => 'data:image/jpeg;base64,' . base64_encode('img'),
        ])->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_checkout_mengisi_jam_keluar(): void
    {
        Storage::fake('public');
        $this->seedSettings();
        $kar = $this->karyawan();

        $this->travelTo(Carbon::today()->setTime(7, 30));
        $this->actingAs($kar)->postJson(route('presensi.check-in'), [
            'latitude' => -6.2, 'longitude' => 106.8,
            'foto_masuk_base64' => 'data:image/jpeg;base64,' . base64_encode('img'),
        ])->assertOk();

        $this->travelTo(Carbon::today()->setTime(16, 5));
        $this->actingAs($kar)->postJson(route('presensi.check-out'), [
            'latitude' => -6.2, 'longitude' => 106.8,
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertNotNull(Presensi::where('user_id', $kar->id)->first()->jam_keluar);
    }

    public function test_karyawan_terima_dan_tolak_tugas(): void
    {
        $kar = $this->karyawan();
        $jadwal = JadwalPekerjaan::create([
            'user_id' => $kar->id,
            'tanggal_kerja' => Carbon::today()->toDateString(),
            'jam_masuk' => '08:00:00', 'jam_pulang' => '16:00:00', 'status' => 'aktif',
        ]);
        $tugasA = DetailPekerjaan::create(['jadwal_id' => $jadwal->id, 'user_id' => $kar->id, 'nama_lokasi' => 'Lokasi A', 'status' => 'pending']);
        $tugasB = DetailPekerjaan::create(['jadwal_id' => $jadwal->id, 'user_id' => $kar->id, 'nama_lokasi' => 'Lokasi B', 'status' => 'pending']);

        $this->actingAs($kar)->post(route('karyawan.tugas.terima'), ['detail_pekerjaan_id' => $tugasA->id]);
        $this->actingAs($kar)->post(route('karyawan.tugas.tolak'), [
            'detail_pekerjaan_id' => $tugasB->id,
            'alasan_tolak' => 'Lokasi terlalu jauh dari domisili saya',
        ]);

        $this->assertSame('disetujui', $tugasA->fresh()->status);
        $this->assertSame('ditolak', $tugasB->fresh()->status);
    }

    public function test_tolak_tugas_membuat_link_konfirmasi_wa(): void
    {
        $admin = User::create([
            'nama' => 'Admin WA', 'email' => 'adminwa@example.com', 'password' => 'p',
            'role' => 'admin', 'nik' => 'ADM-WA', 'no_hp' => '081234567890',
        ]);
        $kar = $this->karyawan();
        $jadwal = JadwalPekerjaan::create([
            'user_id' => $kar->id,
            'dibuat_oleh' => $admin->id,
            'tanggal_kerja' => Carbon::today()->toDateString(),
            'jam_masuk' => '08:00:00', 'jam_pulang' => '16:00:00', 'status' => 'aktif',
        ]);
        $tugas = DetailPekerjaan::create(['jadwal_id' => $jadwal->id, 'user_id' => $kar->id, 'nama_lokasi' => 'Lokasi A', 'status' => 'pending']);

        $resp = $this->actingAs($kar)->post(route('karyawan.tugas.tolak'), [
            'detail_pekerjaan_id' => $tugas->id,
            'alasan_tolak' => 'Lokasi terlalu jauh dari domisili saya',
        ]);

        $this->assertSame('ditolak', $tugas->fresh()->status);
        // Nomor admin dinormalisasi (0812 -> 62812) & alasan masuk template WA.
        $resp->assertSessionHas('wa_konfirmasi', fn ($url) => is_string($url)
            && str_contains($url, 'https://wa.me/6281234567890')
            && str_contains($url, rawurlencode('Lokasi terlalu jauh')));
    }

    public function test_nomor_wa_konfirmasi_mengikuti_pengaturan(): void
    {
        Setting::clearCache();
        Setting::updateOrCreate(['key' => 'wa_admin'], [
            'key' => 'wa_admin', 'value' => '085799990000', 'group' => 'kontak', 'label' => 'WA', 'type' => 'text',
        ]);
        Setting::clearCache();

        $kar = $this->karyawan();
        $jadwal = JadwalPekerjaan::create([
            'user_id' => $kar->id,
            'tanggal_kerja' => Carbon::today()->toDateString(),
            'jam_masuk' => '08:00:00', 'jam_pulang' => '16:00:00', 'status' => 'aktif',
        ]);
        $tugas = DetailPekerjaan::create(['jadwal_id' => $jadwal->id, 'user_id' => $kar->id, 'nama_lokasi' => 'Lokasi A', 'status' => 'pending']);

        $resp = $this->actingAs($kar)->post(route('karyawan.tugas.tolak'), [
            'detail_pekerjaan_id' => $tugas->id,
            'alasan_tolak' => 'Alasan penolakan yang cukup panjang',
        ]);

        // Pakai nomor dari Pengaturan (085799990000 -> 6285799990000), bukan no_hp admin.
        $resp->assertSessionHas('wa_konfirmasi', fn ($url) => is_string($url)
            && str_contains($url, 'https://wa.me/6285799990000'));
    }

    private function tugasDisetujui(string $email): DetailPekerjaan
    {
        $kar = $this->karyawan($email);
        $jadwal = JadwalPekerjaan::create([
            'user_id' => $kar->id,
            'tanggal_kerja' => Carbon::today()->toDateString(),
            'jam_masuk' => '08:00:00', 'jam_pulang' => '16:00:00', 'status' => 'aktif',
        ]);

        return DetailPekerjaan::create([
            'jadwal_id' => $jadwal->id, 'user_id' => $kar->id,
            'nama_lokasi' => 'Lokasi A', 'status' => 'disetujui',
        ]);
    }

    public function test_upload_bukti_banyak_foto_tersimpan(): void
    {
        Storage::fake('public');
        $tugas = $this->tugasDisetujui('uploadbukti@example.com');
        $img = 'data:image/jpeg;base64,' . base64_encode('imgbytes');

        $resp = $this->actingAs($tugas->user)->postJson(route('presensi.bukti-pekerjaan'), [
            'detail_pekerjaan_id' => $tugas->id,
            'foto_base64' => [$img, $img, $img], // 3 foto sekaligus
            'keterangan' => 'Pekerjaan selesai',
        ]);
        $resp->assertOk()->assertJson(['success' => true, 'jumlah_foto' => 3]);

        $bukti = BuktiPekerjaan::where('detail_pekerjaan_id', $tugas->id)->first();
        $this->assertCount(3, $bukti->foto);
        $this->assertSame('pending', $bukti->status);
    }

    public function test_upload_bukti_append_dan_maks_20(): void
    {
        Storage::fake('public');
        $tugas = $this->tugasDisetujui('uploadbukti2@example.com');
        $img = 'data:image/jpeg;base64,' . base64_encode('x');

        // Batch 1: 12 foto
        $this->actingAs($tugas->user)->postJson(route('presensi.bukti-pekerjaan'), [
            'detail_pekerjaan_id' => $tugas->id,
            'foto_base64' => array_fill(0, 12, $img),
        ])->assertOk()->assertJson(['jumlah_foto' => 12]);

        // Batch 2: +5 -> total 17 (append, bukan replace)
        $this->actingAs($tugas->user)->postJson(route('presensi.bukti-pekerjaan'), [
            'detail_pekerjaan_id' => $tugas->id,
            'foto_base64' => array_fill(0, 5, $img),
        ])->assertOk()->assertJson(['jumlah_foto' => 17]);

        // Batch 3: +5 -> total 22 > 20 -> ditolak, tetap 17
        $this->actingAs($tugas->user)->postJson(route('presensi.bukti-pekerjaan'), [
            'detail_pekerjaan_id' => $tugas->id,
            'foto_base64' => array_fill(0, 5, $img),
        ])->assertStatus(422);

        $this->assertCount(17, BuktiPekerjaan::where('detail_pekerjaan_id', $tugas->id)->first()->foto);
    }

    public function test_upload_bukti_wajib_minimal_satu_foto(): void
    {
        Storage::fake('public');
        $tugas = $this->tugasDisetujui('uploadbukti3@example.com');

        // Tanpa foto sama sekali -> ditolak.
        $this->actingAs($tugas->user)->postJson(route('presensi.bukti-pekerjaan'), [
            'detail_pekerjaan_id' => $tugas->id,
            'keterangan' => 'tanpa foto',
        ])->assertStatus(422)->assertJson(['success' => false]);

        $this->assertDatabaseMissing('tb_bukti_pekerjaan', ['detail_pekerjaan_id' => $tugas->id]);
    }

    public function test_render_semua_halaman_mobile_karyawan(): void
    {
        $this->seedSettings();
        $kar = $this->karyawan('render-kar@example.com');
        $jadwal = JadwalPekerjaan::create([
            'user_id' => $kar->id,
            'tanggal_kerja' => Carbon::today()->toDateString(),
            'jam_masuk' => '08:00:00', 'jam_pulang' => '16:00:00', 'status' => 'aktif',
        ]);
        // Sudah check-in hari ini -> halaman Tugas tidak redirect ke presensi masuk.
        Presensi::create([
            'user_id' => $kar->id, 'jadwal_id' => $jadwal->id,
            'tanggal' => Carbon::today()->toDateString(),
            'jam_masuk' => Carbon::today()->setTime(8, 0),
            'status_presensi' => 'hadir', 'status_verifikasi' => 'pending',
        ]);
        $tugas = DetailPekerjaan::create([
            'jadwal_id' => $jadwal->id, 'user_id' => $kar->id,
            'nama_lokasi' => 'Lokasi A', 'status' => 'disetujui',
        ]);
        BuktiPekerjaan::create([
            'detail_pekerjaan_id' => $tugas->id, 'user_id' => $kar->id,
            'foto' => ['bukti_pekerjaan/x.jpg', 'bukti_pekerjaan/y.jpg'],
            'keterangan' => 'oke', 'status' => 'pending', 'uploaded_at' => now(),
        ]);

        $this->actingAs($kar);

        $this->get(route('karyawan.beranda'))->assertOk();
        $this->get(route('karyawan.jadwal'))->assertOk();
        $this->get(route('karyawan.riwayat'))->assertOk();
        $this->get(route('karyawan.presensi.pulang'))->assertOk();
        $this->get(route('karyawan.tugas'))->assertOk();
        // Form upload multi-foto + galeri foto yang sudah ada ter-render.
        $this->get(route('karyawan.tugas.upload', ['detail_pekerjaan_id' => $tugas->id]))
            ->assertOk()->assertSee('Foto Bukti');
        // Halaman detail bukti (galeri) ter-render.
        $this->get(route('karyawan.tugas.bukti.detail', ['detail_pekerjaan_id' => $tugas->id]))
            ->assertOk()->assertSee('Foto Bukti');
    }

    public function test_render_halaman_presensi_masuk_karyawan(): void
    {
        $this->seedSettings();
        $kar = $this->karyawan('render-masuk@example.com');
        // Belum check-in -> form presensi masuk tampil (200, tidak redirect).
        $this->actingAs($kar)->get(route('karyawan.presensi.masuk'))->assertOk();
    }

    public function test_user_pakai_relasi_v3_bukan_tabel_anak_lama(): void
    {
        // V3: relasi konsolidasi ada; relasi/model anak lama tidak dipakai lagi.
        $kar = $this->karyawan();
        $this->assertTrue(method_exists($kar, 'presensiSaya'));
        $this->assertTrue(method_exists($kar, 'jadwalKerja'));
        $this->assertFalse(method_exists($kar, 'karyawan'));
        $this->assertFalse(class_exists('App\\Models\\Karyawan', false));
        $this->assertFalse(class_exists('App\\Models\\Verifikasi', false));
    }
}
