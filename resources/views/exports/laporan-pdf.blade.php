<!DOCTYPE html>
<html>
<head>
    <title>Laporan Presensi</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        h2 {
            text-align: center;
        }
    </style>
</head>
<body>
    @php
        $jenisLabel = $jenis ?? 'Bulanan';
        $periodeLabel = (string) $periode;
        try {
            if ($jenisLabel === 'Harian' && strlen($periodeLabel) === 10) {
                $periodeLabel = \Carbon\Carbon::parse($periodeLabel)->translatedFormat('d F Y');
            } elseif ($jenisLabel === 'Mingguan' && strlen($periodeLabel) === 10) {
                $start = \Carbon\Carbon::parse($periodeLabel);
                $end = $start->copy()->addDays(6);
                $periodeLabel = $start->translatedFormat('d M Y') . ' – ' . $end->translatedFormat('d M Y');
            } elseif ($jenisLabel === 'Bulanan' && strlen($periodeLabel) === 7) {
                $periodeLabel = \Carbon\Carbon::createFromFormat('Y-m', $periodeLabel)->translatedFormat('F Y');
            }
        } catch (\Throwable $e) {
            // biarkan periodeLabel apa adanya
        }
    @endphp

    <h2>Laporan Jumlah Presensi Per Karyawan</h2>
    <p><strong>Jenis Laporan:</strong> {{ $jenisLabel }}</p>
    <p><strong>Periode:</strong> {{ $periodeLabel }}</p>

    <table>
        <thead>
            <tr>
                <th>Periode</th>
                <th>Karyawan</th>
                <th>Hadir</th>
                <th>Terlambat</th>
                <th>Tidak Hadir</th>
                <th>Total Potongan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($penggajians as $p)
            <tr>
                <td>{{ $p->periode }}</td>
                <td>{{ $p->karyawan?->user?->nama ?? $p->karyawan?->nik ?? '-' }}</td>
                <td>{{ $p->jumlah_hadir }}</td>
                <td>{{ $p->jumlah_terlambat }}</td>
                <td>{{ $p->jumlah_tidak_hadir }}</td>
                <td>Rp {{ number_format($p->total_potongan_keterlambatan, 0, ',', '.') }}</td>
            </tr>
            @endforeach
            @if($penggajians->isEmpty())
            <tr>
                <td colspan="6" style="text-align: center;">Tidak ada data laporan.</td>
            </tr>
            @endif
        </tbody>
    </table>
</body>
</html>
