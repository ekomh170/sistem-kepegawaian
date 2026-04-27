<?php

use App\Http\Controllers\KaryawanMobileController;
use App\Http\Controllers\LaporanExportController;
use App\Http\Controllers\PresensiController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (!Auth::check()) {
        return redirect('/admin/login');
    }

    return match (Auth::user()?->role) {
        'karyawan' => redirect()->route('karyawan.beranda'),
        'admin', 'supervisor' => redirect('/admin'),
        default => redirect('/admin/login'),
    };
});

Route::get('/login', function () {
    return redirect('/admin/login');
});

/**
 * Employee Attendance Routes
 * UC-07 sampai UC-12: Presensi, Jadwal, Validasi GPS, Upload Foto, Riwayat
 * Requires authentication
 */
Route::middleware(['auth'])->group(function () {
    // UC-04: Export laporan presensi (Admin & Supervisor)
    Route::get('/laporan/export', [LaporanExportController::class, 'exportCsv'])
        ->middleware('role:admin,supervisor')
        ->name('laporan.export');

    // Halaman mobile Karyawan (UC-07, UC-08, UC-09, UC-10, UC-12)
    Route::prefix('karyawan')
        ->name('karyawan.')
        ->middleware('role:karyawan')
        ->group(function () {
            Route::get('/beranda', [KaryawanMobileController::class, 'beranda'])->name('beranda');
            Route::get('/presensi/masuk', [KaryawanMobileController::class, 'formPresensiMasuk'])->name('presensi.masuk');
            Route::post('/presensi/masuk', [KaryawanMobileController::class, 'submitPresensiMasuk'])->name('presensi.masuk.submit');
            Route::get('/presensi/pulang', [KaryawanMobileController::class, 'formPresensiPulang'])->name('presensi.pulang');
            Route::post('/presensi/pulang', [KaryawanMobileController::class, 'submitPresensiPulang'])->name('presensi.pulang.submit');
            Route::get('/jadwal', [KaryawanMobileController::class, 'jadwalMingguan'])->name('jadwal');
            Route::get('/riwayat', [KaryawanMobileController::class, 'riwayat'])->name('riwayat');
            Route::post('/logout', [KaryawanMobileController::class, 'logout'])->name('logout');
        });

    // UC-10: Melihat Jadwal Kerja
    Route::get('/api/jadwal', [PresensiController::class, 'viewSchedule'])->name('presensi.jadwal');

    // UC-11: Validasi Lokasi GPS
    Route::post('/api/validasi-gps', [PresensiController::class, 'validateGPS'])->name('presensi.validasi-gps');

    // UC-07: Check-in (Presensi Masuk)
    Route::post('/api/presensi/check-in', [PresensiController::class, 'checkIn'])->name('presensi.check-in');

    // UC-08: Check-out (Presensi Pulang)
    Route::post('/api/presensi/check-out', [PresensiController::class, 'checkOut'])->name('presensi.check-out');

    // UC-09: Upload Foto Presensi
    Route::post('/api/presensi/upload-foto', [PresensiController::class, 'uploadFoto'])->name('presensi.upload-foto');

    // UC-12: Melihat Riwayat Presensi
    Route::get('/api/presensi/history', [PresensiController::class, 'viewHistory'])->name('presensi.history');

    // UC-03 & UC-04: Monitoring Real-Time & Laporan Presensi (Supervisor/Admin)
    Route::get('/api/presensi/current', [PresensiController::class, 'getCurrentAttendance'])
        ->middleware('role:admin,supervisor')
        ->name('presensi.current');
});
