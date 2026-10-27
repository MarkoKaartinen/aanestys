<?php

use App\Actions\GetPollResults;
use App\Enums\PollPhase;
use App\Models\Poll;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.bare', ['noindex' => true])] class extends Component
{
    public Poll $poll;

    public function mount(string $token): void
    {
        $this->poll = Poll::where('live_token', $token)->with('options')->firstOrFail();
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
};
?>

<div class="space-y-4" wire:poll.3s.visible>
    <header class="space-y-1">
        <h1 class="text-2xl font-semibold tracking-tight">{{ $poll->title }}</h1>
        <p class="text-sm text-muted">
            {{ $this->phase === PollPhase::Closed ? __('Final results') : __('Live results') }}
            · {{ trans_choice(':count participant|:count participants', $this->standings['participants']) }}
        </p>
    </header>

    <x-results-list :poll="$poll" :standings="$this->standings" :timezone="$poll->timezone" />
</div>
