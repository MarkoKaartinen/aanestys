<?php

use App\Actions\SuggestOption;
use App\Enums\Category;
use App\Models\Poll;
use App\Models\PollOption;
use Illuminate\Validation\ValidationException;

test('adds a suggestion after the existing options', function () {
    $poll = Poll::factory()->collectingSuggestions()->create();
    PollOption::factory()->for($poll)->create(['label' => 'Dune', 'position' => 0]);

    $option = (new SuggestOption)->handle($poll, 'token', ['label' => '  Piranesi ']);

    expect($option->label)->toBe('Piranesi')
        ->and($option->position)->toBe(1)
        ->and($option->suggested_by_hash)->toBe($poll->voterHash('token'));
});

test('rejects a suggestion outside the suggestion round', function () {
    (new SuggestOption)->handle(Poll::factory()->create(), 'token', ['label' => 'Dune']);
})->throws(ValidationException::class, 'Suggestions are not open.');

test('rejects an empty suggestion', function () {
    (new SuggestOption)->handle(Poll::factory()->collectingSuggestions()->create(), 'token', ['label' => '   ']);
})->throws(ValidationException::class);

test('limits how many options one person can suggest', function () {
    $poll = Poll::factory()->collectingSuggestions()->create(['max_suggestions' => 2]);

    (new SuggestOption)->handle($poll, 'token', ['label' => 'One']);
    (new SuggestOption)->handle($poll, 'token', ['label' => 'Two']);
    (new SuggestOption)->handle($poll, 'token', ['label' => 'Three']);
})->throws(ValidationException::class, 'at most 2 options');

test('lets different people suggest their own options', function () {
    $poll = Poll::factory()->collectingSuggestions()->create();

    (new SuggestOption)->handle($poll, 'one', ['label' => 'One']);
    (new SuggestOption)->handle($poll, 'two', ['label' => 'Two']);

    expect($poll->options()->count())->toBe(2);
});

test('rejects a duplicate ignoring case and spacing', function () {
    $poll = Poll::factory()->collectingSuggestions()->create(['max_suggestions' => 2]);
    (new SuggestOption)->handle($poll, 'one', ['label' => 'The  Hobbit']);

    (new SuggestOption)->handle($poll, 'two', ['label' => 'the hobbit']);
})->throws(ValidationException::class, 'already been suggested');

test('rejects a duplicate date', function () {
    $poll = Poll::factory()->collectingSuggestions()->create(['category' => Category::Dates]);
    (new SuggestOption)->handle($poll, 'one', ['date' => '2026-12-05']);

    (new SuggestOption)->handle($poll, 'two', ['date' => '2026-12-05']);
})->throws(ValidationException::class, 'already been suggested');

test('converts a suggested time from the voters timezone to UTC', function () {
    $poll = Poll::factory()->collectingSuggestions()->create(['category' => Category::Dates, 'includes_time' => true]);

    $option = (new SuggestOption)->handle($poll, 'token', ['date' => '2026-12-05', 'time' => '12:00'], 'America/New_York');

    expect($option->starts_at->toIso8601String())->toBe('2026-12-05T17:00:00+00:00');
});

test('requires a time when the poll includes one', function () {
    $poll = Poll::factory()->collectingSuggestions()->create(['category' => Category::Dates, 'includes_time' => true]);

    (new SuggestOption)->handle($poll, 'token', ['date' => '2026-12-05']);
})->throws(ValidationException::class);

test('stops at the maximum number of options', function () {
    $poll = Poll::factory()->collectingSuggestions()->create();
    PollOption::factory()->count(SuggestOption::MAX_OPTIONS)->for($poll)->create();

    (new SuggestOption)->handle($poll, 'token', ['label' => 'One too many']);
})->throws(ValidationException::class, 'maximum number of options');
