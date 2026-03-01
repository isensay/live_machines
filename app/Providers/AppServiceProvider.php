<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\UrlGenerator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Отключение кэширования роутов в development
        /*if ($this->app->environment('local')) {
            $this->app->singleton('router', function ($app) {
                return new \Illuminate\Routing\Router($app['events'], $app);
            });
        }*/
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Принудительная очистка кэша роутов при каждом запросе в development
        /*if ($this->app->environment('local')) {
            $this->app['router']->getRoutes()->refreshNameLookups();
            $this->app['router']->getRoutes()->refreshActionLookups();
        }*/
    }
}