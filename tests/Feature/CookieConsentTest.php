<?php

use Illuminate\Support\Facades\Facade;
use Whitecube\LaravelCookieConsent\CookiesManager;
use Whitecube\LaravelCookieConsent\CookiesRegistrar;
use Whitecube\LaravelCookieConsent\Facades\Cookies;

/**
 * The value the package stores once everything is accepted: plain JSON with an
 * entry for every registered cookie.
 */
function acceptedConsentCookie(): string
{
    $names = collect(Cookies::getCategories())
        ->flatMap(fn ($category) => collect($category->getCookies())->pluck('name'))
        ->mapWithKeys(fn (string $name) => [$name => true])
        ->all();

    // Forget the instances built above, as the next request starts from scratch.
    app()->forgetInstance(CookiesManager::class);
    app()->forgetInstance(CookiesRegistrar::class);
    Facade::clearResolvedInstances();

    return json_encode(['consent_at' => time(), ...$names]);
}

test('the cookie banner stays away after the visitor accepted the cookies', function () {
    // The package sets this cookie outside the web middleware group, so it is not encrypted.
    $this->withUnencryptedCookie(config('cookieconsent.cookie.name'), acceptedConsentCookie())
        ->get('/')
        ->assertOk()
        ->assertDontSee('We use cookies');
});

test('the cookie banner is shown to a visitor who has not chosen yet', function () {
    $this->get('/')->assertSee('We use cookies');
});
