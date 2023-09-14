import { _decorator, Camera, Canvas, Component, director, EventKeyboard, EventMouse, find, Input, input, instantiate, KeyCode, Label, log, Pool, Prefab, Scene, Vec2, } from 'cc';
import { SocketBridge } from '../../Plugins/SocketBridge';
import { PlayerManager } from './PlayerManager';
import { PoolManager } from './PoolManager';
const { ccclass, property } = _decorator;



//#region 移動相關定義與Map
export enum MoveState {
    Stop,
    Up,
    Down,
    Left,
    Right,
}

//輸入按鍵與玩家移動的pairMap
const KeyMoveMap = new Map([
    [KeyCode.ARROW_UP, MoveState.Up], [KeyCode.KEY_W, MoveState.Up],
    [KeyCode.ARROW_DOWN, MoveState.Down], [KeyCode.KEY_S, MoveState.Down],
    [KeyCode.ARROW_LEFT, MoveState.Left], [KeyCode.KEY_A, MoveState.Left],
    [KeyCode.ARROW_RIGHT, MoveState.Right], [KeyCode.KEY_D, MoveState.Right],
])

//#endregion

//#region Action相關定義與Map
//記得跟伺服器同步
export enum ActionState {
    None,
    PlaceBomb = "PLACE_BOMB",
}

//輸入按鍵與玩家其他行為的pairMap
const KeyActMap = new Map([
    [KeyCode.SPACE, ActionState.PlaceBomb]
])
//#endregion

/**與後端溝通的CMD，必須全大寫 */
export enum BackendCMD {
    /**後端->前端, 有人狀態更新了 */
    UPDATE_POSITION = "UPDATE_POSITION",
    /**後端->前端, 有人放炸彈了 */
    BOMB = "BOMB",
    /**後端->前端, 某顆炸彈炸了*/
    EXPOLDE = "EXPOLDE",
    /**前端->後端, 我移動了 */
    MOVE = "MOVE",
    /**前端->後端, 我要放炸彈了 */
    PLACE_BOMB = "PLACE_BOMB",
}

/**內部事件 */
export enum InternalCMD {
    /**Input->本機移動 */
    Move = "Move",
    /**本機玩家定期狀態更新->Input->後端 */
    RoutineUpdate = "RoutineUpdate",
    /**後端->Input->本機更新位置 */
    Update = "Update",
    /**Input->AbstractAct */
    Act = "Act",
}

export enum InputState {
    Pause = "Pause",
    Resume = "Resume"
}


/**
 * 專門處理玩家按鍵輸入、按鍵優先權、組合鍵與伺服器輸入
 */
@ccclass('InputManager')
export class InputManager extends Component {

    @property(Camera)
    public camera: Camera

    private _MoveVecMap: Map<MoveState, Vec2> = new Map();
    private _moveInputList: MoveState[] = [];

    private _inputState: InputState = InputState.Pause;

    @property(Label)
    public gameTime: Label

    @property(Label)
    public readyTime: Label

    //HACK: 待刪除
    private _pingMS = 0;
    private _pongMS = 0;
    private _pingPongMS = 0;


    @property(Prefab)
    public PoolManagerPrefab: Prefab


    protected update(dt: number): void {

    }
    //#region 玩家鍵盤輸入相關

    /**玩家最新按下的會是玩家最優先做的事 */
    onKeyDown(event: EventKeyboard): void {
        if (this._inputState == InputState.Pause)
            return;
        if (KeyMoveMap.has(event.keyCode)) {
            //直接走更新的流程
            this.updateMoveInputList(true, event.keyCode);
        }
    }

    /**玩家鬆開某個按鍵 */
    onKeyUp(event: EventKeyboard): void {
        if (this._inputState == InputState.Pause)
            return;
        if (KeyMoveMap.has(event.keyCode)) {
            //直接走更新的流程
            this.updateMoveInputList(false, event.keyCode);
        }
        else if (KeyActMap.has(event.keyCode)) {
            //待處理(如果有需要放開才做的動作)
        }
    }
    //#endregion

    //#region 伺服器與更新相關

    /**
     * 來自後端的狀態更新
     * @param key 
     * @param value 
     */
    cmd_Update(key: string, value: string) {
        log(value);
        GE.dispatchCustomEvent(BackendCMD.UPDATE_POSITION, value);
    }

    /**
     * 按下小鍵盤的. 可以得知ping值
     * @param key 
     * @param value 
     */
    cmd_Pong(key: string, value: string) {
        this._pongMS = new Date().getTime();
        log(`end pong at ${this._pongMS}`);
        log(`ping pong MS: ${this._pongMS - this._pingMS}`);
    }




    //本機玩家移動
    onPlayerUpdate(playerLiteJson: string) {
        //狀態改變，但預設KEY是MOVE
        SocketBridge.sendString(`${BackendCMD.MOVE} ${playerLiteJson}`)
    }

    /**
     * 本回合結束
     * @param key 
     * @param value 
     */
    cmd_ROUND_END(key: string, value: string) {
        let obj = JSON.parse(value);
        let timesUp = obj.timesUp as boolean;
        let survivorFd = obj.survivorFd as number[];
        let nextRound = obj.nextRound as number;
        PlayerManager.instance.roundEnd = obj;
    }

    /**
     * 
     * @param key 
     * @param value 
     */
    cmd_ACHIEVEMENT_DATA(key: string, value: string) {


        let temp = {
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
                "killedPlayers": 0,
                "totalEarnedMoney": 1000,
                "fd": 456,
                "current_money": 500
            }]
        }

        PlayerManager.instance.achievements = JSON.parse(value);
        //HACK:奇妙的時間差寫法
        this.scheduleOnce(()=>{director.loadScene("achievement");},2)
    }

    /**
  * 
  * @param key 
  * @param value 
  */
    cmd_FINAL_ACHIEVEMENT(key: string, value: string) {
        let temp = {
            "final_achievement_info": [{
                "fd": 56, "name": "a", "achievement": [
                    "\u70b8\u5f48\u4f9b\u61c9\u5546", "\u706b\u529b\u8986\u84cb", "\u5730\u5f62\u7834\u58de\u738b", "\u6bba\u795e", "\u5bcc\u53ef\u6575\u570b"]
            }, {
                "fd": 57, "name": "b", "achievement": [
                    "\u7121\u540d\u5c0f\u5352"]
            }]
        }
        let obj = JSON.parse(value);
        
        PlayerManager.instance.achievementsString = obj;
        //HACK:奇妙的時間差寫法
        this.scheduleOnce(()=>{director.loadScene("achievement");},1)
    }

    /**
     * 
     * 在預備開始之前就會載入這些資訊
     * @param key 
     * @param value 
     */
    //XXX:之後考慮將Parse移到Model內部，各自處理
    cmd_PLAYER_ATTRIBUTE(key: string, value: string) {
        log(value);
        let obj = {
            "player_attribute": {
                "123": {
                    "passive": {
                        "max_health": 100,
                        "speed": 1,
                        "armor": 1,
                        "shield": 0,
                        "bomb_range": 3,
                        "bomb_power": 50,
                        "bomb_limit": 5,
                        "land_mine": 0
                    },
                    "active": {
                        "ice_attack": 0,
                        "shoot_bullet": 0,
                        "remote_trigger_bomb": 0,
                        "flash": 0,
                        "pass_through": 0,
                        "immune_star": 0,
                        "time_stop": 0,
                        "god_punish": 0,
                        "revive": 0
                    },
                    "current_health": 100
                },
                "456": {
                    "passive": {
                        "max_health": 100,
                        "speed": 1,
                        "armor": 1,
                        "shield": 0,
                        "bomb_range": 3,
                        "bomb_power": 50,
                        "bomb_limit": 5,
                        "land_mine": 0
                    },
                    "active": {
                        "ice_attack": 0,
                        "shoot_bullet": 0,
                        "remote_trigger_bomb": 0,
                        "flash": 0,
                        "pass_through": 0,
                        "immune_star": 0,
                        "time_stop": 0,
                        "god_punish": 0,
                        "revive": 0
                    },
                    "current_health": 100
                }
            }
        }
        obj = JSON.parse(value).player_attribute;
        for (const fdStr in obj) {
            let i = Number.parseInt(fdStr)
            let playerModel = PlayerManager.instance.playerModelsMap.get(i);
            let objPlayer = obj[fdStr];
            playerModel.fd = i;
            playerModel.currentHealth = objPlayer.current_health;
            playerModel.money = objPlayer.money;

            //#region passive
            let p = objPlayer.passive;
            playerModel.maxHealth = p.max_health;
            playerModel.speed = p.speed;
            playerModel.armor = p.armor;
            playerModel.shield = p.shield;
            playerModel.bombRange = p.bomb_range;
            playerModel.bombPower = p.bomb_power;
            playerModel.bombLimit = p.bomb_limit;
            playerModel.landMine = p.land_mine;
            //#endregion

            //#region active
            let a = objPlayer.active;
            playerModel.iceAttack = a.ice_attack;
            playerModel.shootBullet = a.shoot_bullet;
            playerModel.remoteTriggerBomb = a.remote_trigger_bomb;
            playerModel.flash = a.flash;
            playerModel.passThrough = a.pass_through;
            playerModel.immuneStar = a.immune_star;
            playerModel.timeStop = a.time_stop;
            playerModel.godPunish = a.god_punish;
            playerModel.revive = a.revive;
            //#endregion

            GE.dispatchCustomEvent("PlayerAttrubuteConstructed", playerModel);
            GE.dispatchCustomEvent("Attribute", [{ fd: i, attribute: playerModel }]);

        }
    }

    //#endregion

    //#region 移動相關

    /**
     * 更新MoveStateMap
     * @param keyCode 按鍵
     * @param activate 壓下或放開
    */
    private updateMoveInputList(activate: boolean, keyCode: KeyCode) {
        let newState = KeyMoveMap.get(keyCode);

        //True ：加入inputList 
        //False：從inputList刪除
        if (activate)
            this._moveInputList.unshift(newState);
        else
            this._moveInputList = this._moveInputList.filter((element) => {
                return element != newState;
            });

        //更新移動按鍵
        this.updateMoveState();
    }

    /**
     * 對移動的輸入列表做處理與狀態更新
     */
    private updateMoveState() {
        let newState: Vec2 = Vec2.ZERO;
        //先看前兩個新按鍵
        if (this._moveInputList.length >= 2) {

            let NewestVec = this._MoveVecMap.get(this._moveInputList[0]).clone();
            let SecondVec = this._MoveVecMap.get(this._moveInputList[1]).clone();

            let temp = NewestVec.cross(SecondVec);
            if (temp != 0) //兩個呈90度，可以混合變成斜著走
                newState = NewestVec.add(SecondVec);
            else //兩個按鍵是剛好相反方向，選最新按下的按鍵
                newState = NewestVec;
        }
        //按一個很單純
        else if (this._moveInputList.length == 1)
            newState = this._MoveVecMap.get(this._moveInputList[0]).clone();
        //按鍵=0 reset
        else
            newState = Vec2.ZERO;

        //保留方向並確保不會超過原來速度
        newState.normalize();

        //讓自己移動
        GE.dispatchCustomEvent(InternalCMD.Move, newState);
    }


    cmd_GAME_TIME(key: string, value: string) {
        let obj = JSON.parse(value);
        let time = obj.time
        //XXX: 在這裡直接做顯然不太好
        this.gameTime.string = (time / 1000).toString();
    }

    cmd_READY_TIME(key: string, value: string) {
        let obj = JSON.parse(value);
        let time = obj.time;
        //XXX:同上
        this.readyTime.node.active = true;
        this.readyTime.string = (time / 1000).toString();
        if (this.readyTime.string == "0") {
            this.readyTime.string = "";
            this.readyTime.node.active = false;
            GE.dispatchCustomEvent(InputState.Resume);
        }
    }
    //#endregion

    protected onDisable(): void {
        GE.removeCustomEvent(InternalCMD.RoutineUpdate, (model: string) => this.onPlayerUpdate(model), this);
        GE.removeCustomEvent(InputState.Pause, () => { this._inputState = InputState.Pause }, this);
        GE.removeCustomEvent(InputState.Resume, () => { this._inputState = InputState.Resume }, this);
        SocketBridge.removeSocketKeyDispatch(this);
    }

    protected onLoad(): void {

        if (!PoolManager.instance) {
            instantiate(this.PoolManagerPrefab).parent = this.node.parent.getComponentInChildren(Canvas).node;
        }
        //聽伺服器的事件
        //Update:狀態更新，包含移動或是死亡
        //Act:行為通知，包含放炸彈
        //Join:玩家加入
        SocketBridge.instance;
        SocketBridge.addSocketKeyDispatch(this, {
            [BackendCMD.UPDATE_POSITION]: this.cmd_Update,
            "PONG": this.cmd_Pong,
            "ROUND_END": this.cmd_ROUND_END,
            "ACHIEVEMENT_DATA": this.cmd_ACHIEVEMENT_DATA,
            "FINAL_ACHIEVEMENT": this.cmd_FINAL_ACHIEVEMENT,
            "PLAYER_ATTRIBUTE": this.cmd_PLAYER_ATTRIBUTE,
            "GAME_TIME": this.cmd_GAME_TIME,
            "READY_TIME": this.cmd_READY_TIME,
        });

        //按鍵的Down Up 註冊
        input.on(Input.EventType.KEY_DOWN, this.onKeyDown, this);
        input.on(Input.EventType.KEY_UP, this.onKeyUp, this);

        //玩家移動與變動量的pairMap
        let UNIT_X = Vec2.UNIT_X.clone();
        let UNIT_Y = Vec2.UNIT_Y.clone();
        let UNIT_N_X = UNIT_X.clone().multiplyScalar(-1);
        let UNIT_N_Y = UNIT_Y.clone().multiplyScalar(-1);
        this._MoveVecMap = new Map([
            [MoveState.Up, UNIT_Y], [MoveState.Down, UNIT_N_Y],
            [MoveState.Left, UNIT_N_X], [MoveState.Right, UNIT_X]
        ]);

        //聽玩家的狀態改變
        GE.addListener(InternalCMD.RoutineUpdate, (model: string) => this.onPlayerUpdate(model), this);
        GE.addListener(InputState.Resume, () => { this._inputState = InputState.Resume }, this);
        GE.addListener(InputState.Pause, () => { this._inputState = InputState.Pause }, this);
    }
}