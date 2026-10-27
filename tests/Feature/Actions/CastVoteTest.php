<?php

use App\Actions\CastVote;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\Vote;
use Illuminate\Validation\ValidationException;

/**
 * @return array{0: Poll, 1: list<int>}
 */
function pollWithOptions(Poll $poll, int $count = 4): array
{
    $ids = PollOption::factory()->count($count)->sequence(fn ($s) => ['position' => $s->index])->for($poll)->create()->pluck('id')->all();

    return [$poll, $ids];
}

test('saves a single choice vote', function () {
    [$poll, $ids] = pollWithOptions(Poll::factory()->create());

    $vote = (new CastVote)->handle($poll, 'token', [$ids[1]]);

    expect($vote->choices)->toHaveCount(1)
        ->and($vote->choices[0]->poll_option_id)->toBe($ids[1])
        ->and($vote->choices[0]->rank)->toBeNull()
        ->and($vote->voter_token_hash)->not->toBe('token');
});

test('saves several options for a multiple choice vote', function () {
    [$poll, $ids] = pollWithOptions(Poll::factory()->create(['voting_method' => 'multiple']));

    $vote = (new CastVote)->handle($poll, 'token', [$ids[0], $ids[2]]);

    expect($vote->choices->pluck('poll_option_id')->all())->toBe([$ids[0], $ids[2]]);
});

test('saves the order of a ranking vote', function () {
    [$poll, $ids] = pollWithOptions(Poll::factory()->ranking(3)->create());

    $vote = (new CastVote)->handle($poll, 'token', [$ids[2], $ids[0], $ids[3]]);

    expect($vote->choices->pluck('rank', 'poll_option_id')->all())->toBe([$ids[2] => 1, $ids[0] => 2, $ids[3] => 3]);
});

test('replaces the earlier vote when the same voter votes again', function () {
    [$poll, $ids] = pollWithOptions(Poll::factory()->create());

    (new CastVote)->handle($poll, 'token', [$ids[0]]);
    (new CastVote)->handle($poll, 'token', [$ids[1]]);

    expect(Vote::count())->toBe(1)
        ->and(Vote::first()->choices->pluck('poll_option_id')->all())->toBe([$ids[1]]);
});

test('keeps votes from different voters apart', function () {
    [$poll, $ids] = pollWithOptions(Poll::factory()->create());

    (new CastVote)->handle($poll, 'one', [$ids[0]]);
    (new CastVote)->handle($poll, 'two', [$ids[0]]);

    expect(Vote::count())->toBe(2);
});

test('rejects a vote before voting has opened', function () {
    [$poll, $ids] = pollWithOptions(Poll::factory()->scheduled()->create());

    (new CastVote)->handle($poll, 'token', [$ids[0]]);
})->throws(ValidationException::class, 'Voting is not open.');

test('rejects a vote after voting has ended', function () {
    [$poll, $ids] = pollWithOptions(Poll::factory()->ended()->create());

    (new CastVote)->handle($poll, 'token', [$ids[0]]);
})->throws(ValidationException::class, 'Voting is not open.');

test('rejects an option that belongs to another poll', function () {
    [$poll] = pollWithOptions(Poll::factory()->create());
    [, $otherIds] = pollWithOptions(Poll::factory()->create());

    (new CastVote)->handle($poll, 'token', [$otherIds[0]]);
})->throws(ValidationException::class, 'invalid option');

test('rejects choosing two options in a single choice poll', function () {
    [$poll, $ids] = pollWithOptions(Poll::factory()->create());

    (new CastVote)->handle($poll, 'token', [$ids[0], $ids[1]]);
})->throws(ValidationException::class, 'Choose one option.');

test('rejects more options than the multiple choice limit', function () {
    [$poll, $ids] = pollWithOptions(Poll::factory()->create(['voting_method' => 'multiple', 'max_choices' => 2]));

    (new CastVote)->handle($poll, 'token', [$ids[0], $ids[1], $ids[2]]);
})->throws(ValidationException::class, 'between 1 and 2');

test('rejects ranking fewer options than the poll asks for', function () {
    [$poll, $ids] = pollWithOptions(Poll::factory()->ranking(3)->create());

    (new CastVote)->handle($poll, 'token', [$ids[0], $ids[1]]);
})->throws(ValidationException::class, 'Rank exactly 3');

test('rejects the same option twice in a ranking', function () {
    [$poll, $ids] = pollWithOptions(Poll::factory()->ranking(2)->create());

    (new CastVote)->handle($poll, 'token', [$ids[0], $ids[0]]);
})->throws(ValidationException::class, 'invalid option');

test('a full ranking needs every option', function () {
    [$poll, $ids] = pollWithOptions(Poll::factory()->ranking()->create(), 3);

    expect((new CastVote)->handle($poll, 'token', array_reverse($ids))->choices)->toHaveCount(3);
});
