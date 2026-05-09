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
                    $borderColor = match($jadwal->status) {
                        'disetujui' => '#10b981',
                        'ditolak' => '#ef4444',
                        default => '#3b82f6',
                    };
                    $badgeClass = match($jadwal->status) {
                        'disetujui' => 'pill-success',
                        'ditolak' => 'pill-danger',
                        default => 'pill-warning',
                    };
                    $badgeText = match($jadwal->status) {
                        'disetujui' => 'DITERIMA',
                        'ditolak' => 'DITOLAK',
                        default => 'MENUNGGU',
                    };
                @endphp

                <div class="card" style="margin:10px 0 0; padding: 15px; border-left: 4px solid {{ $borderColor }};">
                    <div class="row" style="justify-content:space-between;align-items:center;">
                        <b style="color: #1e3a8a;">{{ optional($jadwal->tanggal)->translatedFormat('l') }}</b>
                        <span class="pill {{ $badgeClass }}">{{ $badgeText }}</span>
                    </div>
                    <span class="text-muted" style="font-size: 0.85em;">{{ optional($jadwal->tanggal)->format('d M Y') }}</span>
                    <p class="text-muted" style="margin:6px 0 0;">
                        🕰️ {{ substr((string) $jadwal->jam_masuk, 0, 5) }} - {{ substr((string) $jadwal->jam_pulang, 0, 5) }}
                        <br>📍 {{ $jadwal->nama_lokasi }}
                    </p>
                    <div style="margin-top: 10px; padding: 8px; background-color: #f3f4f6; border-radius: 6px; font-size: 0.9em;">
                        <strong>Tugas:</strong> {{ $jadwal->keterangan_pekerjaan ?? 'Pekerjaan rutin (belum ada detail spesifik)' }}
                    </div>
                    @if ($jadwal->status === 'ditolak' && $jadwal->alasan_tolak)
                        <div style="margin-top: 8px; padding: 8px; background-color: #fef2f2; border-radius: 6px; font-size: 0.85em; border: 1px solid #fca5a5;">
                            <strong style="color: #b91c1c;">Alasan ditolak:</strong> {{ $jadwal->alasan_tolak }}
                        </div>
                    @endif
                </div>
            @endforeach
        @endif
    </section>
@endsection
