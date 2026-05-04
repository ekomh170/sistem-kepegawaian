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
                    'nip' => $data['admin_nip'],
                    'divisi' => $data['admin_divisi'],
                    'level_akses' => $data['admin_level_akses'],
                ]),
                'supervisor' => Supervisor::create([
                    'user_id' => $user->id,
                    'jabatan' => $data['supervisor_jabatan'],
                    'level_akses' => $data['supervisor_level_akses'],
                ]),
                'karyawan' => Karyawan::create([
                    'user_id' => $user->id,
                    'nik' => $data['karyawan_nik'],
                    'posisi_karyawan' => $data['karyawan_posisi_karyawan'],
                    'tgl_masuk' => $data['karyawan_tgl_masuk'],
                    'status_kontrak' => $data['karyawan_status_kontrak'],
                    'no_hp' => $data['karyawan_no_hp'],
                    'bidang_tugas' => $data['karyawan_bidang_tugas'],
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
