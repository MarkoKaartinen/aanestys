@props(['code', 'title', 'message'])

{{-- Self-contained on purpose: no database, cache, cookie banner or named routes, so it still renders when something else is broken. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">

        <title>{{ $title }} - {{ config('app.name') }}</title>

        <script>
            try {
                const theme = localStorage.getItem('theme');
                if (theme === 'dark' || (theme !== 'light' && matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        </script>

        @vite(['resources/css/app.css'])
    </head>
    <body class="flex min-h-screen items-center justify-center px-4">
        <main class="max-w-md space-y-4 text-center">
            <p class="text-6xl font-semibold tracking-tight text-accent-strong">{{ $code }}</p>
            <h1 class="text-2xl font-semibold tracking-tight">{{ $title }}</h1>
            <p class="text-muted">{{ $message }}</p>
            <a href="{{ app()->getLocale() === config('app.fallback_locale') ? '/' : '/'.app()->getLocale() }}" class="btn-primary">{{ __('Back to the front page') }}</a>
        </main>
    </body>
</html>
