@extends('karyawan.layout', [
    'title' => 'Presensi Masuk',
    'subtitle' => 'Check-in dengan lokasi GPS dan foto presensi.',
])

@section('content')
    <section class="card">
        <h2>Form Check-in</h2>

        @if ($presensiHariIni && $presensiHariIni->jam_masuk)
            <p class="text-muted">Anda sudah check-in pada {{ optional($presensiHariIni->jam_masuk)->format('H:i') }}.</p>
        @else
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
                    <label for="foto_masuk">Foto Masuk</label>
                    <input id="foto_masuk" name="foto_masuk" type="file" accept="image/*" capture="user">
                </div>

                <div class="row" style="margin-top:10px;">
                    <button class="btn btn-muted" type="button" id="ambil-lokasi">Ambil Lokasi</button>
                    <button class="btn btn-primary" type="submit">Kirim Presensi Masuk</button>
                </div>
            </form>
        @endif
    </section>

    <script>
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
                    latitudeInput.value = position.coords.latitude;
                    longitudeInput.value = position.coords.longitude;
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
