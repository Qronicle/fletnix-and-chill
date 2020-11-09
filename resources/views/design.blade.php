<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Picturnery</title>

    <style type="text/css">
        html, body {
            margin: 0;
            padding: 0;
        }
        * {
            box-sizing: border-box;
            font-family: "Open Sans", sans-serif;
        }
        header {
            display: block;
            height: 50px;
            padding: 10px 20px;
            background: aqua;
        }
        header h1 {
            margin: 0;
        }
        .game {
            margin: 0 auto;
            width: 1000px;
        }
        .canvas {
            width: 700px;
            height: 700px;
            float: left;
        }
        .players {
            margin: 0;
            float: right;
            width: 300px;
            background: #2d995b;
        }
        .players li {
            padding: 2px 6px;
        }
        .players li:nth-child(odd) {
            background: #2fa360;
        }
        .players .icon {
            display: inline-block;
            width: 24px;
            text-align: center;
        }
        .players .score {
            float: right;
        }
        ul {
            margin: 0;
            padding: 0;
        }
        li {
            display: block;
        }
        .chatbox {
            margin: 0;
            float: right;
            width: 300px;
            background: #2d995b;
        }
        .messages {
            height: 200px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <header>
        <h1>Picturnery</h1>
    </header>
    <div class="game">
        <div class="canvas">

        </div>
        <ul class="players">
            <li>
                <span class="icon">/</span>
                <span class="name">Ruud</span>
                <span class="score">125</span>
            </li>
            <li>
                <span class="icon">/</span>
                <span class="name">Jan Pater-Noster</span>
                <span class="score">56</span>
            </li>
            <li>
                <span class="icon">/</span>
                <span class="name">Basiel</span>
                <span class="score">45</span>
            </li>
            <li>
                <span class="icon">/</span>
                <span class="name">Bert</span>
                <span class="score">45</span>
            </li>
            <li>
                <span class="icon">/</span>
                <span class="name">Hendrik-Jan</span>
                <span class="score">45</span>
            </li>
            <li>
                <span class="icon">/</span>
                <span class="name">Jess</span>
                <span class="score">45</span>
            </li>
            <li>
                <span class="icon">/</span>
                <span class="name">Bella</span>
                <span class="score">45</span>
            </li>
            <li>
                <span class="icon">/</span>
                <span class="name">Zoë</span>
                <span class="score">45</span>
            </li>
            <li>
                <span class="icon">/</span>
                <span class="name">Kato</span>
                <span class="score">45</span>
            </li>
            <li>
                <span class="icon">/</span>
                <span class="name">Hazze</span>
                <span class="score">45</span>
            </li>
        </ul>
        <div class="chatbox">
            <ul class="messages">
                <li>
                    <span class="username">Ruud</span>
                    <span class="message">Dit is een bericht. Dit zijn Jan & Flo. We hebben elkaar leren kennen op de Gentse feesten.</span>
                </li>
                <li>
                    <span class="username">Jan Pater-Noster</span>
                    <span class="message">Vliegtuig</span>
                </li>
                <li>
                    <span class="username">Jan Pater-Noster</span>
                    <span class="message">Vogel</span>
                </li>
                <li>
                    <span class="username">Jan Pater-Noster</span>
                    <span class="message">Mega Mindy</span>
                </li>
            </ul>
            <form class="message-form">
                <input class="input-text" />
                <button class="">Send</button>
            </form>
        </div>
    </div>
    <footer>
        &copy;2020 Qronicle.be
    </footer>
</body>
</html>
