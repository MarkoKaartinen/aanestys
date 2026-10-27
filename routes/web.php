<?php

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
