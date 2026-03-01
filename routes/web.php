<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LocalizationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JsonController;
use Illuminate\Http\Request;
use App\Http\Controllers\TestController;
use App\Http\Controllers\Livemachines\SpravController;

// Маршруты аутентификации (доступны без авторизации)
require __DIR__.'/auth.php';

Route::get("/json", [JsonController::class, "index"])->name("json");

// Test Routes
Route::prefix('tests')->group(function () {

    // Главная страница со всеми тестами
    Route::get('/', [TestController::class, 'index']);
    
    // Активные тесты
    Route::get('/active', [TestController::class, 'activeTests']);
    
    // Поиск по имени
    Route::get('/search', [TestController::class, 'searchByName']);
    
    // Recent tests (последние 7 дней)
    Route::get('/recent', [TestController::class, 'recentTests']);
    
    // Первый тест
    Route::get('/first', [TestController::class, 'firstTest']);
    
    // Создание нового теста
    Route::get('/create', [TestController::class, 'createTest']);

});

// ВСЕ ОСТАЛЬНЫЕ маршруты требуют авторизации и автоматически редиректят на /login
Route::middleware(['auth', 'verified'])->group(function () {

    // Маршруты, доступные БЕЗ авторизации (явно указанные)
    Route::get("/error{code}", function ($code) {
        $allowedCodes = [401, 403, 404, 419, 429, 500, 503];
        if (!in_array($code, $allowedCodes)) {
            abort(404);
        }
        abort($code);
    })->where("code", "[0-9]+")->name("errors_401_403_404_419_500");

    // Главная страница
    Route::get("/", [HomeController::class, "index"])->name("home");
    
    // Смена языка
    Route::get("/locale/{locale}", [LocalizationController::class, "setLang"])->name("locale.switch");
    
    // Сброс к языку браузера
    Route::get("/locale_reset", [LocalizationController::class, "resetLang"])->name("locale.reset");
    
    // Дашборд
    Route::get("/dashboard", function () {
        return view("dashboard");
    })->name("dashboard");
    
    // Профиль
    Route::get("/profile", function () {
        return view("profile");
    })->name("profile");
    Route::get("/profile_new",    [ProfileController::class, "edit"])->name("profile.edit");
    Route::patch("/profile_new",  [ProfileController::class, "update"])->name("profile.update");
    Route::delete("/profile_new", [ProfileController::class, "destroy"])->name("profile.destroy");
    
    // Другие ваши маршруты
    Route::get("/logout", [HomeController::class, "index"])->name("logout");

    Route::get("/maintenance", function () {
        return view("errors.maintenance");
    })->name("maintenance");
    
    Route::get("/comming_soon", function () {
        return view("errors.comming_soon");
    })->name("comming_soon");

    // Отладка IP адреса пользователя
    Route::get("/debug/ip", function (Illuminate\Http\Request $request) {
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

    Route::get('livemachines/sprav/manuf', [SpravController::class, 'manuf_list'])
        ->name('lm_manuf.list');

    Route::get('livemachines/sprav/country', [SpravController::class, 'country_list'])
        ->name('lm_country.list');

    Route::get('livemachines/sprav/model', [SpravController::class, 'model_list'])
        ->name('lm_model.list');

    Route::get('livemachines/sprav/tech', [SpravController::class, 'tech_list'])
        ->name('lm_tech.list');
    Route::get('livemachines/sprav/data', [SpravController::class, 'tech_data_ajax'])
        ->name('lm_tech.data');
    Route::delete('livemachines/sprav/{id}', [SpravController::class, 'tech_destroy'])
        ->name('lm_sprav.tech_destroy')
        ->whereNumber('id');

    Route::get('livemachines/sprav/group', [SpravController::class, 'group_list'])
        ->name('lm_group.list');
});

// Fallback - для несуществующих маршрутов (показывает 404 без редиректа)
Route::fallback(function () {
    abort(404);
});