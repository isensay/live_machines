<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LocalizationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JsonController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

// Маршруты аутентификации (доступны без авторизации)
require __DIR__.'/auth.php';

// Маршруты для /livemachines/
require __DIR__.'/livemachines.php';

// Тестовые маршруты
require __DIR__.'/test.php';

// Маршруты для системных страниц
require __DIR__.'/system_pages.php';

// Основные маршруты
Route::middleware(['auth', 'verified'])->group(function () {

    // Главная страница
    Route::get("/", [HomeController::class, "index"])->name("home");
    
    // Язык
    Route::get("/locale/{locale}", [LocalizationController::class, "setLang"])->name("locale.switch");  // Смена языка
    Route::get("/locale_reset",    [LocalizationController::class, "resetLang"])->name("locale.reset"); // Сброс к языку браузера
    
    // Профиль
    //Route::get("/profile", function () {
    //    return view("profile");
    //})->name("profile");
    //Route::get("/profile_new",    [ProfileController::class, "edit"])->name("profile.edit");
    //Route::patch("/profile_new",  [ProfileController::class, "update"])->name("profile.update");
    //Route::delete("/profile_new", [ProfileController::class, "destroy"])->name("profile.destroy");

});

// Fallback - для несуществующих маршрутов (показывает 404 без редиректа)
Route::fallback(function () {
    abort(404);
});