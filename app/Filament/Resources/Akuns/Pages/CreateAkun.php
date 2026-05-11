<?php

namespace App\Filament\Resources\Akuns\Pages;

use App\Filament\Resources\Akuns\AkunResource;
use App\Models\Admin;
use App\Models\Karyawan;
use App\Models\Supervisor;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateAkun extends CreateRecord
{
    protected static string $resource = AkunResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Model {
            $user = User::create([
                'nama' => $data['nama'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => $data['role'],
            ]);

            match ($data['role']) {
                'admin' => Admin::create([
                    'user_id' => $user->id,
                    'nik' => $data['admin_nik'],
                    'no_hp' => $data['admin_no_hp'],
                ]),
                'supervisor' => Supervisor::create([
                    'user_id' => $user->id,
                    'nik' => $data['supervisor_nik'],
                    'no_hp' => $data['supervisor_no_hp'],
                ]),
                'karyawan' => Karyawan::create([
                    'user_id' => $user->id,
                    'nik' => $data['karyawan_nik'],
                    'no_ktp' => $data['karyawan_no_ktp'] ?? null,
                    'posisi_karyawan' => $data['karyawan_posisi_karyawan'],
                    'tgl_masuk' => $data['karyawan_tgl_masuk'],
                    'status_kontrak' => $data['karyawan_status_kontrak'],
                    'no_hp' => $data['karyawan_no_hp'],
                    'bidang_tugas' => $data['karyawan_bidang_tugas'],
                    'alamat' => $data['karyawan_alamat'] ?? null,
                    'gaji_pokok' => $data['karyawan_gaji_pokok'] ?? 0,
                ]),
                default => throw new \InvalidArgumentException('Role akun tidak valid.'),
            };

            return $user;
        });
    }

    protected function getRedirectUrl(): string
    {
        return AkunResource::getUrl('index');
    }
}
