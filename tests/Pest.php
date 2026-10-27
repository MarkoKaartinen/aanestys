<?php

use App\Actions\CreatePoll;
use App\Enums\Category;
use App\Enums\VotingMethod;
use App\Models\Poll;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        Http::preventStrayRequests();
        Sleep::fake(syncWithCarbon: true);
        Exceptions::fake();
        $this->withoutVite();
    })
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Create a free text poll through the action, like the creation page does.
 *
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
