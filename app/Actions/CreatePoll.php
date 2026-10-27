<?php

namespace App\Actions;

use App\Enums\Category;
use App\Enums\VotingMethod;
use App\Models\Poll;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreatePoll
{
    /**
     * Create a poll with its options. Voting opens immediately.
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
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'category' => $data['category'],
                'voting_method' => $data['voting_method'],
                'max_choices' => $data['voting_method'] === VotingMethod::Multiple ? ($data['max_choices'] ?? null) : null,
                'ranking_limit' => $data['voting_method'] === VotingMethod::Ranking ? ($data['ranking_limit'] ?? null) : null,
                'timezone' => $data['timezone'],
                'includes_time' => $data['category'] === Category::Dates && ($data['includes_time'] ?? false),
                'voting_starts_at' => now(),
                'voting_ends_at' => $data['voting_ends_at'],
            ]);

            foreach ($data['options'] as $position => $option) {
                $poll->options()->create([
                    'position' => $position,
                    ...$this->optionAttributes($poll, $option),
                ]);
            }

            return $poll;
        });

        return ['poll' => $poll, 'admin_token' => $adminToken];
    }

    /**
     * @param  array{label: string|null, date: string|null, time: string|null}  $option
     * @return array{label: string|null, date: string|null, starts_at: CarbonInterface|null}
     */
    private function optionAttributes(Poll $poll, array $option): array
    {
        if ($poll->category === Category::Custom) {
            return ['label' => $option['label'], 'date' => null, 'starts_at' => null];
        }

        if ($poll->includes_time) {
            $startsAt = Carbon::parse($option['date'].' '.$option['time'], $poll->timezone)->utc();

            return ['label' => null, 'date' => null, 'starts_at' => $startsAt];
        }

        return ['label' => null, 'date' => $option['date'], 'starts_at' => null];
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
