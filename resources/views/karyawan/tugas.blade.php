@extends('karyawan.layout', [
    'title' => 'Daftar Tugas',
    'subtitle' => 'Tugas harian yang ditetapkan oleh Admin.',
])

@section('content')
    <section class="card">
        <h2>Tugas Hari Ini — {{ \Carbon\Carbon::parse($today)->translatedFormat('l, d M Y') }}</h2>

        @if ($tugasHariIni->isNotEmpty())
            @foreach ($tugasHariIni as $tugas)
                @php
                    $bukti = $buktiHariIni->get($tugas->id);
                    $statusTugas = $tugas->status;
                    $statusBukti = $bukti ? $bukti->status : null;

                    // Badge untuk status tugas
                    $badgeClass = match ($statusTugas) {
                        'disetujui' => ($statusBukti ? match($statusBukti) {
                            'disetujui' => 'pill-success',
                            'ditolak'   => 'pill-danger',
                            'pending'   => 'pill-warning',
                            default     => 'pill-info',
                        } : 'pill-success'),
                        'ditolak' => 'pill-danger',
                        default   => 'pill-info',
                    };

                    $badgeText = match ($statusTugas) {
                        'disetujui' => ($statusBukti ? match($statusBukti) {
                            'disetujui' => 'BUKTI DISETUJUI',
                            'ditolak'   => 'BUKTI DITOLAK',
                            'pending'   => 'MENUNGGU REVIEW',
                            default     => 'BELUM UPLOAD',
                        } : 'DITERIMA'),
                        'ditolak' => 'DITOLAK',
                        default   => 'MENUNGGU RESPON',
                    };
                @endphp

                <div class="card" style="margin:10px 0 0; padding: 15px; border-left: 4px solid {{ $statusTugas === 'ditolak' ? '#ef4444' : ($statusTugas === 'disetujui' ? '#10b981' : '#3b82f6') }}; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
                    <div class="row" style="justify-content:space-between;align-items:center;">
                        <b style="color: #1e3a8a; font-size: 1.05em;">{{ $tugas->nama_lokasi }}</b>
                        <span class="pill {{ $badgeClass }}">{{ $badgeText }}</span>
                    </div>

                    <p class="text-muted" style="margin:6px 0 0;">
                        🕰️ {{ substr((string) optional($tugas->jadwal)->jam_masuk, 0, 5) }} - {{ substr((string) optional($tugas->jadwal)->jam_pulang, 0, 5) }}
                    </p>

                    <div style="margin-top: 8px; padding: 10px; background-color: #f3f4f6; border-radius: 8px; font-size: 0.9em;">
                        <strong>📋 Tugas:</strong> {{ $tugas->keterangan_pekerjaan ?? 'Belum ada detail tugas spesifik.' }}
                    </div>

                    <p class="text-muted" style="margin:6px 0 0; font-size: 0.85em;">
                        📍 {{ $tugas->alamat_lokasi ?? '-' }}
                    </p>

                    @if ($tugas->latitude && $tugas->longitude)
                        <a href="https://www.google.com/maps?q={{ $tugas->latitude }},{{ $tugas->longitude }}"
                           target="_blank" rel="noopener"
                           style="display:inline-flex;align-items:center;gap:6px;margin-top:8px;padding:7px 12px;background:#1d4ed8;color:#fff;border-radius:8px;font-size:0.82em;font-weight:600;text-decoration:none;">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="white">
                                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                            </svg>
                            Buka di Google Maps
                        </a>
                    @endif

                    {{-- STATUS: PENDING — Karyawan pilih Terima / Tolak --}}
                    @if ($statusTugas === 'pending')
                        <div style="margin-top: 12px; padding: 12px; background: #fffbeb; border-radius: 8px; border: 1px solid #fcd34d;">
                            <p style="margin: 0 0 10px; font-size: 0.9em; color: #92400e; font-weight: 600;">⚠️ Tugas ini menunggu respon Anda:</p>

                            {{-- Tombol Terima --}}
                            <form action="{{ route('karyawan.tugas.terima') }}" method="POST" style="display:inline;">
                                @csrf
                                <input type="hidden" name="detail_pekerjaan_id" value="{{ $tugas->id }}">
                                <button type="submit" class="btn btn-primary" style="padding:8px 20px;font-size:0.9em;">✅ Terima Tugas</button>
                            </form>

                            {{-- Tombol Tolak (toggle form) --}}
                            <button type="button" class="btn btn-secondary" style="padding:8px 20px;font-size:0.9em;background:#ef4444;color:white;border:none;" onclick="document.getElementById('tolak-form-{{ $tugas->id }}').style.display = document.getElementById('tolak-form-{{ $tugas->id }}').style.display === 'none' ? 'block' : 'none'">
                                ❌ Tolak Tugas
                            </button>

                            {{-- Form Alasan Tolak (hidden by default) --}}
                            <div id="tolak-form-{{ $tugas->id }}" style="display:none; margin-top: 10px;">
                                <form action="{{ route('karyawan.tugas.tolak') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="detail_pekerjaan_id" value="{{ $tugas->id }}">
                                    <textarea name="alasan_tolak" rows="3" placeholder="Jelaskan alasan menolak tugas ini (min. 10 karakter)..." style="width:100%;padding:10px;border:1px solid #fca5a5;border-radius:8px;font-size:14px;margin-bottom:8px;" required minlength="10">{{ old('alasan_tolak') }}</textarea>
                                    <button type="submit" class="btn" style="padding:8px 20px;font-size:0.9em;background:#b91c1c;color:white;border:none;border-radius:6px;width:100%;">
                                        Kirim Penolakan
                                    </button>
                                </form>
                            </div>
                        </div>

                    {{-- STATUS: DITERIMA — Bisa upload bukti --}}
                    @elseif ($statusTugas === 'disetujui')
                        @if ($bukti)
                            @php
                                $buktiStyle = match ($bukti->status) {
                                    'disetujui' => ['bg' => '#ecfdf5', 'border' => '#a7f3d0', 'text' => '#065f46', 'icon' => '✅', 'label' => 'Bukti Disetujui'],
                                    'ditolak'   => ['bg' => '#fef2f2', 'border' => '#fca5a5', 'text' => '#b91c1c', 'icon' => '❌', 'label' => 'Bukti Ditolak'],
                                    default     => ['bg' => '#fffbeb', 'border' => '#fcd34d', 'text' => '#92400e', 'icon' => '⏳', 'label' => 'Menunggu Review Admin'],
                                };
                            @endphp
                            <div style="margin-top:10px;padding:10px;background:{{ $buktiStyle['bg'] }};border-radius:8px;border:1px solid {{ $buktiStyle['border'] }};">
                                <strong style="color:{{ $buktiStyle['text'] }};">{{ $buktiStyle['icon'] }} {{ $buktiStyle['label'] }}</strong><br>
                                <span class="text-muted" style="font-size:0.82em;">
                                    Dikirim: {{ optional($bukti->uploaded_at)->format('d M Y H:i') }}
                                </span>
                            </div>
                            <a href="{{ route('karyawan.tugas.bukti.detail', ['detail_pekerjaan_id' => $tugas->id]) }}"
                               class="btn btn-secondary"
                               style="display:block;text-align:center;margin-top:8px;padding:9px;font-size:0.9em;">
                                🔍 Lihat Detail Bukti
                            </a>
                        @else
                            <a class="btn btn-primary" style="margin-top:10px;display:block;text-align:center;padding:10px;" href="{{ route('karyawan.tugas.upload', ['detail_pekerjaan_id' => $tugas->id]) }}">
                                📤 Upload Bukti Pekerjaan
                            </a>
                        @endif

                    {{-- STATUS: DITOLAK — Tampilkan alasan + tombol konfirmasi WhatsApp --}}
                    @elseif ($statusTugas === 'ditolak')
                        <div style="margin-top: 10px; padding: 10px; background-color: #fef2f2; border-radius: 8px; border: 1px solid #fca5a5;">
                            <strong style="color: #b91c1c;">❌ Anda menolak tugas ini</strong><br>
                            <span class="text-muted" style="font-size:0.88em;">Alasan: {{ $tugas->alasan_tolak ?? '-' }}</span>
                        </div>

                        @php $waLink = ($waKonfirmasi ?? [])[$tugas->id] ?? null; @endphp
                        @if ($waLink)
                            <a href="{{ $waLink }}" target="_blank" rel="noopener"
                               style="display:flex;align-items:center;justify-content:center;gap:8px;margin-top:10px;padding:11px;background:#25D366;color:#fff;border-radius:8px;font-size:0.9em;font-weight:700;text-decoration:none;">
                                💬 Konfirmasi Penolakan via WhatsApp
                            </a>
                        @endif
                    @endif
                </div>
            @endforeach
        @else
            <p class="text-muted">Belum ada tugas yang ditetapkan untuk hari ini.</p>
        @endif
    </section>
@endsection
