<?php

test('home page is served in English without a locale prefix', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Ask a group. Get an answer.')
        ->assertSee('lang="en"', false);
});

test('home page is served in Finnish under the fi prefix', function () {
    $this->get('/fi')
        ->assertOk()
        ->assertSee('Kysy ryhmältä. Saat vastauksen.')
        ->assertSee('lang="fi"', false);
});

test('redirects to the fi prefix when the browser prefers Finnish', function () {
    $this->get('/', ['Accept-Language' => 'fi-FI,fi;q=0.9,en;q=0.5'])
        ->assertRedirect('/fi');
});

test('stays on the unprefixed URL when the browser prefers English', function () {
    $this->get('/', ['Accept-Language' => 'en-US,en;q=0.9'])
        ->assertOk();
});

test('redirects the explicit en prefix to the unprefixed URL', function () {
    $this->get('/en')->assertRedirect('/');
});

test('language switcher links to the prefixed URL of each language', function () {
    $this->get('/')->assertSee('href="'.url('/fi').'"', false);
    $this->get('/fi')->assertSee('href="'.url('/en').'"', false);
});

test('cookie consent banner is fully translated on the Finnish page', function () {
    $this->get('/fi')
        ->assertSee('Käytämme evästeitä')
        ->assertSee('Välttämättömät evästeet')
        ->assertSee('Muistaa valitsemasi kielen.')
        ->assertDontSee('Essential cookies');
});

test('cookie consent banner is fully translated on the English page', function () {
    $this->get('/')
        ->assertSee('We use cookies')
        ->assertSee('Essential cookies')
        ->assertSee('Remembers the language you chose.');
});

test('paths are translated in Finnish and the language switcher follows them', function () {
    $this->get('/about')->assertOk()->assertSee('href="'.url('/fi/tietoa').'"', false);
    $this->get('/fi/tietoa')->assertOk()->assertSee('href="'.url('/en/about').'"', false);
});

test('route helpers return the path of the current language', function () {
    $this->get('/about');
    expect(route('privacy'))->toBe(url('/privacy'));

    $this->get('/fi/tietoa');
    expect(route('privacy'))->toBe(url('/fi/tietosuoja'));
});

test('the English path does not exist under the Finnish prefix', function () {
    $this->get('/fi/about')->assertNotFound();
});

test('poll links keep their translated Finnish paths', function () {
    ['poll' => $poll] = createdPoll();

    $this->get("/fi/aanestys/{$poll->slug}")->assertOk();
    $this->get("/p/{$poll->slug}")->assertOk()->assertSee('href="'.url("/fi/aanestys/{$poll->slug}").'"', false);
});

test('a saved language wins over the browser language on an unprefixed URL', function () {
    $this->withCookie('locale', 'fi')
        ->get('/about', ['Accept-Language' => 'en-US,en;q=0.9'])
        ->assertRedirect('/fi/tietoa');
});

test('a saved English choice stops the redirect to Finnish', function () {
    $this->withCookie('locale', 'en')
        ->get('/about', ['Accept-Language' => 'fi-FI,fi;q=0.9'])
        ->assertOk();
});

test('the redirect to the preferred language keeps the query string', function () {
    $this->get('/about?ref=mail', ['Accept-Language' => 'fi'])->assertRedirect('/fi/tietoa?ref=mail');
});

test('posts are never redirected to another language', function () {
    $this->post('/cookie-consent/reset', [], ['Accept-Language' => 'fi'])->assertRedirect();
});

test('public pages point search engines to each language version', function () {
    $this->get('/about')
        ->assertSee('<link rel="alternate" hreflang="en" href="'.url('/about').'">', false)
        ->assertSee('<link rel="alternate" hreflang="fi" href="'.url('/fi/tietoa').'">', false);
});

test('poll pages do not advertise language versions', function () {
    ['poll' => $poll] = createdPoll();

    $this->get("/p/{$poll->slug}")->assertDontSee('rel="alternate"', false);
});
