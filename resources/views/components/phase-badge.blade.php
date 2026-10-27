@props(['phase'])

@php
    [$label, $dot] = match ($phase) {
        \App\Enums\PollPhase::Scheduled => [__('Scheduled'), 'bg-muted'],
        \App\Enums\PollPhase::Suggestions => [__('Suggestions'), 'bg-winner'],
        \App\Enums\PollPhase::Voting => [__('Open'), 'bg-accent animate-pulse'],
        \App\Enums\PollPhase::Closed => [__('Closed'), 'bg-line-strong'],
    };
@endphp

<span {{ $attributes->class(['eyebrow inline-flex items-center gap-1.5 rounded-full border border-line-strong bg-surface px-2.5 py-0.5']) }}>
    <span class="{{ $dot }} size-1.5 rounded-full"></span>{{ $label }}
</span>
