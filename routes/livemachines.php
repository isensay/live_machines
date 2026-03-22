<?php

/**
 * Маршруты для /livemachines/
 */

use App\Http\Controllers\Livemachines\TechController;    // Технические характеристики
use App\Http\Controllers\Livemachines\CompController;    // Комплектации
use App\Http\Controllers\Livemachines\ModelController; // Страны
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
            // Основная страница
            Route::get('/', [TechController::class, 'index'])->name('index'); 

            // Ajax - маршруты
            Route::middleware('ajax')->group(function () {
                Route::get('/data',          [TechController::class, 'data'])->name('data');                 // Получение списка технических характеристик
                Route::get('/references',    [TechController::class, 'references'])->name('references');     // Получение справочников для формы создания/редактирования
                Route::get('/create',        [TechController::class, 'edit'])->name('create');               // Создание технической характеристики (загрузка информации в окно)
                Route::post('/create',       [TechController::class, 'create'])->name('create');             // Создание технической характеристики (сохранение)
                Route::post('/create/group', [TechController::class, 'create_group'])->name('create_group'); // Создание группы

                // Маршруты с ID
                Route::whereNumber('id')->group(function () {
                    Route::get('/edit/{id}',     [TechController::class, 'edit'])->name('edit');      // Редактирование (загрузка информации в окно)
                    Route::post('/update/{id}',  [TechController::class, 'update'])->name('update');  // Редактирование (сохранение)
                    Route::delete('/{id}',       [TechController::class, 'remove'])->name('remove');  // Удаление технической характеристики
                });
            });
        });
    });

    // Комплектации
    Route::prefix('livemachines/sprav')->name('lm_comp_')->group(function () {
        Route::prefix('comp')->group(function () {
            // Основная страница
            Route::get('/', [CompController::class, 'index'])->name('index'); 

            // Ajax - маршруты
            Route::middleware('ajax')->group(function () {
                Route::get('/data',          [CompController::class, 'data'])->name('data');                 // Получение списка комплектаций
                Route::get('/references',    [CompController::class, 'references'])->name('references');     // Получение справочников для формы создания/редактирования
                Route::get('/create',        [CompController::class, 'edit'])->name('create');               // Создание комплектации (загрузка информации в окно)
                Route::post('/create',       [CompController::class, 'create'])->name('create');             // Создание комплектации (сохранение)
                Route::post('/create/group', [CompController::class, 'create_group'])->name('create_group'); // Создание комплектации

                // Маршруты с ID
                Route::whereNumber('id')->group(function () {
                    Route::get('/edit/{id}',     [CompController::class, 'edit'])->name('edit');      // Редактирование (загрузка информации в окно)
                    Route::post('/update/{id}',  [CompController::class, 'update'])->name('update');  // Редактирование (сохранение)
                    Route::delete('/{id}',       [CompController::class, 'remove'])->name('remove');  // Удаление комплектации
                });
            });
        });
    });

    //  Модели
    Route::prefix('livemachines/sprav')->name('lm_model_')->group(function () {
        Route::prefix('model')->group(function () {
            // Основная страница
            Route::get('/', [ModelController::class, 'index'])->name('index');

            // Ajax - маршруты
            Route::middleware('ajax')->group(function () {
                Route::get('/data',    [ModelController::class, 'data'])->name('data');     // Получение списка моделей
                Route::post('/create', [ModelController::class, 'create'])->name('create'); // Создание модели (сохранение)

                // Маршруты с ID
                Route::whereNumber('id')->group(function () {
                    Route::get('/edit/{id}',    [ModelController::class, 'edit'])->name('edit');       // Редактирование модели (загрузка информации в окно)
                    Route::post('/update/{id}', [ModelController::class, 'update'])->name('update');   // Редактирование модели (сохранение)
                    Route::delete('/{id}',      [ModelController::class, 'remove'])->name('remove'); // Удаление модели
                });
            });
        });
    });

    // Страны
    Route::prefix('livemachines/sprav')->name('lm_country_')->group(function () {
        Route::prefix('country')->group(function () {
            // Основная страница
            Route::get('/', [CountryController::class, 'index'])->name('index');

            // Ajax - маршруты
            Route::middleware('ajax')->group(function () {
                Route::get('/data',    [CountryController::class, 'data'])->name('data');     // Получение списка стран
                Route::post('/create', [CountryController::class, 'create'])->name('create'); // Создание страны (сохранение)

                // Маршруты с ID
                Route::whereNumber('id')->group(function () {
                    Route::get('/edit/{id}',    [CountryController::class, 'edit'])->name('edit');     // Редактирование страны (загрузка информации в окно)
                    Route::post('/update/{id}', [CountryController::class, 'update'])->name('update'); // Редактирование страны (сохранение)
                    Route::delete('/{id}',      [CountryController::class, 'remove'])->name('remove'); // Удаление страны
                });
            });
        });
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