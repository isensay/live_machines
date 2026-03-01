@extends('layouts.lite')

@section('page_content')
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 col-xl-4">
            <div class="card">
                <div class="card-body p-4">
                    <div class="error-ghost text-center">
                        <img src="{{ $image }}" width="200" alt="error-image"/>
                    </div>

                    <div class="text-center">
                        <h3 class="mt-4 text-uppercase fw-bold">{{ $title }}</h3>

                        <p class="text-muted mb-0 mt-3" style="line-height: 20px;">{!! $text !!}</p>

                        <a class="btn btn-primary mt-4" href="/">
                            <i class="mdi mdi-reply me-1"></i>
                            {{ __('errors.button_to_main') }}
                        </a>
                        
                        <p class="text-muted mb-0 mt-5">
                            {{ __('errors.support_line') }}: 
                            <a href="mailto:support@adoxa.ru">support@adoxa.ru</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection