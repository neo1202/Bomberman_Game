import { _decorator, Button, Component, EditBox, JsonAsset, log, Node, SpriteFrame, sys } from 'cc';
import { SocketBridge } from '../../Plugins/SocketBridge';
const { ccclass, property } = _decorator;

@ccclass('EnterName')
export class EnterName extends Component {

    @property(EditBox)
    editbox: EditBox;

    @property(EditBox)
    IPEditbox: EditBox;

    @property(EditBox)
    PortEditbox: EditBox;

    // @property(SpriteFrame)
    // disableBtn: SpriteFrame;
    // @property(SpriteFrame)
    // normalBtn: SpriteFrame;


    start() {

        SocketBridge.instance;
        SocketBridge.addSocketKeyDispatch(this, {
            'CONNECT': this.cmd_CONNECT,
        })

        let storageName = localStorage.getItem("Name");
        let storageIP = localStorage.getItem("IP");
        let storagePort = localStorage.getItem("Port");

        if (storageName)
            this.editbox.string = storageName;
        if (storageIP)
            this.IPEditbox.string = storageIP;
        if (storagePort)
            this.PortEditbox.string = storagePort;

        let btn = this.node.getComponent(Button);
        if (this.editbox.string.length > 0) {
            btn.interactable = true;
            //btn.normalSprite = this.normalBtn; 
        }

        //按鈕能不能按
        this.editbox.node.on('text-changed', () => {
            if (this.editbox.string.length == 0) {
                btn.interactable = false;
                //btn.normalSprite = this.disableBtn;
            }
            else {
                btn.interactable = true;
                //btn.normalSprite = this.normalBtn;
            }
        });

        this.editbox.node.on('editing-return', this.nameEntered, this); //玩家輸入後按下鍵盤Enter
        this.node.on(Button.EventType.CLICK, this.nameEntered, this); //玩家按下螢幕的Enter鈕



    }

    update(deltaTime: number) {

    }

    /**處理CONNECT */
    cmd_CONNECT(key: string, value: string) {
        let connect = JSON.parse(value);
        GE.dispatchCustomEvent("PlayerFdInit", connect.fd);
        log(connect);

        //收到連線成功後再發名字，就不會發不出去了吧
        let name = this.editbox.string;
        log('name: ', name);
        SocketBridge.sendString(`BOMBER ${JSON.stringify({ "name": name })}`);

       this.node.parent.active = false;

    }

    /**玩家輸入好名字後，發送玩家名字等資訊到後端，並離開輸入名字的畫面 */
    nameEntered() {
        let name = this.editbox.string;
        log('name: ', name);


        if (this.IPEditbox.string.length == 0 || this.PortEditbox.string.length == 0)
            SocketBridge.instance.startConnect('localhost', '8080')
        else
            //Jason 電腦
            //SocketBridge.instance.startConnect('192.168.31.137', '5000');
            //jing-xiang 電腦
            //SocketBridge.instance.startConnect('192.168.31.20', '5000');
            //Neo電腦
            //SocketBridge.instance.startConnect('192.168.31.97', '5000');
            SocketBridge.instance.startConnect(this.IPEditbox.string, this.PortEditbox.string);



        GE.dispatchCustomEvent("PlayerNameInit", name);

        //HACK: 防止跟StartConnect幾乎同時發出
        /*setTimeout(() => {
            SocketBridge.sendString(`BOMBER ${JSON.stringify({ "name": name })}`)
        }, 100);*/


        sys.localStorage.setItem("IP", this.IPEditbox.string);
        sys.localStorage.setItem("Port", this.PortEditbox.string);
        sys.localStorage.setItem("Name", this.editbox.string);

    }

    // protected onDisable(): void {
    //     SocketBridge.removeSocketKeyDispatch(this);
    // }
}

