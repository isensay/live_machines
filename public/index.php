<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define("LARAVEL_START", microtime(true));

if (file_exists($maintenance = __DIR__."/../storage/framework/maintenance.php")) {
    require $maintenance;
}

// СНАЧАЛА загружаем autoloader
require __DIR__."/../vendor/autoload.php";

// ПОТОМ загружаем .env (теперь Dotenv доступен)
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/../");
    $dotenv->load();
    // Для отладки
    $_ENV = array_merge($_ENV, $_SERVER);
} catch (Exception $e) {
    // Логируем ошибку но продолжаем
    error_log("Dotenv load warning: " . $e->getMessage());
}

/** @var Application $app */
$app = require_once __DIR__."/../bootstrap/app.php";

$app->handleRequest(Request::capture());