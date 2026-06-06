<x-filament-panels::page>
    {{-- Navigasi bulan --}}
    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;margin-bottom:1rem;">
        <div style="display:flex;gap:6px;">
            <x-filament::button color="gray" size="sm" wire:click="bulanSebelumnya" icon="heroicon-o-chevron-left">
                Sebelumnya
            </x-filament::button>
            <x-filament::button color="gray" size="sm" wire:click="bulanIni">Bulan Ini</x-filament::button>
            <x-filament::button color="gray" size="sm" wire:click="bulanBerikutnya" icon="heroicon-o-chevron-right" icon-position="after">
                Berikutnya
            </x-filament::button>
        </div>
        <h2 style="font-size:1.1rem;font-weight:700;margin:0;">{{ $this->getNamaBulan() }}</h2>
    </div>

    {{-- Nama hari --}}
    <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;text-align:center;font-weight:600;font-size:0.78rem;color:#64748b;margin-bottom:4px;">
        @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $h)
            <div>{{ $h }}</div>
        @endforeach
    </div>

    {{-- Grid kalender --}}
    <div style="display:flex;flex-direction:column;gap:4px;">
        @foreach ($this->getMinggu() as $minggu)
            <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;">
                @foreach ($minggu as $hari)
                    <div style="min-height:98px;border:1px solid #e5e7eb;border-radius:8px;padding:6px;overflow:hidden;background:{{ $hari['dalamBulan'] ? '#ffffff' : '#f8fafc' }};{{ $hari['hariIni'] ? 'outline:2px solid #f59e0b;outline-offset:-1px;' : '' }}">
                        <div style="display:flex;align-items:center;justify-content:space-between;">
                            <span style="font-size:0.8rem;font-weight:700;color:{{ $hari['dalamBulan'] ? '#111827' : '#9ca3af' }};">{{ $hari['tanggal']->format('j') }}</span>
                            @if ($hari['libur'])
                                <span style="font-size:9px;background:#fee2e2;color:#b91c1c;padding:1px 6px;border-radius:999px;font-weight:700;">Libur</span>
                            @elseif ($hari['jadwal']->isNotEmpty())
                                <span style="font-size:9px;background:#dbeafe;color:#1d4ed8;padding:1px 6px;border-radius:999px;font-weight:700;">{{ $hari['jadwal']->count() }}</span>
                            @endif
                        </div>

                        <div style="margin-top:4px;display:flex;flex-direction:column;gap:2px;">
                            @foreach ($hari['jadwal']->take(3) as $j)
                                <a href="{{ \App\Filament\Resources\JadwalPekerjaans\JadwalPekerjaanResource::getUrl('edit', ['record' => $j->id]) }}"
                                   title="{{ $j->user?->nama }} ({{ substr((string) $j->jam_masuk, 0, 5) }}-{{ substr((string) $j->jam_pulang, 0, 5) }})"
                                   style="font-size:10px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:4px;padding:1px 4px;color:#1e3a8a;text-decoration:none;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block;">
                                    {{ substr((string) $j->jam_masuk, 0, 5) }} {{ $j->user?->nama ?? '-' }}
                                </a>
                            @endforeach
                            @if ($hari['jadwal']->count() > 3)
                                <span style="font-size:9px;color:#6b7280;">+{{ $hari['jadwal']->count() - 3 }} lainnya</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
