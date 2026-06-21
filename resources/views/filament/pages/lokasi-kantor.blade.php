<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        @if(Auth::user()?->isAdmin())
        <div style="display:flex; justify-content:flex-end; align-items:center; gap:0.75rem; margin-top:1.5rem; padding-top:1.25rem; border-top:1px solid rgb(var(--gray-200, 229 231 235));">
            <span style="font-size:0.8rem; color:rgb(var(--gray-500, 107 114 128));">Perubahan akan langsung berlaku untuk validasi presensi.</span>
            <x-filament::button type="submit" color="success" icon="heroicon-m-check" size="lg">
                Simpan Lokasi
            </x-filament::button>
        </div>
        @endif
    </form>
</x-filament-panels::page>
