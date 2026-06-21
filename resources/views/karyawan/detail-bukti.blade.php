@extends('karyawan.layout', [
    'title' => 'Detail Bukti Pekerjaan',
    'subtitle' => 'Rincian bukti yang sudah Anda upload.',
])

@section('content')

    {{-- Info Tugas --}}
    <section class="card">
        <h2>Informasi Tugas</h2>
        <div style="padding:12px;background:#eff6ff;border-radius:8px;border-left:4px solid #3b82f6;">
            <b style="color:#1e3a8a;font-size:1.05em;">{{ $tugas->nama_lokasi }}</b><br>
            <span class="text-muted">📅 {{ optional($tugas->jadwal)->tanggal_kerja?->translatedFormat('l, d M Y') ?? '-' }}</span><br>
            <span class="text-muted">🕰️ {{ substr((string) optional($tugas->jadwal)->jam_masuk, 0, 5) }} - {{ substr((string) optional($tugas->jadwal)->jam_pulang, 0, 5) }}</span><br>
            @if ($tugas->alamat_lokasi)
                <span class="text-muted">📍 {{ $tugas->alamat_lokasi }}</span><br>
            @endif
            @if ($tugas->keterangan_pekerjaan)
                <span class="text-muted">📋 {{ $tugas->keterangan_pekerjaan }}</span>
            @endif
        </div>
    </section>

    {{-- Status Bukti --}}
    <section class="card">
        <h2>Status Bukti</h2>
        @php
            $statusColor = match ($bukti->status) {
                'disetujui' => ['bg' => '#ecfdf5', 'border' => '#a7f3d0', 'text' => '#065f46', 'icon' => '✅', 'label' => 'Disetujui'],
                'ditolak'   => ['bg' => '#fef2f2', 'border' => '#fca5a5', 'text' => '#b91c1c', 'icon' => '❌', 'label' => 'Ditolak'],
                default     => ['bg' => '#fffbeb', 'border' => '#fcd34d', 'text' => '#92400e', 'icon' => '⏳', 'label' => 'Menunggu Review'],
            };
        @endphp
        <div style="padding:12px;background:{{ $statusColor['bg'] }};border-radius:8px;border:1px solid {{ $statusColor['border'] }};">
            <p style="margin:0;font-weight:600;color:{{ $statusColor['text'] }};">
                {{ $statusColor['icon'] }} {{ $statusColor['label'] }}
            </p>
            <p style="margin:6px 0 0;font-size:0.85em;color:#555;">
                Dikirim: {{ optional($bukti->uploaded_at)->format('d M Y, H:i') }}
            </p>
            @if ($bukti->keterangan)
                <p style="margin:6px 0 0;font-size:0.9em;color:#333;">
                    📝 {{ $bukti->keterangan }}
                </p>
            @endif
        </div>
    </section>

    {{-- Foto Bukti (galeri Sebelum & Sesudah) --}}
    @php
        $sebelum = is_array($bukti->foto_before) ? $bukti->foto_before : [];
        $sesudah = is_array($bukti->foto_after) ? $bukti->foto_after : [];
        // Fallback data lama: galeri gabungan `foto`.
        $legacy = is_array($bukti->foto) ? $bukti->foto : [];

        $galeriBukti = [
            ['judul' => '📸 Foto Sebelum', 'warna' => '#f59e0b', 'foto' => $sebelum],
            ['judul' => '📸 Foto Sesudah', 'warna' => '#10b981', 'foto' => $sesudah],
            ['judul' => '🖼️ Foto Bukti (lama)', 'warna' => '#6b7280', 'foto' => $legacy],
        ];
        $adaFoto = count($sebelum) > 0 || count($sesudah) > 0 || count($legacy) > 0;
    @endphp
    @if ($adaFoto)
        @foreach ($galeriBukti as $g)
            @if (count($g['foto']) > 0)
                <section class="card">
                    <h2 style="color:{{ $g['warna'] }};">{{ $g['judul'] }} ({{ count($g['foto']) }})</h2>
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-top:8px;">
                        @foreach ($g['foto'] as $f)
                            <img src="{{ asset('storage/' . $f) }}"
                                 alt="Foto bukti"
                                 style="width:100%;aspect-ratio:1/1;border-radius:8px;border:1px solid #e5e7eb;object-fit:cover;cursor:pointer;"
                                 onclick="openLightbox(this.src)">
                        @endforeach
                    </div>
                </section>
            @endif
        @endforeach
        <p style="font-size:0.78em;color:#aaa;margin:-4px 0 8px;text-align:center;">Ketuk foto untuk memperbesar</p>
    @else
        <section class="card">
            <p class="text-muted" style="text-align:center;">Tidak ada foto bukti yang diupload.</p>
        </section>
    @endif

    <section class="card">
        <a href="{{ route('karyawan.tugas') }}" class="btn btn-secondary" style="width:100%;text-align:center;padding:12px;">
            ← Kembali ke Daftar Tugas
        </a>
    </section>

    {{-- Lightbox sederhana --}}
    <div id="lightbox" onclick="closeLightbox()"
         style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.9);z-index:9999;align-items:center;justify-content:center;padding:16px;">
        <img id="lightbox-img" src="" alt="Foto" style="max-width:100%;max-height:90vh;border-radius:8px;object-fit:contain;">
    </div>

    <script>
        function openLightbox(src) {
            document.getElementById('lightbox-img').src = src;
            const lb = document.getElementById('lightbox');
            lb.style.display = 'flex';
        }
        function closeLightbox() {
            document.getElementById('lightbox').style.display = 'none';
        }
    </script>

@endsection
