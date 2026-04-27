@extends('karyawan.layout', [
    'title' => 'Beranda Karyawan',
    'subtitle' => 'Ringkasan jadwal dan status presensi hari ini.',
])

@section('content')
    <section class="card">
        <h2>Jadwal Hari Ini</h2>

        @if ($jadwalHariIni)
            <p><b>{{ $jadwalHariIni->hari }}</b>, {{ optional($jadwalHariIni->tanggal)->format('d M Y') }}</p>
            <p class="text-muted">{{ substr((string) $jadwalHariIni->jam_masuk, 0, 5) }} - {{ substr((string) $jadwalHariIni->jam_keluar, 0, 5) }} | {{ $jadwalHariIni->lokasi_kerja }}</p>
        @else
            <p class="text-muted">Belum ada jadwal kerja untuk hari ini.</p>
        @endif
    </section>

    <section class="card">
        <h2>Status Presensi Hari Ini</h2>

        @if ($presensiHariIni)
            @php
                $statusClass = match ($presensiHariIni->status) {
                    'hadir' => 'pill-success',
                    'terlambat' => 'pill-warning',
                    'tidak_hadir' => 'pill-danger',
                    default => 'pill-info',
                };
            @endphp

            <div class="row" style="align-items:center;justify-content:space-between;">
                <span class="pill {{ $statusClass }}">{{ strtoupper($presensiHariIni->status) }}</span>
                <span class="text-muted">{{ optional($presensiHariIni->tgl_presensi)->format('d M Y') }}</span>
            </div>

            <p class="text-muted" style="margin-top:10px;">
                Jam masuk: {{ optional($presensiHariIni->jam_masuk)->format('H:i') ?? '-' }}<br>
                Jam keluar: {{ optional($presensiHariIni->jam_keluar)->format('H:i') ?? '-' }}<br>
                Durasi: {{ $presensiHariIni->durasi_menit ?? 0 }} menit
            </p>
        @else
            <p class="text-muted">Belum ada data presensi untuk hari ini.</p>
        @endif

        <div class="row" style="margin-top:12px;">
            <a class="btn btn-primary" href="{{ route('karyawan.presensi.masuk') }}">Presensi Masuk</a>
            <a class="btn btn-secondary" href="{{ route('karyawan.presensi.pulang') }}">Presensi Pulang</a>
        </div>
    </section>
@endsection
