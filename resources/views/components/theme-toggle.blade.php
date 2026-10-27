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
    class="inline-flex rounded-full border border-line bg-canvas p-0.5 text-sm"
>
    @foreach (['light' => __('Light'), 'system' => __('Auto'), 'dark' => __('Dark')] as $value => $label)
        <button
            type="button"
            role="radio"
            x-on:click="set('{{ $value }}')"
            x-bind:aria-checked="theme === '{{ $value }}'"
            x-bind:class="theme === '{{ $value }}' ? 'bg-accent text-on-accent' : 'text-muted hover:text-ink'"
            class="rounded-full px-3 py-1 font-medium transition-colors"
        >{{ $label }}</button>
    @endforeach
</div>
