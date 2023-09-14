import { _decorator, Component, log, tween } from 'cc';
import { SocketBridge } from './SocketBridge';
const { ccclass, property } = _decorator;

@ccclass('SocketTest')
export class SocketTest extends Component {
    start() {
        log('SocketTest start');

        SocketBridge.instance;

        //聽server的hello，然後給
        SocketBridge.addSocketKeyDispatch(this, {
            'hello': this.serverSayHello,
        });

        GE.addListener('NotifyLoginStatus', this.onNotifyLoginStatus, this);

        SocketBridge.instance.startConnect('192.168.31.20', '3000');

        // tween(this.node)
        //     .delay(1)
        //     .call(() => {
        //         SocketBridge.sendString("hello {\"BangA\":\"BangB\"}");
        //     })
        //     .start()

    }

    serverSayHello(key: string, value: string) {
        log("my function Triggered by key:", key, JSON.parse(value))
    }

    /** 處理rank */
    cmd_rank(key: string, value: string) {
        var json = JSON.parse(value);
        log('cmd_rank', key, json);
    }

    /** 收到通知已經連上ws */
    onNotifyLoginStatus(key: string, value: string) {
        log('onNotifyLoginStatus', key, value);
    }
}
