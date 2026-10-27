<?php

namespace App\Support;

use Illuminate\Support\Facades\RateLimiter;

class Throttle
{
    /**
     * Count a request from the current client and tell whether it went over the limit.
     *
     * The client is identified by a keyed hash of the IP address, so the address
     * itself is never stored.
     */
    public static function exceeded(string $name, int $maxAttempts, int $decaySeconds): bool
    {
        $key = $name.':'.hash_hmac('sha256', (string) request()->ip(), (string) config('app.key'));

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return true;
        }

        RateLimiter::hit($key, $decaySeconds);

        return false;
    }
}
