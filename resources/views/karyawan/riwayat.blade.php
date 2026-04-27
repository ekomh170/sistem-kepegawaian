@extends('karyawan.layout', [
    'title' => 'Riwayat Presensi',
    'subtitle' => 'Riwayat kehadiran dan estimasi gaji terbaru.',
])

@section('content')
    <section class="card">
        <h2>Estimasi Gaji</h2>
        @if ($laporanTerbaru)
            <div class="stats" style="grid-template-columns:1fr;">
                <div class="stat-item">
                    <small class="text-muted">Periode {{ $laporanTerbaru->periode }}</small>
                    <b>Rp {{ number_format((float) $laporanTerbaru->estimasi_gaji, 0, ',', '.') }}</b>
                </div>
            </div>
        @else
            <p class="text-muted">Belum ada laporan gaji untuk ditampilkan.</p>
        @endif
    </section>

    <section class="card">
        <h2>Ringkasan 30 Hari Terakhir</h2>
        <div class="stats">
            <div class="stat-item"><small>Hadir</small><b>{{ $summary['hadir'] }}</b></div>
            <div class="stat-item"><small>Terlambat</small><b>{{ $summary['terlambat'] }}</b></div>
            <div class="stat-item"><small>Tidak Hadir</small><b>{{ $summary['tidak_hadir'] }}</b></div>
            <div class="stat-item"><small>Izin</small><b>{{ $summary['izin'] }}</b></div>
        </div>
    </section>

    <section class="card">
        <h2>Daftar Presensi</h2>

        @if ($riwayat->isEmpty())
            <p class="text-muted">Belum ada data riwayat presensi.</p>
        @else
            @foreach ($riwayat as $item)
                @php
                    $statusClass = match ($item->status) {
                        'hadir' => 'pill-success',
                        'terlambat' => 'pill-warning',
                        'tidak_hadir' => 'pill-danger',
                        default => 'pill-info',
                    };
                @endphp

                <div class="card" style="margin:10px 0 0;">
                    <div class="row" style="justify-content:space-between;align-items:center;">
                        <b>{{ optional($item->tgl_presensi)->format('d M Y') }}</b>
                        <span class="pill {{ $statusClass }}">{{ strtoupper($item->status) }}</span>
                    </div>
                    <p class="text-muted" style="margin:6px 0 0;">
                        Masuk: {{ optional($item->jam_masuk)->format('H:i') ?? '-' }} | Pulang: {{ optional($item->jam_keluar)->format('H:i') ?? '-' }}
                        <br>Durasi: {{ $item->durasi_menit ?? 0 }} menit
                    </p>
                </div>
            @endforeach
        @endif
    </section>
@endsection
