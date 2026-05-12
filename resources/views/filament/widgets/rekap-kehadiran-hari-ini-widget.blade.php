<div>
<style>
.kh-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 14px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.dark .kh-card {
    background: #1e293b;
    border-color: #334155;
}
.kh-name {
    font-weight: 600;
    font-size: 13px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: #111827;
}
.dark .kh-name { color: #f1f5f9; }
.kh-posisi {
    font-size: 11px;
    color: #6b7280;
    margin-bottom: 4px;
}
.dark .kh-posisi { color: #94a3b8; }
.kh-jam {
    font-size: 11px;
    color: #374151;
}
.dark .kh-jam { color: #cbd5e1; }
.kh-footer {
    margin-top: 14px;
    font-size: 12px;
    color: #9ca3af;
    text-align: right;
}
.dark .kh-footer { color: #64748b; }
</style>

<x-filament::section>
    <x-slot name="heading">
        Rekap Kehadiran Hari Ini
    </x-slot>
    <x-slot name="description">
        {{ now()->translatedFormat('l, d F Y') }}
    </x-slot>

    @if (empty($list) || $list->isEmpty())
        <p style="color:#6b7280;text-align:center;padding:24px 0;">Belum ada data karyawan.</p>
    @else
        <div style="display:grid;gap:10px;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));">
            @foreach ($list as $item)
                @php
                    $bgInisial = match($item['status']) {
                        'hadir'      => '#16a34a',
                        'terlambat'  => '#d97706',
                        'izin'       => '#0284c7',
                        default      => '#9ca3af',
                    };
                    $badgeText = match($item['status']) {
                        'hadir'     => 'Hadir',
                        'terlambat' => 'Terlambat',
                        'izin'      => 'Izin',
                        default     => 'Belum hadir',
                    };
                    $badgeBg = match($item['status']) {
                        'hadir'     => '#dcfce7',
                        'terlambat' => '#fef3c7',
                        'izin'      => '#dbeafe',
                        default     => '#f3f4f6',
                    };
                    $badgeColor = match($item['status']) {
                        'hadir'     => '#15803d',
                        'terlambat' => '#92400e',
                        'izin'      => '#1d4ed8',
                        default     => '#6b7280',
                    };
                @endphp
                <div class="kh-card">
                    <div style="width:40px;height:40px;border-radius:50%;background:{{ $bgInisial }};color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0;">
                        {{ $item['inisial'] }}
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div class="kh-name">{{ $item['nama'] }}</div>
                        <div class="kh-posisi">{{ $item['posisi'] }}</div>
                        <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                            <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:999px;background:{{ $badgeBg }};color:{{ $badgeColor }};">
                                {{ $badgeText }}
                            </span>
                            @if ($item['jam_masuk'])
                                <span class="kh-jam">{{ $item['jam_masuk'] }} WIB</span>
                            @endif
                            @if ($item['menit_terlambat'])
                                <span style="font-size:11px;color:#b45309;font-weight:600;">+{{ $item['menit_terlambat'] }} mnt</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <p class="kh-footer">
            Menampilkan {{ $total_karyawan }} karyawan · {{ $total_hadir }} hadir hari ini
        </p>
    @endif
</x-filament::section>
</div>
