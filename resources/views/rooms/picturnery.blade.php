@extends('layouts.picturnery')

@section('content')
    <div id="game" class="game" v-cloak>
        <div class="canvas">
            <div class="timer" v-if="mode && timeRemaining > -1" >
                <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <g class="circle">
                        <circle class="path-elapsed" cx="50" cy="50" r="45" />
                        <path v-bind:stroke-dasharray="(timeRemaining / totalTime * 273) + ' 283'" class="path-remaining" d="M 50, 50 m -45, 0 a 45,45 0 1,0 90,0 a 45,45 0 1,0 -90,0"></path>
                    </g>
                </svg>
                <span>@{{ Math.max(timeRemaining, 0) }}</span>
            </div>
            <div class="word-info">
                <span v-if="mode == 'guessing'" v-html="formattedHint"></span>
                <span v-if="mode == 'drawing'">You are drawing <strong>@{{ word }}</strong></span>
            </div>
            <div class="word-choice" v-if="mode == 'choosing'">
                <h2>Choose the word you want to draw</h2>
                <button v-for="(word, wordId) in words" v-bind:value="wordId" v-on:click="chooseWord">@{{ word }}</button>
            </div>
            <div class="drawing-result" v-if="mode == 'drawing_result'">
                <h2>Drawing finished!</h2>
                <h3>The word was <em>@{{ word }}</em></h3>
                <table class="guessers">
                    <tr v-for="guesser in guessers">
                        <td class="username">@{{ guesser.username }}</td>
                        <td class="time"><span class="oi" data-glyph="clock" title="Time"> @{{ guesser.time }}</td>
                        <td class="points">+ @{{ guesser.points }}</td>
                    </tr>
                </table>
            </div>
            <div class="game-result" v-if="mode == 'game_result'">
                <h2>Game finished!</h2>
                <table class="users">
                    <tr v-for="user in users">
                        <td class="username">@{{ user.username }}</td>
                        <td class="points">@{{ user.points }}</td>
                    </tr>
                </table>
            </div>
            <canvas id="drawing"></canvas>
            <div class="toolbar" v-if="mode == 'drawing'">
                <ul class="tools">
                    <li v-bind:class="tool == 'brush' ? 'active' : ''"><span class="oi" data-glyph="brush" title="Brush" v-on:click="tool = 'brush'"></span></li>
                    <li v-bind:class="tool == 'bucket' ? 'active' : ''"><span class="oi" data-glyph="beaker" title="Bucket" v-on:click="tool = 'bucket'"></span></li>
                </ul>
                <div class="brush-size" v-if="tool == 'brush'">
                    <input type="range" value="5" v-on:change="updateBrushSize()" min="1" max="50" />
                    <span class="brush-size-graphic"></span>
                </div>
                <div class="colors">
                    <ul>
                        <li v-for="color in colors" v-on:click="currentColor = color" v-bind:style="'background: rgba(' + color.join(',') + ')'"></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="sidebar">
            <div class="game-info">
                Round @{{ currentRound }}/@{{ totalRounds }}
            </div>
            <ul class="players">
                <li v-for="user in users">
                    <span class="icon" v-if="user.drawer"><span class="oi" data-glyph="pencil" aria-hidden="true"></span></span>
                    <span class="icon" v-else><span class="oi" data-glyph="person" aria-hidden="true"></span></span>
                    <span class="name">@{{ user.username }}</span>
                    <span class="score">@{{ user.points }}</span>
                </li>
            </ul>
            <ul class="messages">
                <li v-for="message in messages" v-bind:class="message.username ? 'user-message' : 'system-message'">
                    <span class="username" v-if="message.username">@{{ message.username }}</span>
                    <span class="message">@{{ message.text }}</span>
                </li>
            </ul>
            <form class="message-form" v-on:submit="sendMessage">
                <input id="message-input" class="input-text" placeholder="Type your guesses here!" autocomplete="off" />
                <button class="">Send</button>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
<script type="text/javascript" src="/js/games/socket.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
<script type="text/javascript" src="/js/games/picturnery.js"></script>
<script>

    function documentReady() {
        var canvasSize = Math.min(window.innerHeight - 60, window.innerWidth - 300);
        var $drawing = $('#drawing');
        $('.game').css({width: canvasSize + 300, height: canvasSize + 40});
        $drawing.parent().css({width: canvasSize, height: canvasSize + 40});
        Drawing.init($drawing);
        Drawing.clear();
        Drawing.mode = Drawing.MODE_VIEW;

        Server.init({
            url: {!! json_encode(env('WS_URL')) !!},
            room_id: {!! json_encode($roomId) !!},
            user_id: {{ $user->id }},
            user_secret: {!! json_encode($user->secret) !!},
            receive: function(message) {
                switch (message.type) {
                    case 'init':
                        picturneryApp.users = message.users;
                        picturneryApp.currentRound = message.currentRound;
                        picturneryApp.totalRounds = message.totalRounds;
                        if (message.mode !== 'choice' || picturneryApp.mode === 'choosing') {
                            picturneryApp.mode = null;
                        }
                        Drawing.mode = Drawing.MODE_VIEW;
                        Drawing.clear();
                        Drawing.draw(message.drawing);
                        break;
                    case 'draw_choice':
                        picturneryApp.words = message.options;
                        picturneryApp.mode = 'choosing';
                        break;
                    case 'start_drawing':
                        Drawing.resetTools();
                        Drawing.mode = Drawing.MODE_IDLE;
                        picturneryApp.mode = 'drawing';
                        picturneryApp.word = message.word;
                        picturneryApp.setTimer(10);
                        break;
                    case 'start_guessing':
                        picturneryApp.mode = 'guessing';
                        picturneryApp.hint = message.hint;
                        picturneryApp.setTimer(10);
                        break;
                    case 'drawing_end':
                        picturneryApp.mode = 'drawing_result';
                        picturneryApp.users = message.users;
                        picturneryApp.guessers = message.guessers;
                        picturneryApp.word = message.word;
                        break;
                    case 'drawing_update':
                        Drawing.draw(message.data);
                        break;
                    case 'new_user':
                        picturneryApp.users.push(message.user);
                        break;
                    case 'game_end':
                        picturneryApp.users = message.users;
                        picturneryApp.mode = 'game_result';
                        break;
                }
                if (message.timer) {
                    picturneryApp.setTimer(message.timer);
                }
                if (message.message) {
                    picturneryApp.messages.push(message.message);
                    setTimeout(function(){
                        var d = $('ul.messages');
                        d.scrollTop(d.prop('scrollHeight') - d.prop('clientHeight'));
                    }, 10)
                }
            }
        });

        Drawing.$canvas.on('update', function(e, data) {
            console.log(data);
            Server.send({
                type: 'drawing_update',
                data: data
            });
        });
    }

    var timer;

    var picturneryApp = new Vue({
        el: '#game',
        data: {
            users: [],
            messages: [],
            mode: null,
            currentRound: 1,
            totalRounds: 1,
            words: [],
            tool: 'brush',
            brushSize: 5,
            currentColor: [0, 0, 0],
            word: '',
            hint: '',
            timeRemaining: -1,
            totalTime: 120,
            guessers: [],
            colors: [
                [0,0,0],[127,127,127],[203,203,203],[255,255,255],
                [28,36,51],[93,101,118],[147,156,176],[211,217,227],
                [186,42,26],[214,59,45],[242,91,78],[254,204,209],
                [233,92,6],[233,142,13],[236,184,27],[248,229,173],
                [22,72,45],[34,114,70],[48,180,121],[187,227,206],
                [1,78,66],[0,138,127],[0,214,197],[188,239,232],
                [6,87,154],[10,136,207],[43,182,244],[178,228,251],
                [4,35,127],[25,63,160],[80,116,203],[174,193,253],
                [57,38,94],[79,64,130],[126,107,172],[196,191,217],
                [75,37,63],[128,52,89],[168,83,123],[213,188,203],
                [61,38,34],[92,63,54],[129,98,87],[209,196,192]
            ]
        },
        mounted: documentReady,
        methods: {
            chooseWord: function(e) {
                this.mode = null;
                Server.send({
                    type: 'pick_word',
                    word_id: e.target.value
                });
            },
            sendMessage: function(e) {
                e.preventDefault();
                var messageInput = document.getElementById('message-input');
                var message = $.trim(messageInput.value);
                messageInput.value = '';
                if (message !== '') {
                    Server.send({
                        type: 'message',
                        message: message
                    });
                }
            },
            updateBrushSize: function() {
                this.brushSize = $('.brush-size input').val();
            },
            setTimer: function(seconds) {
                this.totalTime = seconds;
                this.timeRemaining = seconds;
                clearInterval(timer);
                timer = setInterval(function(){
                    picturneryApp.timeRemaining--;
                    if (picturneryApp.timeRemaining < 0) {
                        clearInterval(timer);
                    }
                }, 1000);
            }
        },
        computed: {
            formattedHint: function() {
                return this.hint.split('').join('&nbsp;')
            }
        },
        watch: {
            tool: function(tool) {
                Drawing.setTool(tool);
            },
            currentColor: function(color) {
                Drawing.setColor(color);
            },
            brushSize: function(size) {
                Drawing.setBrushSize(size);
            },
        }
    });

</script>
@endsection
