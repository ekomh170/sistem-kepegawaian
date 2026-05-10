<?php

namespace App\Filament\Resources\RekapPresensiBulanans\Pages;

use App\Filament\Resources\RekapPresensiBulanans\RekapPresensiBulananResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRekapPresensiBulanan extends EditRecord
{
    protected static string $resource = RekapPresensiBulananResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
