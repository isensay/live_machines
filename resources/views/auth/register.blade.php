@extends('layouts.lite')

{{-- Auth text --}}
@section('auth_text')
Don't have an account? Create your own account, it takes less than a minute
@endsection

{{-- Page Content --}}
@section('page_content')

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6 col-xl-4">
        <div class="card">

            <div class="card-body p-4">

                {{-- LOGO --}}
                @include('auth.logo')

                <form method="POST" action="{{ route('register') }}">

                    {{--
                    <div class="mb-2">
                        <x-input-label for="first_name" :value="__('Имя')" />
                        <x-text-input id="first_name" class="form-control" type="text" name="first_name" :value="old('first_name')" required autofocus autocomplete="given-name" />
                        <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                    </div>
                    --}}
                    <!-- First Name -->
                    <div class="mb-3">
                        <label for="first_name" class="form-label">Full Name</label>
                        <input class="form-control" type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" placeholder="Enter your name" required autofocus autocomplete="given-name" />
                        @error('first_name')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    {{--
                    <div class="mb-2">
                        <x-input-label for="last_name" :value="__('Фамилия')" />
                        <x-text-input id="last_name" class="form-control" type="text" name="last_name" :value="old('last_name')" required autocomplete="family-name" />
                        <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                    </div>
                    --}}
                    <!-- Last Name -->
                    <div class="mb-3">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input class="form-control" type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" placeholder="Enter your last name" required autocomplete="family-name" />
                        @error('last_name')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    {{--
                    <div class="mb-2">
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input id="email" class="form-control" type="email" name="email" :value="old('email')" required autocomplete="username" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>
                    --}}
                    <!-- Email Address -->
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input class="form-control @error('email') is-invalid @enderror" type="email" id="email" name="email" value="{{ old('email') }}" required placeholder="Enter your email">
                        @error('email')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    {{--
                    <div class="mb-3">
                        <x-input-label for="password" :value="__('Пароль')" />

                        <x-text-input id="password" class="form-control"
                                        type="password"
                                        name="password"
                                        required autocomplete="new-password" />

                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>
                    --}}
                    <!-- Password -->
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password">
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword1">
                                <i class="mdi mdi-eye" id="passwordIcon1"></i>
                            </button>
                            @error('password')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    {{--
                    <div class="mb-4">
                        <x-input-label for="password_confirmation" :value="__('Подтвердите пароль')" />

                        <x-text-input id="password_confirmation" class="form-control"
                                        type="password"
                                        name="password_confirmation" required autocomplete="new-password" />

                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                    </div>
                    --}}
                    <!-- Confirm Password -->
                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label">Password confirmation</label>
                        <div class="input-group">
                            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" placeholder="Confirm your password">
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword2">
                                <i class="mdi mdi-eye" id="passwordIcon2"></i>
                            </button>
                            @error('password_confirmation')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="d-grid text-center">
                        <button class="btn btn-primary" type="submit"> Sign Up </button>
                    </div>

                    {{--
                    <div class="flex items-center justify-end mt-4">
                        <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                            {{ __('Уже зарегистрированы?') }}
                        </a>

                        <x-primary-button class="ms-4">
                            {{ __('Зарегистрироваться') }}
                        </x-primary-button>
                    </div>
                    --}}

                    {{--
                    <div class="mb-2">
                        <label for="fullname" class="form-label">Full Name</label>
                        <input class="form-control" type="text" id="fullname" placeholder="Enter your name" required>
                    </div>
                    <div class="mb-2">
                        <label for="emailaddress" class="form-label">Email address</label>
                        <input class="form-control" type="email" id="emailaddress" required placeholder="Enter your email">
                    </div>
                    <div class="mb-2">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group input-group-merge">
                            <input type="password" id="password" class="form-control" placeholder="Enter your password">

                            <div class="input-group-text" data-password="false">
                                <span class="password-eye"></span>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="checkbox-signup">
                            <label class="form-check-label" for="checkbox-signup">
                                I accept <a href="javascript: void(0);" class="text-dark">Terms and Conditions</a>
                            </label>
                        </div>
                            
                    </div>
                    <div class="d-grid text-center">
                        <button class="btn btn-primary" type="submit"> Sign Up </button>
                    </div>
                    --}}

                </form>
                
            </div> <!-- end card-body -->
        </div>
        <!-- end card -->

        <div class="row mt-3">
            <div class="col-12 text-center">
                <p class="text-muted">Already have account? <a href="{{ route('login') }}" class="text-primary fw--medium ms-1">Sign In</a></p>
            </div> <!-- end col -->
        </div>
        <!-- end row -->

    </div> <!-- end col -->
</div>
<!-- end row -->

@endsection

@section('page_more_java_script')

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    document.addEventListener('click', function(e) {
        if (e.target.closest('#togglePassword1')) {
            togglePassword('password', 'passwordIcon1');
        }
        if (e.target.closest('#togglePassword2')) {
            togglePassword('password_confirmation', 'passwordIcon2');
        }
    });
    
    function togglePassword(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (input && icon) {
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            icon.classList.toggle('mdi-eye');
            icon.classList.toggle('mdi-eye-off');
        }
    }
});
</script>

@endsection
