<?php

/**
 * Тестовые маршруты
 */

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/test-encrypt', function () {
        try {
            $encrypted = encrypt('test');
            $decrypted = decrypt($encrypted);
            return [
                'status' => 'ok',
                'encryption_works' => ($decrypted === 'test'),
                'app_key' => config('app.key') ? 'set' : 'not set',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    });

    Route::get('/test-session', function () {
        try {
            session(['test' => 'value']);
            return [
                'session_set' => true,
                'session_get' => session('test'),
                'session_driver' => config('session.driver'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    });

    Route::get('/test-db', function () {
        try {
            $users = DB::table('users')->count();
            return [
                'status' => 'ok',
                'users_count' => $users,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    });

    // Отладка IP адреса пользователя
    Route::get("/test_ip", function (Illuminate\Http\Request $request) {
        $trustedProxies = $request->getTrustedProxies();
        echo "<h1>IP Detection Debug</h1>";
        echo "<p><strong>Your Real IP should be visible below</strong></p>";
        echo "<hr>";
        echo "<p>Laravel request()->ip(): <strong style='color: blue; font-size: 1.2em;'>{$request->ip()}</strong></p>";
        echo "<p>SERVER REMOTE_ADDR: " . ($_SERVER["REMOTE_ADDR"] ?? "N/A") . "</p>";
        echo "<h3>All IP-related Headers:</h3>";
        $ipHeaders = [
            "HTTP_X_REAL_IP", "HTTP_X_FORWARDED_FOR", "HTTP_X_FORWARDED_HOST",
            "HTTP_X_FORWARDED_PORT", "HTTP_X_FORWARDED_PROTO", "HTTP_CLIENT_IP", "HTTP_CF_CONNECTING_IP",
        ];
        foreach ($ipHeaders as $header) {
            $value = $_SERVER[$header] ?? "Not set";
            echo "<p>{$header}: {$value}</p>";
        }
    });

});
