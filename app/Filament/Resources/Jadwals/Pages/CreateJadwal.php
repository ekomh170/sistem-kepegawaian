<?php

namespace App\Filament\Resources\Jadwals\Pages;

use App\Filament\Resources\Jadwals\JadwalResource;
use App\Models\Jadwal;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateJadwal extends CreateRecord
{
    protected static string $resource = JadwalResource::class;

    private int $createdCount = 0;

    protected function handleRecordCreation(array $data): Model
    {
        $tanggalAkhir = $data['tanggal_akhir'] ?? null;
        unset($data['tanggal_akhir']);

        $start = Carbon::parse($data['tanggal_kerja']);
        $end = $tanggalAkhir ? Carbon::parse($tanggalAkhir) : null;

        if ($end && $end->gt($start)) {
            $firstRecord = null;
            $current = $start->copy();

            while ($current->lte($end)) {
                $dateStr = $current->toDateString();
                $exists = Jadwal::where('karyawan_id', $data['karyawan_id'])
                    ->where('tanggal_kerja', $dateStr)
                    ->exists();

                if (!$exists) {
                    $record = Jadwal::create(array_merge($data, ['tanggal_kerja' => $dateStr]));
                    $this->createdCount++;
                    $firstRecord ??= $record;
                }

                $current->addDay();
            }

            if ($firstRecord) {
                return $firstRecord;
            }

            // Semua tanggal di rentang sudah ada jadwalnya
            $this->createdCount = 1;
            return Jadwal::create(array_merge($data, ['tanggal_kerja' => $start->toDateString()]));
        }

        $this->createdCount = 1;
        return Jadwal::create($data);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return $this->createdCount > 1
            ? "{$this->createdCount} jadwal berhasil dibuat"
            : 'Jadwal berhasil dibuat';
    }
}
