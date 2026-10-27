<?php

use App\Enums\PollPhase;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\Vote;
use App\Models\VoteChoice;
use Illuminate\Database\QueryException;

test('phase is voting while the voting time is running', function () {
    expect(Poll::factory()->create()->phase())->toBe(PollPhase::Voting);
});

test('phase is scheduled before voting starts and no suggestion round is running', function () {
    expect(Poll::factory()->scheduled()->create()->phase())->toBe(PollPhase::Scheduled);
});

test('phase is suggestions while the suggestion round is running', function () {
    expect(Poll::factory()->collectingSuggestions()->create()->phase())->toBe(PollPhase::Suggestions);
});

test('phase is scheduled when the suggestion round has ended but voting has not started', function () {
    $poll = Poll::factory()->collectingSuggestions()->create([
        'suggestions_ends_at' => now()->subMinute(),
    ]);

    expect($poll->phase())->toBe(PollPhase::Scheduled);
});

test('phase is closed after the voting time has run out', function () {
    expect(Poll::factory()->ended()->create()->phase())->toBe(PollPhase::Closed);
});

test('phase is closed when the creator closed the poll early', function () {
    expect(Poll::factory()->closedEarly()->create()->phase())->toBe(PollPhase::Closed);
});

test('phase can be evaluated at a given moment', function () {
    $poll = Poll::factory()->create();

    expect($poll->phase($poll->voting_ends_at->copy()->addSecond()))->toBe(PollPhase::Closed)
        ->and($poll->phase($poll->voting_starts_at->copy()->subSecond()))->toBe(PollPhase::Scheduled);
});

test('expiry is the retention period after the voting end', function () {
    config(['polls.retention_days' => 90]);

    $poll = Poll::factory()->create();

    expect($poll->expires_at->equalTo($poll->voting_ends_at->copy()->addDays(90)))->toBeTrue();
});

test('expiry moves to the retention period after an early close', function () {
    config(['polls.retention_days' => 90]);

    $poll = Poll::factory()->closedEarly()->create();

    expect($poll->expires_at->equalTo($poll->closed_at->copy()->addDays(90)))->toBeTrue();
});

test('pruning deletes polls past their retention with their options and votes', function () {
    $expired = Poll::factory()->ended()->create();
    $option = PollOption::factory()->for($expired)->create();
    $vote = Vote::factory()->for($expired)->create();
    VoteChoice::factory()->create(['vote_id' => $vote->id, 'poll_option_id' => $option->id]);
    $expired->forceFill(['expires_at' => now()->subDay()])->saveQuietly();

    $kept = Poll::factory()->create();

    $this->artisan('model:prune', ['--model' => Poll::class])->assertSuccessful();

    expect(Poll::count())->toBe(1)
        ->and(Poll::first()->is($kept))->toBeTrue()
        ->and(PollOption::count())->toBe(0)
        ->and(Vote::count())->toBe(0)
        ->and(VoteChoice::count())->toBe(0);
});

test('the admin token hash is hidden when the poll is serialized', function () {
    expect(Poll::factory()->create()->toArray())->not->toHaveKey('admin_token_hash');
});

test('a voter can only vote once per poll', function () {
    $poll = Poll::factory()->create();
    Vote::factory()->for($poll)->create(['voter_token_hash' => 'same']);

    Vote::factory()->for($poll)->create(['voter_token_hash' => 'same']);
})->throws(QueryException::class);
