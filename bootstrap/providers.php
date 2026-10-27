<?php

use App\Providers\AppServiceProvider;
use App\Providers\CookiesServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\TelescopeServiceProvider;

return [
    AppServiceProvider::class,
    CookiesServiceProvider::class,
    HorizonServiceProvider::class,
    TelescopeServiceProvider::class,
];
