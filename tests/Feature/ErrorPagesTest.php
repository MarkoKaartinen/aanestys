<?php

test('an unknown address shows the English not found page', function () {
    $this->get('/no-such-page')
        ->assertNotFound()
        ->assertSee('Page not found')
        ->assertSee('Back to the front page');
});

test('an unknown address under the fi prefix shows the Finnish not found page', function () {
    $this->get('/fi/no-such-page')
        ->assertNotFound()
        ->assertSee('Sivua ei löytynyt')
        ->assertSee('Takaisin etusivulle')
        ->assertSee('href="/fi"', false);
});

test('an unknown poll shows the not found page in the language of the address', function () {
    $this->get('/p/unknown')->assertNotFound()->assertSee('Page not found');
    $this->get('/fi/aanestys/unknown')->assertNotFound()->assertSee('Sivua ei löytynyt');
});

test('an unknown admin token shows the not found page', function () {
    $this->get('/fi/hallinta/unknown')->assertNotFound()->assertSee('Sivua ei löytynyt');
});

test('error pages are not indexed by search engines', function () {
    $this->get('/no-such-page')->assertSee('noindex', false);
});

test('every error page is available in both languages', function (int $status, string $english, string $finnish) {
    app()->setLocale('en');
    expect(view("errors.$status")->render())->toContain($english);

    app()->setLocale('fi');
    expect(view("errors.$status")->render())->toContain($finnish);
})->with([
    [403, 'Forbidden', 'Pääsy estetty'],
    [404, 'Page not found', 'Sivua ei löytynyt'],
    [419, 'Page expired', 'Sivu on vanhentunut'],
    [429, 'Too many requests', 'Liian monta pyyntöä'],
    [500, 'Something went wrong', 'Jokin meni pieleen'],
    [503, 'Back soon', 'Palaamme pian'],
]);

test('the Livewire error messages are on the page in the current language', function () {
    $this->get('/fi/tietoa')->assertSee('data-message-expired="Sivu on vanhentunut.', false);
    $this->get('/about')->assertSee('data-message-expired="This page has expired.', false);
});

test('every translation key used by the app exists in the Finnish file', function () {
    $finnish = json_decode((string) file_get_contents(lang_path('fi.json')), true);
    $missing = [];

    foreach (['app', 'resources'] as $directory) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path($directory), FilesystemIterator::SKIP_DOTS)) as $file) {
            if (! str_ends_with($file->getFilename(), '.php')) {
                continue;
            }

            preg_match_all("/(?:__|trans_choice)\\(\\s*'((?:[^'\\\\]|\\\\.)*)'/", (string) file_get_contents($file->getPathname()), $matches);

            foreach ($matches[1] as $key) {
                $key = stripslashes($key);

                if (! str_contains($key, '::') && ! array_key_exists($key, $finnish)) {
                    $missing[$key] = $file->getFilename();
                }
            }
        }
    }

    expect($missing)->toBe([]);
});
