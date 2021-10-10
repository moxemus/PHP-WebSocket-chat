socket_url = "ws://127.0.0.1:27800/";


class TextValidator {
    static escapeHtml(text) {
        let map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };

        return text.replace(/[&<>"']/g, function (m) {
            return map[m];
        });
    }
}


class SocketClient {

    static userName;
    static url;
    static ws;
    static reconnectTimer;
    static wasDisconnected;

    static isConnected() {
        return (this.ws.readyState === WebSocket.OPEN);
    }

    static StartReconnectTimer() {
        this.reconnectTimer = null;

        this.reconnectTimer = setTimeout(function tick() {

            if (SocketClient.wasDisconnected) {

                if (SocketClient.ws == null) {
                    SocketClient.initWs();
                } else {
                    if (SocketClient.isConnected()) {
                        SocketClient.wasDisconnected = false;
                    }
                }
            }

            SocketClient.connectionTimer = setTimeout(tick, 2000); // (*)
        }, 2000);

    }

    static initWs() {
        this.ws = null;

        this.ws = new WebSocket(this.url);

        this.ws.onmessage = function (e) {
            Painter.InsertOtherMessageJSON(e.data);
        }

        this.ws.onopen = function (e) {
            SocketClient.connectionTimer = null;

            SocketClient.wasDisconnected = false;
            SocketClient.SendMessageToServer('', 'register');

            Painter.InsertOtherMessage('Connected successfully');
        }

        this.ws.onclose = function (e) {

            if (!SocketClient.wasDisconnected) {
                Painter.InsertOtherMessage('Lost connection');
                SocketClient.wasDisconnected = true;

                SocketClient.StartReconnectTimer();
            }

            delete SocketClient.ws;
        }
    }

    static init(userName, url) {
        this.userName = userName;
        this.url = url;

        this.initWs();

        Painter.DrawMessageGUI();
    }

    static SendMessageToServer(message, type) {
        let jsonData = '{"messageType" : "' + type + '", "name" : "' + this.userName + '", "message" : "' + message + '"}';
        this.ws.send(jsonData);
    }
}

class Painter {
    static InsertOtherMessageJSON(message) {

        let obj = JSON.parse(message);

        let newP = document.createElement('p')
        newP.className = obj['typeMessage'];

        if (obj['name'] != undefined) {
            newP.innerHTML = '<b>' + obj['name'] + ': </b>' + obj['message'];
        } else {
            newP.innerHTML = '<b>' + obj['message'] + '</b>';
        }

        document.body.append(newP);
    }

    static InsertOtherMessage(message) {
        let newP = document.createElement('p')
        newP.className = 'send';
        newP.innerHTML = '<b>' + message + '</b>';
        document.body.append(newP);
    }

    static InsertMyMessage(message) {
        let newP = document.createElement('p')
        newP.className = 'send';
        newP.innerHTML = '<b>Вы</b>: ' + message;
        document.body.append(newP);
    }

    static DrawMessageGUI() {
        let send_input = document.createElement('input')
        send_input.type = 'text';
        send_input.id = 'send_input';
        send_input.maxLength = 500;
        document.body.append(send_input);

        let send_button = document.createElement('button')
        send_button.class = 'button21';
        send_button.id = 'send_button';
        send_button.innerHTML = 'Отправить';
        document.body.append(send_button);

        send_button.onclick = function () {
            let message = TextValidator.escapeHtml(send_input.value);

            if (message != '' && SocketClient.isConnected()) {
                SocketClient.SendMessageToServer(message, 'mailAll');
                Painter.InsertMyMessage(message);
                send_input.value = '';
            }
        }
    }
}

let buttonName = document.getElementById('button_name');
let inputName = document.getElementById('input_name');

buttonName.onclick = function () {

    let form = document.getElementById('prompt-form-container');
    let username = TextValidator.escapeHtml(inputName.value);

    if (username != '') {
        form.remove();
        SocketClient.init(username, socket_url);
    }
}




