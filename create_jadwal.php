<?php
use App\Models\Jadwal_kerja;
use App\Models\Karyawan;
use Carbon\Carbon;
$today = Carbon::now()->toDateString();
$karyawans = Karyawan::all();
foreach ($karyawans as $k) {
    Jadwal_kerja::firstOrCreate(
        ['karyawan_id' => $k->id, 'tanggal' => $today],
        ['hari' => Carbon::now()->translatedFormat('l'), 'jam_masuk' => '08:00:00', 'jam_keluar' => '17:00:00', 'lokasi_kerja' => 'Kantor Pusat']
    );
}
echo "Schedules created for all Karyawans!\n";
