@extends('karyawan.layout', [
    'title' => 'Jadwal Mingguan',
    'subtitle' => 'Jadwal kerja 7 hari ke depan.',
])

@section('content')
    <section class="card">
        <h2>Periode {{ $startDate->format('d M') }} - {{ $endDate->format('d M Y') }}</h2>

        @if ($jadwalMingguan->isEmpty())
            <p class="text-muted">Belum ada jadwal untuk minggu berjalan.</p>
        @else
            @foreach ($jadwalMingguan as $jadwal)
                <div class="card" style="margin:10px 0 0;">
                    <div class="row" style="justify-content:space-between;align-items:center;">
                        <b>{{ $jadwal->hari }}</b>
                        <span class="text-muted">{{ optional($jadwal->tanggal)->format('d M Y') }}</span>
                    </div>
                    <p class="text-muted" style="margin:6px 0 0;">
                        {{ substr((string) $jadwal->jam_masuk, 0, 5) }} - {{ substr((string) $jadwal->jam_keluar, 0, 5) }}
                        <br>{{ $jadwal->lokasi_kerja }}
                    </p>
                </div>
            @endforeach
        @endif
    </section>
@endsection
