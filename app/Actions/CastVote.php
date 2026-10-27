<?php

namespace App\Actions;

use App\Enums\PollPhase;
use App\Enums\VotingMethod;
use App\Models\Poll;
use App\Models\Vote;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CastVote
{
    /**
     * Save a voter's choice, replacing an earlier vote from the same voter.
     *
     * The option IDs are in the voter's order, which matters for ranking polls.
     *
     * @param  list<int>  $optionIds
     *
     * @throws ValidationException
     */
    public function handle(Poll $poll, string $voterToken, array $optionIds): Vote
    {
        if ($poll->phase() !== PollPhase::Voting) {
            $this->fail(__('Voting is not open.'));
        }

        $optionIds = array_map('intval', $optionIds);
        $validIds = $poll->options()->pluck('id')->all();

        if (array_diff($optionIds, $validIds) !== [] || count($optionIds) !== count(array_unique($optionIds))) {
            $this->fail(__('Your choice contains an invalid option.'));
        }

        $this->checkAmount($poll, $optionIds, count($validIds));

        return DB::transaction(function () use ($poll, $voterToken, $optionIds): Vote {
            $vote = Vote::firstOrCreate([
                'poll_id' => $poll->id,
                'voter_token_hash' => $poll->voterHash($voterToken),
            ]);

            $vote->choices()->delete();

            foreach ($optionIds as $index => $optionId) {
                $vote->choices()->create([
                    'poll_option_id' => $optionId,
                    'rank' => $poll->voting_method === VotingMethod::Ranking ? $index + 1 : null,
                ]);
            }

            $vote->touch();

            return $vote;
        });
    }

    /**
     * @param  list<int>  $optionIds
     */
    private function checkAmount(Poll $poll, array $optionIds, int $optionCount): void
    {
        $count = count($optionIds);

        match ($poll->voting_method) {
            VotingMethod::Single => $count !== 1 && $this->fail(__('Choose one option.')),
            VotingMethod::Multiple => ($count < 1 || $count > ($poll->max_choices ?? $optionCount))
                && $this->fail(__('Choose between 1 and :max options.', ['max' => $poll->max_choices ?? $optionCount])),
            VotingMethod::Ranking => $count !== $poll->rankingPlaces()
                && $this->fail(__('Rank exactly :places options.', ['places' => $poll->rankingPlaces()])),
        };
    }

    /**
     * @throws ValidationException
     */
    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['selection' => $message]);
    }
}
