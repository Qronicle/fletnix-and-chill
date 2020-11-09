@extends('layouts.tetrus')

@section('content')
    <div id="game" v-cloak>
        <div v-if="status == 'ready'" class="message">
            GET READY
        </div>
        <div v-if="status == 'finished'" class="message">
            @{{ winner }} WINS !!!
        </div>
        <!-- Player game -->
        <div class="tetrus-container">
            <div class="tetrus-field" id="tetrus-player"></div>
            <ul>
                <li>
                    <label>Player</label>
                    <span>@{{ player.name }}</span>
                </li>
                <li>
                    <label>Score</label>
                    <span>@{{ player.points }}</span>
                </li>
            </ul>
        </div>
        <!-- Opponent game -->
        <div class="tetrus-container">
            <div class="tetrus-field" id="tetrus-opponent"></div>
            <ul>
                <li>
                    <label>Player</label>
                    <span>@{{ opponent.name }}</span>
                </li>
                <li>
                    <label>Score</label>
                    <span>@{{ opponent.points }}</span>
                </li>
            </ul>
        </div>
    </div>
@endsection

@section('scripts')
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
    <script type="text/javascript" src="/js/games/socket.js"></script>
    <script type="text/javascript" src="/js/games/tetrus.js?t={{ time() }}"></script>
    <script>

        function documentReady() {

            var playerTetris = new Tetrus();
            playerTetris.init('tetrus-player');

            var opponentTetris = new Tetrus();
            opponentTetris.init('tetrus-opponent');

            function resize() {
                var fieldWidth = Math.floor(Math.min(
                    (window.innerHeight - 80) * 0.5,
                    (window.innerWidth - 80) * 0.25
                ));
                fieldWidth -= fieldWidth % 10;
                var containers = document.getElementsByClassName('tetrus-container');
                for (i = 0; i < containers.length; i++) {
                    containers[i].style.width = (fieldWidth + fieldWidth*0.4 + 88) + 'px';
                    containers[i].style.height = (fieldWidth * 2 + 48) + 'px';
                    var field = containers[i].children[0];
                    field.style.width = (fieldWidth) + 'px';
                    field.style.height = (fieldWidth * 2) + 'px';
                }
            }
            resize();

            Server.init({
                url: {!! json_encode(env('WS_URL')) !!},
                room_id: {!! json_encode($roomId) !!},
                user_id: {{ $user->id }},
                user_secret: {!! json_encode($user->secret) !!},
                receive: function(message) {
                    //console.log(message);
                    switch (message.type) {
                        case 'init':
                            playerTetris.clear();
                            opponentTetris.clear();
                            playerTetris.queueTetrominos(message.queue);
                            opponentTetris.queueTetrominos(message.queue.slice(0));
                            playerTetris._interval = parseInt(message.speed);
                            opponentTetris._interval = parseInt(message.speed);
                            playerTetris.drawQueuedTetromino();
                            opponentTetris.drawQueuedTetromino();
                            tetrusApp.player.name = message.player.username;
                            tetrusApp.player.points = 0;
                            tetrusApp.opponent.name = message.opponent.username;
                            tetrusApp.opponent.points = 0;
                            tetrusApp.status = 'ready';
                            break;
                        case 'start':
                            tetrusApp.status = 'game';
                            playerTetris.start();
                            opponentTetris.startView();
                            break;
                        case 'game_finished':
                            tetrusApp.winner = message.winner.username;
                            tetrusApp.status = 'finished';
                            playerTetris.pause();
                            opponentTetris.pause();
                            break;
                        case 'end':
                            break;
                        case 'self_update':
                            playerTetris.queueTetromino(message.queue);
                            tetrusApp.player.points = message.points;
                            break;
                        case 'foe_block_placed':
                            opponentTetris.queueTetromino(message.queue);
                            tetrusApp.opponent.points = message.points;
                            opponentTetris.placeBlock(message.tetromino);
                            playerTetris.addLines(message.lines);
                            break;
                        case 'foe_block_moved':
                            opponentTetris.moveBlock(message.tetromino, message.lines);
                            break;
                    }
                }
            });

            playerTetris.onBlockPlaced = function(e) {
                e.type = 'block_placed';
                Server.send(e);
            };

            playerTetris.onBlockMoved = function(e) {
                e.type = 'block_moved';
                Server.send(e);
            };

            playerTetris.onGameOver = function(e) {
                e.type = 'game_over';
                Server.send(e);
            };
        }

        var tetrusApp = new Vue({
            el: '#game',
            data: {
                status: 'waiting_for_players',
                player: {
                    name: '',
                    points: 0
                },
                opponent: {
                    name: '',
                    points: 0
                },
                winner: ''
            },
            mounted: documentReady
        });


    </script>
@endsection

@section('head')
    <style type="text/css">

        body {
            text-align: center;
            margin: 0;
            padding: 0;
        }

        .message {
            position: absolute;
            bottom: 50%;
            height: 100px;
            width: 100%;
            left: 0;
            background: rgba(0,0,0,0.5);
            color: white;
            font-size: 30px;
            z-index: 10;
            line-height: 100px;
        }

        /** Tetris Container ******************************************************************************************/

        .tetrus-container {
            text-align: left;
            position: relative;
            display: inline-block;
            width: 200px;
            height: 400px;
            margin: 20px 20px 0 0;
            background: #ccc;
            padding: 20px;
            border-top: 4px solid rgba(255, 255, 255, 0.5);
            border-left: 4px solid rgba(255, 255, 255, 0.25);
            border-right: 4px solid rgba(0, 0, 0, 0.15);
            border-bottom: 4px solid rgba(0, 0, 0, 0.25);
            border-radius: 2px;
        }

        .tetrus-container ul {
            position: absolute;
            width: calc((100% - 80px) * 0.357);
            top: calc((100% - 40px) * 0.2 + 60px);
            left: calc((100% - 80px) * 0.714 + 40px);
        }

        .tetrus-container ul li {
            margin: 0 0 10px;
        }

        .tetrus-container ul li label {
            font-size: 12px;
            display: block;
            color: #555;
        }

        /** Tetris Field **********************************************************************************************/

        .tetrus-field {
            width: 200px;
            height: 400px;
            position: relative;
        }

        .tetrus-field table {
            z-index: 5;
            position: absolute;
            border-collapse: collapse;
        }

        .tetrus-field .tetrus {
            top: 0;
            left: 0;
            height: 100%;
            width: 100%;
        }

        .tetrus-field .tetrus td {
            width: 10%;
            height: 5%;
        }

        .tetrus-field .tetrus-bg {
            background: #4d4d4d;
            box-shadow: 0 0 0 2px #999, 0 0 0 3px #555;
        }

        .tetrus-field .tetrus-bg td {
            box-shadow:
                inset 1px 1px 0 #404040,
                inset -1px -1px 0 #404040;
        }

        .tetrus-field .tetrus-next {
            top: 0;
            left: 100%;
            margin: 10px 0 0 30px;
            height: 20%;
            width: 40%;
        }

        .tetrus-field .tetrus-next-bg {
            position: absolute;
            top: 0;
            left: 100%;
            margin: 0 0 0 20px;
            height: calc(20% + 20px);
            width: calc(40% + 20px);
            background: #4d4d4d;
            box-shadow: 0 0 0 2px #999, 0 0 0 3px #555;
        }

        /** Tetrus colors *********************************************************************************************/

        .tetrus-fg {
            z-index: 6;
        }

        .tetrus-fg td.shape-I {
            background-color: #00ffff;
        }

        .tetrus-fg td.shape-J {
            background-color: #5c5cff;
        }

        .tetrus-fg td.shape-L {
            background-color: #ffc04b;
        }

        .tetrus-fg td.shape-O {
            background-color: #ffff89;
        }

        .tetrus-fg td.shape-S {
            background-color: #5dbf5d;
        }

        .tetrus-fg td.shape-T {
            background-color: #cc8bcc;
        }

        .tetrus-fg td.shape-Z {
            background-color: #ff7373;
        }

        .tetrus-fg td.shape-X {
            background-color: #777777;
        }
    </style>
@endsection
