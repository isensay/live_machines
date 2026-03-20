<?php

/**
 * Маршруты для /livemachines/
 */

use App\Http\Controllers\Livemachines\TechController;    // Технические характеристики
use App\Http\Controllers\Livemachines\CompController;    // Комплектации
use App\Http\Controllers\Livemachines\CountryController; // Страны
use App\Http\Controllers\Livemachines\SpravController;   // ВРЕМЕННЫЙ КОНТРОЛЛЕР

// Запись в БД livemachines данных из json-файлов (не активно)
//Route::get("/json", [JsonController::class, "index"])->name("json");

Route::middleware(['auth', 'verified'])->group(function () {

    // Все маршруты справочников livemachines (для меню слева)
    Route::prefix('livemachines/sprav')->name('lm_')->group(function () {
        // Базовые справочники
        Route::get('/manuf',       [SpravController::class,   'manuf_list'])->name('manuf_list');
        Route::get('/model',       [SpravController::class,   'model_list'])->name('model_list');
        Route::get('/group',       [SpravController::class,   'group_list'])->name('group_list');
    });

    // Технические характеристики
    Route::prefix('livemachines/sprav')->name('lm_tech_')->group(function () {
        Route::prefix('tech')->group(function () {
            Route::get('/',              [TechController::class, 'list'])->name('list');
            Route::get('/data',          [TechController::class, 'data_ajax'])->name('data');
            Route::get('/references',    [TechController::class, 'get_references'])->name('references');
            Route::get('/create',        [TechController::class, 'edit_data'])->name('create_data');
            Route::get('/edit/{id}',     [TechController::class, 'edit_data'])->name('edit_data')->whereNumber('id');
            Route::post('/create',       [TechController::class, 'create'])->name('create');
            Route::post('/update/{id}',  [TechController::class, 'update'])->name('update')->whereNumber('id');
            Route::post('/group/create', [TechController::class, 'group_create'])->name('group_create');
            Route::delete('/{id}',       [TechController::class, 'destroy'])->name('destroy')->whereNumber('id');
        });
    });

    // Комплектации
    Route::prefix('livemachines/sprav')->name('lm_comp_')->group(function () {
        Route::prefix('comp')->group(function () {
            Route::get('/',              [CompController::class, 'list'])->name('list');
            Route::get('/data',          [CompController::class, 'data_ajax'])->name('data');
            Route::get('/references',    [CompController::class, 'get_references'])->name('references');
            Route::get('/create',        [CompController::class, 'edit_data'])->name('create_data');
            Route::get('/edit/{id}',     [CompController::class, 'edit_data'])->name('edit_data')->whereNumber('id');
            Route::post('/create',       [CompController::class, 'create'])->name('create');
            Route::post('/update/{id}',  [CompController::class, 'update'])->name('update')->whereNumber('id');
            Route::post('/group/create', [CompController::class, 'group_create'])->name('group_create');
            Route::delete('/{id}',       [CompController::class, 'destroy'])->name('destroy')->whereNumber('id');
        });
    });

    // Страны
    Route::prefix('livemachines/sprav')->name('lm_country_')->group(function () {
        Route::get('/country',     [CountryController::class, 'index'])->name('index');
        Route::get('/country_old', [SpravController::class,   'country_list'])->name('index_old');
    });

    // Откат базы данных livemachines
    Route::get('/livemachines/reset-database', function () {
        // Проверяем, AJAX ли это запрос
        $isAjax = request()->ajax() || request()->wantsJson();
        
        // Проверяем подтверждение
        if (!request()->has('confirmed') || request()->confirmed !== 'yes') {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'error' => 'Операция не подтверждена'
                ]);
            }
            return redirect()->back()->with('error', 'Операция отменена');
        }
        
        $previousUrl = url()->previous();
        $dumpPath = storage_path('../livemachines_dump.sql');
        
        if (!File::exists($dumpPath)) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'error' => 'Файл дампа не найден: ' . $dumpPath
                ]);
            }
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
            
            // Импортируем дамп
            $sql = File::get($dumpPath);
            $connection->unprepared($sql);
            
            // Включаем обратно проверку ключей
            $connection->statement('SET FOREIGN_KEY_CHECKS=1');
            
            // Возвращаем ответ в зависимости от типа запроса
            if ($isAjax) {
                return response()->json([
                    'success' => true,
                    'message' => 'База данных livemachines успешно сброшена!',
                    'redirect' => $previousUrl
                ]);
            }
            
            return redirect($previousUrl)->with('success', 'База данных livemachines успешно сброшена!');
            
        } catch (\Exception $e) {
            // В случае ошибки пытаемся включить проверку ключей
            if (isset($connection)) {
                $connection->statement('SET FOREIGN_KEY_CHECKS=1');
            }
            
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
            
            return redirect($previousUrl)->with('error', 'Ошибка: ' . $e->getMessage());
        }
    });

});