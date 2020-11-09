@extends('layouts.app')

@section('content')
    <div id="app" v-cloak>

        <!-- Actions -->

        <div class="actions">
            <button type="button" class="btn btn-primary" v-on:click="status = 'search'">
                <i class="oi oi-magnifying-glass" style="font-size: 0.7em"></i>&nbsp;
                Search
            </button>
            <button type="button" class="btn btn-primary" v-on:click="status = 'create'">
                <i class="oi oi-plus" style="font-size: 0.7em"></i>&nbsp;
                Create Room
            </button>
        </div>

        <!-- Search Form -->

        <div class="room" v-if="status == 'search'">
            <p>// @todo Implement search functionality</p>
        </div>

        <!-- Create Room Form -->

        <form class="block" v-if="status == 'create'" v-on:submit.prevent="createRoom">
            @csrf
            <h2>Create a new room!</h2>
            <div class="form-group row">
                <label for="game" class="col-md-4 col-form-label text-md-right">{{ __('Game') }}</label>
                <div class="col-md-6">
                    <select id="game" name="game" class="form-control" v-model="newRoom.game">
                        <option v-for="game in games" v-bind:value="game.type">@{{ game.name }}</option>
                    </select>
                </div>
            </div>
            <div class="form-group row">
                <label for="email" class="col-md-4 col-form-label text-md-right">{{ __('Room name') }}</label>
                <div class="col-md-6">
                    <input id="room_name" type="text" class="form-control" name="room_name" required v-model="newRoom.name">
                </div>
            </div>
            <div class="form-group row mb-0">
                <div class="col-md-8 offset-md-4">
                    <button type="submit" class="btn btn-primary">{{ __('Create') }}</button>
                    <button type="button" class="btn btn-secondary" v-on:click="status = false">{{ __('Cancel') }}</button>
                </div>
            </div>
        </form>

        <!-- Room Overview -->

        <a v-for="room in rooms" class="room" v-bind:href="'{{ url('/') }}/' + room.type + '/' + room.id">
            <img v-bind:src="'/img/fletnix/room-icons/' + room.type + '.png'" />
            <h5>@{{ room.game }} <em>@{{ room.name }}</em></h5>
            <p>
                <i class="oi oi-person"></i> @{{ room.players.length }}
                <span v-if="room.full" class="full">FULL</span>
            </p>
        </a>
    </div>
@endsection

@section('scripts')
    <script type="text/javascript" src="/js/games/socket.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
    <script type="text/javascript">

        var indexApp = new Vue({
            el: '#app',
            data: {
                status: false,
                rooms: [],
                games: [
                    {'type': 'picturnery', 'name': 'Picturnery'},
                    {'type': 'tetrus', 'name': 'Tetrus'}
                ],
                newRoom: {
                    game: 'picturnery',
                    name: ''
                }
            },
            methods: {
                createRoom: function(e) {
                    Server.send({
                        type: 'create_room',
                        game: this.newRoom.game,
                        name: this.newRoom.name
                    });
                    this.status = false;
                }
            }
        });

        Server.init({
            url: {!! json_encode(env('WS_URL')) !!},
            room_id: '-1',
            user_id: {{ $user->id }},
            user_secret: {!! json_encode($user->secret) !!},
            receive: function(message) {
                switch (message.type) {
                    case 'init':
                        indexApp.rooms = message.rooms;
                        break;
                }
            }
        });
    </script>
@endsection
