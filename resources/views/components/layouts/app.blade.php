@props(['title' => null, 'noindex' => false, 'wide' => false])

<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    data-message-expired="{{ __('This page has expired. Refresh the page to continue?') }}"
    data-message-too-many="{{ __('You are doing that too often. Wait a moment and try again.') }}"
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @unless ($noindex)
            @foreach (config('localizer.supported_locales') as $alternate)
                <link rel="alternate" hreflang="{{ $alternate }}" href="{{ Route::localizedUrl($alternate) }}">
            @endforeach
        @endunless

        @if ($noindex)
            <meta name="robots" content="noindex, nofollow">
            <meta name="referrer" content="no-referrer">
        @endif

        <title>{{ $title ? $title.' - ' : '' }}{{ config('app.name') }}</title>

        <script>
            try {
                const theme = localStorage.getItem('theme');
                if (theme === 'dark' || (theme !== 'light' && matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        </script>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        @cookieconsentscripts
    </head>
    <body class="flex min-h-screen flex-col">
        <a href="#main" class="sr-only rounded-lg bg-surface px-3 py-2 focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50">{{ __('Skip to content') }}</a>

        <header class="sticky top-0 z-40 border-b border-line bg-canvas/80 backdrop-blur">
            <div class="mx-auto flex h-14 max-w-5xl items-center justify-between gap-4 px-4">
                <div class="flex items-center gap-8">
                    <a href="{{ route('home') }}" class="flex items-center gap-2 font-semibold tracking-tight">
                        <x-logo-mark class="size-5 text-accent" />
                        {{ config('app.name') }}
                    </a>
                    <nav class="hidden items-center gap-5 text-sm text-muted sm:flex" aria-label="{{ __('Information') }}">
                        <a href="{{ route('about') }}" class="transition-colors hover:text-ink">{{ __('About') }}</a>
                        <a href="{{ route('faq') }}" class="transition-colors hover:text-ink">{{ __('FAQ') }}</a>
                    </nav>
                </div>
                <div class="flex items-center gap-2">
                    <x-language-switcher />
                    <x-theme-toggle />
                </div>
            </div>
        </header>

        <main id="main" class="{{ $wide ? 'max-w-5xl' : 'max-w-3xl' }} mx-auto w-full flex-1 px-4 py-10">
            {{ $slot }}
        </main>

        <footer class="border-t border-line text-sm text-muted">
            <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-x-6 gap-y-3 px-4 py-6">
                <p class="flex items-center gap-2">
                    <x-logo-mark class="size-4 text-accent" />
                    <span>{{ config('app.name') }}</span>
                    <span class="font-mono text-xs">· {{ __('Free, no accounts, no ads.') }}</span>
                </p>
                <nav class="flex flex-wrap items-center gap-x-5 gap-y-1" aria-label="{{ __('Information') }}">
                    <a href="{{ route('about') }}" class="transition-colors hover:text-ink">{{ __('About') }}</a>
                    <a href="{{ route('faq') }}" class="transition-colors hover:text-ink">{{ __('FAQ') }}</a>
                    <a href="{{ route('privacy') }}" class="transition-colors hover:text-ink">{{ __('Privacy') }}</a>
                    <a href="{{ route('cookies') }}" class="transition-colors hover:text-ink">{{ __('Cookies') }}</a>
                    @cookieconsentbutton(action: 'reset', label: __('cookieConsent::cookies.manage'), attributes: ['class' => 'cookie-reset'])
                </nav>
            </div>
        </footer>

        @livewireScripts
        @cookieconsentview
    </body>
</html>
