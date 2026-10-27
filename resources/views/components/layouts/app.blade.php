@props(['title' => null, 'noindex' => false])

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
        <header class="border-b border-line bg-surface">
            <div class="mx-auto flex max-w-3xl items-center justify-between gap-4 px-4 py-3">
                <a href="/" class="text-lg font-semibold tracking-tight text-accent-strong">{{ config('app.name') }}</a>
                <div class="flex items-center gap-3">
                    <x-language-switcher />
                    <x-theme-toggle />
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-3xl flex-1 px-4 py-8">
            {{ $slot }}
        </main>

        <footer class="border-t border-line text-sm text-muted">
            <div class="mx-auto max-w-3xl px-4 py-6">
                <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-2">
                    <span>{{ config('app.name') }}</span>
                    <nav class="flex flex-wrap gap-x-4 gap-y-1" aria-label="{{ __('Information') }}">
                        <a href="{{ route('about') }}" class="hover:text-ink hover:underline">{{ __('About') }}</a>
                        <a href="{{ route('faq') }}" class="hover:text-ink hover:underline">{{ __('FAQ') }}</a>
                        <a href="{{ route('privacy') }}" class="hover:text-ink hover:underline">{{ __('Privacy') }}</a>
                        <a href="{{ route('cookies') }}" class="hover:text-ink hover:underline">{{ __('Cookies') }}</a>
                    </nav>
                    @cookieconsentbutton(action: 'reset', label: __('cookieConsent::cookies.manage'), attributes: ['class' => 'cookie-reset'])
                </div>
            </div>
        </footer>

        @livewireScripts
        @cookieconsentview
    </body>
</html>
