<x-layouts.app>
    <section class="space-y-6">
        <h1 class="text-3xl font-semibold tracking-tight">{{ __('Make a decision together') }}</h1>
        <p class="text-muted">{{ __('Free, ad-free polls without accounts.') }}</p>

        <div class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
            <h2 class="font-medium">{{ __('Which book should we read next?') }}</h2>

            <ul class="mt-4 space-y-3">
                <li class="rounded-xl border border-winner bg-winner-soft p-3">
                    <div class="flex justify-between text-sm font-medium"><span>Dune</span><span>48%</span></div>
                    <div class="mt-2 h-2 rounded-full bg-line"><div class="h-2 w-[48%] rounded-full bg-winner"></div></div>
                </li>
                <li class="rounded-xl border border-line p-3">
                    <div class="flex justify-between text-sm font-medium"><span>Piranesi</span><span>32%</span></div>
                    <div class="mt-2 h-2 rounded-full bg-line"><div class="h-2 w-[32%] rounded-full bg-accent"></div></div>
                </li>
                <li class="rounded-xl border border-line p-3">
                    <div class="flex justify-between text-sm font-medium"><span>Tuntematon sotilas</span><span>20%</span></div>
                    <div class="mt-2 h-2 rounded-full bg-line"><div class="h-2 w-[20%] rounded-full bg-accent"></div></div>
                </li>
            </ul>

            <button type="button" class="mt-5 rounded-full bg-accent px-5 py-2 font-medium text-on-accent transition-colors hover:bg-accent-strong">
                {{ __('Vote') }}
            </button>
        </div>
    </section>
</x-layouts.app>
