<head>
    <meta charset="utf-8" />
    
    <title>@yield('head_title') | {{ config('app.name') }}</title>

    <meta name="description" content="@yield('head_description')" />
    <meta name="author" content="{{ env('APP_AUTHOR') }}"  />
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
    {{--<link href="/source/base/css/app.min.css?<?=time()?>" rel="stylesheet" type="text/css" id="app-stylesheet" />--}}
    <link href="/source/base/css/app.css?<?=time()?>" rel="stylesheet" type="text/css" id="app-stylesheet" />

    <!-- icons -->
    <link href="/source/base/css/icons.min.css" rel="stylesheet" type="text/css" />

    <!-- Global style -->
    <link href="/source/base/css/default.css" rel="stylesheet" type="text/css" />

    <!-- Theme Config Js -->
    <script src="/source/base/js/config.js?1"></script>
    
</head>