@extends('karyawan.layout', [
    'title' => 'Riwayat Aktivitas',
    'subtitle' => 'Riwayat kehadiran dan pekerjaan 30 hari terakhir.',
])

@section('content')
    <section class="card">
        <h2>Estimasi Gaji — {{ $namaBulan }}</h2>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:10px;">
            <div style="background:#f0fdf4;border-radius:8px;padding:10px;">
                <p class="text-muted" style="margin:0;font-size:0.8em;">Gaji Pokok</p>
                <p style="margin:4px 0 0;font-weight:700;color:#15803d;">Rp {{ number_format((float)$gajiPokok, 0, ',', '.') }}</p>
            </div>
            <div style="background:#fef2f2;border-radius:8px;padding:10px;">
                <p class="text-muted" style="margin:0;font-size:0.8em;">Total Potongan</p>
                <p style="margin:4px 0 0;font-weight:700;color:#dc2626;">- Rp {{ number_format((float)$totalPotongan, 0, ',', '.') }}</p>
            </div>
        </div>
        <div style="background:#eff6ff;border-radius:8px;padding:12px;margin-top:10px;display:flex;justify-content:space-between;align-items:center;">
            <div>
                <p class="text-muted" style="margin:0;font-size:0.8em;">Estimasi Diterima</p>
                <p style="margin:4px 0 0;font-weight:700;font-size:1.15em;color:#1d4ed8;">Rp {{ number_format((float)$estimasiGaji, 0, ',', '.') }}</p>
            </div>
            <span class="pill pill-info" style="font-size:0.75em;">{{ $hariHadir }} hari hadir</span>
        </div>
        <p class="text-muted" style="margin-top:8px;font-size:0.75em;">* Estimasi berdasarkan presensi yang sudah tercatat. Angka final ditentukan oleh admin.</p>
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

    {{-- Tab switcher --}}
    <div style="display:flex; gap:8px; margin-bottom:4px;">
        <button id="tab-presensi" onclick="switchTab('presensi')"
            style="flex:1; padding:10px; border-radius:10px; border:none; font-weight:600; font-size:0.95em; cursor:pointer; background:#fef3c7; color:#92400e;">
            Presensi
        </button>
        <button id="tab-pekerjaan" onclick="switchTab('pekerjaan')"
            style="flex:1; padding:10px; border-radius:10px; border:1px solid #e5e7eb; font-weight:600; font-size:0.95em; cursor:pointer; background:#fff; color:#6b7280;">
            Pekerjaan
        </button>
    </div>

    {{-- Panel: Riwayat Presensi --}}
    <div id="panel-presensi">
        @if ($riwayat->isEmpty())
            <section class="card"><p class="text-muted">Belum ada data riwayat presensi.</p></section>
        @else
            @foreach ($riwayat as $item)
                @php
                    $statusClass = match ($item->status_presensi) {
                        'hadir'       => 'pill-success',
                        'terlambat'   => 'pill-warning',
                        'tidak_hadir' => 'pill-danger',
                        default       => 'pill-info',
                    };
                @endphp
                <div class="card" style="margin-bottom:8px;">
                    <div class="row" style="justify-content:space-between;align-items:center;">
                        <b>{{ optional($item->tanggal)->format('d M Y') }}</b>
                        <span class="pill {{ $statusClass }}">{{ strtoupper($item->status_presensi) }}</span>
                    </div>
                    <p class="text-muted" style="margin:6px 0 0;">
                        Masuk: {{ optional($item->jam_masuk)->format('H:i') ?? '-' }} | Keluar: {{ optional($item->jam_keluar)->format('H:i') ?? '-' }}
                        @if ($item->menit_terlambat > 0)
                            <br>Telat: {{ $item->menit_terlambat }} menit (Rp {{ number_format((float) $item->potongan_terlambat, 0, ',', '.') }})
                        @endif
                    </p>
                </div>
            @endforeach
        @endif
    </div>

    {{-- Panel: Riwayat Pekerjaan --}}
    <div id="panel-pekerjaan" style="display:none;">
        @if ($riwayatPekerjaan->isEmpty())
            <section class="card"><p class="text-muted">Belum ada data riwayat pekerjaan.</p></section>
        @else
            @foreach ($riwayatPekerjaan as $pekerjaan)
                @php
                    $badgeClass  = match ($pekerjaan->status) { 'disetujui' => 'pill-success', 'ditolak' => 'pill-danger', default => 'pill-info' };
                    $badgeText   = match ($pekerjaan->status) { 'disetujui' => 'DITERIMA', 'ditolak' => 'DITOLAK', default => 'PENDING' };
                    $borderColor = match ($pekerjaan->status) { 'disetujui' => '#10b981', 'ditolak' => '#ef4444', default => '#3b82f6' };
                @endphp
                <div class="card" style="margin-bottom:8px; border-left:4px solid {{ $borderColor }};">
                    <div class="row" style="justify-content:space-between;align-items:center;">
                        <b>{{ $pekerjaan->nama_lokasi }}</b>
                        <span class="pill {{ $badgeClass }}">{{ $badgeText }}</span>
                    </div>
                    <p class="text-muted" style="margin:6px 0 0; font-size:0.88em;">
                        📅 {{ optional($pekerjaan->jadwal?->tanggal_kerja)->format('d M Y') ?? '-' }}
                        &nbsp;|&nbsp;
                        🕰️ {{ substr((string) optional($pekerjaan->jadwal)->jam_masuk, 0, 5) }} - {{ substr((string) optional($pekerjaan->jadwal)->jam_pulang, 0, 5) }}
                    </p>
                    @if ($pekerjaan->keterangan_pekerjaan)
                        <p class="text-muted" style="margin:4px 0 0; font-size:0.85em;">{{ $pekerjaan->keterangan_pekerjaan }}</p>
                    @endif
                    @if ($pekerjaan->status === 'ditolak' && $pekerjaan->alasan_tolak)
                        <p style="margin:4px 0 0; font-size:0.85em; color:#b91c1c;">Alasan: {{ $pekerjaan->alasan_tolak }}</p>
                    @endif
                    @if ($pekerjaan->latitude && $pekerjaan->longitude)
                        <a href="https://www.google.com/maps?q={{ $pekerjaan->latitude }},{{ $pekerjaan->longitude }}"
                           target="_blank" rel="noopener"
                           style="display:inline-flex;align-items:center;gap:5px;margin-top:7px;padding:5px 10px;background:#1d4ed8;color:#fff;border-radius:7px;font-size:0.78em;font-weight:600;text-decoration:none;">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="white">
                                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                            </svg>
                            Lihat di Google Maps
                        </a>
                    @endif
                </div>
            @endforeach
        @endif
    </div>

    <script>
        function switchTab(tab) {
            const isPresensi = tab === 'presensi';

            document.getElementById('panel-presensi').style.display = isPresensi ? 'block' : 'none';
            document.getElementById('panel-pekerjaan').style.display = isPresensi ? 'none' : 'block';

            const activeStyle  = 'background:#fef3c7; color:#92400e; border:none;';
            const inactiveStyle = 'background:#fff; color:#6b7280; border:1px solid #e5e7eb;';

            document.getElementById('tab-presensi').style.cssText  += isPresensi ? activeStyle : inactiveStyle;
            document.getElementById('tab-pekerjaan').style.cssText += isPresensi ? inactiveStyle : activeStyle;
        }
    </script>
@endsection
