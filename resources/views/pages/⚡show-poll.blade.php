<?php

use App\Models\Poll;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.app', ['noindex' => true])] class extends Component
{
    public Poll $poll;

    public function mount(string $slug): void
    {
        $this->poll = Poll::where('slug', $slug)->with('options')->firstOrFail();
    }
};
?>

<div class="space-y-6">
    <header class="space-y-2">
        <h1 class="text-3xl font-semibold tracking-tight">{{ $poll->title }}</h1>
        @if ($poll->description)
            <p class="whitespace-pre-line text-muted">{{ $poll->description }}</p>
        @endif
    </header>

    <ul class="space-y-2">
        @foreach ($poll->options as $option)
            <li wire:key="option-{{ $option->id }}" class="rounded-xl border border-line bg-surface px-4 py-3">
                @if ($option->starts_at)
                    {{ $option->starts_at->setTimezone($poll->timezone)->isoFormat('LLLL') }} ({{ $poll->timezone }})
                @elseif ($option->date)
                    {{ $option->date->isoFormat('LL') }}
                @else
                    {{ $option->label }}
                @endif
            </li>
        @endforeach
    </ul>
</div>
