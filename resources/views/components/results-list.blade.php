@props(['poll', 'standings', 'timezone'])

<ol class="space-y-2">
    @foreach ($standings['results'] as $row)
        @php($option = $poll->options->firstWhere('id', $row['option_id']))
        @php($leads = $row['rank'] === 1 && $row['points'] > 0)
        <li wire:key="result-{{ $row['option_id'] }}" class="{{ $leads ? 'border-winner bg-winner-soft' : 'border-line bg-surface' }} rounded-xl border p-4">
            <div class="flex items-baseline justify-between gap-4">
                <span class="flex min-w-0 items-baseline gap-3">
                    <span class="w-5 shrink-0 font-mono text-sm text-muted tabular-nums">{{ $row['rank'] }}</span>
                    <span class="font-medium">{{ $option->displayLabel($timezone) }}</span>
                </span>
                <span class="shrink-0 font-mono text-sm tabular-nums">
                    @if ($row['percentage'] !== null)
                        {{ trans_choice(':count vote|:count votes', $row['points']) }} · <strong class="font-medium">{{ $row['percentage'] }}%</strong>
                    @else
                        {{ trans_choice(':count point|:count points', $row['points']) }}
                    @endif
                </span>
            </div>
            <div class="mt-3 h-1.5 rounded-full bg-surface-2" role="presentation">
                <div class="{{ $leads ? 'bg-winner' : 'bg-accent' }} h-1.5 rounded-full transition-all duration-500" style="width: {{ round($row['bar'] * 100) }}%"></div>
            </div>
        </li>
    @endforeach
</ol>
