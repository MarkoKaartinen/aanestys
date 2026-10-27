<?php

use App\Actions\CastVote;
use App\Enums\PollPhase;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\Vote;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

test('shows the share and admin links to someone with the admin token', function () {
    ['poll' => $poll, 'admin_token' => $token] = createdPoll();

    $this->get("/manage/$token")
        ->assertOk()
        ->assertSee(route('poll.show', $poll->slug))
        ->assertSee(route('poll.manage', $token))
        ->assertSee('noindex', false);
});

test('returns 404 for an unknown admin token', function () {
    $this->get('/manage/not-a-real-token')->assertNotFound();
});

test('the share link opens the poll with its options', function () {
    ['poll' => $poll] = createdPoll();

    $this->get("/p/{$poll->slug}")
        ->assertOk()
        ->assertSee('Next book')
        ->assertSee('Dune')
        ->assertSee('noindex', false);
});

test('returns 404 for an unknown poll slug', function () {
    $this->get('/p/unknown')->assertNotFound();
});

test('the admin token alone does not open the poll by slug', function () {
    ['admin_token' => $token] = createdPoll();

    $this->get("/p/$token")->assertNotFound();
});

test('the creator can edit the title description and end time', function () {
    ['poll' => $poll, 'admin_token' => $token] = createdPoll();
    $newEnd = now()->addDays(10)->setTimezone('Europe/Helsinki');

    Livewire::test('pages::manage-poll', ['token' => $token])
        ->set('title', 'Another book')
        ->set('description', 'Pick one')
        ->set('endsAt', $newEnd->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('saved', true);

    $poll->refresh();

    expect($poll->title)->toBe('Another book')
        ->and($poll->description)->toBe('Pick one')
        ->and($poll->voting_ends_at->setTimezone('Europe/Helsinki')->format('Y-m-d\TH:i'))->toBe($newEnd->format('Y-m-d\TH:i'));
});

test('moving the end time keeps the deletion date in line', function () {
    ['poll' => $poll, 'admin_token' => $token] = createdPoll();

    Livewire::test('pages::manage-poll', ['token' => $token])
        ->set('endsAt', now()->addDays(20)->setTimezone('Europe/Helsinki')->format('Y-m-d\TH:i'))
        ->call('save');

    expect($poll->refresh()->expires_at->equalTo($poll->voting_ends_at->copy()->addDays(config('polls.retention_days'))))->toBeTrue();
});

test('rejects an end time in the past', function () {
    ['poll' => $poll, 'admin_token' => $token] = createdPoll();
    $original = $poll->voting_ends_at->toIso8601String();

    Livewire::test('pages::manage-poll', ['token' => $token])
        ->set('endsAt', now()->subDay()->setTimezone('Europe/Helsinki')->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasErrors(['endsAt']);

    expect($poll->refresh()->voting_ends_at->toIso8601String())->toBe($original);
});

test('shows an error when the title is cleared', function () {
    ['admin_token' => $token] = createdPoll();

    Livewire::test('pages::manage-poll', ['token' => $token])
        ->set('title', '')
        ->call('save')
        ->assertHasErrors(['title' => 'required']);
});

test('the creator can close voting early', function () {
    ['poll' => $poll, 'admin_token' => $token] = createdPoll();

    Livewire::test('pages::manage-poll', ['token' => $token])->call('closeNow');

    expect($poll->refresh()->phase())->toBe(PollPhase::Closed)
        ->and($poll->expires_at->equalTo($poll->closed_at->copy()->addDays(config('polls.retention_days'))))->toBeTrue();
});

test('the creator can delete the poll with its votes', function () {
    ['poll' => $poll, 'admin_token' => $token] = createdPoll();
    $option = $poll->options()->first();
    (new CastVote)->handle($poll, 'voter', [$option->id]);

    Livewire::test('pages::manage-poll', ['token' => $token])
        ->call('deletePoll')
        ->assertRedirect('/');

    expect(Poll::count())->toBe(0)->and(Vote::count())->toBe(0);
});

test('the admin token cannot be changed from the browser', function () {
    ['admin_token' => $token] = createdPoll();
    createdPoll();

    Livewire::test('pages::manage-poll', ['token' => $token])
        ->set('token', 'other');
})->throws(CannotUpdateLockedPropertyException::class);

test('shows the number of participants', function () {
    ['poll' => $poll, 'admin_token' => $token] = createdPoll();
    (new CastVote)->handle($poll, 'one', [$poll->options()->first()->id]);

    Livewire::test('pages::manage-poll', ['token' => $token])->assertSee('1 participant');
});

test('the creator can remove a suggestion during the suggestion round', function () {
    $poll = Poll::factory()->collectingSuggestions()->create();
    $token = 'admin-token';
    $poll->forceFill(['admin_token_hash' => Poll::hashToken($token)])->save();
    $option = PollOption::factory()->for($poll)->create();

    Livewire::test('pages::manage-poll', ['token' => $token])->call('removeOption', $option->id);

    expect($poll->options()->count())->toBe(0);
});

test('options cannot be removed once voting has started', function () {
    ['poll' => $poll, 'admin_token' => $token] = createdPoll();
    $option = $poll->options()->first();

    Livewire::test('pages::manage-poll', ['token' => $token])->call('removeOption', $option->id);

    expect($poll->options()->count())->toBe(2);
});

test('the creator can close the suggestions and start voting', function () {
    $poll = Poll::factory()->collectingSuggestions()->create();
    $token = 'admin-token';
    $poll->forceFill(['admin_token_hash' => Poll::hashToken($token)])->save();

    Livewire::test('pages::manage-poll', ['token' => $token])->call('closeSuggestions');

    expect($poll->refresh()->phase())->toBe(PollPhase::Voting);
});
