<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class SessionLimitMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $maxSessions = 10;
        $key = "session_counter:{$ip}";

        // Получаем текущий счетчик напрямую из Redis
        $sessionCount = (int) Redis::get($key) ?: 0;

        // Увеличиваем счетчик для новой сессии
        if (!$request->session()->has('counted')) {
            $sessionCount++;
            // Сохраняем напрямую в Redis на 24 часа
            Redis::setex($key, 86400, $sessionCount);
            $request->session()->put('counted', true);
        }

        // Проверяем лимит
        if ($sessionCount > $maxSessions) {
            return response()->view('errors.429', [
                'title' => trans('errors')['errors'][429]['title'],
                'text'  => trans('errors')['errors'][429]['text'],
                'ip' => $ip,
                'maxSessions' => $maxSessions,
                'currentCount' => $sessionCount
            ], 429);
        }

        return $next($request);
    }
}