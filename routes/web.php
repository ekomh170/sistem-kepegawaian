<?php

use App\Http\Controllers\KaryawanMobileController;
use App\Http\Controllers\LaporanExportController;
use App\Http\Controllers\PresensiController;
use Filament\Auth\Pages\Login as FilamentLogin;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (!Auth::check()) {
        return redirect('/login');
    }

    return match (Auth::user()?->role) {
        'karyawan' => redirect()->route('karyawan.beranda'),
        'admin' => redirect('/admin'),
        'supervisor' => redirect('/supervisor'),
        default => redirect('/login'),
    };
});

$adminPanel = Filament::getPanel('admin', isStrict: false);

if ($adminPanel) {
    Route::get('/login', FilamentLogin::class)
        ->middleware($adminPanel->getMiddleware())
        ->name('login');
} else {
    Route::get('/login', fn () => abort(503, 'Admin panel is not available.'))->name('login');
}

/**
 * Employee Attendance Routes
 * UC-07 sampai UC-12: Presensi, Jadwal, Validasi GPS, Upload Foto, Riwayat
 * Requires authentication
 */
Route::middleware(['auth'])->group(function () {
    // UC-04: Export laporan rekap presensi bulanan (Admin & Supervisor)
    Route::get('/laporan/export/csv', [LaporanExportController::class, 'exportCsv'])
        ->middleware('role:admin,supervisor')
        ->name('laporan.export.csv');
    Route::get('/laporan/export/excel', [LaporanExportController::class, 'exportExcel'])
        ->middleware('role:admin,supervisor')
        ->name('laporan.export.excel');
    Route::get('/laporan/export/pdf', [LaporanExportController::class, 'exportPdf'])
        ->middleware('role:admin,supervisor')
        ->name('laporan.export.pdf');

    // Export laporan presensi harian (Admin & Supervisor)
    Route::get('/laporan/export-presensi/csv', [LaporanExportController::class, 'exportPresensiCsv'])
        ->middleware('role:admin,supervisor')
        ->name('laporan.export-presensi.csv');
    Route::get('/laporan/export-presensi/excel', [LaporanExportController::class, 'exportPresensiExcel'])
        ->middleware('role:admin,supervisor')
        ->name('laporan.export-presensi.excel');
    Route::get('/laporan/export-presensi/pdf', [LaporanExportController::class, 'exportPresensiPdf'])
        ->middleware('role:admin,supervisor')
        ->name('laporan.export-presensi.pdf');

    // Halaman mobile Karyawan (UC-07, UC-08, UC-09, UC-10, UC-12)
    Route::prefix('karyawan')
        ->name('karyawan.')
        ->middleware('role:karyawan')
        ->group(function () {
            Route::get('/', [KaryawanMobileController::class, 'beranda'])->name('beranda');
            Route::get('/presensi/masuk', [KaryawanMobileController::class, 'formPresensiMasuk'])->name('presensi.masuk');
            Route::post('/presensi/masuk', [KaryawanMobileController::class, 'submitPresensiMasuk'])->name('presensi.masuk.submit');
            Route::get('/presensi/pulang', [KaryawanMobileController::class, 'formPresensiPulang'])->name('presensi.pulang');
            Route::post('/presensi/pulang', [KaryawanMobileController::class, 'submitPresensiPulang'])->name('presensi.pulang.submit');
            Route::get('/tugas', [KaryawanMobileController::class, 'daftarTugas'])->name('tugas');
            Route::get('/tugas/upload', [KaryawanMobileController::class, 'formUploadBukti'])->name('tugas.upload');
            Route::post('/tugas/upload', [KaryawanMobileController::class, 'submitUploadBukti'])->name('tugas.upload.submit');
            Route::post('/tugas/terima', [KaryawanMobileController::class, 'terimaTugas'])->name('tugas.terima');
            Route::post('/tugas/tolak', [KaryawanMobileController::class, 'tolakTugas'])->name('tugas.tolak');
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

    // UC: Upload Bukti Pekerjaan (foto before-after)
    Route::post('/api/presensi/bukti-pekerjaan', [PresensiController::class, 'submitBuktiPekerjaan'])->name('presensi.bukti-pekerjaan');

    // UC-12: Melihat Riwayat Presensi
    Route::get('/api/presensi/history', [PresensiController::class, 'viewHistory'])->name('presensi.history');

    // UC-03 & UC-04: Monitoring Real-Time & Laporan Presensi (Supervisor/Admin)
    Route::get('/api/presensi/current', [PresensiController::class, 'getCurrentAttendance'])
        ->middleware('role:admin,supervisor')
        ->name('presensi.current');
});
