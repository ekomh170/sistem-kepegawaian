<?php

namespace App\Filament\Resources\Karyawans\Pages;

use App\Filament\Resources\Karyawans\KaryawanResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditKaryawan extends EditRecord
{
    protected static string $resource = KaryawanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load user data into form fields
        $user = $this->record->user;
        if ($user) {
            $data['user_nama'] = $user->nama;
            $data['user_email'] = $user->email;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Update user record
        $user = $this->record->user;
        if ($user) {
            $user->nama = $data['user_nama'];
            $user->email = $data['user_email'];
            if (!empty($data['user_password'])) {
                $user->password = $data['user_password'];
            }
            $user->save();
        }

        // Remove user fields from karyawan data
        unset($data['user_nama'], $data['user_email'], $data['user_password']);

        return $data;
    }
}
