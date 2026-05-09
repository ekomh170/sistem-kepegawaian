<?php
use App\Models\Jadwal_kerja;
use Carbon\Carbon;
$today = Carbon::now()->toDateString();
$schedules = Jadwal_kerja::where('tanggal', $today)->get();
echo "Jadwal untuk hari ini ($today):\n";
foreach ($schedules as $s) {
    echo "Karyawan ID: " . $s->karyawan_id . " - Hari: " . $s->hari . "\n";
}
$karyawanIdAuth = App\Models\Karyawan::where('user_id', 3)->first(); // assuming id 3 is karyawan?
echo "Karyawan User ID 3 -> " . ($karyawanIdAuth ? $karyawanIdAuth->id : 'Not Found') . "\n";
