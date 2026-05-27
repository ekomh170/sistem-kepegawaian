@extends('karyawan.layout', [
    'title' => 'Upload Bukti Pekerjaan',
    'subtitle' => 'Upload foto before & after untuk tugas ini.',
])

@section('content')
    {{-- Info Tugas --}}
    <section class="card">
        <h2>Detail Tugas</h2>
        <div style="padding: 12px; background-color: #eff6ff; border-radius: 8px; border-left: 4px solid #3b82f6;">
            <b style="color: #1e3a8a;">{{ $tugas->nama_lokasi }}</b><br>
            <span class="text-muted">🕰️ {{ substr((string) optional($tugas->jadwal)->jam_masuk, 0, 5) }} - {{ substr((string) optional($tugas->jadwal)->jam_pulang, 0, 5) }}</span><br>
            <span class="text-muted">📍 {{ $tugas->alamat_lokasi ?? '-' }}</span><br>
            <span class="text-muted">📋 {{ $tugas->keterangan_pekerjaan ?? 'Pekerjaan rutin' }}</span>
        </div>
    </section>

    @if ($buktiExisting)
        <section class="card">
            <h2>Bukti Sudah Diupload</h2>
            <div class="alert alert-success">Anda sudah mengupload bukti untuk tugas ini (Status: <b>{{ strtoupper($buktiExisting->status) }}</b>).</div>
            <p class="text-muted">Diupload pada: {{ optional($buktiExisting->uploaded_at)->format('d M Y H:i') }}</p>
            <p>{{ $buktiExisting->keterangan ?? '-' }}</p>
            <a class="btn btn-secondary" href="{{ route('karyawan.tugas') }}">← Kembali ke Daftar Tugas</a>
        </section>
    @else
        {{-- Form Upload --}}
        <form action="{{ route('karyawan.tugas.upload.submit') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="detail_pekerjaan_id" value="{{ $tugas->id }}">

            {{-- Foto Before --}}
            <section class="card" style="border-left:5px solid #f59e0b;background:#fffbeb;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                    <span style="background:#f59e0b;color:#fff;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;letter-spacing:0.5px;">BEFORE</span>
                    <h2 style="margin:0;font-size:15px;color:#92400e;">Foto Sebelum Dikerjakan</h2>
                </div>
                <div class="field" style="margin-bottom:0;">
                    <div style="margin-top:6px;">
                        <button type="button" class="btn btn-secondary" onclick="openCamera('before')" style="width:100%;padding:10px;">📷 Buka Kamera</button>
                    </div>
                    <input type="file" name="foto_before" accept="image/*" id="file_before" style="margin-top:8px;">
                    <input type="hidden" name="foto_before_base64" id="foto_before_base64">
                    <video id="video_before" autoplay playsinline style="width:100%;border-radius:8px;display:none;margin-top:8px;"></video>
                    <canvas id="canvas_before" style="display:none;"></canvas>
                    <img id="preview_before" style="width:100%;border-radius:8px;display:none;margin-top:8px;border:2px solid #f59e0b;">
                    <button type="button" id="capture_before" class="btn btn-primary" style="display:none;margin-top:8px;width:100%;padding:10px;" onclick="capturePhoto('before')">📸 Ambil Foto Before</button>
                </div>
            </section>

            {{-- Pemisah visual --}}
            <div style="display:flex;align-items:center;gap:10px;margin:6px 4px 10px;">
                <span style="flex:1;height:1px;background:#cbd5e1;"></span>
                <span style="font-size:11px;font-weight:700;color:#64748b;letter-spacing:1px;">⬇ LANJUT KE FOTO AFTER ⬇</span>
                <span style="flex:1;height:1px;background:#cbd5e1;"></span>
            </div>

            {{-- Foto After --}}
            <section class="card" style="border-left:5px solid #10b981;background:#ecfdf5;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                    <span style="background:#10b981;color:#fff;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;letter-spacing:0.5px;">AFTER</span>
                    <h2 style="margin:0;font-size:15px;color:#065f46;">Foto Sesudah Dikerjakan</h2>
                </div>
                <div class="field" style="margin-bottom:0;">
                    <div style="margin-top:6px;">
                        <button type="button" class="btn btn-secondary" onclick="openCamera('after')" style="width:100%;padding:10px;">📷 Buka Kamera</button>
                    </div>
                    <input type="file" name="foto_after" accept="image/*" id="file_after" style="margin-top:8px;">
                    <input type="hidden" name="foto_after_base64" id="foto_after_base64">
                    <video id="video_after" autoplay playsinline style="width:100%;border-radius:8px;display:none;margin-top:8px;"></video>
                    <canvas id="canvas_after" style="display:none;"></canvas>
                    <img id="preview_after" style="width:100%;border-radius:8px;display:none;margin-top:8px;border:2px solid #10b981;">
                    <button type="button" id="capture_after" class="btn btn-primary" style="display:none;margin-top:8px;width:100%;padding:10px;" onclick="capturePhoto('after')">📸 Ambil Foto After</button>
                </div>
            </section>

            {{-- Keterangan + Submit --}}
            <section class="card">
                <div class="field" style="margin-bottom: 16px;">
                    <label>📝 Keterangan Hasil Kerja</label>
                    <textarea name="keterangan" rows="3" placeholder="Jelaskan hasil pekerjaan..." style="width:100%;padding:10px;border:1px solid #d9e0ef;border-radius:8px;font-size:14px;margin-top:6px;">{{ old('keterangan') }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;padding:14px;font-size:1em;">
                    ✅ Upload Bukti Pekerjaan
                </button>
            </section>
        </form>
    @endif

    <script>
    let streams = {};

    function openCamera(type) {
        const video = document.getElementById('video_' + type);
        const captureBtn = document.getElementById('capture_' + type);

        if (streams[type]) {
            streams[type].getTracks().forEach(t => t.stop());
        }

        navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } }
        }).then(stream => {
            streams[type] = stream;
            video.srcObject = stream;
            video.style.display = 'block';
            captureBtn.style.display = 'block';
        }).catch(err => {
            alert('Tidak bisa membuka kamera: ' + err.message);
        });
    }

    function capturePhoto(type) {
        const video = document.getElementById('video_' + type);
        const canvas = document.getElementById('canvas_' + type);
        const preview = document.getElementById('preview_' + type);
        const base64Input = document.getElementById('foto_' + type + '_base64');
        const captureBtn = document.getElementById('capture_' + type);

        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);

        const dataUrl = canvas.toDataURL('image/jpeg', 0.8);
        base64Input.value = dataUrl;
        preview.src = dataUrl;
        preview.style.display = 'block';

        video.style.display = 'none';
        captureBtn.style.display = 'none';
        if (streams[type]) {
            streams[type].getTracks().forEach(t => t.stop());
        }
    }
    </script>
@endsection
