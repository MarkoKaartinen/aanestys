<nav aria-label="{{ __('Language') }}" class="inline-flex rounded-lg border border-line-strong bg-surface p-0.5 font-mono text-xs">
    @foreach (['en' => 'EN', 'fi' => 'FI'] as $locale => $label)
        <a
            href="{{ Route::localizedSwitcherUrl($locale) }}"
            hreflang="{{ $locale }}"
            lang="{{ $locale }}"
            @if (app()->getLocale() === $locale) aria-current="true" @endif
            class="{{ app()->getLocale() === $locale ? 'bg-accent-soft text-accent-strong' : 'text-muted hover:text-ink' }} grid h-7 place-items-center rounded-md px-2 font-medium transition-colors"
        >{{ $label }}</a>
    @endforeach
</nav>
