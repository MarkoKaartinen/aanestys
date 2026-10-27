<?php

use App\Models\Poll;
use App\Models\PollOption;
use App\Models\Vote;
use Illuminate\Support\Facades\Cookie;
use Livewire\Livewire;

/**
 * @return array{0: Poll, 1: list<int>}
 */
function votablePoll(Poll $poll, int $count = 3): array
{
    $ids = PollOption::factory()->count($count)->sequence(fn ($s) => ['position' => $s->index, 'label' => 'Option '.$s->index])->for($poll)->create()->pluck('id')->all();

    return [$poll, $ids];
}

test('a voter can vote and a cookie is issued', function () {
    [$poll, $ids] = votablePoll(Poll::factory()->create());

    Livewire::test('pages::show-poll', ['slug' => $poll->slug])
        ->set('choice', $ids[1])
        ->call('vote')
        ->assertHasNoErrors()
        ->assertSet('hasVoted', true);

    expect(Vote::count())->toBe(1)
        ->and(Cookie::hasQueued('voter'))->toBeTrue();
});

test('shows an error when nothing is chosen', function () {
    [$poll] = votablePoll(Poll::factory()->create());

    Livewire::test('pages::show-poll', ['slug' => $poll->slug])
        ->call('vote')
        ->assertHasErrors(['selection']);

    expect(Vote::count())->toBe(0);
});

test('restores the earlier vote from the voter cookie and lets it be changed', function () {
    [$poll, $ids] = votablePoll(Poll::factory()->create());

    Livewire::withCookies(['voter' => 'my-token'])
        ->test('pages::show-poll', ['slug' => $poll->slug])
        ->set('choice', $ids[0])
        ->call('vote');

    Livewire::withCookies(['voter' => 'my-token'])
        ->test('pages::show-poll', ['slug' => $poll->slug])
        ->assertSet('hasVoted', true)
        ->assertSet('choice', $ids[0])
        ->set('choice', $ids[2])
        ->call('vote');

    expect(Vote::count())->toBe(1)
        ->and(Vote::first()->choices->first()->poll_option_id)->toBe($ids[2]);
});

test('a multiple choice voter can pick several options', function () {
    [$poll, $ids] = votablePoll(Poll::factory()->create(['voting_method' => 'multiple']));

    Livewire::test('pages::show-poll', ['slug' => $poll->slug])
        ->set('selected', [(string) $ids[0], (string) $ids[2]])
        ->call('vote')
        ->assertHasNoErrors();

    expect(Vote::first()->choices)->toHaveCount(2);
});

test('a full ranking starts with every option in the list', function () {
    [$poll, $ids] = votablePoll(Poll::factory()->ranking()->create());

    $page = Livewire::test('pages::show-poll', ['slug' => $poll->slug]);

    expect($page->get('ranked'))->toEqualCanonicalizing($ids);
});

test('a top N ranking is built by adding, reordering and removing options', function () {
    [$poll, $ids] = votablePoll(Poll::factory()->ranking(2)->create());

    Livewire::test('pages::show-poll', ['slug' => $poll->slug])
        ->assertSet('ranked', [])
        ->call('addToRanking', $ids[0])
        ->call('addToRanking', $ids[1])
        ->call('addToRanking', $ids[2])
        ->assertSet('ranked', [$ids[0], $ids[1]])
        ->call('moveRanked', $ids[1], -1)
        ->assertSet('ranked', [$ids[1], $ids[0]])
        ->call('removeFromRanking', $ids[1])
        ->assertSet('ranked', [$ids[0]])
        ->call('sortRanked', $ids[2], 0, 'ranked')
        ->assertSet('ranked', [$ids[2], $ids[0]])
        ->call('sortRanked', $ids[2], 0, 'available')
        ->assertSet('ranked', [$ids[0]])
        ->call('addToRanking', $ids[1])
        ->call('vote')
        ->assertHasNoErrors();

    expect(Vote::first()->choices->pluck('rank', 'poll_option_id')->all())->toBe([$ids[0] => 1, $ids[1] => 2]);
});

test('an incomplete ranking is rejected', function () {
    [$poll, $ids] = votablePoll(Poll::factory()->ranking(2)->create());

    Livewire::test('pages::show-poll', ['slug' => $poll->slug])
        ->call('addToRanking', $ids[0])
        ->call('vote')
        ->assertHasErrors(['selection']);
});

test('shows when voting opens before it has started', function () {
    [$poll] = votablePoll(Poll::factory()->scheduled()->create());

    $this->get("/p/{$poll->slug}")->assertOk()->assertSee('Voting opens');
});

test('shows that voting has ended and offers no form', function () {
    [$poll] = votablePoll(Poll::factory()->ended()->create());

    $this->get("/p/{$poll->slug}")->assertOk()->assertSee('Voting has ended.')->assertDontSee('Submit vote');
});

test('shows times in the voters timezone for a poll with times', function () {
    $poll = Poll::factory()->create(['category' => 'dates', 'includes_time' => true, 'timezone' => 'Europe/Helsinki']);
    PollOption::factory()->for($poll)->create(['label' => null, 'starts_at' => '2026-12-05 16:00:00']);
    PollOption::factory()->for($poll)->create(['label' => null, 'starts_at' => '2026-12-06 16:00:00']);

    Livewire::test('pages::show-poll', ['slug' => $poll->slug])
        ->assertSee('6:00 PM')
        ->set('viewerTimezone', 'America/New_York')
        ->assertSee('11:00 AM')
        ->set('viewerTimezone', 'Not/AZone')
        ->assertSet('viewerTimezone', 'Europe/Helsinki');
});

test('the voting form is shown in Finnish under the fi prefix', function () {
    [$poll] = votablePoll(Poll::factory()->create());

    $this->get("/fi/aanestys/{$poll->slug}")->assertOk()->assertSee('Äänestä');
});

test('a participant can suggest an option and remove it again', function () {
    $poll = Poll::factory()->collectingSuggestions()->create();

    $page = Livewire::withCookies(['voter' => 'tok'])->test('pages::show-poll', ['slug' => $poll->slug])
        ->set('suggestionLabel', 'Dune')
        ->call('suggest')
        ->assertHasNoErrors()
        ->assertSee('Dune')
        ->assertSee('yours');

    expect($poll->options()->count())->toBe(1);

    $page->call('removeSuggestion', $poll->options()->first()->id);

    expect($poll->options()->count())->toBe(0);
});

test('a participant cannot remove a suggestion made by someone else', function () {
    $poll = Poll::factory()->collectingSuggestions()->create();
    $option = PollOption::factory()->for($poll)->create(['suggested_by_hash' => $poll->voterHash('someone-else')]);

    Livewire::withCookies(['voter' => 'tok'])->test('pages::show-poll', ['slug' => $poll->slug])
        ->call('removeSuggestion', $option->id);

    expect($poll->options()->count())->toBe(1);
});

test('shows an error when the suggestion limit is reached or the option is a duplicate', function () {
    $poll = Poll::factory()->collectingSuggestions()->create(['max_suggestions' => 2]);

    Livewire::withCookies(['voter' => 'tok'])->test('pages::show-poll', ['slug' => $poll->slug])
        ->set('suggestionLabel', 'Dune')->call('suggest')
        ->set('suggestionLabel', 'dune')->call('suggest')
        ->assertHasErrors(['suggestion']);

    expect($poll->options()->count())->toBe(1);
});

test('refreshes automatically while suggestions are being collected', function () {
    $poll = Poll::factory()->collectingSuggestions()->create();

    $this->get("/p/{$poll->slug}")->assertOk()->assertSee('wire:poll.15s.visible', false);
});

test('says there are too few options when voting starts without them', function () {
    $poll = Poll::factory()->create();
    PollOption::factory()->for($poll)->create();

    $this->get("/p/{$poll->slug}")->assertOk()->assertSee('not have enough options');
});
