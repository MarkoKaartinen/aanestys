<?php

use Illuminate\Support\Facades\Route;

Route::localize(function () {
    Route::view('/', 'welcome')->name('home');
});
