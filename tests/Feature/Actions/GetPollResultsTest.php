<?php

use App\Actions\CastVote;
use App\Actions\GetPollResults;
use App\Models\Poll;
use App\Models\PollOption;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * @return array{0: Poll, 1: list<int>}
 */
function resultsPoll(Poll $poll, int $count = 3): array
{
    $ids = PollOption::factory()->count($count)->sequence(fn ($s) => ['position' => $s->index])->for($poll)->create()->pluck('id')->all();

    return [$poll, $ids];
}

test('counts votes and percentages for a single choice poll', function () {
    [$poll, $ids] = resultsPoll(Poll::factory()->create());
    (new CastVote)->handle($poll, 'a', [$ids[0]]);
    (new CastVote)->handle($poll, 'b', [$ids[0]]);
    (new CastVote)->handle($poll, 'c', [$ids[1]]);

    $standings = app(GetPollResults::class)->handle($poll);

    expect($standings['participants'])->toBe(3)
        ->and($standings['results'][0])->toMatchArray(['option_id' => $ids[0], 'points' => 2, 'rank' => 1, 'percentage' => 67, 'bar' => 1.0])
        ->and($standings['results'][1])->toMatchArray(['option_id' => $ids[1], 'points' => 1, 'rank' => 2, 'percentage' => 33, 'bar' => 0.5])
        ->and($standings['results'][2])->toMatchArray(['option_id' => $ids[2], 'points' => 0, 'rank' => 3]);
});

test('scores a ranking poll with points and no percentages', function () {
    [$poll, $ids] = resultsPoll(Poll::factory()->ranking(2)->create());
    (new CastVote)->handle($poll, 'a', [$ids[2], $ids[0]]);
    (new CastVote)->handle($poll, 'b', [$ids[2], $ids[1]]);

    $standings = app(GetPollResults::class)->handle($poll);

    expect($standings['results'][0])->toMatchArray(['option_id' => $ids[2], 'points' => 4, 'percentage' => null])
        ->and(array_column($standings['results'], 'points', 'option_id'))->toBe([$ids[2] => 4, $ids[0] => 1, $ids[1] => 1]);
});

test('an empty poll lists every option without a leader', function () {
    [$poll] = resultsPoll(Poll::factory()->create());

    $standings = app(GetPollResults::class)->handle($poll);

    expect($standings['participants'])->toBe(0)
        ->and(array_unique(array_column($standings['results'], 'rank')))->toBe([1])
        ->and(array_unique(array_column($standings['results'], 'bar')))->toBe([0.0]);
});

test('serves repeated requests from the cache without querying the database', function () {
    [$poll, $ids] = resultsPoll(Poll::factory()->create());
    (new CastVote)->handle($poll, 'a', [$ids[0]]);

    app(GetPollResults::class)->handle($poll);

    DB::enableQueryLog();
    app(GetPollResults::class)->handle($poll);

    expect(DB::getQueryLog())->toBe([]);
});

test('a new vote shows up once the cached result has expired', function () {
    [$poll, $ids] = resultsPoll(Poll::factory()->create());
    app(GetPollResults::class)->handle($poll);

    (new CastVote)->handle($poll, 'a', [$ids[0]]);
    expect(app(GetPollResults::class)->handle($poll)['participants'])->toBe(0);

    Cache::forget("poll:{$poll->id}:results:open");

    expect(app(GetPollResults::class)->handle($poll)['participants'])->toBe(1);
});

test('a closed poll is cached separately from the open one', function () {
    [$poll, $ids] = resultsPoll(Poll::factory()->create());
    app(GetPollResults::class)->handle($poll);

    (new CastVote)->handle($poll, 'a', [$ids[0]]);
    $poll->update(['closed_at' => now()->subSecond()]);

    expect(app(GetPollResults::class)->handle($poll->fresh())['participants'])->toBe(1);
});
