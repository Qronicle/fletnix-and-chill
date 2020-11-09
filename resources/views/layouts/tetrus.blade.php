<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Tetrus ~ Fletnix & Chill</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet" type="text/css">

    <!-- Styles -->
    <link href="{{ asset('css/picturnery/app.css') }}" rel="stylesheet">
    <link href="/img/open-iconic/font/css/open-iconic.css" rel="stylesheet">
    <style>
        [v-cloak] {
            display: none;
        }
    </style>

    @yield('head')
</head>
<body>
    <header>
        <h1>Tetrus</h1>
    </header>

    @yield('content')
    @yield('content-bottom')
    @yield('scripts')
</body>
</html>
