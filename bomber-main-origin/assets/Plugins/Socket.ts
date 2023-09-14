import {_decorator, sys, EventTarget, log, Game, Tween} from 'cc';

const {ccclass} = _decorator;

@ccclass('Socket')
export class Socket extends EventTarget {

    private _webSocket: WebSocket | null;
    private _pingInterval1: number | null;
    private _pingInterval2: number | null;
    private _pingInterval3: number | null;
    private _beginPingPong: number;
    private _logSendColor: string;
    private _logReceiveColor: string;

    public pingPongMS: number;

    constructor(url: string) {
        super();

        // 因為需要用到on emit 這個是給EventTarget需要用到的
        // EventTarget.call(this);

        // 主要的WebSocket
        this._webSocket = new WebSocket(url);
        this._webSocket.onopen = this.onOpen.bind(this);
        this._webSocket.onmessage = this.onMessage.bind(this);
        this._webSocket.onclose = this.onClose.bind(this);
        this._webSocket.onerror = this.onError.bind(this);
    }

    // 連上
    onOpen(event: Event) {
        this.emit('onConnect', this);
    }

    // 收到訊息
    onMessage(event: any) {
        var receiveString = event.data;
        if (typeof receiveString === 'string') {
            this.emit('onReceive', this, receiveString);
        }
        if (receiveString.indexOf("UPDATE_POSITION") == 0)
            return;
        console.log('Socket 收到:' + receiveString, this._logReceiveColor);
    }

    // 關閉
    onClose(event: any) {
        // 通知
        var code = event.code;
        var reason = '被斷線=' + event.reason + 'Code=' + code;
        this.emit('onDisconnect', this, reason);
    }

    // 有錯誤
    onError(event: any) {
        // 通知
        var code = event.code;
        var reason = '被斷線=' + event.reason + 'Code=' + code;
        this.emit('onDisconnect', this, reason);
    }

    send(sendString: string) {
        this.emit('onSend', this, sendString);
        this._webSocket != null && this._webSocket.send(sendString);
        if (sendString.indexOf("MOVE") == 0 || sendString.indexOf("GAME_TIME") == 0)
            return;
        console.log('Socket 送出:' + sendString, this._logSendColor);
    }

    disconnect() {
        if (this._webSocket) {
            this.emit('onDisconnect', this, '主動斷線');
            this._webSocket.close(3978, '主動斷線');
            this._webSocket = null;
        }
    }

    //抓取WebSocket State
    getState() {
        if (this._webSocket)
            return this._webSocket.readyState;
        else
            return 0;
    }
}