@extends('karyawan.layout', [
    'title' => 'Presensi Masuk',
    'subtitle' => 'Check-in dengan lokasi GPS dan foto presensi.',
])

@section('content')
    <section class="card">
        <h2>Form Check-in</h2>

        @if ($presensiHariIni && $presensiHariIni->jam_masuk)
            <p class="text-muted">Anda sudah check-in pada {{ \Carbon\Carbon::parse($presensiHariIni->jam_masuk)->format('H:i') }}.</p>
            @if ($presensiHariIni->foto_masuk)
                <div style="margin-top: 15px; text-align: center;">
                    <p style="margin-bottom: 5px; font-weight: bold; color: #555;">Foto Masuk Anda:</p>
                    <img src="{{ asset('storage/' . $presensiHariIni->foto_masuk) }}" alt="Foto Masuk" style="max-width: 100%; border-radius: 8px; border: 1px solid #ddd; padding: 5px;">
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

            <form method="POST" action="{{ route('karyawan.presensi.masuk.submit') }}" enctype="multipart/form-data">
                @csrf

                <div class="field">
                    <label for="latitude">Latitude</label>
                    <input id="latitude" name="latitude" type="text" value="{{ old('latitude') }}" readonly required>
                </div>

                <div class="field">
                    <label for="longitude">Longitude</label>
                    <input id="longitude" name="longitude" type="text" value="{{ old('longitude') }}" readonly required>
                </div>

                <div class="field">
                    <label>Detail Lokasi</label>
                    <p id="nama_lokasi" style="font-size: 0.9em; color: #666; margin-top: 5px;"><i>(Klik ambil lokasi untuk melihat kota/provinsi)</i></p>
                </div>

                <div class="field">
                    <label>Pilih Metode Foto</label>
                    <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                        <button type="button" class="btn btn-muted" id="btn-upload-mode" style="flex: 1; padding: 5px;">Upload File</button>
                        <button type="button" class="btn btn-primary" id="btn-camera-mode" style="flex: 1; padding: 5px;">Buka Kamera</button>
                    </div>

                    <!-- Mode Upload File -->
                    <div id="upload-mode-container" style="display: none;">
                        <input id="foto_masuk" name="foto_masuk" type="file" accept="image/*" onchange="previewImage(this, 'preview_masuk')">
                        <div style="margin-top: 10px; text-align: center;">
                            <img id="preview_masuk" src="#" alt="Preview Foto" style="display: none; max-width: 100%; border-radius: 8px; border: 1px solid #ddd; padding: 5px;">
                        </div>
                    </div>

                    <!-- Mode Kamera WebRTC -->
                    <div id="camera-mode-container" style="text-align: center;">
                        <video id="webcam-video" autoplay playsinline style="width: 100%; max-width: 400px; border-radius: 8px; background: #000; display: none; margin: 0 auto;"></video>
                        <canvas id="webcam-canvas" style="display: none;"></canvas>
                        <img id="webcam-preview" style="width: 100%; max-width: 400px; border-radius: 8px; display: none; margin: 10px auto; border: 2px solid #4CAF50;" />
                        <input type="hidden" name="foto_masuk_base64" id="foto_masuk_base64">
                        
                        <div style="margin-top: 10px; display: none;" id="camera-controls">
                            <button type="button" class="btn btn-primary" id="btn-snap" style="width: 100%;">📸 Jepret Foto</button>
                            <button type="button" class="btn btn-muted" id="btn-retake" style="width: 100%; display: none; margin-top: 5px;">🔄 Ulangi Foto</button>
                        </div>
                    </div>
                </div>

                <div class="row" style="margin-top:10px;">
                    <button class="btn btn-muted" type="button" id="ambil-lokasi">Ambil Lokasi</button>
                    <button class="btn btn-primary" type="submit">Kirim Presensi Masuk</button>
                </div>
            </form>
        @endif
    </section>

    <script>
        // Tab Switcher
        const btnUploadMode = document.getElementById('btn-upload-mode');
        const btnCameraMode = document.getElementById('btn-camera-mode');
        const uploadContainer = document.getElementById('upload-mode-container');
        const cameraContainer = document.getElementById('camera-mode-container');

        // Camera Elements
        const video = document.getElementById('webcam-video');
        const canvas = document.getElementById('webcam-canvas');
        const webcamPreview = document.getElementById('webcam-preview');
        const base64Input = document.getElementById('foto_masuk_base64');
        const btnSnap = document.getElementById('btn-snap');
        const btnRetake = document.getElementById('btn-retake');
        const cameraControls = document.getElementById('camera-controls');
        let stream = null;

        function stopCamera() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                video.style.display = 'none';
                cameraControls.style.display = 'none';
            }
        }

        async function startCamera() {
            uploadContainer.style.display = 'none';
            cameraContainer.style.display = 'block';
            btnCameraMode.classList.replace('btn-muted', 'btn-primary');
            btnUploadMode.classList.replace('btn-primary', 'btn-muted');
            
            webcamPreview.style.display = 'none';
            base64Input.value = '';
            btnSnap.style.display = 'block';
            btnRetake.style.display = 'none';

            try {
                stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "user" } });
                video.srcObject = stream;
                video.style.display = 'block';
                cameraControls.style.display = 'block';
            } catch (err) {
                alert("Gagal mengakses kamera. Pastikan Anda mengizinkan akses kamera di browser.");
            }
        }

        btnUploadMode.addEventListener('click', () => {
            stopCamera();
            cameraContainer.style.display = 'none';
            uploadContainer.style.display = 'block';
            btnUploadMode.classList.replace('btn-muted', 'btn-primary');
            btnCameraMode.classList.replace('btn-primary', 'btn-muted');
            base64Input.value = ''; // Reset base64
        });

        btnCameraMode.addEventListener('click', startCamera);

        btnSnap.addEventListener('click', () => {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
            
            const imageData = canvas.toDataURL('image/jpeg', 0.8);
            base64Input.value = imageData;
            
            webcamPreview.src = imageData;
            webcamPreview.style.display = 'block';
            video.style.display = 'none';
            
            btnSnap.style.display = 'none';
            btnRetake.style.display = 'block';
            
            // Turn off camera light
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
        });

        btnRetake.addEventListener('click', startCamera);

        // Preview normal file upload
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.src = '#';
                preview.style.display = 'none';
            }
        }

        const latitudeInput = document.getElementById('latitude');
        const longitudeInput = document.getElementById('longitude');
        const ambilLokasiButton = document.getElementById('ambil-lokasi');

        if (ambilLokasiButton) {
            ambilLokasiButton.addEventListener('click', function () {
                if (!navigator.geolocation) {
                    alert('Perangkat tidak mendukung GPS.');
                    return;
                }

                navigator.geolocation.getCurrentPosition(function (position) {
                    const lat = position.coords.latitude;
                    const lon = position.coords.longitude;
                    latitudeInput.value = lat;
                    longitudeInput.value = lon;
                    
                    const lokasiText = document.getElementById('nama_lokasi');
                    lokasiText.innerHTML = "<i>Mencari nama lokasi...</i>";
                    
                    fetch(`https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=${lat}&longitude=${lon}&localityLanguage=id`)
                        .then(res => res.json())
                        .then(data => {
                            const kota = data.city || data.locality || '';
                            const provinsi = data.principalSubdivision || '';
                            if (kota || provinsi) {
                                lokasiText.innerHTML = `<b>${kota}, ${provinsi}</b>`;
                            } else {
                                lokasiText.innerHTML = "<i>Detail lokasi tidak ditemukan</i>";
                            }
                        })
                        .catch(err => {
                            lokasiText.innerHTML = "<i>Gagal memuat detail lokasi</i>";
                        });
                        
                }, function () {
                    alert('Lokasi gagal diambil, pastikan izin GPS aktif.');
                }, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                });
            });
        }
    </script>
@endsection
