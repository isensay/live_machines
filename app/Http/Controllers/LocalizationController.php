<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Session;

class LocalizationController extends Controller
{
    /**
     * Смена языка пользователем
     */
    public function setLang($locale)
    {
        // Проверяем поддержку языка
        if (!$this->isLocaleSupported($locale)) {
            return redirect()->back()->with('error', __('Language not supported'));
        }
        
        // Сохраняем выбор пользователя в сессию
        Session::put('user_locale', $locale);
        
        return redirect()->back()->with('success', __('Language changed to :locale', ['locale' => $this->getLocaleName($locale)]));
    }
    
    /**
     * Сброс выбора языка - удаляем из Redis напрямую
     */
    public function resetLang(Request $request)
    {
        try {
            $sessionId = Session::getId();
            $redisKey = "laravel:{$sessionId}";
            
            // Получаем текущие данные сессии из Redis
            $sessionData = Redis::get($redisKey);
            
            if ($sessionData) {
                // Декодируем данные сессии
                $data = unserialize($sessionData);
                
                // Удаляем user_locale из данных сессии
                if (isset($data['user_locale'])) {
                    unset($data['user_locale']);
                    
                    // Сохраняем обновленные данные обратно в Redis
                    Redis::set($redisKey, serialize($data));
                }
            }
            
            // Также очищаем в Laravel сессии
            Session::forget('user_locale');
            
            // Определяем язык браузера
            $browserLocale = $this->getBrowserLocale($request);
            $localeName = $this->getLocaleName($browserLocale);
            
            return redirect()->back()->with('success', __('Language reset to browser default: :locale', ['locale' => $localeName]));
            
        } catch (\Exception $e) {
            \Log::error('Failed to reset locale: ' . $e->getMessage());
            return redirect()->back()->with('error', __('Failed to reset language'));
        }
    }
    
    /**
     * Полный сброс - удаляем всю сессию из Redis
     */
    public function hardResetLang(Request $request)
    {
        try {
            $sessionId = Session::getId();
            $redisKey = "laravel:{$sessionId}";
            
            // Полностью удаляем сессию из Redis
            Redis::del($redisKey);
            
            // Регенерируем сессию в Laravel
            Session::flush();
            Session::regenerate();
            
            $browserLocale = $this->getBrowserLocale($request);
            $localeName = $this->getLocaleName($browserLocale);
            
            return redirect()->back()->with('success', __('Language completely reset to: :locale', ['locale' => $localeName]));
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to reset language'));
        }
    }
    
    /**
     * Получаем язык браузера
     */
    protected function getBrowserLocale(Request $request): string
    {
        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? $request->header('Accept-Language');
        
        if ($acceptLanguage) {
            $languages = explode(',', $acceptLanguage);
            
            foreach ($languages as $language) {
                $lang = trim(explode(';', $language)[0]);
                $primaryLang = explode('-', $lang)[0];
                
                if ($this->isLocaleSupported($primaryLang)) {
                    return $primaryLang;
                }
            }
        }
        
        return config('app.locale', 'en');
    }
    
    /**
     * Проверка поддержки языка
     */
    protected function isLocaleSupported(string $locale): bool
    {
        return in_array($locale, ['en', 'ru']);
    }
    
    /**
     * Названия языков для отображения
     */
    protected function getLocaleName(string $locale): string
    {
        $names = [
            'en' => 'English',
            'ru' => 'Русский', 
        ];
        
        return $names[$locale] ?? $locale;
    }
}