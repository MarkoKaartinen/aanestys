@props(['poll', 'standings', 'timezone'])

<ol class="space-y-3">
    @foreach ($standings['results'] as $row)
        @php($option = $poll->options->firstWhere('id', $row['option_id']))
        <li wire:key="result-{{ $row['option_id'] }}" class="{{ $row['rank'] === 1 && $row['points'] > 0 ? 'border-winner bg-winner-soft' : 'border-line bg-surface' }} rounded-xl border p-3">
            <div class="flex items-baseline justify-between gap-4">
                <span class="font-medium"><span class="text-muted">{{ $row['rank'] }}.</span> {{ $option->displayLabel($timezone) }}</span>
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
