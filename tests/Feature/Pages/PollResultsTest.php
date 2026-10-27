<?php

use App\Actions\CastVote;
use App\Models\Poll;
use App\Models\PollOption;

/**
 * @return array{0: Poll, 1: list<int>}
 */
function votedPoll(Poll $poll): array
{
    $ids = PollOption::factory()->count(3)->sequence(fn ($s) => ['position' => $s->index, 'label' => 'Book '.$s->index])->for($poll)->create()->pluck('id')->all();

    return [$poll, $ids];
}

test('shows live results that refresh while voting is open', function () {
    [$poll, $ids] = votedPoll(Poll::factory()->create());
    (new CastVote)->handle($poll, 'a', [$ids[0]]);

    $this->get("/p/{$poll->slug}/results")
        ->assertOk()
        ->assertSee('Live results')
        ->assertSee('1 participant')
        ->assertSee('Book 0')
        ->assertSee('wire:poll.5s.visible', false)
        ->assertDontSee('Winner');
});

test('announces the winner and stops refreshing once the poll is closed', function () {
    [$poll, $ids] = votedPoll(Poll::factory()->ended()->create(['voting_starts_at' => now()->subWeek()]));
    $poll->forceFill(['voting_ends_at' => now()->addDay()])->save();
    (new CastVote)->handle($poll, 'a', [$ids[1]]);
    (new CastVote)->handle($poll, 'b', [$ids[1]]);
    $poll->forceFill(['voting_ends_at' => now()->subHour()])->save();

    $this->get("/p/{$poll->slug}/results")
        ->assertOk()
        ->assertSee('Final results')
        ->assertSee('Winner')
        ->assertSee('Book 1')
        ->assertDontSee('wire:poll', false);
});

test('announces a tie when the leading options have equal scores', function () {
    [$poll, $ids] = votedPoll(Poll::factory()->create());
    (new CastVote)->handle($poll, 'a', [$ids[0]]);
    (new CastVote)->handle($poll, 'b', [$ids[1]]);
    $poll->forceFill(['closed_at' => now()->subSecond()])->save();

    $this->get("/p/{$poll->slug}/results")->assertOk()->assertSee('Tie');
});

test('does not announce a winner when nobody has voted', function () {
    [$poll] = votedPoll(Poll::factory()->closedEarly()->create());

    $this->get("/p/{$poll->slug}/results")->assertOk()->assertDontSee('Winner')->assertDontSee('Tie');
});

test('explains the ranking points', function () {
    [$poll] = votedPoll(Poll::factory()->ranking(2)->create());

    $this->get("/p/{$poll->slug}/results")->assertOk()->assertSee('first place of each ballot gets 2 points');
});

test('returns 404 for the results of an unknown poll', function () {
    $this->get('/p/unknown/results')->assertNotFound();
});

test('shows the results in Finnish under the fi prefix', function () {
    [$poll] = votedPoll(Poll::factory()->create());

    $this->get("/fi/aanestys/{$poll->slug}/tulokset")->assertOk()->assertSee('Reaaliaikaiset tulokset')->assertSee('0 osallistujaa');
});
