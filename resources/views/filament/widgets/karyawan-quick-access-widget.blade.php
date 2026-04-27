<x-filament::section>
    <x-slot name="heading">
        Akses Cepat Karyawan
    </x-slot>

    <div style="display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));">
        <a href="{{ route('karyawan.beranda') }}" style="display:block;padding:12px 14px;border-radius:12px;background:#0f766e;color:#ffffff;text-decoration:none;">
            Beranda
        </a>
        <a href="{{ route('karyawan.presensi.masuk') }}" style="display:block;padding:12px 14px;border-radius:12px;background:#0284c7;color:#ffffff;text-decoration:none;">
            Presensi Masuk
        </a>
        <a href="{{ route('karyawan.presensi.pulang') }}" style="display:block;padding:12px 14px;border-radius:12px;background:#7c3aed;color:#ffffff;text-decoration:none;">
            Presensi Pulang
        </a>
        <a href="{{ route('karyawan.jadwal') }}" style="display:block;padding:12px 14px;border-radius:12px;background:#16a34a;color:#ffffff;text-decoration:none;">
            Jadwal Mingguan
        </a>
        <a href="{{ route('karyawan.riwayat') }}" style="display:block;padding:12px 14px;border-radius:12px;background:#f97316;color:#ffffff;text-decoration:none;">
            Riwayat Presensi
        </a>
    </div>
</x-filament::section>
