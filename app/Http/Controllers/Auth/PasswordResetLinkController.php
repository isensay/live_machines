<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        $result = session('reset_link_sent', false);

        if ($result)
        {
            return view('auth.forgot-password-sent');
        }

        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Сначала проверяем капчу отдельно
        if (!captcha_check($request->captcha)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['captcha' => 'Не верный код'])
                ->with('captcha_error', true);
        }

        // Затем проверяем email
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status == Password::RESET_LINK_SENT) {
            return redirect()->route('password.request')
                           ->with('reset_link_sent', true)
                           ->with('email', $request->email)
                           ->with('status', __($status));
        } else {
            return back()->withInput($request->only('email'))
                        ->withErrors(['email' => __($status)]);
        }
    }

    /**
     * Refresh CAPTCHA
     */
    public function refreshCaptcha()
    {
        $captchaUrl = captcha_src('math') . '&refresh=' . uniqid();
        return response()->json(['captcha_url' => $captchaUrl]);
    }
}