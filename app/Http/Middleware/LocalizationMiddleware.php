<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LocalizationMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Получаем язык с правильными приоритетами
        $locale = $this->getLocale($request);
        
        // Устанавливаем язык приложения
        App::setLocale($locale);
        
        return $next($request);
    }
    
    /**
     * Определяем язык с приоритетами
     */
    protected function getLocale(Request $request): string
    {
        // 1. Проверяем, есть ли явный выбор пользователя
        if (Session::has('user_locale') && Session::get('user_locale') !== null) {
            $userLocale = Session::get('user_locale');
            if ($this->isLocaleSupported($userLocale)) {
                return $userLocale;
            }
        }
        
        // 2. Используем язык браузера
        $browserLocale = $this->getBrowserLocale($request);
        if ($browserLocale) {
            return $browserLocale;
        }
        
        // 3. Fallback на язык по умолчанию из конфига
        return config('app.locale', 'en');
    }
    
    /**
     * Получаем язык браузера из заголовка Accept-Language
     */
    protected function getBrowserLocale(Request $request): ?string
    {
        // Используем напрямую из SERVER, как в вашем dd()
        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? $request->header('Accept-Language');
        
        if (!$acceptLanguage) {
            return null;
        }
        
        // Разбираем заголовок
        $languages = explode(',', $acceptLanguage);
        
        foreach ($languages as $language) {
            // Убираем вес качества (q=0.9) и пробелы
            $lang = trim(explode(';', $language)[0]);
            
            // Берем только основную часть (ru-RU -> ru)
            $primaryLang = explode('-', $lang)[0];
            
            if ($this->isLocaleSupported($primaryLang)) {
                return $primaryLang;
            }
        }
        
        return null;
    }
    
    /**
     * Проверяем поддерживается ли язык
     */
    protected function isLocaleSupported(string $locale): bool
    {
        return in_array($locale, $this->getSupportedLocales());
    }
    
    /**
     * Список поддерживаемых языков
     */
    protected function getSupportedLocales(): array
    {
        return ['en', 'ru'];
    }
}