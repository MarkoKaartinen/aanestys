<?php

use App\Enums\Category;
use App\Enums\PollPhase;
use App\Enums\VotingMethod;
use App\Models\Poll;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

/**
 * Fill in a valid free text poll.
 */
function fillPoll(Testable $page): Testable
{
    return $page
        ->set('title', 'Next book')
        ->set('timezone', 'Europe/Helsinki')
        ->set('endsAt', now()->addWeek()->format('Y-m-d\TH:i'))
        ->set('options.0.label', 'Dune')
        ->set('options.1.label', 'Piranesi');
}

test('creates a poll and redirects to the admin page', function () {
    $page = fillPoll(Livewire::test('pages::create-poll'))->call('save');

    $poll = Poll::firstOrFail();

    $page->assertHasNoErrors()->assertRedirectContains('/manage/');

    expect($poll->title)->toBe('Next book')
        ->and($poll->options->pluck('label')->all())->toBe(['Dune', 'Piranesi'])
        ->and($poll->voting_method)->toBe(VotingMethod::Single);
});

test('shows an error when the title is missing', function () {
    fillPoll(Livewire::test('pages::create-poll'))
        ->set('title', '')
        ->call('save')
        ->assertHasErrors(['title' => 'required']);

    expect(Poll::count())->toBe(0);
});

test('shows an error when an option is empty', function () {
    fillPoll(Livewire::test('pages::create-poll'))
        ->set('options.1.label', '')
        ->call('save')
        ->assertHasErrors(['options.1.label' => 'required']);
});

test('shows an error when two options are the same', function () {
    fillPoll(Livewire::test('pages::create-poll'))
        ->set('options.1.label', 'dune')
        ->call('save')
        ->assertHasErrors(['options.0.label' => 'distinct']);
});

test('shows an error when voting would end in the past', function () {
    fillPoll(Livewire::test('pages::create-poll'))
        ->set('endsAt', now()->subDay()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasErrors(['endsAt']);

    expect(Poll::count())->toBe(0);
});

test('limits ranking places to the number of options', function () {
    fillPoll(Livewire::test('pages::create-poll'))
        ->set('votingMethod', 'ranking')
        ->set('rankingLimit', 5)
        ->call('save')
        ->assertHasErrors(['rankingLimit' => 'max']);
});

test('keeps at least two options and adds and removes options', function () {
    Livewire::test('pages::create-poll')
        ->call('removeOption', 0)
        ->assertCount('options', 2)
        ->call('addOption')
        ->assertCount('options', 3)
        ->call('removeOption', 2)
        ->assertCount('options', 2);
});

test('requires the time when the poll includes a time of day', function () {
    fillPoll(Livewire::test('pages::create-poll'))
        ->set('category', 'dates')
        ->set('includesTime', true)
        ->set('options.0.date', '2026-12-05')
        ->set('options.1.date', '2026-12-06')
        ->call('save')
        ->assertHasErrors(['options.0.time' => 'required']);
});

test('creates a dates poll with times in the chosen timezone', function () {
    fillPoll(Livewire::test('pages::create-poll'))
        ->set('category', 'dates')
        ->set('includesTime', true)
        ->set('options.0.date', '2026-12-05')
        ->set('options.0.time', '18:00')
        ->set('options.1.date', '2026-12-06')
        ->set('options.1.time', '19:30')
        ->call('save')
        ->assertHasNoErrors();

    $poll = Poll::firstOrFail();

    expect($poll->category)->toBe(Category::Dates)
        ->and($poll->options[0]->starts_at->toIso8601String())->toBe('2026-12-05T16:00:00+00:00');
});

test('create page is served in Finnish under the fi prefix', function () {
    $this->get('/fi')->assertOk()->assertSee('Luo äänestys');
});

test('creates a poll that collects suggestions first', function () {
    fillPoll(Livewire::test('pages::create-poll'))
        ->set('collectSuggestions', true)
        ->set('suggestionsEndAt', now()->addDays(2)->format('Y-m-d\TH:i'))
        ->set('maxSuggestions', 3)
        ->set('options.0.label', '')
        ->set('options.1.label', '')
        ->call('save')
        ->assertHasNoErrors();

    $poll = Poll::firstOrFail();

    expect($poll->phase())->toBe(PollPhase::Suggestions)
        ->and($poll->max_suggestions)->toBe(3)
        ->and($poll->options)->toHaveCount(0);
});

test('keeps starting options when collecting suggestions', function () {
    fillPoll(Livewire::test('pages::create-poll'))
        ->set('collectSuggestions', true)
        ->set('suggestionsEndAt', now()->addDays(2)->format('Y-m-d\TH:i'))
        ->set('options.1.label', '')
        ->call('save')
        ->assertHasNoErrors();

    expect(Poll::firstOrFail()->options->pluck('label')->all())->toBe(['Dune']);
});

test('requires the suggestion end to come before the voting end', function () {
    fillPoll(Livewire::test('pages::create-poll'))
        ->set('collectSuggestions', true)
        ->set('suggestionsEndAt', now()->addMonth()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasErrors(['suggestionsEndAt']);

    expect(Poll::count())->toBe(0);
});

test('requires a suggestion end time when collecting suggestions', function () {
    fillPoll(Livewire::test('pages::create-poll'))
        ->set('collectSuggestions', true)
        ->call('save')
        ->assertHasErrors(['suggestionsEndAt' => 'required']);
});

test('limits how many polls one client can create', function () {
    for ($i = 0; $i < 10; $i++) {
        fillPoll(Livewire::test('pages::create-poll'))->call('save')->assertHasNoErrors();
    }

    fillPoll(Livewire::test('pages::create-poll'))
        ->call('save')
        ->assertHasErrors(['title']);

    expect(Poll::count())->toBe(10);
});
