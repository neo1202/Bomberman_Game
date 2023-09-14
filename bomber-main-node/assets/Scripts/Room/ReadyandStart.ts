import { _decorator, Button, Color, Component, director, Enum, Label, log, Node, Sprite, SpriteFrame } from 'cc';
import { SocketBridge } from '../../Plugins/SocketBridge';
const { ccclass, property, type } = _decorator;

export enum RoomBtn {
    READY,
    UNREADY,
    START
}

@ccclass('ReadyandStart')
export class ReadyandStart extends Component {

    /*
    如果他是房主 > 給他Start鍵
        所有人都準備之後才能按Start（後端判斷？）
        按Start > 發送開始訊息給後端 > 切換場景進入遊戲
    如果他不是房主 > 給他Ready鍵
        按Ready > 發送準備資訊給後端 > 按鈕變成Unready
        按Unready鍵 > 發送取消準備資訊給後端 > 按鈕變成Ready
    */

    @property(Label)
    label: Label;

    @property(SpriteFrame)
    yellowBtn: SpriteFrame;
    @property(SpriteFrame)
    blueBtn: SpriteFrame;

    @type(Enum(RoomBtn))
    btntype: RoomBtn;   

    start() {

        //不同按鈕點下去後會發生的事
        this.node.on(Button.EventType.CLICK, () => {
            switch (this.btntype) {
                case RoomBtn.READY:
                    SocketBridge.sendString(`READY ${JSON.stringify({"isReady":true})}`)
                    this.label.string = "Unready";
                    this.node.getComponent(Sprite).color = new Color(81, 86, 139);
                    this.node.getComponent(Sprite).spriteFrame = this.blueBtn;
                    this.btntype = RoomBtn.UNREADY;
                    log("ready")
                    break;

                case RoomBtn.UNREADY:
                    SocketBridge.sendString(`UNREADY ${JSON.stringify({"isReady":false})}`)
                    this.label.string = "Ready";
                    this.node.getComponent(Sprite).color = new Color(255, 255, 255);
                    this.node.getComponent(Sprite).spriteFrame = this.blueBtn;
                    this.btntype = RoomBtn.READY;
                    log("unready")
                    break;

                case RoomBtn.START:
                    SocketBridge.sendString(`START ${JSON.stringify({"message":"Start"})}`)
                    break;
                
                default:
                    break;
            }
        })

    }

    update(deltaTime: number) {

    }

    protected onEnable(): void {

        SocketBridge.addSocketKeyDispatch(this, {
            'START': this.cmd_START,
            'ALLREADY': this.cmd_ALLREADY,
        });
        
        this.BtnType(this.btntype);
    }


    /**不同按鈕的事件 */
    BtnType(n) {

        //初始Ready或Start的樣子
        switch (n) {
            case RoomBtn.READY:
                this.label.string = "Ready";
                this.node.getComponent(Sprite).spriteFrame = this.blueBtn;
                this.node.getComponent(Button).interactable = true;
                break;

            case RoomBtn.START:
                this.label.string = "Start";
                this.node.getComponent(Sprite).spriteFrame = this.yellowBtn;
                this.node.getComponent(Button).interactable = false;
                director.preloadScene("map"); //預先加載場景（地圖也不會先生成還需要嗎？好吧差了131ms還是要）
                break;
            
            default:
                break;
        }

    }

    
    /**收到ALLREADY後Start可以按 */
    cmd_ALLREADY(key: string, value: string){
        this.node.getComponent(Button).interactable = true;
        let allready = JSON.parse(value);
        log(allready);
    }

    /**收到START後換場景 */
    cmd_START(key: string, value: string){
        director.loadScene("map");
        let start = JSON.parse(value);
        log(start);
    }

    protected onDisable(): void {
        SocketBridge.removeSocketKeyDispatch(this);
    }
}

