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
                @php
                    $isLibur = $jadwal->isHariLibur();
                    $borderColor = $isLibur
                        ? '#9ca3af'
                        : ($jadwal->status === 'dibatalkan' ? '#ef4444' : '#10b981');
                    $badgeClass = $isLibur
                        ? 'pill-warning'
                        : ($jadwal->status === 'dibatalkan' ? 'pill-danger' : 'pill-success');
                    $badgeText = $isLibur
                        ? 'LIBUR'
                        : ($jadwal->status === 'dibatalkan' ? 'DIBATALKAN' : 'AKTIF');
                @endphp

                <div class="card" style="margin:10px 0 0; padding: 15px; border-left: 4px solid {{ $borderColor }};">
                    <div class="row" style="justify-content:space-between;align-items:center;">
                        <b style="color: #1e3a8a;">{{ optional($jadwal->tanggal_kerja)->translatedFormat('l') }}</b>
                        <span class="pill {{ $badgeClass }}">{{ $badgeText }}</span>
                    </div>
                    <span class="text-muted" style="font-size: 0.85em;">{{ optional($jadwal->tanggal_kerja)->format('d M Y') }}</span>
                    @unless ($isLibur)
                        <p class="text-muted" style="margin:6px 0 0;">
                            🕰️ {{ substr((string) $jadwal->jam_masuk, 0, 5) }} - {{ substr((string) $jadwal->jam_pulang, 0, 5) }}
                        </p>
                    @endunless
                </div>
            @endforeach
        @endif
    </section>
@endsection
