<?php

namespace App\Filament\Resources\Karyawans\Pages;

use App\Filament\Resources\Karyawans\KaryawanResource;
use App\Models\Karyawan;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateKaryawan extends CreateRecord
{
    protected static string $resource = KaryawanResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Model {
            // Buat akun user dulu
            $user = User::create([
                'nama' => $data['user_nama'],
                'email' => $data['user_email'],
                'password' => $data['user_password'],
                'role' => 'karyawan',
            ]);

            // Buat data karyawan
            return Karyawan::create([
                'user_id' => $user->id,
                'nik' => $data['nik'],
                'no_ktp' => $data['no_ktp'] ?? null,
                'posisi_karyawan' => $data['posisi_karyawan'],
                'bidang_tugas' => $data['bidang_tugas'],
                'no_hp' => $data['no_hp'],
                'foto' => $data['foto'] ?? null,
                'alamat' => $data['alamat'] ?? null,
                'tgl_masuk' => $data['tgl_masuk'],
                'status_kontrak' => $data['status_kontrak'],
                'gaji_pokok' => $data['gaji_pokok'] ?? 0,
            ]);
        });
    }

    protected function getRedirectUrl(): string
    {
        return KaryawanResource::getUrl('index');
    }
}
