<?php

/**
 * Маршруты для системных страниц
 */

Route::middleware(['auth', 'verified'])->group(function () {

    // Страницы ошибок: 401, 403, 404, 419 и 500
    Route::get("/error{code}", function ($code) {
        $allowedCodes = [401, 403, 404, 419, 429, 500, 503];
        if (!in_array($code, $allowedCodes)) {
            abort(404);
        }
        abort($code);
    })->where("code", "[0-9]+")->name("errors_401_403_404_419_500");

    // Страница Maintenance
    Route::get("/maintenance", function () { return view("errors.maintenance"); })->name("maintenance");
        
    // Страница Comming Soon
    Route::get("/comming_soon", function () { return view("errors.comming_soon"); })->name("comming_soon");

});