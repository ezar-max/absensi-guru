@php
    $livewire ??= null;
@endphp

<x-filament-panels::layout.base :livewire="$livewire">
    <div class="auth-page-container">
        {{ $slot }}
    </div>
</x-filament-panels::layout.base>
