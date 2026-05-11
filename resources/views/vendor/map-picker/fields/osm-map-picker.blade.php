<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <style>
        .map-search-wrap {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.22);
            overflow: hidden;
            width: 260px;
        }
        .map-search-row {
            display: flex;
            align-items: center;
        }
        .map-search-input {
            border: none;
            outline: none;
            padding: 9px 10px;
            font-size: 13px;
            width: 100%;
            font-family: "Plus Jakarta Sans", "Poppins", "Segoe UI", sans-serif;
            background: #fff;
            color: #111827;
        }
        .map-search-input::placeholder {
            color: #9ca3af;
        }
        .map-search-btn {
            background: #0f766e;
            border: none;
            padding: 9px 11px;
            cursor: pointer;
            color: #fff;
            flex-shrink: 0;
            line-height: 0;
        }
        .map-search-btn:hover { background: #0d6460; }
        .map-search-status {
            padding: 7px 10px;
            font-size: 11.5px;
            color: #6b7280;
            border-top: 1px solid #f3f4f6;
            display: none;
        }
        .map-search-results {
            border-top: 1px solid #e5e7eb;
            max-height: 170px;
            overflow-y: auto;
            display: none;
        }
        .map-search-item {
            padding: 7px 10px;
            font-size: 12px;
            cursor: pointer;
            border-bottom: 1px solid #f9fafb;
            color: #374151;
            line-height: 1.4;
        }
        .map-search-item:hover { background: #f0fdf4; }
        .map-search-item strong { display: block; font-size: 13px; color: #111827; }
        .map-search-item small { color: #9ca3af; }
    </style>

    <div x-data="mapPicker($wire, {{ $getMapConfig() }})"
            x-init="async () => {
            do {
                await (new Promise(resolve => setTimeout(resolve, 100)));
            } while (!$refs.map);
            attach($refs.map, $refs);
            // Tambah kontrol pencarian setelah peta siap
            if (map) {
                addMapSearchControl(map, $wire, {{ $getMapConfig() }});
            }
        }"
            wire:ignore
    >
        <div
            x-ref="map"
            class="w-full" style="min-height: 30vh; {{ $getExtraStyle() }}">
        </div>
        <input type="text" id="{{ $getStatePath() }}_fmrest" x-ref="formRestorationInput" style="display:none"/>
    </div>

    <script>
    window.addMapSearchControl = function (leafletMap, wire, config) {
        // Hitung prefix state path (mis. "data." dari "data.location")
        const prefix = config.statePath.replace(/[^.]*$/, '');

        const MapSearchControl = L.Control.extend({
            options: { position: 'topleft' },
            onAdd: function () {
                const wrap = L.DomUtil.create('div', 'map-search-wrap');
                L.DomEvent.disableClickPropagation(wrap);
                L.DomEvent.disableScrollPropagation(wrap);

                wrap.innerHTML = `
                    <div class="map-search-row">
                        <input class="map-search-input" type="text" placeholder="Cari tempat atau alamat...">
                        <button class="map-search-btn" type="button" title="Cari">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                                 stroke="white" stroke-width="2.5" stroke-linecap="round">
                                <circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                            </svg>
                        </button>
                    </div>
                    <div class="map-search-status"></div>
                    <div class="map-search-results"></div>
                `;

                const input   = wrap.querySelector('.map-search-input');
                const btn     = wrap.querySelector('.map-search-btn');
                const status  = wrap.querySelector('.map-search-status');
                const results = wrap.querySelector('.map-search-results');

                const showStatus = (msg) => {
                    status.textContent  = msg;
                    status.style.display = msg ? 'block' : 'none';
                    results.style.display = 'none';
                };

                const doSearch = async () => {
                    const q = input.value.trim();
                    if (!q) return;
                    results.innerHTML     = '';
                    results.style.display = 'none';
                    showStatus('Mencari…');

                    try {
                        const res  = await fetch(
                            'https://nominatim.openstreetmap.org/search?' +
                            new URLSearchParams({ q, format: 'json', limit: 5, addressdetails: 1, 'accept-language': 'id' }),
                            { headers: { 'User-Agent': 'SistemKepegawaian/1.0' } }
                        );
                        const data = await res.json();
                        showStatus('');

                        if (!data.length) { showStatus('Lokasi tidak ditemukan.'); return; }

                        data.forEach(item => {
                            const el   = L.DomUtil.create('div', 'map-search-item');
                            const name = item.name || item.display_name.split(',')[0].trim();
                            el.innerHTML = `<strong>${name}</strong><small>${item.display_name}</small>`;

                            L.DomEvent.on(el, 'click', async () => {
                                const lat = parseFloat(item.lat);
                                const lng = parseFloat(item.lon);

                                // Animasi fly peta ke lokasi
                                leafletMap.flyTo([lat, lng], 16);

                                // Update semua field terkait via Livewire
                                await wire.set(config.statePath,         { lat, lng });
                                wire.set(prefix + 'latitude',            lat);
                                wire.set(prefix + 'longitude',           lng);
                                wire.set(prefix + 'nama_lokasi',         name);
                                wire.set(prefix + 'alamat_lokasi',       item.display_name);

                                results.style.display = 'none';
                                input.value = name;
                            });
                            results.appendChild(el);
                        });
                        results.style.display = 'block';
                    } catch (e) {
                        showStatus('Gagal mencari. Periksa koneksi.');
                    }
                };

                btn.addEventListener('click', doSearch);
                input.addEventListener('keydown', e => {
                    if (e.key === 'Enter') { e.preventDefault(); doSearch(); }
                });

                // Tutup dropdown saat klik di luar kontrol
                document.addEventListener('click', e => {
                    if (!wrap.contains(e.target)) results.style.display = 'none';
                });

                return wrap;
            }
        });

        new MapSearchControl().addTo(leafletMap);
    };
    </script>
</x-dynamic-component>
