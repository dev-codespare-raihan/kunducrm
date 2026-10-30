<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}
        <div class="mt-8 border-t border-gray-200 pt-6 dark:border-gray-700">
            <x-filament::button type="submit" icon="heroicon-o-check">Save website settings</x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
