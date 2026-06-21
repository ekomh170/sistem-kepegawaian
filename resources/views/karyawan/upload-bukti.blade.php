@extends('karyawan.layout', [
    'title' => 'Upload Bukti Pekerjaan',
    'subtitle' => 'Unggah foto Sebelum & Sesudah (boleh banyak) untuk tugas ini.',
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
        $sebelumTersimpan = ($buktiExisting && is_array($buktiExisting->foto_before)) ? $buktiExisting->foto_before : [];
        $sesudahTersimpan = ($buktiExisting && is_array($buktiExisting->foto_after)) ? $buktiExisting->foto_after : [];
        $sisaSebelum = max(0, 20 - count($sebelumTersimpan));
        $sisaSesudah = max(0, 20 - count($sesudahTersimpan));

        // Konfigurasi dua galeri: Sebelum & Sesudah.
        $grup = [
            'before' => [
                'judul' => '📸 Foto SEBELUM Kerja',
                'fileName' => 'foto_before[]',
                'b64Name' => 'foto_before_base64[]',
                'tersimpan' => $sebelumTersimpan,
                'sisa' => $sisaSebelum,
                'warna' => '#f59e0b',
            ],
            'after' => [
                'judul' => '📸 Foto SESUDAH Kerja',
                'fileName' => 'foto_after[]',
                'b64Name' => 'foto_after_base64[]',
                'tersimpan' => $sesudahTersimpan,
                'sisa' => $sisaSesudah,
                'warna' => '#10b981',
            ],
        ];
    @endphp

    {{-- Galeri foto yang sudah tersimpan (Sebelum & Sesudah) --}}
    @if (count($sebelumTersimpan) > 0 || count($sesudahTersimpan) > 0)
        <section class="card">
            <h2>Sudah Diupload</h2>
            <p class="text-muted" style="margin:0 0 8px;">Status: <b>{{ strtoupper($buktiExisting->status) }}</b> · {{ optional($buktiExisting->uploaded_at)->format('d M Y H:i') }}</p>
            @foreach ($grup as $g)
                @if (count($g['tersimpan']) > 0)
                    <h3 style="font-size:13px;margin:10px 0 6px;color:{{ $g['warna'] }};">{{ $g['judul'] }} ({{ count($g['tersimpan']) }}/20)</h3>
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;">
                        @foreach ($g['tersimpan'] as $f)
                            <img src="{{ asset('storage/' . $f) }}" alt="Bukti"
                                 style="width:100%;aspect-ratio:1/1;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;">
                        @endforeach
                    </div>
                @endif
            @endforeach
        </section>
    @endif

    @if ($sisaSebelum <= 0 && $sisaSesudah <= 0)
        <section class="card">
            <div class="alert alert-success">Foto Sebelum & Sesudah sudah mencapai batas maksimal (20 masing-masing). Tidak bisa menambah lagi.</div>
            <a class="btn btn-secondary" href="{{ route('karyawan.tugas') }}" style="width:100%;text-align:center;padding:12px;">← Kembali ke Daftar Tugas</a>
        </section>
    @else
        {{-- Form Upload Multi-Foto (Sebelum & Sesudah) --}}
        <form action="{{ route('karyawan.tugas.upload.submit') }}" method="POST" enctype="multipart/form-data" id="form-bukti">
            @csrf
            <input type="hidden" name="detail_pekerjaan_id" value="{{ $tugas->id }}">

            @foreach ($grup as $key => $g)
                <section class="card" style="border-top:3px solid {{ $g['warna'] }};">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                        <h2 style="margin:0;font-size:15px;color:{{ $g['warna'] }};">{{ $g['judul'] }}</h2>
                        <span id="counter-{{ $key }}" style="font-size:12px;font-weight:700;color:{{ $g['warna'] }};">0 dipilih (sisa slot {{ $g['sisa'] }})</span>
                    </div>

                    @if ($g['sisa'] <= 0)
                        <div class="alert alert-success" style="margin:0;">Galeri ini sudah penuh (20 foto).</div>
                    @else
                        {{-- Ambil dari galeri (boleh banyak sekaligus) --}}
                        <label style="font-size:13px;font-weight:600;color:#374151;">Pilih dari galeri (bisa banyak):</label>
                        <input type="file" name="{{ $g['fileName'] }}" id="file-{{ $key }}" accept="image/*" multiple style="margin:6px 0 12px;width:100%;"
                               data-grup="{{ $key }}">

                        {{-- Atau ambil via kamera (bisa beberapa kali) --}}
                        <div style="border-top:1px dashed #cbd5e1;padding-top:10px;">
                            <label style="font-size:13px;font-weight:600;color:#374151;">Atau ambil via kamera:</label>
                            <button type="button" class="btn btn-secondary" onclick="bukaKamera('{{ $key }}')" style="width:100%;padding:10px;margin-top:6px;">📷 Buka Kamera</button>
                            <video id="kamera-{{ $key }}" autoplay playsinline style="width:100%;border-radius:8px;display:none;margin-top:8px;"></video>
                            <button type="button" id="btn-ambil-{{ $key }}" class="btn btn-primary" style="display:none;width:100%;padding:10px;margin-top:8px;" onclick="ambilFoto('{{ $key }}')">📸 Ambil Foto (boleh berkali-kali)</button>
                            <canvas id="canvas-{{ $key }}" style="display:none;"></canvas>
                        </div>

                        {{-- Preview semua foto terpilih --}}
                        <div id="preview-{{ $key }}" style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-top:12px;"></div>
                        {{-- Hidden inputs base64 hasil kamera --}}
                        <div id="base64-{{ $key }}"></div>
                    @endif
                </section>
            @endforeach

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
        // Sisa slot per galeri (Sebelum/Sesudah), dipakai untuk batasi jumlah foto.
        const SISA = {
            before: {{ $sisaSebelum }},
            after: {{ $sisaSesudah }},
        };
        // State foto galeri (File) + kamera (dataURL) & stream per galeri.
        // galeriFiles dikelola manual agar tiap file bisa dihapus per-item (native
        // file input read-only) lalu disinkron balik ke input lewat DataTransfer.
        const galeriFiles = { before: [], after: [] };
        const kameraFotos = { before: [], after: [] };
        let streamAktif = null;
        let grupKameraAktif = null;

        function totalDipilih(key) {
            return galeriFiles[key].length + kameraFotos[key].length;
        }

        // Tulis ulang FileList input dari galeriFiles agar yang terkirim = yang tampil.
        function syncInput(key) {
            const input = document.getElementById('file-' + key);
            if (!input) return;
            const dt = new DataTransfer();
            galeriFiles[key].forEach((f) => dt.items.add(f));
            input.files = dt.files;
        }

        function updateCounter(key) {
            const el = document.getElementById('counter-' + key);
            if (el) el.textContent = totalDipilih(key) + ' dipilih (sisa slot ' + SISA[key] + ')';
        }

        function renderPreview(key) {
            const wrap = document.getElementById('preview-' + key);
            const b64wrap = document.getElementById('base64-' + key);
            if (!wrap || !b64wrap) return;
            wrap.innerHTML = '';
            b64wrap.innerHTML = '';

            // File galeri (bisa dihapus per-item)
            galeriFiles[key].forEach((file, i) => {
                const url = URL.createObjectURL(file);
                wrap.insertAdjacentHTML('beforeend',
                    '<div style="position:relative;">' +
                    '<img src="' + url + '" onload="URL.revokeObjectURL(this.src)" style="width:100%;aspect-ratio:1/1;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;">' +
                    '<button type="button" onclick="hapusGaleri(\'' + key + '\',' + i + ')" style="position:absolute;top:2px;right:2px;background:#ef4444;color:#fff;border:none;border-radius:999px;width:22px;height:22px;font-weight:700;">×</button>' +
                    '</div>');
            });

            // Foto kamera (base64) + hidden input + tombol hapus
            kameraFotos[key].forEach((data, i) => {
                wrap.insertAdjacentHTML('beforeend',
                    '<div style="position:relative;">' +
                    '<img src="' + data + '" style="width:100%;aspect-ratio:1/1;object-fit:cover;border-radius:8px;border:2px solid #10b981;">' +
                    '<button type="button" onclick="hapusKamera(\'' + key + '\',' + i + ')" style="position:absolute;top:2px;right:2px;background:#ef4444;color:#fff;border:none;border-radius:999px;width:22px;height:22px;font-weight:700;">×</button>' +
                    '</div>');
                const inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = (key === 'before' ? 'foto_before_base64[]' : 'foto_after_base64[]');
                inp.value = data;
                b64wrap.appendChild(inp);
            });

            updateCounter(key);
        }

        function hapusKamera(key, i) {
            kameraFotos[key].splice(i, 1);
            renderPreview(key);
        }

        function hapusGaleri(key, i) {
            galeriFiles[key].splice(i, 1);
            syncInput(key);
            renderPreview(key);
        }

        function bukaKamera(key) {
            if (streamAktif) streamAktif.getTracks().forEach(t => t.stop());
            grupKameraAktif = key;
            navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } } })
                .then(s => {
                    streamAktif = s;
                    const v = document.getElementById('kamera-' + key);
                    v.srcObject = s;
                    v.style.display = 'block';
                    document.getElementById('btn-ambil-' + key).style.display = 'block';
                })
                .catch(err => alert('Tidak bisa membuka kamera: ' + err.message));
        }

        function ambilFoto(key) {
            if (totalDipilih(key) >= SISA[key]) {
                alert('Galeri ini sudah mencapai batas ' + SISA[key] + ' foto.');
                return;
            }
            const v = document.getElementById('kamera-' + key);
            const c = document.getElementById('canvas-' + key);
            c.width = v.videoWidth;
            c.height = v.videoHeight;
            c.getContext('2d').drawImage(v, 0, 0);
            kameraFotos[key].push(c.toDataURL('image/jpeg', 0.85));
            renderPreview(key);
        }

        ['before', 'after'].forEach((key) => {
            document.getElementById('file-' + key)?.addEventListener('change', function (e) {
                // Tambah file baru ke daftar (aditif), hormati sisa slot galeri ini.
                const dipilih = Array.from(e.target.files || []);
                const sisa = SISA[key] - totalDipilih(key);
                if (sisa <= 0) {
                    alert('Galeri ini sudah penuh (maksimal ' + SISA[key] + ' foto).');
                } else {
                    if (dipilih.length > sisa) {
                        alert('Galeri ini maksimal ' + SISA[key] + ' foto. Hanya ' + sisa + ' foto pertama yang ditambahkan.');
                    }
                    galeriFiles[key] = galeriFiles[key].concat(dipilih.slice(0, sisa));
                }
                syncInput(key);   // FileList input = daftar terkelola
                renderPreview(key);
            });
        });

        document.getElementById('form-bukti')?.addEventListener('submit', function (e) {
            const total = totalDipilih('before') + totalDipilih('after');
            if (total === 0) {
                e.preventDefault();
                alert('Pilih atau ambil minimal 1 foto (Sebelum atau Sesudah).');
            }
        });
    </script>
@endsection
