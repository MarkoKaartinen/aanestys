<?php

namespace App\Actions;

use App\Enums\PollPhase;
use App\Enums\VotingMethod;
use App\Models\Poll;
use App\Scoring\Tally;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GetPollResults
{
    public function __construct(private Tally $tally) {}

    /**
     * Get the current standings of a poll.
     *
     * Many people may watch the same poll and refresh it at once, so the result
     * is shared through the cache. While voting is open it is fresh for a few
     * seconds and then served stale while one request recalculates it. A closed
     * poll no longer changes and is kept for much longer. Labels are not part of
     * the result because they depend on the viewer's timezone.
     *
     * @return array{participants: int, results: list<array{option_id: int, points: int, rank: int, percentage: int|null, bar: float}>}
     */
    public function handle(Poll $poll): array
    {
        $closed = $poll->phase() === PollPhase::Closed;

        return Cache::flexible(
            "poll:{$poll->id}:results:".($closed ? 'closed' : 'open'),
            $closed ? [600, 3600] : [3, 30],
            fn (): array => $this->calculate($poll),
        );
    }

    /**
     * @return array{participants: int, results: list<array{option_id: int, points: int, rank: int, percentage: int|null, bar: float}>}
     */
    private function calculate(Poll $poll): array
    {
        $optionIds = array_values($poll->options()->pluck('id')->map(fn ($id): int => (int) $id)->all());
        $participants = $poll->votes()->count();

        $ballots = array_values(DB::table('vote_choices')
            ->join('votes', 'votes.id', '=', 'vote_choices.vote_id')
            ->where('votes.poll_id', $poll->id)
            ->orderBy('vote_choices.vote_id')
            ->orderBy('vote_choices.id')
            ->get(['vote_choices.vote_id', 'vote_choices.poll_option_id'])
            ->groupBy('vote_id')
            ->map(fn ($choices): array => array_values($choices->pluck('poll_option_id')->map(fn ($id): int => (int) $id)->all()))
            ->all());

        $isRanking = $poll->voting_method === VotingMethod::Ranking;

        $scored = $isRanking
            ? $this->tally->ranking($optionIds, $ballots, $poll->ranking_limit)
            : $this->tally->choices($optionIds, $ballots);

        $maxPoints = max(array_column($scored, 'points') ?: [0]);

        return [
            'participants' => $participants,
            'results' => array_map(fn (array $row): array => [
                ...$row,
                'percentage' => ! $isRanking && $participants > 0 ? (int) round($row['points'] / $participants * 100) : null,
                'bar' => $maxPoints > 0 ? $row['points'] / $maxPoints : 0.0,
            ], $scored),
        ];
    }
}
