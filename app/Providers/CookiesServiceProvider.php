<?php

namespace App\Providers;

use Whitecube\LaravelCookieConsent\CookiesRegistrar;
use Whitecube\LaravelCookieConsent\CookiesServiceProvider as ServiceProvider;
use Whitecube\LaravelCookieConsent\Facades\Cookies;

class CookiesServiceProvider extends ServiceProvider
{
    /**
     * Register the cookies lazily, when the registrar is first resolved.
     *
     * The package translates titles and descriptions while registering, so
     * registering during boot would always use the default locale instead of
     * the one chosen by the localized route.
     */
    public function register(): void
    {
        $this->app->afterResolving(CookiesRegistrar::class, fn () => $this->registerCookies());
    }

    /**
     * Define the cookies users should be aware of.
     */
    protected function registerCookies(): void
    {
        Cookies::essentials()
            ->session()
            ->csrf()
            ->name('locale')
            ->description(__('cookieConsent::cookies.defaults.locale'))
            ->duration(60 * 24 * 30);

        Cookies::essentials()
            ->name('voter')
            ->description(__('cookieConsent::cookies.defaults.voter'))
            ->duration(60 * 24 * 365);
    }
}
