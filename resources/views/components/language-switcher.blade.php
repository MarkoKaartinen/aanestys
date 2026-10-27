<nav aria-label="{{ __('Language') }}" class="flex gap-1 text-sm">
    @foreach (['en' => 'EN', 'fi' => 'FI'] as $locale => $label)
        <a
            href="{{ Route::localizedSwitcherUrl($locale) }}"
            hreflang="{{ $locale }}"
            lang="{{ $locale }}"
            @if (app()->getLocale() === $locale) aria-current="true" @endif
            class="{{ app()->getLocale() === $locale ? 'bg-accent-soft text-accent-strong' : 'text-muted hover:text-ink' }} rounded-full px-2.5 py-1 font-medium transition-colors"
        >{{ $label }}</a>
    @endforeach
</nav>
