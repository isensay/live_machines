<head>
    <meta charset="utf-8" />
    
    @if (isset($error_title))
    <title>{{ $error_pre_title }} {{ $httpStatusCode }} | {{ $error_title }} | {{ config('app.name') }} </title>
    @elseif (__(Route::currentRouteName() == 'logout'))
    <title> {{ __('auth.logout.title') }} | {{ config('app.name') }} </title>
    @elseif (__(Route::currentRouteName() == 'login'))
    <title> {{ __('auth.login.title') }} | {{ config('app.name') }} </title>
    @elseif (__(Route::currentRouteName() == 'maintenance'))
    <title> {{ __('errors.errors.503.title') }} | {{ config('app.name') }} </title>
    @else
    <title> {{ __(Route::currentRouteName().'.page_title') }} | {{ config('app.name') }} </title>
    @endif

    <meta name="description" content="" />
    <meta name="author" content="Petr Minkin"  />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    
    <!-- App favicon -->
    <link rel="shortcut icon" href="/favicon.ico">

    <!-- plugin css -->
    <!-- <link href="/source/base/libs/admin-resources/jquery.vectormap/jquery-jvectormap-1.2.2.css" rel="stylesheet" type="text/css" /> -->

    

    {{-- Add other js or css --}}
    @yield('head_other')

    <!-- App css -->
    <link href="/source/base/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <!-- <link href="/source/base/css/app.min.css?10" rel="stylesheet" type="text/css" id="app-stylesheet" /> -->
    <link href="/source/base/css/app.css?<?=time()?>" rel="stylesheet" type="text/css" id="app-stylesheet" />

    <!-- icons -->
    <link href="/source/base/css/icons.min.css" rel="stylesheet" type="text/css" />

    <!-- Theme Config Js -->
    <script src="/source/base/js/config.js?1"></script>

    
</head>