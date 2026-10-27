<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use NielsNumbers\LaravelLocalizer\Facades\Localizer;

Route::translate(function () {
    Route::livewire('/', 'pages::create-poll')->name('home');

    foreach (['about', 'faq', 'privacy', 'cookies'] as $page) {
        Route::view(Localizer::url($page), 'content.page', ['page' => $page])->name($page);
    }

    Route::livewire(Localizer::url('manage/{token}'), 'pages::manage-poll')->name('poll.manage');
    Route::livewire(Localizer::url('p/{slug}'), 'pages::show-poll')->name('poll.show');
    Route::livewire(Localizer::url('p/{slug}/results'), 'pages::poll-results')->name('poll.results');
    Route::livewire(Localizer::url('live/{token}'), 'pages::live-results')->name('poll.live');
});

// An unknown address never reaches the localizer's middleware, so take the
// language for the error page from the first part of the path.
Route::fallback(function (Request $request): never {
    $locale = $request->segment(1);

    if (in_array($locale, config('localizer.supported_locales'), true)) {
        app()->setLocale($locale);
    }

    abort(404);
});
