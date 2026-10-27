<?php

use App\Actions\GetPollResults;
use App\Enums\PollPhase;
use App\Enums\VotingMethod;
use App\Models\Poll;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.app', ['noindex' => true])] class extends Component
{
    public Poll $poll;

    public string $viewerTimezone = '';

    public function mount(string $slug): void
    {
        $this->poll = Poll::where('slug', $slug)->with('options')->firstOrFail();
        $this->viewerTimezone = $this->poll->timezone;
    }

    public function updatedViewerTimezone(): void
    {
        if (! in_array($this->viewerTimezone, DateTimeZone::listIdentifiers(), true)) {
            $this->viewerTimezone = $this->poll->timezone;
        }
    }

    #[Computed]
    public function phase(): PollPhase
    {
        return $this->poll->phase();
    }

    /**
     * @return array{participants: int, results: list<array{option_id: int, points: int, rank: int, percentage: int|null, bar: float}>}
     */
    #[Computed]
    public function standings(): array
    {
        return app(GetPollResults::class)->handle($this->poll);
    }

    /**
     * The options that are ahead, or none while nobody has scored.
     *
     * @return list<array{option_id: int, points: int, rank: int, percentage: int|null, bar: float}>
     */
    #[Computed]
    public function leaders(): array
    {
        return array_values(array_filter(
            $this->standings['results'],
            fn (array $row): bool => $row['rank'] === 1 && $row['points'] > 0,
        ));
    }
};
?>
<div
    class="space-y-6"
    @if ($this->phase !== PollPhase::Closed) wire:poll.5s.visible @endif
>
    <header class="space-y-2">
        <p class="eyebrow text-accent">
            {{ $this->phase === PollPhase::Closed ? __('Final results') : __('Live results') }}
        </p>
        <h1 class="text-3xl font-semibold sm:text-4xl">{{ $poll->title }}</h1>
        @if ($poll->resultsVisibleToParticipants())
            <p class="text-muted">{{ trans_choice(':count participant|:count participants', $this->standings['participants']) }}</p>
        @endif
    </header>

    @if (! $poll->resultsVisibleToParticipants())
        <p class="rounded-lg border border-accent/25 bg-accent-soft px-4 py-3 text-sm text-accent-strong">{{ __('The creator has hidden the results until voting ends.') }}</p>
    @else
        @if ($this->phase === PollPhase::Closed && $this->leaders !== [])
            <div class="rounded-xl border border-winner bg-winner-soft p-5">
                <p class="eyebrow">{{ count($this->leaders) > 1 ? __('Tie') : __('Winner') }}</p>
                <p class="text-xl font-semibold">
                    {{ collect($this->leaders)->map(fn ($row) => $poll->options->firstWhere('id', $row['option_id'])->displayLabel($viewerTimezone))->join(' · ') }}
                </p>
            </div>
        @endif

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

        <x-results-list :poll="$poll" :standings="$this->standings" :timezone="$viewerTimezone" />

        @if ($poll->voting_method === VotingMethod::Ranking)
            <p class="field-hint">
                {{ __('Points: the first place of each ballot gets :places points, the second one fewer, and so on down to one point. Options that are not ranked get none.', ['places' => $poll->rankingPlaces()]) }}
            </p>
        @endif

        @if ($this->phase !== PollPhase::Closed)
            <p class="field-hint">{{ __('The results update automatically while voting is open.') }}</p>
        @endif
    @endif

    <div class="flex flex-wrap gap-3">
        <a href="{{ route('poll.show', $poll->slug) }}" class="btn-secondary">{{ __('Back to the poll') }}</a>
    </div>
</div>
