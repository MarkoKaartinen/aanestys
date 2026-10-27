<?php

use App\Actions\CastVote;
use App\Enums\PollPhase;
use App\Enums\VotingMethod;
use App\Models\Poll;
use App\Models\Vote;
use App\Support\Throttle;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.app', ['noindex' => true])] class extends Component
{
    public Poll $poll;

    public string $viewerTimezone = '';

    public ?int $choice = null;

    /** @var list<int|string> */
    public array $selected = [];

    /** @var list<int> */
    public array $ranked = [];

    public bool $hasVoted = false;

    public bool $justSaved = false;

    public function mount(string $slug): void
    {
        $this->poll = Poll::where('slug', $slug)->with('options')->firstOrFail();
        $this->viewerTimezone = $this->poll->timezone;

        $this->restoreVote();

        if ($this->poll->voting_method === VotingMethod::Ranking && ! $this->hasVoted && $this->places === $this->poll->options->count()) {
            $this->ranked = $this->poll->options->pluck('id')->shuffle()->all();
        }
    }

    #[Computed]
    public function phase(): PollPhase
    {
        return $this->poll->phase();
    }

    #[Computed]
    public function places(): int
    {
        return $this->poll->rankingPlaces();
    }

    /**
     * Options a voter has not placed in their ranking yet.
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\PollOption>
     */
    #[Computed]
    public function available()
    {
        return $this->poll->options->whereNotIn('id', $this->ranked);
    }

    public function updatedViewerTimezone(): void
    {
        if (! in_array($this->viewerTimezone, DateTimeZone::listIdentifiers(), true)) {
            $this->viewerTimezone = $this->poll->timezone;
        }
    }

    /**
     * Fill the form with the voter's earlier vote, found through their cookie.
     */
    private function restoreVote(): void
    {
        $token = request()->cookie('voter');

        if (! is_string($token) || $token === '') {
            return;
        }

        $vote = Vote::where('poll_id', $this->poll->id)
            ->where('voter_token_hash', $this->poll->voterHash($token))
            ->with('choices')
            ->first();

        if (! $vote) {
            return;
        }

        $ids = $vote->choices->pluck('poll_option_id')->map(fn ($id) => (int) $id)->all();

        $this->hasVoted = true;

        match ($this->poll->voting_method) {
            VotingMethod::Single => $this->choice = $ids[0] ?? null,
            VotingMethod::Multiple => $this->selected = $ids,
            VotingMethod::Ranking => $this->ranked = $ids,
        };
    }

    public function vote(CastVote $castVote): void
    {
        $this->resetErrorBag();

        if (Throttle::exceeded('vote', 30, 60)) {
            $this->addError('selection', __('Too many attempts. Try again in a minute.'));

            return;
        }

        $token = request()->cookie('voter');
        $token = is_string($token) && $token !== '' ? $token : Str::random(40);

        $optionIds = match ($this->poll->voting_method) {
            VotingMethod::Single => $this->choice ? [$this->choice] : [],
            VotingMethod::Multiple => array_map('intval', $this->selected),
            VotingMethod::Ranking => $this->ranked,
        };

        $castVote->handle($this->poll, $token, $optionIds);

        Cookie::queue('voter', $token, 60 * 24 * 365);

        $this->hasVoted = true;
        $this->justSaved = true;
    }

    public function addToRanking(int $id): void
    {
        if (count($this->ranked) < $this->places && $this->poll->options->contains('id', $id) && ! in_array($id, $this->ranked, true)) {
            $this->ranked[] = $id;
        }
    }

    public function removeFromRanking(int $id): void
    {
        $this->ranked = array_values(array_diff($this->ranked, [$id]));
    }

    public function moveRanked(int $id, int $direction): void
    {
        $index = array_search($id, $this->ranked, true);
        $target = $index === false ? null : $index + $direction;

        if ($target === null || $target < 0 || $target >= count($this->ranked)) {
            return;
        }

        [$this->ranked[$index], $this->ranked[$target]] = [$this->ranked[$target], $this->ranked[$index]];
    }

    /**
     * Handle dragging between and within the "ranked" and "available" lists.
     */
    public function sortRanked(int|string $id, int $position, string $group): void
    {
        $id = (int) $id;

        $without = array_values(array_diff($this->ranked, [$id]));

        if ($group !== 'ranked' || ! $this->poll->options->contains('id', $id)) {
            $this->ranked = $without;

            return;
        }

        if (count($without) >= $this->places) {
            return;
        }

        array_splice($without, $position, 0, [$id]);
        $this->ranked = $without;
    }
};
?>

<div
    class="space-y-6"
    @if ($poll->includes_time)
        x-data
        x-init="
            const zone = Intl.DateTimeFormat().resolvedOptions().timeZone
            if (zone && zone !== $wire.viewerTimezone) $wire.$set('viewerTimezone', zone)
        "
    @endif
>
    <header class="space-y-2">
        <h1 class="text-3xl font-semibold tracking-tight">{{ $poll->title }}</h1>
        @if ($poll->description)
            <p class="whitespace-pre-line text-muted">{{ $poll->description }}</p>
        @endif
    </header>

    @if ($poll->includes_time)
        <div>
            <label for="viewerTimezone" class="field-label">{{ __('Show times in') }}</label>
            <select id="viewerTimezone" wire:model.live="viewerTimezone" class="field-input sm:w-72">
                @foreach (DateTimeZone::listIdentifiers() as $identifier)
                    <option value="{{ $identifier }}">{{ $identifier }}</option>
                @endforeach
            </select>
        </div>
    @endif

    @if ($this->phase === PollPhase::Voting)
        <form wire:submit="vote" class="space-y-6" novalidate>
            @if ($hasVoted && ! $justSaved)
                <p class="rounded-xl bg-accent-soft px-4 py-3 text-sm text-accent-strong">
                    {{ __('You have already voted. You can change your vote until voting ends.') }}
                </p>
            @endif

            @if ($poll->voting_method === VotingMethod::Single)
                <fieldset class="space-y-2">
                    <legend class="field-label">{{ __('Choose one option.') }}</legend>
                    @foreach ($poll->options as $option)
                        <label wire:key="option-{{ $option->id }}" class="flex cursor-pointer items-center gap-3 rounded-xl border border-line bg-surface px-4 py-3 has-checked:border-accent has-checked:bg-accent-soft">
                            <input type="radio" wire:model="choice" value="{{ $option->id }}" class="size-4 accent-[var(--accent)]">
                            <span>{{ $option->displayLabel($viewerTimezone) }}</span>
                        </label>
                    @endforeach
                </fieldset>
            @elseif ($poll->voting_method === VotingMethod::Multiple)
                <fieldset class="space-y-2">
                    <legend class="field-label">
                        {{ $poll->max_choices ? __('Choose up to :max options.', ['max' => $poll->max_choices]) : __('Choose as many as you like.') }}
                    </legend>
                    @foreach ($poll->options as $option)
                        <label wire:key="option-{{ $option->id }}" class="flex cursor-pointer items-center gap-3 rounded-xl border border-line bg-surface px-4 py-3 has-checked:border-accent has-checked:bg-accent-soft">
                            <input type="checkbox" wire:model="selected" value="{{ $option->id }}" class="size-4 accent-[var(--accent)]">
                            <span>{{ $option->displayLabel($viewerTimezone) }}</span>
                        </label>
                    @endforeach
                </fieldset>
            @else
                <div class="space-y-4">
                    <p class="text-sm text-muted">
                        {{ $this->places === $poll->options->count()
                            ? __('Put the options in your order of preference. Drag them or use the arrows.')
                            : __('Pick and order your top :places. Drag options between the lists or use the buttons.', ['places' => $this->places]) }}
                    </p>

                    <div>
                        <h2 class="field-label">{{ __('Your ranking') }} ({{ count($ranked) }}/{{ $this->places }})</h2>
                        <ol wire:sort="sortRanked" wire:sort:group="ranking" wire:sort:group-id="ranked" class="min-h-14 space-y-2 rounded-xl border border-dashed border-line p-2">
                            @foreach ($ranked as $position => $optionId)
                                @php($option = $poll->options->firstWhere('id', $optionId))
                                <li wire:key="ranked-{{ $optionId }}" wire:sort:item="{{ $optionId }}" class="flex cursor-grab items-center gap-2 rounded-lg border border-accent bg-accent-soft px-3 py-2">
                                    <span class="w-6 text-sm font-semibold text-accent-strong">{{ $position + 1 }}</span>
                                    <span class="flex-1">{{ $option->displayLabel($viewerTimezone) }}</span>
                                    <button type="button" wire:click="moveRanked({{ $optionId }}, -1)" class="btn-secondary px-2 py-0.5" aria-label="{{ __('Move up') }}" @disabled($position === 0)>&uarr;</button>
                                    <button type="button" wire:click="moveRanked({{ $optionId }}, 1)" class="btn-secondary px-2 py-0.5" aria-label="{{ __('Move down') }}" @disabled($position === count($ranked) - 1)>&darr;</button>
                                    <button type="button" wire:click="removeFromRanking({{ $optionId }})" class="btn-secondary px-2 py-0.5" aria-label="{{ __('Remove from ranking') }}">&times;</button>
                                </li>
                            @endforeach
                        </ol>
                    </div>

                    @if ($this->available->isNotEmpty())
                        <div>
                            <h2 class="field-label">{{ __('Available options') }}</h2>
                            <ul wire:sort="sortRanked" wire:sort:group="ranking" wire:sort:group-id="available" class="min-h-14 space-y-2 rounded-xl border border-dashed border-line p-2">
                                @foreach ($this->available as $option)
                                    <li wire:key="available-{{ $option->id }}" wire:sort:item="{{ $option->id }}" class="flex cursor-grab items-center gap-2 rounded-lg border border-line bg-surface px-3 py-2">
                                        <span class="flex-1">{{ $option->displayLabel($viewerTimezone) }}</span>
                                        <button type="button" wire:click="addToRanking({{ $option->id }})" class="btn-secondary px-3 py-0.5" aria-label="{{ __('Add to ranking') }}">+</button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endif

            @error('selection') <p class="field-error" role="alert">{{ $message }}</p> @enderror

            <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                {{ $hasVoted ? __('Change vote') : __('Submit vote') }}
            </button>

            @if ($justSaved)
                <p class="text-sm font-medium text-accent-strong" role="status">{{ __('Your vote has been saved. You can change it until voting ends.') }}</p>
            @endif

            <p class="field-hint">
                {{ __('Voting ends :date.', ['date' => $poll->voting_ends_at->setTimezone($viewerTimezone)->isoFormat('LLLL')]) }}
            </p>
        </form>
    @elseif ($this->phase === PollPhase::Scheduled)
        <p class="rounded-xl bg-accent-soft px-4 py-3 text-accent-strong">
            {{ __('Voting opens :date.', ['date' => $poll->voting_starts_at->setTimezone($viewerTimezone)->isoFormat('LLLL')]) }}
        </p>
    @elseif ($this->phase === PollPhase::Suggestions)
        <p class="rounded-xl bg-accent-soft px-4 py-3 text-accent-strong">{{ __('Suggestions are being collected. Voting has not started yet.') }}</p>
    @else
        <p class="rounded-xl bg-accent-soft px-4 py-3 text-accent-strong">{{ __('Voting has ended.') }}</p>
    @endif
</div>
