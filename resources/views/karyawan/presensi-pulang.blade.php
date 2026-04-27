@extends('karyawan.layout', [
    'title' => 'Presensi Pulang',
    'subtitle' => 'Check-out dengan upload foto hasil kerja.',
])

@section('content')
    <section class="card">
        <h2>Form Check-out</h2>

        @if (!$presensiHariIni || !$presensiHariIni->jam_masuk)
            <p class="text-muted">Anda belum check-in hari ini, sehingga belum bisa check-out.</p>
        @elseif ($presensiHariIni->jam_keluar)
            <p class="text-muted">Anda sudah check-out pada {{ optional($presensiHariIni->jam_keluar)->format('H:i') }}.</p>
        @else
            <form method="POST" action="{{ route('karyawan.presensi.pulang.submit') }}" enctype="multipart/form-data">
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
                    <label for="foto_keluar">Foto Hasil Kerja</label>
                    <input id="foto_keluar" name="foto_keluar" type="file" accept="image/*" capture="environment">
                </div>

                <div class="row" style="margin-top:10px;">
                    <button class="btn btn-muted" type="button" id="ambil-lokasi">Ambil Lokasi</button>
                    <button class="btn btn-primary" type="submit">Kirim Presensi Pulang</button>
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
