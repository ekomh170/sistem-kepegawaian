<?php

namespace App\Filament\Resources\Akuns\Pages;

use App\Filament\Resources\Akuns\AkunResource;
use App\Models\Admin;
use App\Models\Karyawan;
use App\Models\Supervisor;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditAkun extends EditRecord
{
    protected static string $resource = AkunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $user = $this->getRecord();

        if ($user->role === 'admin' && $user->admin) {
            $data['admin_nik']   = $user->admin->nik;
            $data['admin_no_hp'] = $user->admin->no_hp;
        }

        if ($user->role === 'supervisor' && $user->supervisor) {
            $data['supervisor_nik']   = $user->supervisor->nik;
            $data['supervisor_no_hp'] = $user->supervisor->no_hp;
        }

        if ($user->role === 'karyawan' && $user->karyawan) {
            $k = $user->karyawan;
            $data['karyawan_nik']             = $k->nik;
            $data['karyawan_no_ktp']          = $k->no_ktp;
            $data['karyawan_posisi_karyawan'] = $k->posisi_karyawan;
            $data['karyawan_tgl_masuk']       = $k->tgl_masuk;
            $data['karyawan_status_kontrak']  = $k->status_kontrak;
            $data['karyawan_no_hp']           = $k->no_hp;
            $data['karyawan_bidang_tugas']    = $k->bidang_tugas;
            $data['karyawan_alamat']          = $k->alamat;
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data): Model {
            $userFields = ['nama' => $data['nama'], 'email' => $data['email'], 'role' => $data['role']];
            if (filled($data['password'] ?? null)) {
                $userFields['password'] = $data['password'];
            }
            $record->update($userFields);

            match ($data['role']) {
                'admin' => Admin::updateOrCreate(
                    ['user_id' => $record->id],
                    ['nik' => $data['admin_nik'], 'no_hp' => $data['admin_no_hp']]
                ),
                'supervisor' => Supervisor::updateOrCreate(
                    ['user_id' => $record->id],
                    ['nik' => $data['supervisor_nik'], 'no_hp' => $data['supervisor_no_hp']]
                ),
                'karyawan' => Karyawan::updateOrCreate(
                    ['user_id' => $record->id],
                    [
                        'nik'              => $data['karyawan_nik'],
                        'no_ktp'           => $data['karyawan_no_ktp'] ?? null,
                        'posisi_karyawan'  => $data['karyawan_posisi_karyawan'],
                        'tgl_masuk'        => $data['karyawan_tgl_masuk'],
                        'status_kontrak'   => $data['karyawan_status_kontrak'],
                        'no_hp'            => $data['karyawan_no_hp'],
                        'bidang_tugas'     => $data['karyawan_bidang_tugas'],
                        'alamat'           => $data['karyawan_alamat'] ?? null,
                    ]
                ),
                default => null,
            };

            return $record;
        });
    }

    protected function getRedirectUrl(): string
    {
        return AkunResource::getUrl('index');
    }
}
