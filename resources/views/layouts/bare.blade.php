@props(['title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <meta name="referrer" content="no-referrer">

        <title>{{ $title ?? config('app.name') }}</title>

        <script>
            // ?theme=dark or ?theme=light forces a theme, otherwise the browser decides.
            try {
                const theme = new URLSearchParams(location.search).get('theme');
                if (theme === 'dark' || (theme !== 'light' && matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        </script>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    {{-- Transparent, so it can sit on top of a stream in OBS. --}}
    <body class="p-4" style="background: transparent">
        {{ $slot }}

        @livewireScripts
    </body>
</html>
