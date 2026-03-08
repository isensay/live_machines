<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Cache;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            // Валидация полей - может выбросить ValidationException
            $validated = $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required'],
            ]);

            // Попытка аутентификации
            if (Auth::attempt($validated, $request->boolean('remember'))) {
                $request->session()->regenerate();
                $this->resetLoginAttempts($request); // Сбрасываем счетчик неудачных попыток при успешном входе
                return redirect()->intended(route('home', absolute: false));
            }

            // Увеличиваем счетчик при неудачной аутентификации
            $this->incrementLoginAttempts($request);

            // Если аутентификация не удалась - возвращаем назад с ошибкой
            return back()
                ->withErrors(['email' => __('auth.failed')])
                ->withInput($request->only('email', 'remember'));

        } catch (ValidationException $e) {
            // Перехватываем ValidationException и возвращаем на форму с ошибками
            return back()
                ->withErrors($e->errors())
                ->withInput($request->only('email', 'remember'));
        }
    }

    public function destroy(Request $request): View //RedirectResponse
    {
        // Сохраняем локаль
        $currentLocale = app()->getLocale();
        
        Auth::guard('web')->logout();

        // Очищаем только аутентификационные данные, сохраняя локаль
        $request->session()->forget('_token');
        $request->session()->regenerateToken();

        // Восстанавливаем локаль
        session()->put('locale', $currentLocale);
        app()->setLocale($currentLocale);

        //return redirect('/');
        //return redirect()->intended(route('logout', absolute: false));
        return view('auth.logout');
    }

    protected function incrementLoginAttempts(Request $request): void
    {
        $key = 'login_attempts:' . $request->ip();
        $attempts = Cache::get($key, 0) + 1;
        Cache::put($key, $attempts, now()->addHours(24));
    }

    /**
     * Сброс счетчика неудачных попыток входа
     */
    protected function resetLoginAttempts(Request $request): void
    {
        $key = 'login_attempts:' . $request->ip();
        Cache::forget($key);
        Cache::forget($key . ':blocked');
    }
}