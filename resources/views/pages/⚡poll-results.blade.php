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
        <p class="text-sm font-medium text-accent-strong">
            {{ $this->phase === PollPhase::Closed ? __('Final results') : __('Live results') }}
        </p>
        <h1 class="text-3xl font-semibold tracking-tight">{{ $poll->title }}</h1>
        <p class="text-muted">{{ trans_choice(':count participant|:count participants', $this->standings['participants']) }}</p>
    </header>

    @if ($this->phase === PollPhase::Closed && $this->leaders !== [])
        <div class="rounded-2xl border border-winner bg-winner-soft p-4">
            <p class="text-sm font-medium">{{ count($this->leaders) > 1 ? __('Tie') : __('Winner') }}</p>
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

    <ol class="space-y-3">
        @foreach ($this->standings['results'] as $row)
            @php($option = $poll->options->firstWhere('id', $row['option_id']))
            <li wire:key="result-{{ $row['option_id'] }}" class="{{ $row['rank'] === 1 && $row['points'] > 0 ? 'border-winner bg-winner-soft' : 'border-line bg-surface' }} rounded-xl border p-3">
                <div class="flex items-baseline justify-between gap-4">
                    <span class="font-medium"><span class="text-muted">{{ $row['rank'] }}.</span> {{ $option->displayLabel($viewerTimezone) }}</span>
                    <span class="shrink-0 text-sm tabular-nums">
                        @if ($row['percentage'] !== null)
                            {{ trans_choice(':count vote|:count votes', $row['points']) }} · {{ $row['percentage'] }}%
                        @else
                            {{ trans_choice(':count point|:count points', $row['points']) }}
                        @endif
                    </span>
                </div>
                <div class="mt-2 h-2 rounded-full bg-line" role="presentation">
                    <div class="{{ $row['rank'] === 1 && $row['points'] > 0 ? 'bg-winner' : 'bg-accent' }} h-2 rounded-full transition-all" style="width: {{ round($row['bar'] * 100) }}%"></div>
                </div>
            </li>
        @endforeach
    </ol>

    @if ($poll->voting_method === VotingMethod::Ranking)
        <p class="field-hint">
            {{ __('Points: the first place of each ballot gets :places points, the second one fewer, and so on down to one point. Options that are not ranked get none.', ['places' => $poll->rankingPlaces()]) }}
        </p>
    @endif

    @if ($this->phase !== PollPhase::Closed)
        <p class="field-hint">{{ __('The results update automatically while voting is open.') }}</p>
    @endif

    <div class="flex flex-wrap gap-3">
        <a href="{{ route('poll.show', $poll->slug) }}" class="btn-secondary">{{ __('Back to the poll') }}</a>
    </div>
</div>
