import { _decorator, Component, Node } from 'cc';
import { SocketBridge } from './SocketBridge';
const { ccclass, property } = _decorator;

@ccclass('Welcome')
export class Welcome extends Component {

    public socketBridge:SocketBridge;

    start() {
        this.socketBridge = new SocketBridge();
        this.socketBridge.startConnect("127.0.0.1","9487");
    }
}

