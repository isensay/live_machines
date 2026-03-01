@extends('layouts.lite')

{{-- Auth text --}}
@section('auth_text')
Enter your email address and we'll send you an email with instructions to reset your password.
@endsection

{{-- Page Content --}}
@section('page_content')

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6 col-xl-4">
        <div class="card">

            <div class="card-body p-4">

                {{-- LOGO --}}
                @include('auth.logo')

                <form method="POST" action="{{ route('password.store') }}">

                    @csrf

                    <!-- Password Reset Token -->
                    <input type="hidden" name="token" value="{{ $request->route('token') }}">

                    <!-- Email Address -->
                    <div class="mb-3">
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input id="email" class="form-control" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <!-- Password -->
                    <div class="mb-3">
                        <x-input-label for="password" :value="__('Password')" />
                        <x-text-input id="password" class="form-control" type="password" name="password" required autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <!-- Confirm Password -->
                    <div class="mb-3">
                        <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

                        <x-text-input id="password_confirmation" class="form-control"
                                            type="password"
                                            name="password_confirmation" required autocomplete="new-password" />

                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                    </div>

                    <div class="d-grid text-center">
                        <x-primary-button class="btn btn-primary">
                            {{ __('Reset Password') }}
                        </x-primary-button>
                    </div>
                </form>

            </div> <!-- end card-body -->
        </div>
        <!-- end card -->

        <div class="row mt-3">
            <div class="col-12 text-center">
                <p class="text-muted">Back to <a href="{{ route('login') }}" class="text-primary fw-medium ms-1">Log in</a></p>
            </div> <!-- end col -->
        </div>
        <!-- end row -->

    </div> <!-- end col -->
</div>
<!-- end row -->

@endsection