{{-- <x-filament::page>
    <livewire:ticket-orders />
    <livewire:package-orders />

    @foreach ($tables as $table)
        @livewire($table)
    @endforeach
</x-filament::page> --}}

<x-filament::page :active-tab="$activeTab" :actions="null">
    <div class="flex justify-center">
        <x-filament::tabs>
            @foreach ($tables as $key)
                <x-filament::tabs.item :active="$key == $activeTab" wire:click="$set('activeTab', '{{ $key }}')">
                    {{ Str::title($key) }}
                </x-filament::tabs.item>
            @endforeach
        </x-filament::tabs>
    </div>
    @foreach ($tables as $key)
        <div @if ($activeTab != $key) hidden @endif>
            @livewire($key, key($key))
        </div>
    @endforeach
</x-filament::page>
