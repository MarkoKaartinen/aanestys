<?php

test('information pages are served in English', function (string $path, string $text) {
    $this->get($path)->assertOk()->assertSee($text);
})->with([
    ['/about', 'free poll service'],
    ['/faq', 'Do I need an account?'],
    ['/privacy', 'What we store'],
    ['/cookies', 'Cookies we use'],
]);

test('information pages are served in Finnish', function (string $path, string $text) {
    $this->get("/fi$path")->assertOk()->assertSee($text);
})->with([
    ['/about', 'ilmainen äänestyspalvelu'],
    ['/faq', 'Tarvitsenko tilin?'],
    ['/privacy', 'Mitä tallennamme'],
    ['/cookies', 'Käyttämämme evästeet'],
]);

test('the cookies page lists the cookies the service sets', function () {
    $this->get('/cookies')
        ->assertOk()
        ->assertSee('XSRF-TOKEN')
        ->assertSee('voter')
        ->assertSee('locale');
});

test('the retention period on the pages follows the configuration', function () {
    config(['polls.retention_days' => 45]);

    $this->get('/privacy')->assertSee('45 days');
});

test('the privacy policy names the operator when it is configured', function () {
    config(['polls.operator.name' => 'Example Oy', 'polls.operator.email' => 'hello@example.com']);

    $this->get('/privacy')->assertSee('Example Oy')->assertSee('hello@example.com');
});

test('the footer links to the information pages', function () {
    $this->get('/')->assertSee(route('privacy'), false)->assertSee(route('cookies'), false);
});

test('the cookie banner links to the cookies page', function () {
    $this->get('/')->assertSee(route('cookies'), false);
});
