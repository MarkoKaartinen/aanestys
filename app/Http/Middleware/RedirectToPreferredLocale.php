<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use NielsNumbers\LaravelLocalizer\Detectors\BrowserDetector;
use NielsNumbers\LaravelLocalizer\Localizer;
use Symfony\Component\HttpFoundation\Response;

/**
 * Send visitors who arrive on an unprefixed URL to the language they prefer.
 *
 * The localizer's translated routes mark the unprefixed variant with the default
 * locale, which wins over the saved choice and the browser language. This runs
 * before SetLocale and looks at the saved language first and the browser second.
 */
class RedirectToPreferredLocale
{
    public function __construct(private Localizer $localizer) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethodSafe() || ! str_starts_with((string) $request->route()?->getName(), 'without_locale.')) {
            return $next($request);
        }

        $preferred = $this->preferredLocale($request);

        if ($preferred === null || $preferred === $this->localizer->defaultLocale()) {
            return $next($request);
        }

        // The switcher URL already carries the query string.
        return redirect(Route::localizedSwitcherUrl($preferred));
    }

    /**
     * The language saved in the cookie, or else the best supported browser language.
     */
    private function preferredLocale(Request $request): ?string
    {
        $candidates = [$request->cookie('locale'), ...Arr::wrap((new BrowserDetector)->detect($request))];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $this->localizer->isActive($candidate)) {
                return $this->localizer->canonicalize($candidate);
            }
        }

        return null;
    }
}
