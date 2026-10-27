<?php

test('home page is served in English without a locale prefix', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Make a decision together')
        ->assertSee('lang="en"', false);
});

test('home page is served in Finnish under the fi prefix', function () {
    $this->get('/fi')
        ->assertOk()
        ->assertSee('Päättäkää yhdessä')
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
