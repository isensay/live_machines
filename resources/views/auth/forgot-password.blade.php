@extends('layouts.lite')

{{-- Page Content --}}
@section('page_content')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6 col-xl-4">
        <div class="card">
            <div class="card-body p-4">
                
                {{-- LOGO --}}
                @include('auth.logo')

                <x-auth-session-status class="mb-4 text-danger" :status="session('status')" />

                <form method="POST" action="{{ route('password.email') }}" id="passwordForm">
                    @csrf

                    <div class="mb-3">
                        <label for="emailaddress" class="form-label">Email address</label>
                        <input class="form-control" type="email" name="email" value="{{ old('email') }}" required placeholder="Enter your email" autofocus />
                        @error('email')
                            <span class="mt-2 text-primary">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- CAPTCHA Section -->
                    <div class="mb-3">
                        <label class="form-label">Security Question</label>
                        <div class="row g-2 align-items-stretch">
                            <div class="col-5">
                                <div class="captcha-image zh-100 zd-flex zalign-items-center justify-content-left zbg-light zrounded zborder">
                                    <img src="{{ captcha_src('math') }}" alt="CAPTCHA" id="captchaImg" class="img-fluid">
                                </div>
                            </div>
                            <div class="col-5">
                                <div class="h-100 d-flex flex-column justify-content-center">
                                    <input class="form-control" type="text" name="captcha" required placeholder="Enter answer" value="{{ old('captcha') }}" />
                                    
                                    <!-- Отображение ошибки капчи -->
                                    @if($errors->has('captcha') || session('captcha_error'))
                                        <div class="text-danger small mt-1">
                                            <i class="mdi mdi-alert-circle"></i> Не верный код
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="mt-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="refreshCaptchaBtn">
                                <i class="mdi mdi-refresh"></i> New Question
                            </button>
                        </div>
                    </div>

                    <div class="d-grid text-center">
                        <button class="btn btn-primary" type="submit">Reset Password</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-12 text-center">
                <p class="text-muted">Back to <a href="{{ route('login') }}" class="text-primary fw-medium ms-1">Sign In</a></p>
            </div> <!-- end col -->
        </div>
        <!-- end row -->
    </div>
</div>
@endsection

@section('page_more_java_script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Восстанавливаем email из localStorage при загрузке
    const emailInput = document.querySelector('input[name="email"]');
    const savedEmail = localStorage.getItem('password_reset_email');
    if (emailInput && savedEmail && !emailInput.value) {
        emailInput.value = savedEmail;
    }
    
    // Сохраняем email при вводе
    if (emailInput) {
        emailInput.addEventListener('input', function() {
            localStorage.setItem('password_reset_email', this.value);
        });
    }
    
    // Находим кнопку и добавляем обработчик
    const refreshBtn = document.getElementById('refreshCaptchaBtn');
    
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            refreshCaptcha();
        });
    }
    
    // Функция обновления капчи
    function refreshCaptcha() {
        const refreshBtn = document.getElementById('refreshCaptchaBtn');
        const originalHtml = refreshBtn.innerHTML;
        
        refreshBtn.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> Loading...';
        refreshBtn.disabled = true;
        
        fetch('{{ route("captcha.refresh") }}')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network error');
                }
                return response.json();
            })
            .then(data => {
                // Обновляем src изображения
                const captchaImg = document.getElementById('captchaImg');
                if (captchaImg) {
                    captchaImg.src = data.captcha_url + '&t=' + new Date().getTime();
                }
                // Очищаем поле ввода капчи
                document.querySelector('input[name="captcha"]').value = '';
                
                // Убираем сообщение об ошибке если было
                const errorDiv = document.querySelector('.text-danger small');
                if (errorDiv) {
                    errorDiv.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error refreshing captcha:', error);
                window.location.reload();
            })
            .finally(() => {
                refreshBtn.innerHTML = originalHtml;
                refreshBtn.disabled = false;
            });
    }
    
    // Авто-фокус на поле капчи при ошибке
    const captchaError = document.querySelector('.text-danger small');
    if (captchaError) {
        document.querySelector('input[name="captcha"]').focus();
    }
    
    // Очищаем localStorage при успешной отправке формы
    document.getElementById('passwordForm').addEventListener('submit', function() {
        localStorage.removeItem('password_reset_email');
    });
});
</script>
@endsection