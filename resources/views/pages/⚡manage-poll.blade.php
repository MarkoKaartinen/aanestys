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

    public bool $liveResultsVisible = true;

    public bool $saved = false;

    public function mount(string $token): void
    {
        $this->poll = Poll::findByAdminToken($token) ?? abort(404);
        $this->token = $token;

        $this->liveResultsVisible = $this->poll->live_results_visible;
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
            'liveResultsVisible' => ['boolean'],
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
        $poll->live_results_visible = $this->liveResultsVisible;

        if ($poll->phase() !== PollPhase::Closed) {
            $poll->voting_ends_at = $endsAt;
        }

        $poll->save();

        $this->poll = $poll;
        $this->saved = true;
    }

    public function removeOption(int $id): void
    {
        $poll = $this->ownPoll();

        if ($poll->canEditOptions()) {
            $poll->options()->whereKey($id)->delete();
        }

        $this->poll = $poll;
    }

    public function closeSuggestions(): void
    {
        $poll = $this->ownPoll();

        if ($poll->phase() === PollPhase::Suggestions) {
            $poll->forceFill(['suggestions_ends_at' => now(), 'voting_starts_at' => now()])->save();
        }

        $this->poll = $poll;
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
    <header class="space-y-3">
        <p class="eyebrow text-accent">{{ __('Poll admin') }}</p>
        <h1 class="text-3xl font-semibold tracking-tight">{{ $poll->title }}</h1>
        <p class="flex flex-wrap items-center gap-3 text-muted">
            <x-phase-badge :phase="$this->phase" />
            <span class="font-mono text-sm">{{ trans_choice(':count participant|:count participants', $this->participants) }}</span>
        </p>
    </header>

    <section class="card space-y-2 p-5" x-data="{ copied: false }">
        <h2 class="font-medium">{{ __('Share link') }}</h2>
        <p class="field-hint">{{ __('Send this to the people who should vote.') }}</p>
        <div class="flex gap-2">
            <input type="text" readonly value="{{ route('poll.show', $poll->slug) }}" class="field-input font-mono text-sm" x-ref="share" x-on:focus="$el.select()" aria-label="{{ __('Share link') }}">
            <button type="button" class="btn-secondary" x-on:click="navigator.clipboard.writeText($refs.share.value); copied = true; setTimeout(() => copied = false, 2000)">
                <span x-show="! copied">{{ __('Copy') }}</span>
                <span x-show="copied" x-cloak>{{ __('Copied') }}</span>
            </button>
        </div>
    </section>

    <section class="card space-y-2 p-5" x-data="{ copied: false }">
        <h2 class="font-medium">{{ __('Results link') }}</h2>
        <div class="flex gap-2">
            <input type="text" readonly value="{{ route('poll.results', $poll->slug) }}" class="field-input font-mono text-sm" x-ref="results" x-on:focus="$el.select()" aria-label="{{ __('Results link') }}">
            <button type="button" class="btn-secondary" x-on:click="navigator.clipboard.writeText($refs.results.value); copied = true; setTimeout(() => copied = false, 2000)">
                <span x-show="! copied">{{ __('Copy') }}</span>
                <span x-show="copied" x-cloak>{{ __('Copied') }}</span>
            </button>
        </div>
    </section>

    @if ($poll->live_token)
        <section class="card space-y-2 p-5" x-data="{ copied: false }">
            <h2 class="font-medium">{{ __('Live link for streaming') }}</h2>
            <p class="field-hint">{{ __('Always shows the live results, even when they are hidden from participants. Add it as a browser source in OBS. Add ?theme=dark or ?theme=light to force a theme.') }}</p>
            <div class="flex gap-2">
                <input type="text" readonly value="{{ route('poll.live', $poll->live_token) }}" class="field-input font-mono text-sm" x-ref="live" x-on:focus="$el.select()" aria-label="{{ __('Live link for streaming') }}">
                <button type="button" class="btn-secondary" x-on:click="navigator.clipboard.writeText($refs.live.value); copied = true; setTimeout(() => copied = false, 2000)">
                    <span x-show="! copied">{{ __('Copy') }}</span>
                    <span x-show="copied" x-cloak>{{ __('Copied') }}</span>
                </button>
            </div>
        </section>
    @endif

    <section class="space-y-2 rounded-xl border border-winner bg-winner-soft p-5" x-data="{ copied: false }">
        <h2 class="font-medium">{{ __('Admin link') }}</h2>
        <p class="text-sm">{{ __('Keep this link private and save it now. It is the only way to manage the poll, and we cannot recover it for you.') }}</p>
        <div class="flex gap-2">
            <input type="text" readonly value="{{ route('poll.manage', $token) }}" class="field-input font-mono text-sm" x-ref="admin" x-on:focus="$el.select()" aria-label="{{ __('Admin link') }}">
            <button type="button" class="btn-secondary" x-on:click="navigator.clipboard.writeText($refs.admin.value); copied = true; setTimeout(() => copied = false, 2000)">
                <span x-show="! copied">{{ __('Copy') }}</span>
                <span x-show="copied" x-cloak>{{ __('Copied') }}</span>
            </button>
        </div>
    </section>

    @if ($poll->suggestions_starts_at)
        <section class="space-y-3">
            <h2 class="font-medium">{{ __('Suggestions') }} ({{ $poll->options->count() }})</h2>

            @forelse ($poll->options as $option)
                <div wire:key="option-{{ $option->id }}" class="flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2">
                    <span class="flex-1">{{ $option->displayLabel($poll->timezone) }}</span>
                    @if ($poll->canEditOptions())
                        <button type="button" wire:click="removeOption({{ $option->id }})" wire:confirm="{{ __('Remove this option?') }}" class="btn-secondary px-3 py-0.5" aria-label="{{ __('Remove option') }}">&times;</button>
                    @endif
                </div>
            @empty
                <p class="field-hint">{{ __('No suggestions yet.') }}</p>
            @endforelse

            @if ($this->phase === PollPhase::Suggestions)
                <button type="button" wire:click="closeSuggestions" wire:confirm="{{ __('Close the suggestions and start voting now?') }}" class="btn-secondary">{{ __('Close suggestions and start voting') }}</button>
            @endif
        </section>
    @endif

    <form wire:submit="save" class="card space-y-4 p-5" novalidate>
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

        <label class="flex items-center gap-2">
            <input type="checkbox" wire:model="liveResultsVisible" class="size-4 accent-[var(--accent)]">
            <span>{{ __('Show the results to participants while voting is open') }}</span>
        </label>

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

    <section class="space-y-3 rounded-xl border border-danger/50 p-5">
        <h2 class="font-medium text-danger">{{ __('Danger zone') }}</h2>
        <div class="flex flex-wrap gap-3">
            @if ($this->phase !== PollPhase::Closed)
                <button type="button" wire:click="closeNow" wire:confirm="{{ __('Close the poll now? Nobody can vote after this.') }}" class="btn-secondary">{{ __('Close voting now') }}</button>
            @endif
            <button type="button" wire:click="deletePoll" wire:confirm="{{ __('Delete the poll and all its votes? This cannot be undone.') }}" class="btn border-danger text-danger hover:bg-danger hover:text-on-accent">{{ __('Delete poll') }}</button>
        </div>
    </section>
</div>
