<?php

namespace Tests\Feature;

use App\Models\BuktiPekerjaan;
use App\Models\DetailPekerjaan;
use App\Models\JadwalPekerjaan;
use App\Models\Presensi;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Uji keamanan & kebenaran fitur upload karyawan:
 *  - Bukti pekerjaan: galeri banyak foto (akumulasi, cap 20, base64 kamera, kepemilikan).
 *  - Presensi: foto masuk wajib & tersimpan.
 */
class KaryawanUploadTest extends TestCase
{
    use RefreshDatabase;

    private function karyawan(string $email = 'kar@upload.test'): User
    {
        return User::create([
            'nama' => 'Karyawan Upload',
            'email' => $email,
            'password' => 'password',
            'role' => 'karyawan',
            'nik' => 'KRY-' . substr(md5($email), 0, 6),
            'no_hp' => '0812',
            'posisi' => 'Staf',
        ]);
    }

    private function tugasMilik(User $kar): DetailPekerjaan
    {
        $jadwal = JadwalPekerjaan::create([
            'user_id' => $kar->id,
            'tanggal_kerja' => Carbon::today()->toDateString(),
            'jam_masuk' => '08:00:00',
            'jam_pulang' => '16:00:00',
            'status' => 'aktif',
        ]);

        return DetailPekerjaan::create([
            'jadwal_id' => $jadwal->id,
            'user_id' => $kar->id,
            'nama_lokasi' => 'Lokasi Uji',
            'status' => 'disetujui',
        ]);
    }

    private function seedSettings(): void
    {
        foreach ([
            ['key' => 'kantor_lat', 'value' => '-6.2', 'group' => 'lokasi', 'label' => 'Lat', 'type' => 'text'],
            ['key' => 'kantor_lng', 'value' => '106.8', 'group' => 'lokasi', 'label' => 'Lng', 'type' => 'text'],
            ['key' => 'kantor_radius', 'value' => '500', 'group' => 'lokasi', 'label' => 'Radius', 'type' => 'number'],
            ['key' => 'toleransi_menit', 'value' => '10', 'group' => 'kehadiran', 'label' => 'Tol', 'type' => 'number'],
        ] as $s) {
            Setting::updateOrCreate(['key' => $s['key']], $s);
        }
        Setting::clearCache();
    }

    // ===================== BUKTI PEKERJAAN (galeri) =====================

    public function test_upload_banyak_foto_sekaligus_masuk_galeri(): void
    {
        Storage::fake('public');
        $kar = $this->karyawan();
        $tugas = $this->tugasMilik($kar);

        $this->actingAs($kar)->post(route('presensi.bukti-pekerjaan'), [
            'detail_pekerjaan_id' => $tugas->id,
            'foto' => [
                UploadedFile::fake()->image('a.jpg'),
                UploadedFile::fake()->image('b.jpg'),
                UploadedFile::fake()->image('c.jpg'),
            ],
            'keterangan' => 'Area bersih',
        ])->assertOk()->assertJson(['success' => true]);

        $bukti = BuktiPekerjaan::where('user_id', $kar->id)->firstOrFail();
        $this->assertCount(3, $bukti->foto);
        foreach ($bukti->foto as $path) {
            $this->assertStringStartsWith('bukti_pekerjaan/', $path);
            Storage::disk('public')->assertExists($path);
        }
    }

    public function test_upload_berulang_diappend_ke_galeri_yang_sama(): void
    {
        Storage::fake('public');
        $kar = $this->karyawan();
        $tugas = $this->tugasMilik($kar);

        $this->actingAs($kar)->post(route('presensi.bukti-pekerjaan'), [
            'detail_pekerjaan_id' => $tugas->id,
            'foto' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
        ])->assertOk();

        $this->actingAs($kar)->post(route('presensi.bukti-pekerjaan'), [
            'detail_pekerjaan_id' => $tugas->id,
            'foto' => [UploadedFile::fake()->image('c.jpg')],
        ])->assertOk();

        // Tetap 1 record bukti per tugas, foto terakumulasi (2 + 1 = 3).
        $this->assertSame(1, BuktiPekerjaan::where('detail_pekerjaan_id', $tugas->id)->count());
        $this->assertCount(3, BuktiPekerjaan::where('detail_pekerjaan_id', $tugas->id)->first()->foto);
    }

    public function test_maksimal_20_foto_ditegakkan(): void
    {
        Storage::fake('public');
        $kar = $this->karyawan();
        $tugas = $this->tugasMilik($kar);

        $duaPuluh = [];
        for ($i = 0; $i < 20; $i++) {
            $duaPuluh[] = UploadedFile::fake()->image("f{$i}.jpg");
        }

        // 20 foto pertama diterima.
        $this->actingAs($kar)->post(route('presensi.bukti-pekerjaan'), [
            'detail_pekerjaan_id' => $tugas->id,
            'foto' => $duaPuluh,
        ])->assertOk();

        // Foto ke-21 ditolak.
        $this->actingAs($kar)->post(route('presensi.bukti-pekerjaan'), [
            'detail_pekerjaan_id' => $tugas->id,
            'foto' => [UploadedFile::fake()->image('lebih.jpg')],
        ])->assertStatus(422)->assertJson(['success' => false]);

        $this->assertCount(20, BuktiPekerjaan::where('detail_pekerjaan_id', $tugas->id)->first()->foto);
    }

    public function test_upload_foto_base64_kamera_tersimpan(): void
    {
        Storage::fake('public');
        $kar = $this->karyawan();
        $tugas = $this->tugasMilik($kar);

        $this->actingAs($kar)->postJson(route('presensi.bukti-pekerjaan'), [
            'detail_pekerjaan_id' => $tugas->id,
            'foto_base64' => ['data:image/jpeg;base64,' . base64_encode('img-kamera')],
        ])->assertOk()->assertJson(['success' => true]);

        $bukti = BuktiPekerjaan::where('user_id', $kar->id)->firstOrFail();
        $this->assertCount(1, $bukti->foto);
        Storage::disk('public')->assertExists($bukti->foto[0]);
    }

    public function test_upload_tanpa_foto_ditolak(): void
    {
        Storage::fake('public');
        $kar = $this->karyawan();
        $tugas = $this->tugasMilik($kar);

        $this->actingAs($kar)->postJson(route('presensi.bukti-pekerjaan'), [
            'detail_pekerjaan_id' => $tugas->id,
            'keterangan' => 'tanpa foto',
        ])->assertStatus(422)->assertJson(['success' => false]);

        $this->assertSame(0, BuktiPekerjaan::count());
    }

    public function test_tidak_bisa_upload_ke_tugas_milik_karyawan_lain(): void
    {
        Storage::fake('public');
        $kar = $this->karyawan('saya@upload.test');
        $orang_lain = $this->karyawan('lain@upload.test');
        $tugasOrangLain = $this->tugasMilik($orang_lain);

        // Karyawan lain tidak boleh upload ke tugas yang bukan miliknya (IDOR).
        $this->actingAs($kar)->post(route('presensi.bukti-pekerjaan'), [
            'detail_pekerjaan_id' => $tugasOrangLain->id,
            'foto' => [UploadedFile::fake()->image('a.jpg')],
        ])->assertNotFound();

        $this->assertSame(0, BuktiPekerjaan::count());
    }

    // ============ BUKTI PEKERJAAN (galeri Sebelum & Sesudah) ============

    public function test_upload_foto_sebelum_dan_sesudah_tersimpan_terpisah(): void
    {
        Storage::fake('public');
        $kar = $this->karyawan();
        $tugas = $this->tugasMilik($kar);

        $this->actingAs($kar)->post(route('presensi.bukti-pekerjaan'), [
            'detail_pekerjaan_id' => $tugas->id,
            'foto_before' => [
                UploadedFile::fake()->image('b1.jpg'),
                UploadedFile::fake()->image('b2.jpg'),
            ],
            'foto_after' => [
                UploadedFile::fake()->image('a1.jpg'),
            ],
            'keterangan' => 'Sebelum 2, sesudah 1',
        ])->assertOk()->assertJson([
            'success' => true,
            'jumlah_sebelum' => 2,
            'jumlah_sesudah' => 1,
            'jumlah_foto' => 3,
        ]);

        $bukti = BuktiPekerjaan::where('user_id', $kar->id)->firstOrFail();
        $this->assertCount(2, $bukti->foto_before);
        $this->assertCount(1, $bukti->foto_after);
        foreach (array_merge($bukti->foto_before, $bukti->foto_after) as $path) {
            $this->assertStringStartsWith('bukti_pekerjaan/', $path);
            Storage::disk('public')->assertExists($path);
        }
    }

    public function test_galeri_sebelum_dan_sesudah_diappend_independen(): void
    {
        Storage::fake('public');
        $kar = $this->karyawan();
        $tugas = $this->tugasMilik($kar);

        // Batch 1: hanya foto sebelum.
        $this->actingAs($kar)->post(route('presensi.bukti-pekerjaan'), [
            'detail_pekerjaan_id' => $tugas->id,
            'foto_before' => [UploadedFile::fake()->image('b1.jpg')],
        ])->assertOk();

        // Batch 2: hanya foto sesudah — galeri sebelum tidak hilang.
        $this->actingAs($kar)->post(route('presensi.bukti-pekerjaan'), [
            'detail_pekerjaan_id' => $tugas->id,
            'foto_after' => [UploadedFile::fake()->image('a1.jpg'), UploadedFile::fake()->image('a2.jpg')],
        ])->assertOk();

        $this->assertSame(1, BuktiPekerjaan::where('detail_pekerjaan_id', $tugas->id)->count());
        $bukti = BuktiPekerjaan::where('detail_pekerjaan_id', $tugas->id)->firstOrFail();
        $this->assertCount(1, $bukti->foto_before);
        $this->assertCount(2, $bukti->foto_after);
    }

    public function test_foto_sebelum_sesudah_base64_kamera_tersimpan(): void
    {
        Storage::fake('public');
        $kar = $this->karyawan();
        $tugas = $this->tugasMilik($kar);

        $this->actingAs($kar)->postJson(route('presensi.bukti-pekerjaan'), [
            'detail_pekerjaan_id' => $tugas->id,
            'foto_before_base64' => ['data:image/jpeg;base64,' . base64_encode('cam-before')],
            'foto_after_base64' => ['data:image/jpeg;base64,' . base64_encode('cam-after')],
        ])->assertOk()->assertJson(['success' => true, 'jumlah_sebelum' => 1, 'jumlah_sesudah' => 1]);

        $bukti = BuktiPekerjaan::where('user_id', $kar->id)->firstOrFail();
        $this->assertCount(1, $bukti->foto_before);
        $this->assertCount(1, $bukti->foto_after);
        Storage::disk('public')->assertExists($bukti->foto_before[0]);
        Storage::disk('public')->assertExists($bukti->foto_after[0]);
    }

    public function test_cap_20_per_galeri_sebelum_ditegakkan(): void
    {
        Storage::fake('public');
        $kar = $this->karyawan();
        $tugas = $this->tugasMilik($kar);

        $duaPuluh = [];
        for ($i = 0; $i < 20; $i++) {
            $duaPuluh[] = UploadedFile::fake()->image("b{$i}.jpg");
        }
        $this->actingAs($kar)->post(route('presensi.bukti-pekerjaan'), [
            'detail_pekerjaan_id' => $tugas->id,
            'foto_before' => $duaPuluh,
        ])->assertOk();

        // Foto sebelum ke-21 ditolak.
        $this->actingAs($kar)->post(route('presensi.bukti-pekerjaan'), [
            'detail_pekerjaan_id' => $tugas->id,
            'foto_before' => [UploadedFile::fake()->image('lebih.jpg')],
        ])->assertStatus(422)->assertJson(['success' => false]);

        $this->assertCount(20, BuktiPekerjaan::where('detail_pekerjaan_id', $tugas->id)->first()->foto_before);
    }

    // ===================== PRESENSI (foto) =====================

    public function test_presensi_checkin_menyimpan_foto_masuk(): void
    {
        Storage::fake('public');
        $this->seedSettings();
        $kar = $this->karyawan();

        $this->travelTo(Carbon::today()->setTime(7, 30));

        $this->actingAs($kar)->post(route('presensi.check-in'), [
            'latitude' => -6.2,
            'longitude' => 106.8,
            'foto_masuk' => UploadedFile::fake()->image('selfie.jpg'),
        ])->assertOk()->assertJson(['success' => true, 'status' => 'hadir']);

        $presensi = Presensi::where('user_id', $kar->id)->firstOrFail();
        $this->assertNotNull($presensi->foto_masuk);
        $this->assertStringStartsWith('presensi/masuk/', $presensi->foto_masuk);
        Storage::disk('public')->assertExists($presensi->foto_masuk);
    }

    public function test_presensi_checkin_wajib_foto(): void
    {
        Storage::fake('public');
        $this->seedSettings();
        $kar = $this->karyawan();

        $this->travelTo(Carbon::today()->setTime(7, 30));

        // Tanpa foto (file maupun base64) → ditolak.
        $this->actingAs($kar)->postJson(route('presensi.check-in'), [
            'latitude' => -6.2,
            'longitude' => 106.8,
        ])->assertStatus(422)->assertJson(['success' => false]);

        $this->assertSame(0, Presensi::count());
    }
}
