<?php

use App\Enums\PollPhase;
use App\Models\Poll;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

new #[Layout('layouts.app', ['noindex' => true])] class extends Component
{
    public Poll $poll;

    #[Locked]
    public string $token = '';

    public string $title = '';

    public string $description = '';

    public string $endsAt = '';

    public bool $saved = false;

    public function mount(string $token): void
    {
        $this->poll = Poll::findByAdminToken($token) ?? abort(404);
        $this->token = $token;

        $this->title = $this->poll->title;
        $this->description = (string) $this->poll->description;
        $this->endsAt = $this->poll->voting_ends_at->copy()->setTimezone($this->poll->timezone)->format('Y-m-d\TH:i');
    }

    #[Computed]
    public function phase(): PollPhase
    {
        return $this->poll->phase();
    }

    #[Computed]
    public function participants(): int
    {
        return $this->poll->votes()->count();
    }

    /**
     * Load the poll again from the admin token, so every action is checked
     * against the token and not only against the page state.
     */
    private function ownPoll(): Poll
    {
        return Poll::findByAdminToken($this->token) ?? abort(404);
    }

    public function save(): void
    {
        $this->saved = false;
        $poll = $this->ownPoll();

        $this->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'endsAt' => ['required', 'date'],
        ], attributes: [
            'title' => __('title'),
            'description' => __('description'),
            'endsAt' => __('end time'),
        ]);

        $endsAt = Carbon::parse($this->endsAt, $poll->timezone)->utc();

        if ($poll->phase() !== PollPhase::Closed && ($endsAt->lte(now()) || $endsAt->lte($poll->voting_starts_at))) {
            $this->addError('endsAt', __('The end time must be in the future.'));

            return;
        }

        $poll->title = trim($this->title);
        $poll->description = trim($this->description) ?: null;

        if ($poll->phase() !== PollPhase::Closed) {
            $poll->voting_ends_at = $endsAt;
        }

        $poll->save();

        $this->poll = $poll;
        $this->saved = true;
    }

    public function closeNow(): void
    {
        $poll = $this->ownPoll();

        if ($poll->phase() !== PollPhase::Closed) {
            $poll->update(['closed_at' => now()]);
        }

        $this->poll = $poll;
    }

    public function deletePoll(): void
    {
        $this->ownPoll()->delete();

        $this->redirectRoute('home', navigate: true);
    }
};
?>

<div class="space-y-8">
    <header class="space-y-2">
        <p class="text-sm font-medium text-accent-strong">{{ __('Poll admin') }}</p>
        <h1 class="text-3xl font-semibold tracking-tight">{{ $poll->title }}</h1>
        <p class="text-muted">
            {{ match ($this->phase) {
                PollPhase::Scheduled => __('Voting has not started yet.'),
                PollPhase::Suggestions => __('Suggestions are being collected.'),
                PollPhase::Voting => __('Voting is open.'),
                PollPhase::Closed => __('Voting has ended.'),
            } }}
            · {{ trans_choice(':count participant|:count participants', $this->participants) }}
        </p>
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

    <section class="space-y-2" x-data="{ copied: false }">
        <h2 class="font-medium">{{ __('Results link') }}</h2>
        <div class="flex gap-2">
            <input type="text" readonly value="{{ route('poll.results', $poll->slug) }}" class="field-input" x-ref="results" x-on:focus="$el.select()" aria-label="{{ __('Results link') }}">
            <button type="button" class="btn-secondary" x-on:click="navigator.clipboard.writeText($refs.results.value); copied = true; setTimeout(() => copied = false, 2000)">
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

    <form wire:submit="save" class="space-y-4" novalidate>
        <h2 class="font-medium">{{ __('Edit poll') }}</h2>

        <div>
            <label for="title" class="field-label">{{ __('Question') }}</label>
            <input id="title" type="text" wire:model="title" class="field-input" maxlength="200">
            @error('title') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="description" class="field-label">{{ __('Description (optional)') }}</label>
            <textarea id="description" wire:model="description" rows="3" class="field-input" maxlength="2000"></textarea>
            @error('description') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        @if ($this->phase !== PollPhase::Closed)
            <div>
                <label for="endsAt" class="field-label">{{ __('Voting ends') }} ({{ $poll->timezone }})</label>
                <input id="endsAt" type="datetime-local" wire:model="endsAt" class="field-input sm:w-72">
                @error('endsAt') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        @endif

        <div class="flex items-center gap-3">
            <button type="submit" class="btn-primary" wire:loading.attr="disabled">{{ __('Save changes') }}</button>
            @if ($saved)
                <p class="text-sm font-medium text-accent-strong" role="status">{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>

    <p class="field-hint">
        {{ __('This poll and its votes will be deleted on :date.', ['date' => $poll->expires_at->setTimezone($poll->timezone)->isoFormat('LL')]) }}
    </p>

    <div class="flex flex-wrap gap-3">
        <a href="{{ route('poll.show', $poll->slug) }}" class="btn-secondary">{{ __('Open the poll') }}</a>
        <a href="{{ route('poll.results', $poll->slug) }}" class="btn-secondary">{{ __('See the results') }}</a>
    </div>

    <section class="space-y-3 rounded-2xl border border-danger p-4">
        <h2 class="font-medium text-danger">{{ __('Danger zone') }}</h2>
        <div class="flex flex-wrap gap-3">
            @if ($this->phase !== PollPhase::Closed)
                <button type="button" wire:click="closeNow" wire:confirm="{{ __('Close the poll now? Nobody can vote after this.') }}" class="btn-secondary">{{ __('Close voting now') }}</button>
            @endif
            <button type="button" wire:click="deletePoll" wire:confirm="{{ __('Delete the poll and all its votes? This cannot be undone.') }}" class="btn border-danger text-danger hover:bg-danger hover:text-on-accent">{{ __('Delete poll') }}</button>
        </div>
    </section>
</div>
