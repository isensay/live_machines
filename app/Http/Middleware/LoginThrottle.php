<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class LoginThrottle
{
    public function handle(Request $request, Closure $next, $maxAttempts = 3, $decayMinutes = 1440): Response
    {
        $ip = $request->ip();
        $key = "login_attempts:{$ip}";
        $blockedKey = "login_blocked:{$ip}";

        // Проверяем блокировку
        if (Cache::has($blockedKey)) {
            abort(429, 'Слишком много неудачных попыток входа. Попробуйте снова через 24 часа.');
        }

        $attempts = Cache::get($key, 0);

        // Если достигли лимита - блокируем
        if ($attempts >= $maxAttempts) {
            Cache::put($blockedKey, true, now()->addMinutes($decayMinutes));
            abort(429, 'Слишком много неудачных попыток входа. Попробуйте снова через 24 часа.');
        }

        return $next($request);
    }
}