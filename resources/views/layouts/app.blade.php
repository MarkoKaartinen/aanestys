{{-- Default Livewire page layout; delegates to the Blade layout component. --}}
<x-layouts.app :title="$title ?? null" :noindex="$noindex ?? false" :wide="$wide ?? false">
    {{ $slot }}
</x-layouts.app>
