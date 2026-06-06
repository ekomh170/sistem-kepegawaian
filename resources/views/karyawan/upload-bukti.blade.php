@extends('karyawan.layout', [
    'title' => 'Upload Bukti Pekerjaan',
    'subtitle' => 'Unggah banyak foto bukti (hingga 20) untuk tugas ini.',
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

    @php
        $fotoTersimpan = ($buktiExisting && is_array($buktiExisting->foto)) ? $buktiExisting->foto : [];
        $sisaSlot = max(0, 20 - count($fotoTersimpan));
    @endphp

    {{-- Foto yang sudah diupload (galeri) --}}
    @if (count($fotoTersimpan) > 0)
        <section class="card">
            <h2>Sudah Diupload ({{ count($fotoTersimpan) }}/20)</h2>
            <p class="text-muted" style="margin:0 0 8px;">Status: <b>{{ strtoupper($buktiExisting->status) }}</b> · {{ optional($buktiExisting->uploaded_at)->format('d M Y H:i') }}</p>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;">
                @foreach ($fotoTersimpan as $f)
                    <img src="{{ asset('storage/' . $f) }}" alt="Bukti"
                         style="width:100%;aspect-ratio:1/1;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;">
                @endforeach
            </div>
        </section>
    @endif

    @if ($sisaSlot <= 0)
        <section class="card">
            <div class="alert alert-success">Foto sudah mencapai batas maksimal (20). Tidak bisa menambah lagi.</div>
            <a class="btn btn-secondary" href="{{ route('karyawan.tugas') }}" style="width:100%;text-align:center;padding:12px;">← Kembali ke Daftar Tugas</a>
        </section>
    @else
        {{-- Form Upload Multi-Foto --}}
        <form action="{{ route('karyawan.tugas.upload.submit') }}" method="POST" enctype="multipart/form-data" id="form-bukti">
            @csrf
            <input type="hidden" name="detail_pekerjaan_id" value="{{ $tugas->id }}">

            <section class="card">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                    <h2 style="margin:0;font-size:15px;">📸 Foto Bukti</h2>
                    <span id="counter" style="font-size:12px;font-weight:700;color:#2563eb;">0 dipilih (sisa slot {{ $sisaSlot }})</span>
                </div>

                {{-- Ambil dari galeri (boleh banyak sekaligus) --}}
                <label style="font-size:13px;font-weight:600;color:#374151;">Pilih dari galeri (bisa banyak):</label>
                <input type="file" name="foto[]" id="file_foto" accept="image/*" multiple style="margin:6px 0 12px;width:100%;">

                {{-- Atau ambil via kamera (bisa beberapa kali) --}}
                <div style="border-top:1px dashed #cbd5e1;padding-top:10px;">
                    <label style="font-size:13px;font-weight:600;color:#374151;">Atau ambil via kamera:</label>
                    <button type="button" class="btn btn-secondary" onclick="bukaKamera()" style="width:100%;padding:10px;margin-top:6px;">📷 Buka Kamera</button>
                    <video id="kamera" autoplay playsinline style="width:100%;border-radius:8px;display:none;margin-top:8px;"></video>
                    <button type="button" id="btn-ambil" class="btn btn-primary" style="display:none;width:100%;padding:10px;margin-top:8px;" onclick="ambilFoto()">📸 Ambil Foto (boleh berkali-kali)</button>
                    <canvas id="canvas" style="display:none;"></canvas>
                </div>

                {{-- Preview semua foto terpilih --}}
                <div id="preview" style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-top:12px;"></div>
                {{-- Hidden inputs base64 hasil kamera --}}
                <div id="base64-container"></div>
            </section>

            <section class="card">
                <div class="field" style="margin-bottom: 16px;">
                    <label>📝 Keterangan Hasil Kerja</label>
                    <textarea name="keterangan" rows="3" placeholder="Jelaskan hasil pekerjaan..." style="width:100%;padding:10px;border:1px solid #d9e0ef;border-radius:8px;font-size:14px;margin-top:6px;">{{ old('keterangan', $buktiExisting->keterangan ?? '') }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;padding:14px;font-size:1em;">
                    ✅ Upload Bukti Pekerjaan
                </button>
            </section>
        </form>
    @endif

    <script>
        const SISA_SLOT = {{ $sisaSlot }};
        let stream = null;
        let kameraFotos = []; // dataURL hasil kamera

        function totalDipilih() {
            const files = document.getElementById('file_foto')?.files?.length ?? 0;
            return files + kameraFotos.length;
        }

        function updateCounter() {
            const el = document.getElementById('counter');
            if (el) el.textContent = totalDipilih() + ' dipilih (sisa slot ' + SISA_SLOT + ')';
        }

        function renderPreview() {
            const wrap = document.getElementById('preview');
            const b64wrap = document.getElementById('base64-container');
            wrap.innerHTML = '';
            b64wrap.innerHTML = '';

            // File galeri
            const files = document.getElementById('file_foto')?.files ?? [];
            Array.from(files).forEach((file) => {
                const url = URL.createObjectURL(file);
                wrap.insertAdjacentHTML('beforeend',
                    '<img src="' + url + '" style="width:100%;aspect-ratio:1/1;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;">');
            });

            // Foto kamera (base64) + hidden input + tombol hapus
            kameraFotos.forEach((data, i) => {
                wrap.insertAdjacentHTML('beforeend',
                    '<div style="position:relative;">' +
                    '<img src="' + data + '" style="width:100%;aspect-ratio:1/1;object-fit:cover;border-radius:8px;border:2px solid #10b981;">' +
                    '<button type="button" onclick="hapusKamera(' + i + ')" style="position:absolute;top:2px;right:2px;background:#ef4444;color:#fff;border:none;border-radius:999px;width:22px;height:22px;font-weight:700;">×</button>' +
                    '</div>');
                const inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'foto_base64[]';
                inp.value = data;
                b64wrap.appendChild(inp);
            });

            updateCounter();
        }

        function hapusKamera(i) {
            kameraFotos.splice(i, 1);
            renderPreview();
        }

        function bukaKamera() {
            if (stream) stream.getTracks().forEach(t => t.stop());
            navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } } })
                .then(s => {
                    stream = s;
                    const v = document.getElementById('kamera');
                    v.srcObject = s;
                    v.style.display = 'block';
                    document.getElementById('btn-ambil').style.display = 'block';
                })
                .catch(err => alert('Tidak bisa membuka kamera: ' + err.message));
        }

        function ambilFoto() {
            if (totalDipilih() >= SISA_SLOT) {
                alert('Sudah mencapai batas ' + SISA_SLOT + ' foto.');
                return;
            }
            const v = document.getElementById('kamera');
            const c = document.getElementById('canvas');
            c.width = v.videoWidth;
            c.height = v.videoHeight;
            c.getContext('2d').drawImage(v, 0, 0);
            kameraFotos.push(c.toDataURL('image/jpeg', 0.85));
            renderPreview();
        }

        document.getElementById('file_foto')?.addEventListener('change', function () {
            if (totalDipilih() > SISA_SLOT) {
                alert('Maksimal ' + SISA_SLOT + ' foto. Sebagian akan diabaikan.');
            }
            renderPreview();
        });

        document.getElementById('form-bukti')?.addEventListener('submit', function (e) {
            if (totalDipilih() === 0) {
                e.preventDefault();
                alert('Pilih atau ambil minimal 1 foto.');
            }
        });
    </script>
@endsection
