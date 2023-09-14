import { _decorator, EventTarget, sys, log, game, Game } from 'cc';
import { Socket } from './Socket';
import { SocketKeyMap } from './SocketKeyMap';
const { ccclass } = _decorator;

class SocketKeyCallbackMapItem {
    public socketKey: string = '';      // 抓出送出去的Key
    public callback: Function = null!;
    public time: number = -1;           // 現在的時間 用來比對
}

@ccclass('SocketBridge')
export class SocketBridge extends EventTarget {

    static _instance: SocketBridge;
    static get instance() {
        if (this._instance) {
            return this._instance;
        }
        this._instance = new SocketBridge();
        return this._instance;
    }

    // 加SocketKey
    public static addSocketKeyDispatch(context: any, keyMap: any) {
        var socketKeyMap = new SocketKeyMap(context, keyMap);
        this._instance._socketKeyDispatchMapArray.push(socketKeyMap);
    }

    // 移除SocketKey
    public static removeSocketKeyDispatch(context: any) {
        for (var i = 0; i < this._instance._socketKeyDispatchMapArray.length; i++) {
            var socketKeyMap = this._instance._socketKeyDispatchMapArray[i];
            if (socketKeyMap.context == context) {
                this._instance._socketKeyDispatchMapArray.splice(i, 1);
                break;
            }
        }
    }

    //抓取註冊的SocketKey
    public static getSocketKeyDispatch(context: any) {
        for (var i = 0; i < this._instance._socketKeyDispatchMapArray.length; i++) {
            var socketKeyMap = this._instance._socketKeyDispatchMapArray[i];
            if (socketKeyMap.context == context) {
                return socketKeyMap;
            }
        }
    }

    /**
     * 送字串出去
     * @param sendString 要送的字串
     * @param callback
     * @param customKey 要callback時自定義的socketKey 不帶的話會自動使用sendString的key
     */
    public static sendString(sendString: string, callback?: Function, customKey?: any) {
        this._instance.sendString(sendString + '\n', callback, customKey);
    }

    // 送字串給自己
    public static sendStringToSelf(sendString: string) {
        if (!sendString)
            return;

        var socketBridge = this._instance;

        socketBridge.onReceive(SocketBridge.instance._socket, sendString);
    }

    // 斷線
    public static disconnect(needReconnect?: boolean) {
        this.instance.disconnect(needReconnect);
    }

    // 抓取PingPongd間隔時間
    public static getPingPongMS() {
        var socket = this.instance._socket;
        return socket ? socket.pingPongMS : 0;
    }

    private _socketKeyDispatchMapArray: SocketKeyMap[] = [];
    private _socket: Socket | null;

    private _host: string;
    private _port: string;
    private _wsURL: string;

    private _sendStringQueue: string[];
    private _socketKeyCallbackMapArray: SocketKeyCallbackMapItem[];
    private _socketOnConnect: boolean;

    constructor() {
        super();

        this._socket = null;                        // 連接的Socket

        this._socketOnConnect = false;

        this._sendStringQueue = [];                 // 要送的String的queue
        this._socketKeyCallbackMapArray = [];       // 用來對應Socket Key 要直接Callback到誰身上 callback完會直接砍掉 Promise也放在這裡面 因為時間的關係

        // 做一個定時去砍掉Callback & Promise的地方 TODO 確定一下還要不要
        setInterval((_: any) => {
            var len = this._socketKeyCallbackMapArray.length;
            if (len === 0)
                return;

            var nowTime = new Date().getTime();
            var findIt = false;
            for (var i = 0; i < len; i++) {
                var object = this._socketKeyCallbackMapArray[i];
                if (nowTime - object.time < 30000) {
                    // 代表到這邊的時間還沒超過幾30秒 之前的都超過了 要砍掉
                    this._socketKeyCallbackMapArray.splice(0, i);
                    findIt = true;
                    break;
                }
            }

            if (!findIt) {
                // 代表全都超過了 全砍
                this._socketKeyCallbackMapArray = [];
            }
        }, 30000);
    }

    startConnect(host: string, port: string) {
        this._host = host;
        this._port = port;

        this._startConnectSocket();
    }

    // 開始連Socket
    _startConnectSocket() {
        //先把Socket清掉
        this.disconnect();

        var socket;
        if (this._host && this._port) {
            let isWSS = false;
            this._wsURL = (isWSS ? 'wss://' : 'ws://') + this._host + ':' + this._port;

            socket = this._socket = new Socket(this._wsURL);

            // 透過Event註冊
            socket.on('onConnect', this.onConnect, this);
            socket.on('onReceive', this.onReceiveEvent, this);
            socket.on('onDisconnect', this.onDisconnect, this);
        } else {
            // 沒資訊可用了，通知斷線
            this._notifyUIDisconnect();
        }
    }

    // 送字串出去
    _sendString(sendString: string) {
        var socketConnected = (this._socket && this._socket.getState() === 1);
        if (socketConnected) {
            // 送出
            this._socket && this._socket.send(sendString);
        }
    }

    /**
     * 外部可以call送字串的地方 傳callback
     * @param sendString 要送的字串
     * @param callback
     * @param customKey 要callback時自定義的socketKey 不帶的話會自動使用sendString的key
     */
    sendString(sendString: string, callback?: Function, customKey?: string) {
        // 判斷是否有callback 有的話代表有收到此相關SocketKey 就直接callback回去
        if (callback) {
            var tempItem = new SocketKeyCallbackMapItem();
            tempItem.socketKey = customKey || sendString.split(' ')[0];    // 抓出送出去的Key
            tempItem.callback = callback;
            tempItem.time = new Date().getTime();                          // 現在的時間 用來比對
        }

        var socketConnected = (this._socket && this._socket.getState() === 1);
        if (socketConnected) {
            // 已連上 送出去
            this._sendString(sendString);
        }
    }

    // 自行斷線
    disconnect(needReconnect?: boolean) {
        // 把Socket清掉
        if (this._socket) {
            var oldSocket = this._socket;
            if (!needReconnect)
                this._socket = null;
            oldSocket.disconnect();
        }
    }

    // 通知UI斷線
    _notifyUIDisconnect() {
        GE.dispatchCustomEvent('NotifySocketResult', 'Disconnect');
    }

    /**
     * 把收到的String分送給其他Object
     */
    _dispatchSocketData(data: any) {
        var key = data;
        var value = '';
        var spaceIndex = key.indexOf(' ');
        if (spaceIndex > 0) {
            //要切空格
            key = data.slice(0, spaceIndex);
            value = data.slice(spaceIndex + 1);
        }

        // 分發給其他人
        for (var i = 0; i < this._socketKeyDispatchMapArray.length; i++) {
            var socketKeyMap = this._socketKeyDispatchMapArray[i];
            socketKeyMap.onReceiveSocket(key, value);
        }

        // 看有沒有需要直接callback 要從最前找 先進先出
        for (var i = 0; i < this._socketKeyCallbackMapArray.length; i++) {
            var object = this._socketKeyCallbackMapArray[i];
            if (object.socketKey === key) {
                object.callback(value);

                // 砍掉此物件
                this._socketKeyCallbackMapArray.splice(i, 1);

                break;
            }
        }
    }

    /**
     * Socket Event通知
     */
    // 連上Socket
    onConnect(target: Socket) {
        console.log("恭喜,已連上socket");

        if (this._socket != target)
            return;

        //記下來 假如收到第一個cmd 把proxy array清掉
        this._socketOnConnect = true;

        // 通知Loading UI
        GE.dispatchCustomEvent('NotifyLoginStatus', 'SocketConnected');
    }

    // Socket收到東西 因為動其他的onReceive比較麻煩 在轉一層
    onReceiveEvent(target: Socket, value: any) {
        //後面的true代表斷線重連用的RC Count要增加
        this.onReceive(target, value);
    }

    // Socket收到東西
    onReceive(socket: Socket | null, data: any) {
        if (socket == null || this._socket != socket)
            return;

        if (this._socketOnConnect) {
            // ProxyArray清掉
            this._socketOnConnect = false;
        }

        this._dispatchSocketData(data);
    }

    // Socket斷線
    onDisconnect(target: Socket, reason: string) {
        if (this._socket != target)
            return;

        //清掉自己的Socket
        this._socket = null;

        // 通知UI
        if (this._host && this._port) {
            //代表還有得連
            this._startConnectSocket();
        } else {
            this._notifyUIDisconnect();
        }
    }

    //清除queue住的指令和callback資料
    clearSocketCommands() {
        this._socketKeyCallbackMapArray = [];
        this._sendStringQueue = [];
    }
}
