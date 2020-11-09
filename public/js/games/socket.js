var Server = {

    settings: null,
    socket: null,
    pingTimer: null,

    init: function(settings) {
        this.settings = settings; // @todo default merge
        this.socket = new WebSocket(settings.url);
        this.socket.onopen = Server.open;
        this.socket.onmessage = Server.receive;
        this.socket.onclose = Server.close;
    },

    open: function() {
        Server.send({
            type: 'auth',
            room_id: Server.settings.room_id,
            user_id: Server.settings.user_id,
            user_secret: Server.settings.user_secret
        });
        Server.queuePing();
    },

    close: function () {
        clearTimeout(Server.pingTimer);
        setTimeout(function(){
            alert("Lost connection :(\nTry refreshing the page");
        }, 1000);
    },

    receive: function (event) {
        // When receiving "pong" message, ignore
        if (event.data === 'pong') return;
        var message = JSON.parse(event.data);
        Server.settings.receive(message);
        Server.queuePing();
    },

    send: function(message) {
        Server.socket.send(JSON.stringify(message));
        Server.queuePing();
    },

    queuePing: function() {
        if (Server.pingTimer) {
            clearTimeout(Server.pingTimer);
        }
        Server.pingTimer = setTimeout(Server.ping, 10000);
    },

    ping: function() {
        Server.send({type:'ping'});
        Server.queuePing();
    }
};
