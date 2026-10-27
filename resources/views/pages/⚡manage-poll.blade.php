<?php

use App\Models\Poll;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app', ['noindex' => true])] class extends Component
{
    public Poll $poll;

    public string $token = '';

    public function mount(string $token): void
    {
        $this->poll = Poll::findByAdminToken($token) ?? abort(404);
        $this->token = $token;
    }
};
?>

<div class="space-y-8">
    <header class="space-y-2">
        <p class="text-sm font-medium text-accent-strong">{{ __('Your poll is ready') }}</p>
        <h1 class="text-3xl font-semibold tracking-tight">{{ $poll->title }}</h1>
    </header>

    <section class="space-y-2" x-data="{ copied: false }">
        <h2 class="font-medium">{{ __('Share link') }}</h2>
        <p class="field-hint">{{ __('Send this to the people who should vote.') }}</p>
        <div class="flex gap-2">
            <input type="text" readonly value="{{ route('poll.show', $poll->slug) }}" class="field-input" x-ref="share" x-on:focus="$el.select()" aria-label="{{ __('Share link') }}">
            <button type="button" class="btn-secondary" x-on:click="navigator.clipboard.writeText($refs.share.value); copied = true; setTimeout(() => copied = false, 2000)">
                <span x-show="! copied">{{ __('Copy') }}</span>
                <span x-show="copied" x-cloak>{{ __('Copied') }}</span>
            </button>
        </div>
    </section>

    <section class="space-y-2 rounded-2xl border border-winner bg-winner-soft p-4" x-data="{ copied: false }">
        <h2 class="font-medium">{{ __('Admin link') }}</h2>
        <p class="text-sm">{{ __('Keep this link private and save it now. It is the only way to manage the poll, and we cannot recover it for you.') }}</p>
        <div class="flex gap-2">
            <input type="text" readonly value="{{ route('poll.manage', $token) }}" class="field-input" x-ref="admin" x-on:focus="$el.select()" aria-label="{{ __('Admin link') }}">
            <button type="button" class="btn-secondary" x-on:click="navigator.clipboard.writeText($refs.admin.value); copied = true; setTimeout(() => copied = false, 2000)">
                <span x-show="! copied">{{ __('Copy') }}</span>
                <span x-show="copied" x-cloak>{{ __('Copied') }}</span>
            </button>
        </div>
    </section>

    <p class="field-hint">
        {{ __('This poll and its votes will be deleted on :date.', ['date' => $poll->expires_at->setTimezone($poll->timezone)->isoFormat('LL')]) }}
    </p>

    <a href="{{ route('poll.show', $poll->slug) }}" class="btn-primary">{{ __('Open the poll') }}</a>
</div>
