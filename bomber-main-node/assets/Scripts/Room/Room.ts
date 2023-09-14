import { _decorator, Button, CCInteger, Component, EAxisDirection, EditBox, Enum, EventKeyboard, Input, input, instantiate, KeyCode, Label, log, Node, Prefab, Sprite, SpriteFrame } from 'cc';
import { SocketBridge } from '../../Plugins/SocketBridge';
import { PlayerInRoom } from './PlayerInRoom';
import { ReadyandStart, RoomBtn } from './ReadyandStart';
import { PlayerManager } from '../Managers/PlayerManager';
const { ccclass, property, type } = _decorator;

@ccclass('Room')
export class Room extends Component {

    //後端發訊息可以進入房間（應該還要給房間都有誰 + 廣播有人進來？) > 出現房間畫面（包含人、開始鍵）
    //應該要判斷是原本就在房間裡的人，還是新加進去的人

    @property(Prefab)
    player: Prefab;
    @property(Node)
    readyBtn: Node;

    @property(Node)
    loginPanel: Node;
    @property(Node)
    blurbg: Node;

    start() {

        SocketBridge.sendString(`ACHIEVEMENT_DONE {"message":"Done"}`);
        SocketBridge.addSocketKeyDispatch(this, {
            'ROOM': this.cmd_ROOM,
            'READY': this.cmd_READY,
            'UNREADY': this.cmd_UNREADY,
        })

    }

    update(deltaTime: number) {

    }

    /**處理ROOM，房間出現名字 */
    cmd_ROOM(key: string, value: string) {

        let room = JSON.parse(value);
        GE.dispatchCustomEvent("Room", room);
        log(room);

        //強制把輸入名字與id的框框隱藏
        this.loginPanel.active = false;
        this.blurbg.active = true;

        //為了不要重複產生名字所以把前次生成的銷毀，很笨的方法
        for (let j = 0; j < this.node.children.length; j++) {
            this.node.children[j].destroy();
        }

        for (let i = 0; i < room.players.length; i++) {

            let addPlayer = instantiate(this.player);

            let playerinfo = addPlayer.getComponent(PlayerInRoom);
            playerinfo.fd = room.players[i].fd;
            playerinfo.n = room.players[i].name;
            playerinfo.isleader = room.players[i].isLeader;
            playerinfo.isready = room.players[i].isReady;

            addPlayer.parent = this.node;
        }

        this.readyBtn.active = false;

        //自己是不是leader、有沒有ready
        let btn = this.readyBtn.getComponent(ReadyandStart);

        room.players.some(player => {
            if (player.fd == PlayerManager.instance.selfFD) {
                if (player.isLeader == true) {
                    btn.btntype = RoomBtn.START;
                }
                else if (player.isReady == true) {
                    btn.btntype = RoomBtn.UNREADY;
                }
                else {
                    btn.btntype = RoomBtn.READY;
                }

                return true;
            }

        });

        // for (let i = 0; i < room.players.length; i++) {
        //     if (room.players[i].fd != PlayerManager.instance.selfFD)
        //         continue;

        //     if (room.players[i].isLeader == true) {
        //         btn.btntype = RoomBtn.START;
        //     }
        //     else if (room.players[i].isReady == true) {
        //         btn.btntype = RoomBtn.UNREADY;
        //     }
        //     else {
        //         btn.btntype = RoomBtn.READY;
        //     }
        // }
        this.readyBtn.active = true;
    }

    /**處理READY */
    cmd_READY(key: string, value: string) {
        let ready = JSON.parse(value);
        log(ready);

        for (let i = 0; i < this.node.children.length; i++) {

            let playerinfo = this.node.children[i].getComponent(PlayerInRoom);
            if (playerinfo.fd == ready.fd) {
                playerinfo.isready = ready.isReady;
            }
        }

        GE.dispatchCustomEvent("Ready", ready);
    }

    /**處理UNREADY */
    cmd_UNREADY(key: string, value: string) {
        let unready = JSON.parse(value);
        log(unready);

        for (let i = 0; i < this.node.children.length; i++) {

            let playerinfo = this.node.children[i].getComponent(PlayerInRoom);
            if (playerinfo.fd == unready.fd) {
                playerinfo.isready = unready.isReady;
            }
        }

        GE.dispatchCustomEvent("Unready", unready);
    }

    protected onDisable(): void {
        SocketBridge.removeSocketKeyDispatch(this);
    }
}

