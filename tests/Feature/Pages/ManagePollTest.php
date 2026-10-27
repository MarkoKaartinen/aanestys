<?php

use App\Actions\CreatePoll;
use App\Enums\Category;
use App\Enums\VotingMethod;
use App\Models\Poll;

/**
 * @return array{poll: Poll, admin_token: string}
 */
function createdPoll(): array
{
    return (new CreatePoll)->handle([
        'title' => 'Next book',
        'category' => Category::Custom,
        'voting_method' => VotingMethod::Single,
        'timezone' => 'Europe/Helsinki',
        'voting_ends_at' => now()->addWeek(),
        'options' => [
            ['label' => 'Dune', 'date' => null, 'time' => null],
            ['label' => 'Piranesi', 'date' => null, 'time' => null],
        ],
    ]);
}

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
