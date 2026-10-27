<?php

use Illuminate\Support\Facades\Route;

Route::localize(function () {
    Route::livewire('/', 'pages::create-poll')->name('home');
    Route::livewire('/manage/{token}', 'pages::manage-poll')->name('poll.manage');
    Route::livewire('/p/{slug}', 'pages::show-poll')->name('poll.show');
});
