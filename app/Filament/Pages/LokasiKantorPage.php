<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Dotswan\MapPicker\Fields\Map;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Auth;
use Filament\Support\Icons\Heroicon;

class LokasiKantorPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;
    protected static string|\UnitEnum|null $navigationGroup = 'Sistem';
    protected static ?string $navigationLabel = 'Lokasi Kantor';
    protected static ?string $title = 'Pengaturan Lokasi Kantor';
    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.lokasi-kantor';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'location' => [
                'lat' => (float) Setting::get('kantor_lat', -6.2087634),
                'lng' => (float) Setting::get('kantor_lng', 106.8222568),
            ],
            'kantor_lat' => Setting::get('kantor_lat', '-6.2087634'),
            'kantor_lng' => Setting::get('kantor_lng', '106.8222568'),
            'kantor_radius' => Setting::get('kantor_radius', '500'),
        ]);
    }

    public function form(\Filament\Schemas\Schema $form): \Filament\Schemas\Schema
    {
        return $form
            ->schema([
                Section::make('Lokasi Kantor Pusat')
                    ->description('Klik atau geser pin di peta untuk mengubah lokasi kantor. Koordinat akan terisi otomatis.')
                    ->columnSpanFull()
                    ->schema([
                        Map::make('location')
                            ->label('Pilih Lokasi Kantor di Peta')
                            ->columnSpanFull()
                            ->defaultLocation(
                                latitude: (float) Setting::get('kantor_lat', -6.2087634),
                                longitude: (float) Setting::get('kantor_lng', 106.8222568)
                            )
                            ->afterStateUpdated(function ($set, ?array $state): void {
                                if (!$state || !isset($state['lat'], $state['lng'])) {
                                    return;
                                }
                                $set('kantor_lat', (string) $state['lat']);
                                $set('kantor_lng', (string) $state['lng']);
                            })
                            ->live(onBlur: true)
                            ->showMarker()
                            ->markerColor('#ef4444')
                            ->showFullscreenControl()
                            ->showZoomControl()
                            ->draggable()
                            ->clickable(true),

                        TextInput::make('kantor_lat')
                            ->label('Latitude Kantor')
                            ->required()
                            ->live(),

                        TextInput::make('kantor_lng')
                            ->label('Longitude Kantor')
                            ->required()
                            ->live(),

                        TextInput::make('kantor_radius')
                            ->label('Radius Presensi (Meter)')
                            ->required()
                            ->numeric()
                            ->suffix('meter')
                            ->helperText('Jarak maksimal karyawan dari titik kantor untuk presensi'),
                    ]),
            ])
            ->statePath('data')
            ->disabled(! Auth::user()?->isAdmin());
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::set('kantor_lat', $data['kantor_lat']);
        Setting::set('kantor_lng', $data['kantor_lng']);
        Setting::set('kantor_radius', $data['kantor_radius']);
        Setting::clearCache();

        Notification::make()
            ->title('Lokasi kantor berhasil disimpan!')
            ->success()
            ->send();
    }


    public static function canAccess(): bool
    {
        return (Auth::user()?->isAdmin() || Auth::user()?->isSupervisor());
    }
}
