<?php

namespace Database\Factories;

use App\Enums\Category;
use App\Enums\VotingMethod;
use App\Models\Poll;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Poll>
 */
class PollFactory extends Factory
{
    /**
     * Define the model's default state. The poll is open for voting.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => Str::lower(Str::random(10)),
            'admin_token_hash' => Poll::hashToken(Str::random(40)),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'category' => Category::Custom,
            'voting_method' => VotingMethod::Single,
            'timezone' => 'Europe/Helsinki',
            'voting_starts_at' => now()->subHour(),
            'voting_ends_at' => now()->addWeek(),
        ];
    }

    /**
     * Indicate that the poll has not started yet.
     */
    public function scheduled(): static
    {
        return $this->state(fn () => [
            'voting_starts_at' => now()->addDay(),
            'voting_ends_at' => now()->addWeek(),
        ]);
    }

    /**
     * Indicate that the poll is collecting suggestions before voting starts.
     */
    public function collectingSuggestions(): static
    {
        return $this->state(fn () => [
            'suggestions_starts_at' => now()->subHour(),
            'suggestions_ends_at' => now()->addDay(),
            'voting_starts_at' => now()->addDay(),
            'voting_ends_at' => now()->addWeek(),
        ]);
    }

    /**
     * Indicate that the voting time has run out.
     */
    public function ended(): static
    {
        return $this->state(fn () => [
            'voting_starts_at' => now()->subWeek(),
            'voting_ends_at' => now()->subHour(),
        ]);
    }

    /**
     * Indicate that the creator closed the poll early.
     */
    public function closedEarly(): static
    {
        return $this->state(fn () => ['closed_at' => now()->subMinute()]);
    }

    /**
     * Use a ranking poll, optionally limited to the top N places.
     */
    public function ranking(?int $limit = null): static
    {
        return $this->state(fn () => [
            'voting_method' => VotingMethod::Ranking,
            'ranking_limit' => $limit,
        ]);
    }
}
