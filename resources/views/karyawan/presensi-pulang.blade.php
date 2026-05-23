@extends('karyawan.layout', [
    'title' => 'Presensi Pulang',
    'subtitle' => 'Check-out dari kantor pusat.',
])

@section('content')
    <section class="card">
        <h2>Form Check-out</h2>

        @if (!$presensiHariIni || !$presensiHariIni->jam_masuk)
            <p class="text-muted">Anda belum check-in hari ini, sehingga belum bisa check-out.</p>
        @elseif ($presensiHariIni->jam_keluar)
            <p class="text-muted">Anda sudah check-out pada {{ \Carbon\Carbon::parse($presensiHariIni->jam_keluar)->format('H:i') }}.</p>

            @if ($presensiHariIni->foto_masuk || $presensiHariIni->foto_keluar)
                <div style="display:flex; gap:12px; margin-top:16px; flex-wrap:wrap;">
                    @if ($presensiHariIni->foto_masuk)
                        <div style="flex:1; min-width:130px; text-align:center;">
                            <p style="font-size:0.82em; color:#666; margin-bottom:5px;">📸 Foto Masuk</p>
                            <img src="{{ asset('storage/' . $presensiHariIni->foto_masuk) }}"
                                 alt="Foto Masuk"
                                 style="width:100%; border-radius:10px; border:2px solid #10b981; object-fit:cover;">
                        </div>
                    @endif
                    @if ($presensiHariIni->foto_keluar)
                        <div style="flex:1; min-width:130px; text-align:center;">
                            <p style="font-size:0.82em; color:#666; margin-bottom:5px;">📸 Foto Keluar</p>
                            <img src="{{ asset('storage/' . $presensiHariIni->foto_keluar) }}"
                                 alt="Foto Keluar"
                                 style="width:100%; border-radius:10px; border:2px solid #3b82f6; object-fit:cover;">
                        </div>
                    @endif
                </div>
            @endif
        @else
            @if ($errors->any())
                <div style="background-color: #ffebee; color: #c62828; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                    <ul style="margin: 0; padding-left: 20px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div style="background: #ecfdf5; padding: 12px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #10b981;">
                <p style="margin:0; font-size: 0.9em; color: #065f46;">
                    ✅ Anda check-in pada <b>{{ optional($presensiHariIni->jam_masuk)->format('H:i') }}</b>.
                    Klik tombol di bawah untuk check-out.
                </p>
            </div>

            @if ($presensiHariIni->foto_masuk)
                <div style="margin-bottom:16px; text-align:center;">
                    <p style="font-size:0.85em; color:#666; margin-bottom:6px;">📸 Foto Masuk</p>
                    <img src="{{ asset('storage/' . $presensiHariIni->foto_masuk) }}"
                         alt="Foto Masuk"
                         style="width:100%; max-width:320px; border-radius:10px; border:2px solid #10b981; object-fit:cover;">
                </div>
            @endif

            <form method="POST" action="{{ route('karyawan.presensi.pulang.submit') }}" enctype="multipart/form-data">
                @csrf

                {{-- Foto keluar (opsional) --}}
                <div class="field" style="margin-bottom: 16px;">
                    <label>📷 Foto Pulang (Opsional)</label>
                    <div style="margin-top:6px;">
                        <button type="button" class="btn btn-secondary" onclick="openCamera()" style="width:100%;padding:10px;">Buka Kamera</button>
                    </div>
                    <input type="file" name="foto_keluar" accept="image/*" style="margin-top:8px;">
                    <input type="hidden" name="foto_keluar_base64" id="foto_keluar_base64">
                    <video id="video_keluar" autoplay playsinline style="width:100%;border-radius:8px;display:none;margin-top:8px;"></video>
                    <canvas id="canvas_keluar" style="display:none;"></canvas>
                    <img id="preview_keluar" style="width:100%;border-radius:8px;display:none;margin-top:8px;">
                    <button type="button" id="capture_keluar" class="btn btn-primary" style="display:none;margin-top:8px;width:100%;padding:10px;" onclick="capturePhoto()">📸 Ambil Foto</button>
                </div>

                <button class="btn btn-primary" type="submit" style="width:100%;padding:14px;font-size:1em;">
                    ✅ Kirim Presensi Pulang
                </button>
            </form>
        @endif
    </section>

    <script>
    let stream = null;

    function openCamera() {
        const video = document.getElementById('video_keluar');
        const captureBtn = document.getElementById('capture_keluar');

        if (stream) {
            stream.getTracks().forEach(t => t.stop());
        }

        navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 720 } }
        }).then(s => {
            stream = s;
            video.srcObject = s;
            video.style.display = 'block';
            captureBtn.style.display = 'block';
        }).catch(err => {
            alert('Tidak bisa membuka kamera: ' + err.message);
        });
    }

    function capturePhoto() {
        const video = document.getElementById('video_keluar');
        const canvas = document.getElementById('canvas_keluar');
        const preview = document.getElementById('preview_keluar');
        const base64Input = document.getElementById('foto_keluar_base64');
        const captureBtn = document.getElementById('capture_keluar');

        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);

        const dataUrl = canvas.toDataURL('image/jpeg', 0.8);
        base64Input.value = dataUrl;
        preview.src = dataUrl;
        preview.style.display = 'block';

        video.style.display = 'none';
        captureBtn.style.display = 'none';
        if (stream) {
            stream.getTracks().forEach(t => t.stop());
        }
    }
    </script>
@endsection
