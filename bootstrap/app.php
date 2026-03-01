<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
//use Throwable;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Настройка trusted proxies
        $middleware->trustProxies(at: '*');

        // Middleware для фикса IP на Mac
        $middleware->web(prepend: [
            \App\Http\Middleware\MacIpFix::class,
        ]);

        // Остальные Middleware
        $middleware->web(append: [
            \App\Http\Middleware\SessionLimitMiddleware::class,
            \App\Http\Middleware\LocalizationMiddleware::class,
            \App\Http\Middleware\LoginThrottle::class,
        ]);

        $middleware->alias([
            'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
            'login.throttle' => \App\Http\Middleware\LoginThrottle::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (HttpException $e, Request $request) {
            $status = $e->getStatusCode();
            
            // Для JSON запросов
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => getErrorTitle($status),
                    'message' => $e->getMessage() ?: getErrorText($status),
                ], $status);
            }
            
            // Для веб-запросов - кастомная страница
            if ($status == 429)
            {
                return response()->view('errors.429', [
                    'title' => getErrorTitle($status),
                    'text' => $e->getMessage() ?: getErrorText($status),
                    'image' => getErrorImage($status),
                ], $status);
            }
            else
            {
                return response()->view('errors.custom', [
                    'title' => getErrorTitle($status),
                    'text' => $e->getMessage() ?: getErrorText($status),
                    'image' => getErrorImage($status),
                ], $status);
            }
        });
        
        $exceptions->render(function (Throwable $e, Request $request) {
            // Для всех других исключений
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Server Error',
                    'message' => config('app.debug') ? $e->getMessage() : 'Something went wrong',
                ], 500);
            }
            
            return response()->view('errors.custom', [
                'title' => getErrorTitle(500),
                'text' => config('app.debug') ? $e->getMessage() : getErrorText(500),
                'image' => getErrorImage(500),
            ], 500);
        });

        // Обработка неавторизованного доступа - редирект на login
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                return redirect()->route('login');
            }
            return $response;
        });
    })->create();

// Вспомогательные функции
function getErrorTitle(int $status): string
{
   return trans('errors')['errors'][$status]['title'] ?? 'Error';
}

function getErrorText(int $status): string
{
    return trans('errors')['errors'][$status]['text'] ?? 'An error occurred';
}

function getErrorImage(int $status): string
{
    return "/source/base/images/errors/{$status}.svg";
}