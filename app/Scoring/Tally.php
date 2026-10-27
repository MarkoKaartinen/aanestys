<?php

namespace App\Scoring;

/**
 * Pure scoring logic for a poll. Ballots hold option IDs only, so nothing here
 * touches the database.
 */
class Tally
{
    /**
     * Score a single or multiple choice poll: every selected option gets one point.
     *
     * @param  list<int>  $optionIds
     * @param  list<list<int>>  $ballots  Each ballot is the list of options one voter selected.
     * @return list<array{option_id: int, points: int, rank: int}>
     */
    public function choices(array $optionIds, array $ballots): array
    {
        $points = array_fill_keys($optionIds, 0);

        foreach ($ballots as $ballot) {
            foreach (array_unique($ballot) as $optionId) {
                if (isset($points[$optionId])) {
                    $points[$optionId]++;
                }
            }
        }

        return $this->rank($points);
    }

    /**
     * Score a ranking poll with the Borda count.
     *
     * With 10 options a full ranking gives 10, 9, ... 1 points. With a limit of 5
     * only the first five places of a ballot score 5, 4, 3, 2, 1 and the rest
     * get nothing. The limit is capped by the number of options.
     *
     * @param  list<int>  $optionIds
     * @param  list<list<int>>  $ballots  Each ballot lists option IDs from best to worst.
     * @param  int|null  $limit  Number of places to rank, or null to rank every option.
     * @return list<array{option_id: int, points: int, rank: int}>
     */
    public function ranking(array $optionIds, array $ballots, ?int $limit = null): array
    {
        $places = min($limit ?? count($optionIds), count($optionIds));
        $points = array_fill_keys($optionIds, 0);

        foreach ($ballots as $ballot) {
            $known = array_values(array_unique(array_filter($ballot, fn (int $optionId): bool => isset($points[$optionId]))));

            foreach (array_slice($known, 0, $places) as $index => $optionId) {
                $points[$optionId] += $places - $index;
            }
        }

        return $this->rank($points);
    }

    /**
     * Sort by points and assign ranks. Ties share a rank and the next rank is
     * skipped, so 10, 8, 8, 5 points rank as 1, 2, 2, 4.
     *
     * @param  array<int, int>  $points  Points keyed by option ID.
     * @return list<array{option_id: int, points: int, rank: int}>
     */
    private function rank(array $points): array
    {
        $optionIds = array_keys($points);

        usort($optionIds, fn (int $a, int $b): int => $points[$b] <=> $points[$a]);

        $results = [];
        $rank = 0;

        foreach ($optionIds as $position => $optionId) {
            if ($position === 0 || $points[$optionId] !== $results[$position - 1]['points']) {
                $rank = $position + 1;
            }

            $results[] = ['option_id' => $optionId, 'points' => $points[$optionId], 'rank' => $rank];
        }

        return $results;
    }
}
