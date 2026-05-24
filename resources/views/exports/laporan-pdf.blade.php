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
    <h2>Laporan Jumlah Presensi Per Karyawan</h2>
    <p><strong>Jenis Laporan:</strong> {{ $jenis ?? 'Bulanan' }}</p>
    <p><strong>Periode:</strong> {{ $periode }}</p>

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
