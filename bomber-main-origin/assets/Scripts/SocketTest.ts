import { _decorator, Component, log } from 'cc';
import { SocketBridge } from '../Plugins/SocketBridge';
const { ccclass, property } = _decorator;

@ccclass('SocketTest')
export class SocketTest extends Component {
    start() {
        log('SocketTest start');

        SocketBridge.instance;

        SocketBridge.addSocketKeyDispatch(this, {
            'rank': this.cmd_rank,
        });

        GE.addListener('NotifyLoginStatus', this.onNotifyLoginStatus, this);

        SocketBridge.instance.startConnect('localhost', '8080');
    }

    /** 處理rank */
    cmd_rank(key: string, value: string) {
        var json = JSON.parse(value);
        log('cmd_rank', key, json);
    }

    protected onDestroy(): void {
        SocketBridge.removeSocketKeyDispatch(this);
    }

    /** 收到通知已經連上ws */
    onNotifyLoginStatus(key: string, value: string) {
        log('onNotifyLoginStatus', key, value);

        var json = {
            myKey: 'myValue',
            myStrAry: ['1', '2', '3'],
            myNumAry: [1, 2, 3],
            myObj: {
                myObjKey: 'myObjValue',
            },
            myObjAry: [
                {
                    kk1: 'vv1',
                },
                {
                    kk2: 'vv2',
                },
            ],
        };

        // SocketBridge.sendString(`rank ${JSON.stringify(json)}`);
        this.scheduleOnce(() => {
            SocketBridge.sendStringToSelf(`rank ${JSON.stringify(json)}`);
        }, 1000)
    }

    protected onDisable(): void {
        SocketBridge.removeSocketKeyDispatch(this);
    }
}
