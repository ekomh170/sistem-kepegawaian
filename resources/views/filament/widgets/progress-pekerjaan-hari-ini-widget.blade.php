@php
    // $list, $selesai, $total injected via getViewData()
    $persen = $total > 0 ? round(($selesai / $total) * 100) : 0;
@endphp

<div>
<style>
.pk-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 14px 16px;
}
.dark .pk-card {
    background: #1e293b;
    border-color: #334155;
}
.pk-title {
    font-weight: 600;
    font-size: 13px;
    color: #111827;
}
.dark .pk-title { color: #f1f5f9; }
.pk-alamat {
    font-size: 11px;
    color: #6b7280;
    margin-bottom: 4px;
}
.dark .pk-alamat { color: #94a3b8; }
.pk-meta {
    font-size: 11px;
    color: #374151;
}
.dark .pk-meta { color: #cbd5e1; }
.pk-bar-label {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #6b7280;
    margin-bottom: 4px;
}
.dark .pk-bar-label { color: #94a3b8; }
.pk-bar-pct {
    font-weight: 600;
    color: #374151;
}
.dark .pk-bar-pct { color: #e2e8f0; }
</style>

<x-filament::section>
    <x-slot name="heading">
        Progres Pekerjaan Hari Ini
    </x-slot>
    <x-slot name="description">
        {{ now()->translatedFormat('l, d F Y') }}
    </x-slot>

    @if (empty($list) || $list->isEmpty())
        <p style="color:#6b7280;text-align:center;padding:24px 0;">Tidak ada penugasan untuk hari ini.</p>
    @else
        <div class="pk-bar-label" style="margin-bottom:4px;">
            <span>{{ $selesai }} dari {{ $total }} tugas selesai</span>
            <span class="pk-bar-pct">{{ $persen }}%</span>
        </div>
        <div style="background:#e5e7eb;border-radius:999px;height:8px;overflow:hidden;margin-bottom:16px;">
            <div style="background:#16a34a;height:100%;width:{{ $persen }}%;border-radius:999px;transition:width .4s;"></div>
        </div>

        <div style="display:grid;gap:10px;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));">
            @foreach ($list as $tugas)
                <div class="pk-card">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;margin-bottom:6px;">
                        <div class="pk-title">{{ $tugas['nama_lokasi'] }}</div>
                        <span style="font-size:11px;font-weight:600;padding:2px 9px;border-radius:999px;white-space:nowrap;background:{{ $tugas['badge_warna']['bg'] }};color:{{ $tugas['badge_warna']['color'] }};">
                            {{ $tugas['label_status'] }}
                        </span>
                    </div>
                    @if ($tugas['alamat_lokasi'])
                        <div class="pk-alamat">📍 {{ $tugas['alamat_lokasi'] }}</div>
                    @endif
                    <div class="pk-meta">👤 {{ $tugas['karyawan'] }}</div>
                    @if ($tugas['jam_mulai'])
                        <div class="pk-meta">🕐 Mulai {{ $tugas['jam_mulai'] }} WIB</div>
                    @endif
                    @if ($tugas['alasan_tolak'])
                        <div style="font-size:11px;color:#991b1b;margin-top:4px;">⚠ {{ $tugas['alasan_tolak'] }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</x-filament::section>
</div>
