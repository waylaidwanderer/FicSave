var WebSocketEx = function(address, port, secure, socket) {
    if (typeof socket === 'undefined') {
        socket = this;
        socket.subscribedEvents = {};
        socket.callbackEvents = {};
        socket.retry = 0;
        socket.connected = false;
    }

    socket.EventEnums = Object.freeze({
        OPEN: 0,
        CLOSE: 1,
        RETRY: 2,
        MESSAGE: 3
    });

    if (socket.retry !== 0) {
        clearInterval(socket.retry);
        socket.retry = 0;
    }

    address = address || 'localhost';
    port = port || 8080;
    secure = secure || false;
    var protocol = secure ? 'wss' : 'ws';
    socket.conn = new WebSocket(protocol+'://'+address+':'+port);

    socket.conn.onopen = function() {
        socket.connected = true;
        socket.fireCallback(socket.EventEnums.OPEN);
    };

    socket.conn.onmessage = function(e) {
        try {
            var msg = JSON.parse(e.data);
            if (typeof msg.type !== 'undefined' && typeof msg.data !== 'undefined' && typeof socket.subscribedEvents[msg.type] == 'function') {
                socket.subscribedEvents[msg.type](msg.data);
            } else {
                socket.fireCallback(socket.EventEnums.MESSAGE, e);
            }
        } catch (e) {
            socket.fireCallback(socket.EventEnums.MESSAGE, e);
        }
    };

    socket.conn.onclose = function() {
        socket.connected = false;
        socket.fireCallback(socket.EventEnums.CLOSE);
        if (socket.retry === 0) {
            socket.retry = setInterval(function() {
                console.log('Lost connection to websocket server, attempting to reconnect...');
                socket.fireCallback(socket.EventEnums.RETRY);
                socket = new WebSocketEx(address, port, secure, socket);
            }, 2000);
        }
    };

    socket.fireCallback = function(event, e) {
        if (typeof socket.callbackEvents[event] == 'function') {
            socket.callbackEvents[event](e);
        }
    };
};

WebSocketEx.prototype.onopen = function(callback) {
    this.callbackEvents[this.EventEnums.OPEN] = callback;
};

WebSocketEx.prototype.onclose = function(callback) {
    this.callbackEvents[this.EventEnums.CLOSE] = callback;
};

WebSocketEx.prototype.onretry = function(callback) {
    this.callbackEvents[this.EventEnums.RETRY] = callback;
};

WebSocketEx.prototype.onmessage = function(callback) {
    this.callbackEvents[this.EventEnums.MESSAGE] = callback;
};

WebSocketEx.prototype.subscribe = function(event, callback) {
    this.subscribedEvents[event] = callback;
};

WebSocketEx.prototype.send = function(data) {
    this.conn.send(data);
};

WebSocketEx.prototype.emit = function(event, obj) {
    this.conn.send(JSON.stringify({
        type: event,
        data: obj
    }));
};
