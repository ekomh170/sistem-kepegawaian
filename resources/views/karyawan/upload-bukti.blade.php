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
        <section class="card">
            <h2>Upload Foto Bukti</h2>

            <form action="{{ route('karyawan.tugas.upload.submit') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="detail_pekerjaan_id" value="{{ $tugas->id }}">

                {{-- Foto Before --}}
                <div class="field" style="margin-bottom: 16px;">
                    <label>📷 Foto Before (Sebelum Dikerjakan)</label>
                    <div style="margin-top:6px;">
                        <button type="button" class="btn btn-secondary" onclick="openCamera('before')" style="width:100%;padding:10px;">Buka Kamera</button>
                    </div>
                    <input type="file" name="foto_before" accept="image/*" id="file_before" style="margin-top:8px;">
                    <input type="hidden" name="foto_before_base64" id="foto_before_base64">
                    <video id="video_before" autoplay playsinline style="width:100%;border-radius:8px;display:none;margin-top:8px;"></video>
                    <canvas id="canvas_before" style="display:none;"></canvas>
                    <img id="preview_before" style="width:100%;border-radius:8px;display:none;margin-top:8px;">
                    <button type="button" id="capture_before" class="btn btn-primary" style="display:none;margin-top:8px;width:100%;padding:10px;" onclick="capturePhoto('before')">📸 Ambil Foto Before</button>
                </div>

                {{-- Foto After --}}
                <div class="field" style="margin-bottom: 16px;">
                    <label>📷 Foto After (Sesudah Dikerjakan)</label>
                    <div style="margin-top:6px;">
                        <button type="button" class="btn btn-secondary" onclick="openCamera('after')" style="width:100%;padding:10px;">Buka Kamera</button>
                    </div>
                    <input type="file" name="foto_after" accept="image/*" id="file_after" style="margin-top:8px;">
                    <input type="hidden" name="foto_after_base64" id="foto_after_base64">
                    <video id="video_after" autoplay playsinline style="width:100%;border-radius:8px;display:none;margin-top:8px;"></video>
                    <canvas id="canvas_after" style="display:none;"></canvas>
                    <img id="preview_after" style="width:100%;border-radius:8px;display:none;margin-top:8px;">
                    <button type="button" id="capture_after" class="btn btn-primary" style="display:none;margin-top:8px;width:100%;padding:10px;" onclick="capturePhoto('after')">📸 Ambil Foto After</button>
                </div>

                {{-- Keterangan --}}
                <div class="field" style="margin-bottom: 16px;">
                    <label>📝 Keterangan Hasil Kerja</label>
                    <textarea name="keterangan" rows="3" placeholder="Jelaskan hasil pekerjaan..." style="width:100%;padding:10px;border:1px solid #d9e0ef;border-radius:8px;font-size:14px;margin-top:6px;">{{ old('keterangan') }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;padding:14px;font-size:1em;">
                    ✅ Upload Bukti Pekerjaan
                </button>
            </form>
        </section>
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
