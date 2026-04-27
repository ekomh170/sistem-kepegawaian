<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $title ?? 'Portal Karyawan' }}</title>
    <style>
        :root {
            --bg: #f5f7fb;
            --text: #172038;
            --muted: #5f6b85;
            --card: #ffffff;
            --border: #d9e0ef;
            --brand-a: #0f766e;
            --brand-b: #f59e0b;
            --success: #15803d;
            --warning: #c2410c;
            --danger: #b91c1c;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Plus Jakarta Sans", "Poppins", "Segoe UI", sans-serif;
            background: radial-gradient(circle at top right, #fff8dd 0%, #fffdf3 42%, #fefce8 100%);
            color: var(--text);
            min-height: 100vh;
        }

        .shell {
            max-width: 460px;
            margin: 0 auto;
            min-height: 100vh;
            padding: 18px 16px 90px;
        }

        .hero {
            background: linear-gradient(135deg, var(--brand-a), var(--brand-b));
            color: #fff;
            border-radius: 20px;
            padding: 16px 16px 14px;
            box-shadow: 0 14px 30px rgba(245, 158, 11, 0.25);
            margin-bottom: 14px;
        }

        .hero-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 6px;
        }

        .hero h1 {
            margin: 0;
            font-size: 19px;
            line-height: 1.3;
        }

        .hero p {
            margin: 5px 0 0;
            font-size: 13px;
            opacity: 0.95;
        }

        .alert {
            margin: 10px 0;
            border-radius: 12px;
            padding: 10px 12px;
            font-size: 13px;
            border: 1px solid transparent;
        }

        .alert-success {
            background: #ecfdf3;
            border-color: #86efac;
            color: #14532d;
        }

        .alert-error {
            background: #fef2f2;
            border-color: #fca5a5;
            color: #7f1d1d;
        }

        .card {
            background: var(--card);
            border-radius: 16px;
            border: 1px solid var(--border);
            padding: 14px;
            margin-bottom: 12px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
        }

        .card h2 {
            margin: 0 0 10px;
            font-size: 16px;
        }

        .row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 700;
        }

        .pill-success { background: #dcfce7; color: #166534; }
        .pill-warning { background: #ffedd5; color: #9a3412; }
        .pill-danger { background: #fee2e2; color: #991b1b; }
        .pill-info { background: #fef3c7; color: #92400e; }

        .text-muted {
            color: var(--muted);
            font-size: 13px;
        }

        .field {
            display: grid;
            gap: 6px;
            margin-bottom: 10px;
        }

        .field label {
            font-size: 13px;
            color: #334155;
        }

        .field input,
        .field select,
        .field textarea {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 10px 12px;
            font: inherit;
            background: #fff;
        }

        .btn {
            border: 0;
            border-radius: 12px;
            padding: 11px 14px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary { background: #0f766e; color: #fff; }
        .btn-secondary { background: #f59e0b; color: #fff; }
        .btn-muted { background: #e2e8f0; color: #0f172a; }

        .btn-logout {
            border: 1px solid rgba(255, 255, 255, 0.45);
            background: rgba(255, 255, 255, 0.16);
            color: #fff;
            border-radius: 10px;
            padding: 7px 10px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .stats {
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .stat-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 10px;
        }

        .stat-item b {
            display: block;
            font-size: 20px;
        }

        .bottom-nav {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 6px;
            background: rgba(255, 255, 255, 0.94);
            border-top: 1px solid #dbe3f1;
            backdrop-filter: blur(6px);
            padding: 8px;
            z-index: 50;
        }

        .bottom-nav a {
            text-align: center;
            border-radius: 11px;
            text-decoration: none;
            padding: 8px 4px;
            color: #475569;
            font-size: 11px;
            font-weight: 700;
            border: 1px solid transparent;
        }

        .bottom-nav a.active {
            background: #fef3c7;
            color: #92400e;
            border-color: #fcd34d;
        }
    </style>
</head>
<body>
    <div class="shell">
        <div class="hero">
            <div class="hero-head">
                <h1>{{ $title ?? 'Portal Karyawan' }}</h1>
                <form method="POST" action="{{ route('karyawan.logout') }}">
                    @csrf
                    <button type="submit" class="btn-logout">Logout</button>
                </form>
            </div>
            <p>{{ $subtitle ?? 'Akses presensi, jadwal, dan riwayat dalam satu tampilan mobile.' }}</p>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @yield('content')
    </div>

    <nav class="bottom-nav">
        <a href="{{ route('karyawan.beranda') }}" class="{{ request()->routeIs('karyawan.beranda') ? 'active' : '' }}">Beranda</a>
        <a href="{{ route('karyawan.presensi.masuk') }}" class="{{ request()->routeIs('karyawan.presensi.masuk') ? 'active' : '' }}">Masuk</a>
        <a href="{{ route('karyawan.presensi.pulang') }}" class="{{ request()->routeIs('karyawan.presensi.pulang') ? 'active' : '' }}">Pulang</a>
        <a href="{{ route('karyawan.jadwal') }}" class="{{ request()->routeIs('karyawan.jadwal') ? 'active' : '' }}">Jadwal</a>
        <a href="{{ route('karyawan.riwayat') }}" class="{{ request()->routeIs('karyawan.riwayat') ? 'active' : '' }}">Riwayat</a>
    </nav>
</body>
</html>
