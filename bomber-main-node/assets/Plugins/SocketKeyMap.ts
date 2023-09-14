import { _decorator } from 'cc';
const { ccclass } = _decorator;

@ccclass('SocketKeyMap')
export class SocketKeyMap {

    public context: any;
    public callbackMap: any;   
    
    constructor(context: any, keyMap: any) {
        this.context = context;
        this.callbackMap = keyMap;
    }

    onReceiveSocket(key: string, value: string) {
        var callback = this.callbackMap[key];
        if (callback) {
            callback.call(this.context, key, value);
            return true;
        }
        return false;
    }
}