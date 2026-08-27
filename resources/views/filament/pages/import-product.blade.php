<x-filament-panels::page>
    <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-200">
        <strong>Telif uyarısı.</strong> Ürün fotoğrafları ve açıklamaları eser sayılır.
        Bu aracı yalnızca kendi sitenizden ya da içeriğini kullanma izniniz olan
        tedarikçi kataloglarından aktarım için kullanın. Başka bir mağazanın
        görsellerini izinsiz yayımlamak hukuki sorun çıkarır.
    </div>

    @if ($notes)
        <div class="rounded-lg border border-gray-200 bg-white p-4 text-sm dark:border-white/10 dark:bg-white/5">
            <p class="font-medium mb-2">Sayfa okunurken:</p>
            <ul class="list-disc ps-5 space-y-1">
                @foreach ($notes as $note)
                    <li>{{ $note }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form wire:submit="save">
        {{ $this->form }}

        @if ($fetched)
            <div class="mt-6">
                <x-filament::button type="submit">
                    Taslak ürünü oluştur
                </x-filament::button>
            </div>
        @endif
    </form>
</x-filament-panels::page>
