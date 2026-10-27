<?php

use Illuminate\Support\Facades\Route;

Route::localize(function () {
    Route::livewire('/', 'pages::create-poll')->name('home');

    foreach (['about', 'faq', 'privacy', 'cookies'] as $page) {
        Route::view("/$page", 'content.page', ['page' => $page])->name($page);
    }

    Route::livewire('/manage/{token}', 'pages::manage-poll')->name('poll.manage');
    Route::livewire('/p/{slug}', 'pages::show-poll')->name('poll.show');
    Route::livewire('/p/{slug}/results', 'pages::poll-results')->name('poll.results');
});
