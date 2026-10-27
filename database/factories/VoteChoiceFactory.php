<?php

namespace Database\Factories;

use App\Models\PollOption;
use App\Models\Vote;
use App\Models\VoteChoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VoteChoice>
 */
class VoteChoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vote_id' => Vote::factory(),
            'poll_option_id' => PollOption::factory(),
            'rank' => null,
        ];
    }
}
