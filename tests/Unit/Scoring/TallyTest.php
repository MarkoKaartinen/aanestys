<?php

use App\Scoring\Tally;

test('choices gives one point per selected option', function () {
    $results = (new Tally)->choices([1, 2, 3], [[1], [1, 2], [2]]);

    expect($results)->toBe([
        ['option_id' => 1, 'points' => 2, 'rank' => 1],
        ['option_id' => 2, 'points' => 2, 'rank' => 1],
        ['option_id' => 3, 'points' => 0, 'rank' => 3],
    ]);
});

test('choices ignores duplicate and unknown options in a ballot', function () {
    $results = (new Tally)->choices([1, 2], [[1, 1, 99]]);

    expect($results)->toBe([
        ['option_id' => 1, 'points' => 1, 'rank' => 1],
        ['option_id' => 2, 'points' => 0, 'rank' => 2],
    ]);
});

test('choices lists every option with zero points when there are no ballots', function () {
    $results = (new Tally)->choices([1, 2], []);

    expect($results)->toBe([
        ['option_id' => 1, 'points' => 0, 'rank' => 1],
        ['option_id' => 2, 'points' => 0, 'rank' => 1],
    ]);
});

test('full ranking gives as many points as there are options down to one', function () {
    $options = range(1, 10);

    $results = (new Tally)->ranking($options, [$options]);

    expect(array_column($results, 'points'))->toBe([10, 9, 8, 7, 6, 5, 4, 3, 2, 1]);
});

test('full ranking sums points from all ballots', function () {
    $results = (new Tally)->ranking([1, 2, 3], [[1, 2, 3], [2, 1, 3], [2, 3, 1]]);

    expect($results)->toBe([
        ['option_id' => 2, 'points' => 8, 'rank' => 1],
        ['option_id' => 1, 'points' => 6, 'rank' => 2],
        ['option_id' => 3, 'points' => 4, 'rank' => 3],
    ]);
});

test('top N ranking scores only the first N places and gives the rest zero', function () {
    $options = range(1, 20);

    $results = (new Tally)->ranking($options, [[7, 3, 9, 1, 5, 2, 4]], limit: 5);

    $points = array_column($results, 'points', 'option_id');

    expect($points[7])->toBe(5)
        ->and($points[3])->toBe(4)
        ->and($points[9])->toBe(3)
        ->and($points[1])->toBe(2)
        ->and($points[5])->toBe(1)
        ->and($points[2])->toBe(0)
        ->and($points[4])->toBe(0);
});

test('top N ranking uses every option when there are fewer options than N', function () {
    $results = (new Tally)->ranking([1, 2, 3], [[2, 1, 3]], limit: 5);

    expect(array_column($results, 'points', 'option_id'))->toBe([2 => 3, 1 => 2, 3 => 1]);
});

test('ranking gives tied options the same rank and skips the next one', function () {
    $results = (new Tally)->ranking([1, 2, 3, 4], [[1, 2, 3, 4], [2, 1, 4, 3]]);

    expect(array_column($results, 'rank', 'option_id'))->toBe([1 => 1, 2 => 1, 3 => 3, 4 => 3]);
});

test('ranking ignores duplicate and unknown options in a ballot', function () {
    $results = (new Tally)->ranking([1, 2, 3], [[2, 2, 99, 1]]);

    expect(array_column($results, 'points', 'option_id'))->toBe([2 => 3, 1 => 2, 3 => 0]);
});
