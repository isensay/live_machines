@extends('layouts.lite')

{{-- Auth text --}}
@section('auth_text')
{{ __('auth.login.page_text') }}
@endsection

{{-- Page Content --}}
@section('page_content')

    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 col-xl-4">
            <div class="card">

                <div class="card-body p-4">
                    
                    {{-- LOGO --}}
                    @include('auth.logo')

                    <form action="{{ route('login') }}" method="post">

                        @csrf

                        <div class="mb-2">
                            <label for="emailaddress" class="form-label"> {{ __('auth.login.email.title') }} </label>
                            <input class="form-control @error('email') is-invalid @enderror" type="email" id="email" name="email" required="" placeholder="{{ __('auth.login.email.placeholder') }}" value="{{-- old('email') --}}">
                            @error('email')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-2">
                            <label for="password" class="form-label"> {{ __('auth.login.password.title') }} </label>
                            <div class="input-group input-group-merge">
                                <input class="form-control @error('password') is-invalid @enderror" type="password" id="password" name="password" placeholder="{{ __('auth.login.password.placeholder') }}" value="{{-- old('password', '') --}}">
                            </div>
                            @error('password')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1" checked>
                                <label class="form-check-label" for="remember">
                                    {{ __('auth.login.remember_me') }}
                                </label>
                            </div>
                        </div>

                        <div class="d-grid mb-0 text-center">
                            <button class="btn btn-primary" type="submit"> {{ __('auth.login.log_in') }} </button>
                        </div>

                    </form>

                    {{--
                    <div class="text-center">
                        <h5 class="mt-3 text-muted mb-3"> {{ __('auth.login.sign_in_with') }} </h5>
                        
                        {{-- Open ID List 
                        @include('includes/open_id')
                        

                    </div>
                    --}}

                </div> <!-- end card-body -->
            </div>
            <!-- end card -->

            <div class="row mt-3">
                <div class="col-12 text-center">
                    <p> <a href="{{ route('password.request') }}" class="text-muted ms-1"> {{ __('auth.login.forgot_password') }} </a></p>
                    <p class="text-muted"> {{ __('auth.login.dont_have_account') }} <a href="{{ route('register') }}" class="text-primary fw-medium ms-1"> {{ __('auth.login.sign_up') }} </a></p>
                </div> <!-- end col -->
            </div>
            <!-- end row -->

        </div> <!-- end col -->
    </div>
    <!-- end row -->
    
@endsection
