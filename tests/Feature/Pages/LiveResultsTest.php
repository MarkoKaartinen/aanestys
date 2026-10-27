<?php

use App\Actions\CastVote;
use App\Actions\CreatePoll;
use App\Enums\Category;
use App\Enums\VotingMethod;
use App\Models\Poll;
use App\Models\PollOption;
use Livewire\Livewire;

/**
 * @return array{0: Poll, 1: list<int>}
 */
function streamedPoll(array $attributes = []): array
{
    $poll = Poll::factory()->create($attributes);
    $ids = PollOption::factory()->count(2)->sequence(fn ($s) => ['position' => $s->index, 'label' => 'Game '.$s->index])->for($poll)->create()->pluck('id')->all();

    return [$poll, $ids];
}

test('the live link shows the results in a bare layout that refreshes quickly', function () {
    [$poll, $ids] = streamedPoll();
    (new CastVote)->handle($poll, 'a', [$ids[0]]);

    $this->get("/live/{$poll->live_token}")
        ->assertOk()
        ->assertSee($poll->title)
        ->assertSee('Game 0')
        ->assertSee('1 participant')
        ->assertSee('wire:poll.3s.visible', false)
        ->assertSee('noindex', false)
        ->assertDontSee('Privacy');
});

test('the live link still works when the results are hidden from participants', function () {
    [$poll] = streamedPoll(['live_results_visible' => false]);

    $this->get("/live/{$poll->live_token}")->assertOk()->assertSee('Game 0');
});

test('the results page hides the standings while voting is open when the creator chose so', function () {
    [$poll, $ids] = streamedPoll(['live_results_visible' => false]);
    (new CastVote)->handle($poll, 'a', [$ids[0]]);

    $this->get("/p/{$poll->slug}/results")
        ->assertOk()
        ->assertSee('hidden the results until voting ends')
        ->assertDontSee('Game 0')
        ->assertDontSee('1 participant');
});

test('hidden results become public once the poll is closed', function () {
    [$poll, $ids] = streamedPoll(['live_results_visible' => false]);
    (new CastVote)->handle($poll, 'a', [$ids[0]]);
    $poll->forceFill(['closed_at' => now()->subSecond()])->save();

    $this->get("/p/{$poll->slug}/results")->assertOk()->assertSee('Game 0')->assertDontSee('hidden the results');
});

test('returns 404 for an unknown live link', function () {
    $this->get('/live/not-a-token')->assertNotFound();
});

test('the poll slug does not open the live view', function () {
    [$poll] = streamedPoll();

    $this->get("/live/{$poll->slug}")->assertNotFound();
});

test('every new poll gets its own live token', function () {
    $data = [
        'title' => 'Next game',
        'category' => Category::Custom,
        'voting_method' => VotingMethod::Single,
        'timezone' => 'Europe/Helsinki',
        'voting_ends_at' => now()->addWeek(),
        'options' => [['label' => 'A', 'date' => null, 'time' => null], ['label' => 'B', 'date' => null, 'time' => null]],
    ];

    $first = (new CreatePoll)->handle($data)['poll'];
    $second = (new CreatePoll)->handle($data)['poll'];

    expect($first->live_token)->toHaveLength(24)->not->toBe($second->live_token);
});

test('the creator sees the live link on the admin page and can hide the results', function () {
    ['poll' => $poll, 'admin_token' => $token] = createdPoll();

    Livewire::test('pages::manage-poll', ['token' => $token])
        ->assertSee(route('poll.live', $poll->live_token))
        ->set('liveResultsVisible', false)
        ->call('save')
        ->assertHasNoErrors();

    expect($poll->refresh()->live_results_visible)->toBeFalse();
});

test('a new poll can be created with the results hidden', function () {
    Livewire::test('pages::create-poll')
        ->set('title', 'Secret result')
        ->set('timezone', 'Europe/Helsinki')
        ->set('endsAt', now()->addWeek()->format('Y-m-d\TH:i'))
        ->set('options.0.label', 'A')
        ->set('options.1.label', 'B')
        ->set('liveResultsVisible', false)
        ->call('save')
        ->assertHasNoErrors();

    expect(Poll::firstOrFail()->live_results_visible)->toBeFalse();
});
