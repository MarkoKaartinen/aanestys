<div
    x-data="{
        theme: (() => { try { return localStorage.getItem('theme') || 'system' } catch (e) { return 'system' } })(),
        set(value) {
            this.theme = value
            try {
                value === 'system' ? localStorage.removeItem('theme') : localStorage.setItem('theme', value)
            } catch (e) {}
            window.applyTheme()
        },
    }"
    role="radiogroup"
    aria-label="{{ __('Theme') }}"
    class="inline-flex rounded-lg border border-line-strong bg-surface p-0.5"
>
    @foreach (['light' => __('Light'), 'system' => __('Auto'), 'dark' => __('Dark')] as $value => $label)
        <button
            type="button"
            role="radio"
            title="{{ $label }}"
            x-on:click="set('{{ $value }}')"
            x-bind:aria-checked="theme === '{{ $value }}'"
            x-bind:class="theme === '{{ $value }}' ? 'bg-accent-soft text-accent-strong' : 'text-muted hover:text-ink'"
            class="grid size-7 place-items-center rounded-md transition-colors"
        >
            <span class="sr-only">{{ $label }}</span>
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                @if ($value === 'light')
                    <circle cx="12" cy="12" r="4" />
                    <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" />
                @elseif ($value === 'system')
                    <rect x="3" y="4" width="18" height="12" rx="2" />
                    <path d="M8 20h8M12 16v4" />
                @else
                    <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z" />
                @endif
            </svg>
        </button>
    @endforeach
</div>
