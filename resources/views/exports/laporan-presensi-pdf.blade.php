<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Presensi - {{ $periode }}</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        h2 { text-align: center; margin-bottom: 5px; }
        p.sub { text-align: center; color: #666; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #333; padding: 5px 8px; text-align: left; }
        th { background: #1a1a2e; color: #fff; }
        tr:nth-child(even) { background: #f2f2f2; }
        .badge { padding: 2px 8px; border-radius: 3px; color: #fff; font-size: 10px; }
        .badge-hadir { background: #16a34a; }
        .badge-terlambat { background: #f97316; }
        .badge-alpa { background: #ef4444; }
        .badge-izin { background: #3b82f6; }
        .footer { margin-top: 20px; text-align: right; font-size: 10px; color: #999; }
    </style>
</head>
<body>
    <h2>CV Boss Muda Mandiri</h2>
    <p class="sub">Laporan Presensi {{ $jenis ?? 'Harian' }} - Periode: {{ $periode }}</p>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Karyawan</th>
                <th>NIK</th>
                <th>Masuk</th>
                <th>Pulang</th>
                <th>Status</th>
                <th>Telat</th>
                <th>Potongan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($presensis as $i => $p)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ \Carbon\Carbon::parse($p->tanggal)->format('d/m/Y') }}</td>
                <td>{{ $p->karyawan?->user?->nama ?? '-' }}</td>
                <td>{{ $p->karyawan?->nik ?? '-' }}</td>
                <td>{{ $p->jam_masuk ? \Carbon\Carbon::parse($p->jam_masuk)->format('H:i') : '-' }}</td>
                <td>{{ $p->jam_keluar ? \Carbon\Carbon::parse($p->jam_keluar)->format('H:i') : '-' }}</td>
                <td>
                    @switch($p->status_presensi)
                        @case('hadir') <span class="badge badge-hadir">Hadir</span> @break
                        @case('terlambat') <span class="badge badge-terlambat">Terlambat</span> @break
                        @case('tidak_hadir') <span class="badge badge-alpa">Alpa</span> @break
                        @case('izin') <span class="badge badge-izin">Izin</span> @break
                    @endswitch
                </td>
                <td>{{ $p->menit_terlambat ?? 0 }} mnt</td>
                <td>Rp {{ number_format($p->potongan_terlambat ?? 0, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <p class="footer">Dicetak: {{ now()->format('d/m/Y H:i:s') }} | Total: {{ $presensis->count() }} record</p>
</body>
</html>
