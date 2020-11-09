<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Fletnix & Chill</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Lobster+Two&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@300;400;500&display=swap" rel="stylesheet">

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="/img/open-iconic/font/css/open-iconic-bootstrap.css" rel="stylesheet">
    <style>
        body {
            background: transparent url('/img/fletnix/skulls.png');
            font-family: 'Rajdhani', sans-serif;
            font-size: 20px;
        }
        [v-cloak] {
            display: none;
        }
        .chill-container {
            width: 600px;
            max-width: 100%;
            margin: 0 auto;
            padding: 0 20px;
        }
        h1 {
            text-align: center;
            font-family: 'Lobster Two', cursive;
            font-size: 60px;
            line-height: 1em;
            padding: 20px 0;
            color: #333;
        }
        .room, .block {
            display: block;
            color: #333 !important;
            text-decoration: none !important;
            background-color: #b3b3cc;
            border: 8px solid #a1a1ba;
            border-radius: 1px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px 3px rgba(0,0,0,0.1), inset 0 1px 2px rgba(0,0,0,0.4);
            padding: 15px;
            position: relative;
        }
        .block h2 {
            padding: 0 0 10px;
            border-bottom: 1px dashed #63637a;
            margin-bottom: 25px;
        }
        .form-control, .btn {
            font-size: inherit;
        }
        .block .btn-primary {
            background-color: #49435f;
            border-color: #49435f;
        }
        .block .btn-primary:hover {
            background-color: #403a57;
            border-color: #403a57;
        }
        .room {
            padding: 15px 15px 15px 90px;
        }
        .room img {
            position: absolute;
            top: 20px;
            left: 15px;
            width: 60px;
            border: 1px solid #63637a;
            border-radius: 4px;
        }
        .room h5 {
            font-family: 'Lobster Two', cursive;
            font-size: 26px;
        }
        .room h5 em {
            font-family: 'Rajdhani', sans-serif;
            font-style: normal;
            font-weight: 200;
            /*color: #555;
            font-size: 18px;*/
            padding-left: 10px;
        }
        .room p {
            margin-bottom: 0;
            color: #555;
            font-size: 15px;
        }
        .room p .full {
            margin-bottom: 0;
            background-color: #75758d;
            color: #b3b3cc;
            padding: 0 3px;
            border-radius: 2px;
            font-size: 10px;
            display: inline-block;
            vertical-align: middle;
        }
        .actions {
            margin-bottom: 15px;
            text-align: right;
            font-size: 16px;
        }
        .actions .btn-primary {
            background-color: #b3b3cc;
            border-color: #b3b3cc;
        }
        .actions .btn-primary:hover {
            background-color: #a1a1ba;
            border-color: #a1a1ba;
        }
    </style>

    @yield('head')
</head>
<body>
    <div class="chill-container">
        <h1>Fletnix & Chill</h1>
        @yield('content')
    </div>
    @yield('content-bottom')
    @yield('scripts')
</body>
</html>
