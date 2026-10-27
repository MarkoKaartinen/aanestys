<?php

use App\Actions\CreatePoll;
use App\Enums\Category;
use App\Enums\PollPhase;
use App\Enums\VotingMethod;
use App\Models\Poll;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function pollData(array $overrides = []): array
{
    return [
        'title' => 'Next book',
        'category' => Category::Custom,
        'voting_method' => VotingMethod::Single,
        'timezone' => 'Europe/Helsinki',
        'voting_ends_at' => now()->addWeek(),
        'options' => [['label' => 'Dune', 'date' => null, 'time' => null], ['label' => 'Piranesi', 'date' => null, 'time' => null]],
        ...$overrides,
    ];
}

test('creates a poll with its options in order', function () {
    ['poll' => $poll] = (new CreatePoll)->handle(pollData());

    expect($poll->options->pluck('label')->all())->toBe(['Dune', 'Piranesi'])
        ->and($poll->options->pluck('position')->all())->toBe([0, 1])
        ->and($poll->phase())->toBe(PollPhase::Voting);
});

test('stores only a hash of the admin token and returns the token once', function () {
    ['poll' => $poll, 'admin_token' => $token] = (new CreatePoll)->handle(pollData());

    expect($token)->toHaveLength(40)
        ->and($poll->admin_token_hash)->not->toBe($token)
        ->and(Poll::findByAdminToken($token)?->is($poll))->toBeTrue()
        ->and(Poll::findByAdminToken('wrong'))->toBeNull();
});

test('gives each poll a different ten character slug', function () {
    $first = (new CreatePoll)->handle(pollData())['poll'];
    $second = (new CreatePoll)->handle(pollData())['poll'];

    expect($first->slug)->toHaveLength(10)->not->toBe($second->slug);
});

test('keeps the multiple choice limit only for multiple choice polls', function () {
    $multiple = (new CreatePoll)->handle(pollData(['voting_method' => VotingMethod::Multiple, 'max_choices' => 2]))['poll'];
    $single = (new CreatePoll)->handle(pollData(['max_choices' => 2]))['poll'];

    expect($multiple->max_choices)->toBe(2)->and($single->max_choices)->toBeNull();
});

test('keeps the ranking limit only for ranking polls', function () {
    $ranking = (new CreatePoll)->handle(pollData(['voting_method' => VotingMethod::Ranking, 'ranking_limit' => 5]))['poll'];
    $single = (new CreatePoll)->handle(pollData(['ranking_limit' => 5]))['poll'];

    expect($ranking->ranking_limit)->toBe(5)->and($single->ranking_limit)->toBeNull();
});

test('stores date only options as dates without a timezone conversion', function () {
    ['poll' => $poll] = (new CreatePoll)->handle(pollData([
        'category' => Category::Dates,
        'options' => [['label' => null, 'date' => '2026-12-05', 'time' => null], ['label' => null, 'date' => '2026-12-06', 'time' => null]],
    ]));

    expect($poll->includes_time)->toBeFalse()
        ->and($poll->options->first()->date->toDateString())->toBe('2026-12-05')
        ->and($poll->options->first()->starts_at)->toBeNull();
});

test('converts dates with a time from the poll timezone to UTC including daylight saving', function () {
    ['poll' => $poll] = (new CreatePoll)->handle(pollData([
        'category' => Category::Dates,
        'includes_time' => true,
        'options' => [
            ['label' => null, 'date' => '2026-12-05', 'time' => '18:00'],
            ['label' => null, 'date' => '2026-07-01', 'time' => '18:00'],
        ],
    ]));

    expect($poll->options[0]->starts_at->toIso8601String())->toBe('2026-12-05T16:00:00+00:00')
        ->and($poll->options[1]->starts_at->toIso8601String())->toBe('2026-07-01T15:00:00+00:00');
});

test('ignores the time flag outside the dates category', function () {
    ['poll' => $poll] = (new CreatePoll)->handle(pollData(['includes_time' => true]));

    expect($poll->includes_time)->toBeFalse();
});
