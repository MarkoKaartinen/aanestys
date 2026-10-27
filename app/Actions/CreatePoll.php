<?php

namespace App\Actions;

use App\Enums\Category;
use App\Enums\VotingMethod;
use App\Models\Poll;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreatePoll
{
    /**
     * Create a poll with its options. Voting opens immediately, unless a
     * suggestion round is asked for: then participants suggest options until
     * suggestions_ends_at and voting starts at that moment.
     *
     * The admin token is only returned here; the database keeps its hash. Dates
     * with a time are entered in the poll's timezone and stored as UTC.
     *
     * @param  array{
     *     title: string,
     *     description?: string|null,
     *     category: Category,
     *     voting_method: VotingMethod,
     *     max_choices?: int|null,
     *     ranking_limit?: int|null,
     *     timezone: string,
     *     includes_time?: bool,
     *     voting_ends_at: CarbonInterface,
     *     suggestions_ends_at?: CarbonInterface|null,
     *     max_suggestions?: int|null,
     *     live_results_visible?: bool,
     *     options: list<array{label: string|null, date: string|null, time: string|null}>,
     * }  $data
     * @return array{poll: Poll, admin_token: string}
     */
    public function handle(array $data): array
    {
        $adminToken = Str::random(40);

        $poll = DB::transaction(function () use ($data, $adminToken): Poll {
            $poll = Poll::create([
                'slug' => $this->uniqueSlug(),
                'admin_token_hash' => Poll::hashToken($adminToken),
                'live_token' => Str::random(24),
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'category' => $data['category'],
                'voting_method' => $data['voting_method'],
                'max_choices' => $data['voting_method'] === VotingMethod::Multiple ? ($data['max_choices'] ?? null) : null,
                'ranking_limit' => $data['voting_method'] === VotingMethod::Ranking ? ($data['ranking_limit'] ?? null) : null,
                'timezone' => $data['timezone'],
                'includes_time' => $data['category'] === Category::Dates && ($data['includes_time'] ?? false),
                'max_suggestions' => $data['max_suggestions'] ?? 1,
                'live_results_visible' => $data['live_results_visible'] ?? true,
                'suggestions_starts_at' => isset($data['suggestions_ends_at']) ? now() : null,
                'suggestions_ends_at' => $data['suggestions_ends_at'] ?? null,
                'voting_starts_at' => $data['suggestions_ends_at'] ?? now(),
                'voting_ends_at' => $data['voting_ends_at'],
            ]);

            foreach ($data['options'] as $position => $option) {
                $poll->options()->create([
                    'position' => $position,
                    ...$poll->optionAttributes($option),
                ]);
            }

            return $poll;
        });

        return ['poll' => $poll, 'admin_token' => $adminToken];
    }

    /**
     * Pick a random, hard to guess slug that is not taken yet.
     */
    private function uniqueSlug(): string
    {
        do {
            $slug = Str::lower(Str::random(10));
        } while (Poll::where('slug', $slug)->exists());

        return $slug;
    }
}
