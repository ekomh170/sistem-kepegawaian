<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekap Pekerjaan - {{ $periode }}</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        h2 { text-align: center; margin-bottom: 5px; }
        p.sub { text-align: center; color: #666; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #333; padding: 5px 8px; text-align: left; }
        th { background: #1a1a2e; color: #fff; }
        tr:nth-child(even) { background: #f2f2f2; }
        .badge { padding: 2px 8px; border-radius: 3px; color: #fff; font-size: 10px; }
        .badge-diterima { background: #16a34a; }
        .badge-ditolak { background: #ef4444; }
        .badge-menunggu { background: #f97316; }
        .badge-ya { background: #3b82f6; }
        .footer { margin-top: 20px; text-align: right; font-size: 10px; color: #999; }
    </style>
</head>
<body>
    <h2>CV Boss Muda Mandiri</h2>
    <p class="sub">Rekap Pekerjaan {{ $jenis ?? 'Bulanan' }} - Periode: {{ $periode }}</p>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Karyawan</th>
                <th>NIK</th>
                <th>Nama Lokasi</th>
                <th>Status</th>
                <th>Bukti</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pekerjaans as $i => $p)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ optional($p->jadwal?->tanggal_kerja)->format('d/m/Y') ?? '-' }}</td>
                <td>{{ $p->karyawan?->user?->nama ?? '-' }}</td>
                <td>{{ $p->karyawan?->nik ?? '-' }}</td>
                <td>{{ $p->nama_lokasi }}</td>
                <td>
                    @switch($p->status)
                        @case('disetujui') <span class="badge badge-diterima">Diterima</span> @break
                        @case('ditolak') <span class="badge badge-ditolak">Ditolak</span> @break
                        @default <span class="badge badge-menunggu">Menunggu</span>
                    @endswitch
                </td>
                <td>
                    @if($p->buktiPekerjaans->count() > 0)
                        <span class="badge badge-ya">Ya</span>
                    @else
                        Belum
                    @endif
                </td>
                <td>{{ $p->keterangan_pekerjaan ?? '-' }}</td>
            </tr>
            @if($p->status === 'ditolak' && $p->alasan_tolak)
            <tr style="background:#fff0f0;">
                <td colspan="8" style="font-size:10px;color:#b91c1c;padding-left:20px;">
                    ↳ Alasan tolak: {{ $p->alasan_tolak }}
                </td>
            </tr>
            @endif
            @endforeach
        </tbody>
    </table>

    <p class="footer">Dicetak: {{ now()->format('d/m/Y H:i:s') }} | Total: {{ $pekerjaans->count() }} record</p>
</body>
</html>
