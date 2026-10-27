# Tests

- Write Pest tests together with every new feature.
- A test file mirrors the class under test: `app/Actions/CreateWatch.php` is tested in `tests/Feature/Actions/CreateWatchTest.php`, and pure logic that does not use the framework in `tests/Unit/...`. Livewire pages live in `tests/Feature/Pages`.
- Create fakes (`Queue::fake([...])`, `Notification::fake()`, `Http::fake(...)`) and records inside the test that needs them. Do not use `beforeEach()` in test files; share setup through helper functions in `tests/Pest.php`.
- `tests/Pest.php` guards every Feature test: `Http::preventStrayRequests()`, `Sleep::fake(syncWithCarbon: true)`, `Exceptions::fake()` and `withoutVite()`. Do not repeat them in single tests.
- Name a test by its result and condition, and include the exact status code for an HTTP error (`returns 404 when ...`).
- Run the narrowest tests that cover a change and ask the user to run the complete suite.
