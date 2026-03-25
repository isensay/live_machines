<?php

/**
 * Маршруты для /livemachines/
 */

use App\Http\Controllers\Livemachines\GroupController;   // Группы
use App\Http\Controllers\Livemachines\UnitController;    // Единицы измерения
use App\Http\Controllers\Livemachines\ValueController;   // Значения
use App\Http\Controllers\Livemachines\TechController;    // Технические характеристики
use App\Http\Controllers\Livemachines\CompController;    // Комплектации
use App\Http\Controllers\Livemachines\ModelController;   // Модели
use App\Http\Controllers\Livemachines\ManufController;   // Производители
use App\Http\Controllers\Livemachines\CountryController; // Страны
use App\Http\Controllers\Livemachines\SpravController;   // ВРЕМЕННЫЙ КОНТРОЛЛЕР

// Запись в БД livemachines данных из json-файлов (не активно)
//Route::get("/json", [JsonController::class, "index"])->name("json");

Route::middleware(['auth', 'verified'])->group(function () {

    // Все маршруты справочников livemachines (для меню слева)
    //Route::prefix('livemachines/sprav')->name('lm_')->group(function () {
        // Базовые справочники
        //Route::get('/manuf',       [SpravController::class,   'manuf_list'])->name('manuf_list');
    //});

    // Технические характеристики
    Route::prefix('livemachines/sprav')->name('lm_tech_')->group(function () {
        Route::prefix('tech')->group(function () {
            // Основная страница
            Route::get('/', [TechController::class, 'index'])->name('index'); 

            // Ajax - маршруты
            Route::middleware('ajax')->group(function () {
                Route::get('/data',          [TechController::class, 'data'])->name('data');             // Получение списка технических характеристик
                Route::get('/references',    [TechController::class, 'references'])->name('references'); // Получение справочников для формы создания/редактирования
                Route::get('/create',        [TechController::class, 'edit'])->name('create');           // Создание технической характеристики (загрузка информации в окно)
                Route::post('/create',       [TechController::class, 'create'])->name('create');         // Создание технической характеристики (сохранение)

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
                Route::get('/data',          [CompController::class, 'data'])->name('data');             // Получение списка комплектаций
                Route::get('/references',    [CompController::class, 'references'])->name('references'); // Получение справочников для формы создания/редактирования
                Route::get('/create',        [CompController::class, 'edit'])->name('create');           // Создание комплектации (загрузка информации в окно)
                Route::post('/create',       [CompController::class, 'create'])->name('create');         // Создание комплектации (сохранение)

                // Маршруты с ID
                Route::whereNumber('id')->group(function () {
                    Route::get('/edit/{id}',     [CompController::class, 'edit'])->name('edit');      // Редактирование (загрузка информации в окно)
                    Route::post('/update/{id}',  [CompController::class, 'update'])->name('update');  // Редактирование (сохранение)
                    Route::delete('/{id}',       [CompController::class, 'remove'])->name('remove');  // Удаление комплектации
                });
            });
        });
    });

    // Группы
    Route::prefix('livemachines/sprav')->name('lm_group_')->group(function () {
        Route::prefix('group')->group(function () {
            // Основная страница
            Route::get('/', [GroupController::class, 'index'])->name('index');

            // Ajax - маршруты
            Route::middleware('ajax')->group(function () {
                Route::get('/data',    [GroupController::class, 'data'])->name('data');     // Получение списка
                Route::post('/create', [GroupController::class, 'create'])->name('create'); // Создание записи (сохранение)

                // Маршруты с ID
                Route::whereNumber('id')->group(function () {
                    Route::get('/edit/{id}',    [GroupController::class, 'edit'])->name('edit');     // Получение данных для редактирования (загрузка информации в окно)
                    Route::post('/update/{id}', [GroupController::class, 'update'])->name('update'); // Сохранение изменений имеющейся записи (сохранение)
                    Route::delete('/{id}',      [GroupController::class, 'remove'])->name('remove'); // Удаление записи
                });
            });
        });
    });

    // Единицы измерения
    Route::prefix('livemachines/sprav')->name('lm_unit_')->group(function () {
        Route::prefix('unit')->group(function () {
            // Основная страница
            Route::get('/', [UnitController::class, 'index'])->name('index');

            // Ajax - маршруты
            Route::middleware('ajax')->group(function () {
                Route::get('/data',    [UnitController::class, 'data'])->name('data');     // Получение списка
                Route::post('/create', [UnitController::class, 'create'])->name('create'); // Создание записи (сохранение)

                // Маршруты с ID
                Route::whereNumber('id')->group(function () {
                    Route::get('/edit/{id}',    [UnitController::class, 'edit'])->name('edit');     // Получение данных для редактирования (загрузка информации в окно)
                    Route::post('/update/{id}', [UnitController::class, 'update'])->name('update'); // Сохранение изменений имеющейся записи (сохранение)
                    Route::delete('/{id}',      [UnitController::class, 'remove'])->name('remove'); // Удаление записи
                });
            });
        });
    });

    // Значения
    Route::prefix('livemachines/sprav')->name('lm_value_')->group(function () {
        Route::prefix('value')->group(function () {
            // Основная страница
            Route::get('/', [ValueController::class, 'index'])->name('index');

            // Ajax - маршруты
            Route::middleware('ajax')->group(function () {
                Route::get('/data',    [ValueController::class, 'data'])->name('data');     // Получение списка
                Route::post('/create', [ValueController::class, 'create'])->name('create'); // Создание записи (сохранение)

                // Маршруты с ID
                Route::whereNumber('id')->group(function () {
                    Route::get('/edit/{id}',    [ValueController::class, 'edit'])->name('edit');     // Получение данных для редактирования (загрузка информации в окно)
                    Route::post('/update/{id}', [ValueController::class, 'update'])->name('update'); // Сохранение изменений имеющейся записи (сохранение)
                    Route::delete('/{id}',      [ValueController::class, 'remove'])->name('remove'); // Удаление записи
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

    // Производители
    Route::prefix('livemachines/sprav')->name('lm_manuf_')->group(function () {
        Route::prefix('manuf')->group(function () {
            // Основная страница
            Route::get('/', [ManufController::class, 'index'])->name('index');

            // Ajax - маршруты
            Route::middleware('ajax')->group(function () {
                Route::get('/data',    [ManufController::class, 'data'])->name('data');     // Получение списка
                Route::post('/create', [ManufController::class, 'create'])->name('create'); // Создание записи (сохранение)

                // Маршруты с ID
                Route::whereNumber('id')->group(function () {
                    Route::get('/edit/{id}',    [ManufController::class, 'edit'])->name('edit');     // Получение данных для редактирования (загрузка информации в окно)
                    Route::post('/update/{id}', [ManufController::class, 'update'])->name('update'); // Сохранение изменений имеющейся записи (сохранение)
                    Route::delete('/{id}',      [ManufController::class, 'remove'])->name('remove'); // Удаление записи
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