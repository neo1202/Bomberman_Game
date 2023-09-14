import { _decorator, CCFloat, CCInteger, CCString, Component, Director, director, EventKeyboard, find, Input, input, KeyCode, log, Node, Scheduler, Vec3 } from 'cc';
import { PlayerModel } from '../PlayerModel';
import { PoolManager } from './PoolManager';
import { PlayerController } from '../Map/PlayerController';
import { SocketKeyMap } from '../../Plugins/SocketKeyMap';
import { SocketBridge } from '../../Plugins/SocketBridge';
const { ccclass, property } = _decorator;

@ccclass('PlayerManager')
export class PlayerManager extends Component {

    /**自己的資料 */
    public model: PlayerModel;

    /**所有人的資料 */
    public playerModelsMap: Map<number, PlayerModel> = new Map();

    public get selfFD(): number {
        return this.model.fd;
    }

    public getModel(fd: number): PlayerModel {
        return this.playerModelsMap.get(fd);
    }

    //XXX:趕時間寫法，之後要結構化
    public achievements = {
        "playerInfo": [{
            "totalPlaceBomb": 0,
            "totalDealDamage": 0,
            "destroyedBuildings": 0,
            "killedPlayers": 0,
            "totalEarnedMoney": 1000,
            "fd": 123,
            "current_money": 1000
        }, {
            "totalPlaceBomb": 3,
            "totalDealDamage": 270,
            "destroyedBuildings": 0,
            "killedPlayers": 1,
            "totalEarnedMoney": 1000,
            "fd": 456,
            "current_money": 500
        }]
    }
    public roundEnd = {
        "survivorFd": [123, 12, 4],
        "timesUp": false,
        "nextRound": 0
    }
    public achievementsString = {
        "final_achievement_info": [{
            "fd": 56, "name": "a", "achievement": [
                "\u70b8\u5f48\u4f9b\u61c9\u5546", "\u706b\u529b\u8986\u84cb", "\u5730\u5f62\u7834\u58de\u738b", "\u6bba\u795e", "\u5bcc\u53ef\u6575\u570b"]
        }, {
            "fd": 57, "name": "b", "achievement": [
                "\u7121\u540d\u5c0f\u5352"]
        }]
    };


    private static _instance: PlayerManager;
    public static get instance() {
        // if (this._instance) {
            return this._instance;
        // }
        
        let thisNode = find(PlayerManager.name);
        log(thisNode);
        this._instance = thisNode.getComponent(PlayerManager.name) as PlayerManager;
        this._instance.model = new PlayerModel();
        //實用技巧:可在web console 裡直接下PM來看實例化物件
        (window as any).PM = this._instance;
        return this._instance;
    }

    /**第一次連線時，設定本機 */
    private onConnect(fd: number) {
        this.model.fd = fd;
    }

    private onNameChange(name: string) {
        this.model.name = name;
    }

    private onRoomUpdate(room) {
        this.playerModelsMap.clear();
        for (let i = 0; i < room.players.length; i++) {
            let player = room.players[i];
            this.playerModelsMap.set(player.fd, new PlayerModel(player.fd, player.name));
            this.getModel(player.fd).color = i;
        }
    }
    protected start(): void {
        director.addPersistRootNode(this.node);
        GE.addListener("PlayerFdInit", (fd: number) => this.onConnect(fd), this);
        GE.addListener("PlayerNameInit", (name: string) => this.onNameChange(name), this);
        GE.addListener("Room", (room: any) => this.onRoomUpdate(room), this);
        GE.addListener("Start", (playerInfoPack: any) => this.onStart(playerInfoPack), this);
    }
    private onStart(playerInfoPack) {

        let playerinfo = playerInfoPack;
        for (let i = 0; i < playerinfo.length; i++) {

            /**當前索引取到的player */
            let player = playerinfo[i];

            //若出錯要注意一下，get不到，代表沒有先set就跑進來了，可能是流程問題
            let modelLite = this.playerModelsMap.get(player.fd);
            if (!modelLite)
                throw "playerModelsMap 找不到" + player.fd + "\r\n" + this.playerModelsMap;

            modelLite.position = player.position;
            modelLite.color = i;

            let playerNode = PoolManager.instance.reuse("PlayerController");

            playerNode.setSiblingIndex(6);

            let tempPlayerController = playerNode.getComponent(PlayerController);
            tempPlayerController.init(modelLite);
            tempPlayerController.node.position = new Vec3(modelLite.position.x, modelLite.position.y);
        }
    }


    protected onLoad(): void {
        PlayerManager._instance = this;
        this.model = new PlayerModel();
    }



    protected onDestroy(): void {
        PlayerManager._instance = null;        
    }

    public commandCrossScene(command: string, targetScene: string) {
        let callback =  ()=> {
            if (director.getScene().name == targetScene) {
                SocketBridge.sendStringToSelf(command);
                this.unschedule(callback);
            }
        }
        this.schedule(callback, 0.5, 120, 1);
    }
}