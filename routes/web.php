<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LocalizationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JsonController;
use Illuminate\Http\Request;
use App\Http\Controllers\TestController;
use App\Http\Controllers\Livemachines\SpravController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

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


    Route::get('livemachines/sprav/tech/edit/{id}', [SpravController::class, 'tech_edit_data'])
        ->name('lm_tech.edit_data')
        ->whereNumber('id');

    Route::get('livemachines/sprav/tech/references', [SpravController::class, 'tech_get_references'])
        ->name('lm_tech.references');

    Route::post('livemachines/sprav/tech/update/{id}', [SpravController::class, 'tech_update'])
        ->name('lm_tech.update')
        ->whereNumber('id');






    Route::get('livemachines/sprav/group', [SpravController::class, 'group_list'])
        ->name('lm_group.list');









    Route::get('/livemachines/reset-database', function () {
        // Проверяем подтверждение
        if (!request()->has('confirmed') || request()->confirmed !== 'yes') {
            return redirect()->back()->with('error', 'Операция отменена');
        }
        
        $previousUrl = url()->previous();
        $dumpPath = storage_path('../livemachines_dump.sql');
        
        if (!File::exists($dumpPath)) {
            return redirect($previousUrl)->with('error', 'Файл дампа не найден: ' . $dumpPath);
        }
        
        try {
            // Получаем соединение с БД livemachines
            $connection = DB::connection('livemachines');
            
            // Отключаем проверку внешних ключей
            $connection->statement('SET FOREIGN_KEY_CHECKS=0');
            
            // Получаем все таблицы из БД livemachines
            $tables = $connection->select('SHOW TABLES');
            
            // Получаем имя базы данных livemachines из конфига
            $dbName = config('database.connections.livemachines.database');
            $key = "Tables_in_{$dbName}";
            
            // Удаляем все таблицы
            foreach ($tables as $table) {
                $tableName = $table->$key;
                $connection->statement("DROP TABLE IF EXISTS `{$tableName}`");
            }
            
            // Включаем обратно проверку ключей
            $connection->statement('SET FOREIGN_KEY_CHECKS=1');
            
            // Импортируем дамп
            $sql = File::get($dumpPath);
            $connection->unprepared($sql);
            
            return redirect($previousUrl)->with('success', 'База данных livemachines успешно сброшена!');
            
        } catch (\Exception $e) {
            // В случае ошибки пытаемся включить проверку ключей
            if (isset($connection)) {
                $connection->statement('SET FOREIGN_KEY_CHECKS=1');
            }
            return redirect($previousUrl)->with('error', 'Ошибка: ' . $e->getMessage());
        }
    });

});

// Fallback - для несуществующих маршрутов (показывает 404 без редиректа)
Route::fallback(function () {
    abort(404);
});