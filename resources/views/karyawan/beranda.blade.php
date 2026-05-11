@extends('karyawan.layout', [
    'title' => 'Beranda Karyawan',
    'subtitle' => 'Sistem Informasi ' . \App\Models\Setting::get('nama_perusahaan', 'Kepegawaian'),
])

@section('content')
    {{-- SECTION 1: PRESENSI HARI INI --}}
    <section class="card">
        <h2>Presensi Hari Ini</h2>

        @if (isset($jadwalHariIni) && $jadwalHariIni && $jadwalHariIni->isHariLibur())
            <div class="alert alert-warning" style="padding:10px;border-radius:8px;background:#fef3c7;border:1px solid #fcd34d;">
                Hari ini ditandai sebagai <b>HARI LIBUR</b>. Presensi tidak diperlukan.
            </div>
        @elseif ($presensiHariIni)
            @php
                $statusClass = match ($presensiHariIni->status_presensi) {
                    'hadir' => 'pill-success',
                    'terlambat' => 'pill-warning',
                    'tidak_hadir' => 'pill-danger',
                    default => 'pill-info',
                };
            @endphp

            <div class="row" style="align-items:center;justify-content:space-between;">
                <span class="pill {{ $statusClass }}">{{ strtoupper($presensiHariIni->status_presensi) }}</span>
                <span class="text-muted">{{ optional($presensiHariIni->tanggal)->format('d M Y') }}</span>
            </div>

            <p class="text-muted" style="margin-top:10px;">
                Jam masuk: {{ optional($presensiHariIni->jam_masuk)->format('H:i') ?? '-' }}<br>
                Jam keluar: {{ optional($presensiHariIni->jam_keluar)->format('H:i') ?? '-' }}
                @if ($presensiHariIni->menit_terlambat > 0)
                    <br>Telat: {{ $presensiHariIni->menit_terlambat }} menit (Rp {{ number_format((float) $presensiHariIni->potongan_terlambat, 0, ',', '.') }})
                @endif
            </p>
        @else
            <p class="text-muted">Belum ada data presensi untuk hari ini.</p>
        @endif

        @unless (isset($jadwalHariIni) && $jadwalHariIni && $jadwalHariIni->isHariLibur())
            <div class="row" style="margin-top:12px;">
                <a class="btn btn-primary" href="{{ route('karyawan.presensi.masuk') }}">Presensi Masuk</a>
                <a class="btn btn-secondary" href="{{ route('karyawan.presensi.pulang') }}">Presensi Pulang</a>
            </div>
        @endunless
    </section>

    {{-- SECTION 2: DAFTAR TUGAS HARI INI --}}
    <section class="card">
        <div class="row" style="justify-content:space-between;align-items:center;">
            <h2 style="margin:0;">Tugas Hari Ini</h2>
            <a class="btn btn-primary" style="padding:6px 14px;font-size:0.85em;" href="{{ route('karyawan.tugas') }}">Lihat Semua</a>
        </div>

        @if ($tugasHariIni->isNotEmpty())
            @foreach ($tugasHariIni as $tugas)
                @php
                    $statusTugas = $tugas->status;
                    $bukti = $buktiHariIni->get($tugas->id);

                    $borderColor = match($statusTugas) {
                        'disetujui' => '#10b981',
                        'ditolak' => '#ef4444',
                        default => '#f59e0b',
                    };
                    $badgeClass = match($statusTugas) {
                        'disetujui' => 'pill-success',
                        'ditolak' => 'pill-danger',
                        default => 'pill-warning',
                    };
                    $badgeText = match($statusTugas) {
                        'disetujui' => 'DITERIMA',
                        'ditolak' => 'DITOLAK',
                        default => 'MENUNGGU',
                    };
                @endphp

                <div class="card" style="margin:10px 0 0; padding: 12px; border-left: 4px solid {{ $borderColor }};">
                    <div class="row" style="justify-content:space-between;align-items:center;">
                        <b style="color: #1e3a8a;">{{ $tugas->nama_lokasi }}</b>
                        <span class="pill {{ $badgeClass }}">{{ $badgeText }}</span>
                    </div>
                    <p class="text-muted" style="margin:4px 0 0;font-size:0.88em;">
                        🕰️ {{ substr((string) optional($tugas->jadwal)->jam_masuk, 0, 5) }} - {{ substr((string) optional($tugas->jadwal)->jam_pulang, 0, 5) }}
                    </p>
                    <p class="text-muted" style="margin:4px 0 0;font-size:0.85em;">
                        {{ $tugas->keterangan_pekerjaan ?? '-' }}
                    </p>
                    @if ($tugas->latitude && $tugas->longitude)
                        <a href="https://www.google.com/maps?q={{ $tugas->latitude }},{{ $tugas->longitude }}"
                           target="_blank" rel="noopener"
                           style="display:inline-flex;align-items:center;gap:5px;margin-top:6px;padding:5px 10px;background:#1d4ed8;color:#fff;border-radius:7px;font-size:0.78em;font-weight:600;text-decoration:none;">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="white">
                                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                            </svg>
                            Google Maps
                        </a>
                    @endif
                </div>
            @endforeach
        @else
            <p class="text-muted" style="margin-top:10px;">Belum ada tugas untuk hari ini.</p>
        @endif
    </section>
@endsection
