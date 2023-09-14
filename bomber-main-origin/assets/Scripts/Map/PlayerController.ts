import { _decorator, CCBoolean, CCFloat, CCInteger, CircleCollider2D, Collider2D, Component, Contact2DType, director, EPhysics2DDrawFlags, EventKeyboard, Input, input, IPhysics2DContact, KeyCode, Label, log, Node, NodePool, PhysicsSystem2D, ProgressBar, randomRange, randomRangeInt, resources, RigidBody2D, Sprite, tween, Vec2, Vec3, } from 'cc';
import { PlayerColor, PlayerModel } from '../PlayerModel';
import { PlayerManager } from '../Managers/PlayerManager';
import { BackendCMD, InternalCMD } from '../Managers/InputManager';
import { SocketBridge } from '../../Plugins/SocketBridge';
import { PoolManager } from '../Managers/PoolManager';
import { HitEffect } from '../../Artwork/Effects/HitEffect';

const { ccclass, property } = _decorator;

/**
 * 每個角色不論敵我都會掛這個script
 */
@ccclass('PlayerController')
export class PlayerController extends Component {
    /**這隻角色的model */
    public _model: PlayerModel;

    @property(ProgressBar)
    public healthBar: ProgressBar;

    @property(Node)
    public shadow: Node;

    @property(Label)
    public healthLabel: Label;
    /**新收到的model */
    // public _newModel: PlayerModelLite;

    @property(RigidBody2D)
    public rigidBody: RigidBody2D

    @property(CircleCollider2D)
    public collider: Collider2D

    @property(Label)
    public nameLable: Label;

    @property(Node)
    shield: Node;

    @property(Sprite)
    public sprite: Sprite

    @property(CCFloat)
    public velocity: number = 1

    @property(HitEffect)
    public hitEffect: HitEffect;

    /**是否為本機玩家 */
    public isLocalPlayer = false;

    /**間隔幾個Frame才更新座標給伺服器 */
    private _updateFrameInterval = 1;

    /**Frame計數器，達到Interval會歸零 */
    private _updateFrameCounter = 0;

    private _walkFrameCounter = 0;
    private _walkFrameInterval = 8;
    private _walkFrame = 0;

    /**透過輕便資料初始化角色，並放在地圖上 */
    public init(playerModelLite: PlayerModel) {
        this._model = new PlayerModel(playerModelLite.fd, playerModelLite.name);
        this._model.position = playerModelLite.position;
        this.node.setPosition(this._model.position.x, this._model.position.y);
        this._model.name = playerModelLite.name;
        this._updateFrameCounter = 0;
        this.nameLable.string = this._model.name;

        //本機玩家
        if (PlayerManager.instance.selfFD == this._model.fd) {
            //本機才註冊
            /**註冊InputManager的Move事件(用於接收只有移動方向的玩家輸入更新) */
            GE.addListener(InternalCMD.Move, (direction: Vec2) => this.onMoveDirectionChanged(direction), this);
            /**註冊被移動事件 */
            this.collider.on(Contact2DType.STAY_CONTACT, this.onStayContact, this);

            this.isLocalPlayer = true;
            (window as any).PC = this;

            //按空白鍵放炸彈
            input.on(Input.EventType.KEY_DOWN, (event: EventKeyboard) => {
                if (event.keyCode == KeyCode.SPACE) {
                    if (PlayerManager.instance.model.fd == this._model.fd) {
                        log("space");
                        SocketBridge.sendString(`PLACE_BOMB ${this._model.getJsonPosition()}`)
                    }
                }
            }, this)

        }
        else {//非本機
            this.isLocalPlayer = false;
        }


        /**註冊InputManager的Update事件(用於接收來自後端,且包含ID的狀態更新) */
        GE.addListener(BackendCMD.UPDATE_POSITION, (rawJson: any) => this.onStateUpdate(rawJson), this);
        GE.addListener("Deads", (deads: number[]) => this.onDead(deads), this);
        GE.addListener("Attribute", this.shieldActive, this);
        GE.addListener("Bomb&ShieldN", (info) => this.shieldActive1(info), this);
        GE.addListener("ItemAttribute", (playerAttribute) => this.shieldActive2(playerAttribute), this)
        GE.addListener("Damaged", this.onHurt, this);
        GE.addListener("Flame_damage", this.onHurt, this);
        GE.addListener("Flame_dead", (deads: number[]) => this.onDead(deads), this);
        GE.addListener("PlayerAttrubuteConstructed", this.onPlayerAttrubuteChange, this);
    }

    update(deltaTime: number) {
        //如果是其他玩家(非本機)，則在onStateUpdate內得到通知才處理
        if (this.isLocalPlayer) {
            //本機優先移動
            this.node.translate(
                new Vec3(this._model.moveVelocity.x * deltaTime * this._model.speed, this._model.moveVelocity.y * deltaTime * this._model.speed, 0)
            );
            this._model.position = new Vec2(this.node.position.x, this.node.position.y);
            if (++this._updateFrameCounter % this._updateFrameInterval == 0 &&
                this._model.moveVelocity.length() > 0) {
                GE.dispatchCustomEvent(InternalCMD.RoutineUpdate, this._model.getJsonPosition());
            }
        }
        else {
            //XXX:直接設定位置，下次可能要設定趨近於一半
            let difference = new Vec2(this._model.position.x - this.node.position.x, this._model.position.y - this.node.position.y);
            this._model.moveVelocity = difference;
            if (this._model.moveVelocity.length() != 0)
                this._model.faceDirection = this._model.moveVelocity.clone();
            this.node.setPosition(this._model.position.x, this._model.position.y);
        }
        this.onWalk();
    }


    //HACK:不是人類在看的切Frame法，彈性極低
    onWalk() {
        this._walkFrameCounter++;
        if (this._walkFrameCounter == this._walkFrameInterval) {
            this._walkFrameCounter = 0;
            if (this._model.moveVelocity.length() == 0)
                this._walkFrame = 0;
            this.sprite.spriteFrame =
                this.sprite.spriteAtlas.getSpriteFrames()[
                //一個行走圖有4張，共4個方向，所以*16就是每張的起點
                this._model.color * 16 +
                //面向方向
                (this._model.faceDirection.y < 0 ? 0 ://y向下0
                    this._model.faceDirection.y > 0 ? 3 ://y向上3
                        this._model.faceDirection.x < 0 ? 1 ://x向左1
                            this._model.faceDirection.x > 0 ? 2 : 0)//x向右1
                * 4 //每個行走圖有四張w
                + this._walkFrame
                ]
            this._walkFrame++;
            if (this._walkFrame >= 4)
                this._walkFrame = 0;
        }
    }

    /**
     * 每當本機移動方向改變時，這個函式就會被Input事件告知
     * @param direction 歸一化方向
     */
    onMoveDirectionChanged(direction: Vec2) {
        if (direction.length() != 0)
            this._model.faceDirection = direction;
        this._model.moveVelocity = new Vec2(direction.x * this.velocity, direction.y * this.velocity);

    }

    onDead(fds: number[]) {
        for (let i = 0; i < fds.length; i++) {
            if (this._model.fd == fds[i]) {
                PoolManager.instance.unuse(this.node);
                break;
            }
        }
    }

    /**當前血量改變時(不包含改變生命最大值) */
    onHurt(damagedPlayers: Array<{ fd: number, current_health: number, remaining_shield: number }>) {
        for (let i = 0; i < damagedPlayers.length; i++) {
            if (damagedPlayers[i].fd != this._model.fd)
                continue;


            /**血量變動數值*/
            let difference = damagedPlayers[i].current_health - this._model.currentHealth;

            /**血量比例*/
            let ratio = damagedPlayers[i].current_health / this._model.maxHealth;

            //血條
            this.healthBar.progress = ratio;

            //血條數字
            this.healthLabel.string = `${damagedPlayers[i].current_health}/${this._model.maxHealth}`;

            //TODO:扣血特效
            if (difference < 0) {
                this.hitEffect.doHit();
            }
            //TODO:回血特效
            else if (difference > 0) {

            }
            //TODO:沒事會接到事件嗎? 有幾種可能: 無敵但被打到or創建
            else {

            }


            this._model.currentHealth = damagedPlayers[i].current_health;

            if (damagedPlayers[i].remaining_shield != null) {
                if (damagedPlayers[i].remaining_shield > 0) {
                    this.shield.active = true;
                    break;
                }
                else
                    this.shield.active = false;
            }

            break;
        }
    }


    onPlayerAttrubuteChange(playerModel: PlayerModel) {
        if (playerModel.fd != this._model.fd)
            return;
        this._model = playerModel;
        this.healthLabel.string = `${this._model.currentHealth}/${this._model.maxHealth}`;
        this.healthBar.progress = this._model.currentHealth / this._model.maxHealth;
    }
    /**
     * 被告知狀態改變了，移動將在此處理
     * 情況有兩種

            1. 移動中就接收到下一個指令
                現象:角色突然加速或錯亂
                狀況:代表移動太慢了，或是網路比預期得快

            2. 移動完畢才接收到下一個指令
                現象:角色停一下才動
                狀況:移動速度剛好或是移動得太快，或是網路卡了
     * @param models
     */
    onStateUpdate(rawJson: any) {
        let obj = JSON.parse(rawJson);
        let ofd = 0;
        if (parseInt(obj.fd, 10))
            ofd = obj.fd;
        else
            throw "fd NaN: " + obj.fd;
        if (ofd == this._model.fd)
            this._model.position = new Vec2(obj.position.x, obj.position.y);
        return;
    }

    /**
     * 被外在力量移動了
     * @param selfCollider 
     * @param otherCollider 
     * @param contact 
     */
    onStayContact(selfCollider: Collider2D, otherCollider: Collider2D, contact: IPhysics2DContact | null) {
        if (otherCollider.node.name != "BombV2")
            return;
        this._model.position = new Vec2(this.node.position.x, this.node.position.y);
        GE.dispatchCustomEvent(InternalCMD.RoutineUpdate, this._model.getJsonPosition());
    }

    /**護盾出現與否 */
    shieldActive(PlayerShield: Array<{ fd: number, attribute }>) {
        this.healthBar.node.active = true;
        this.shadow.active = true;
        for (let i = 0; i < PlayerShield.length; i++) {
            if (PlayerShield[i].fd != this._model.fd)
                continue;
            if (PlayerShield[i].attribute.shield > 0) {
                this.shield.active = true;
                break;
            }
            else
                this.shield.active = false;
        }
    }

    //寫三次真的很智障
    /**爆炸後護盾出現與否 */
    shieldActive1(info) {
        for (let i = 0; i < info.length; i++) {
            if (info[i].fd == this._model.fd && info[i].shield_num > 0) {
                this.shield.active = true;
                break;
            }
            else
                this.shield.active = false;
        }
    }

    /**吃到道具後護盾出現與否 */
    shieldActive2(playerAttribute) {
        for (let i = 0; i < playerAttribute.length; i++) {
            log(playerAttribute[i].fd, this._model.fd, playerAttribute[i].attribute.passive.shield);
            if (playerAttribute[i].fd == this._model.fd && playerAttribute[i].attribute.passive.speed != 1) {
                //XXX:速度也在這裡處理
                PlayerManager.instance.model.speed = playerAttribute[i].attribute.passive.speed;
                this._model.speed = playerAttribute[i].attribute.passive.speed;
            }
            if (playerAttribute[i].fd == this._model.fd && playerAttribute[i].attribute.passive.shield > 0) {

                this.shield.active = true;
                break;
            }
            else
                this.shield.active = false;
        }
    }

    protected onDisable(): void {
        log("disable:" + this._model.fd);
        this.healthBar.node.active = false;
        this.shadow.active = false;
        this.collider.off(Contact2DType.STAY_CONTACT, this.onStayContact, this);
        GE.removeCustomEvent(InternalCMD.Move, (direction: Vec2) => this.onMoveDirectionChanged(direction), this);
        GE.removeCustomEvent(BackendCMD.UPDATE_POSITION, (rawJson: any) => this.onStateUpdate(rawJson), this);
        GE.removeCustomEvent("Deads", (deads: number[]) => this.onDead(deads), this);
        GE.removeCustomEvent("Attribute", this.shieldActive, this);
        GE.removeCustomEvent("Bomb&ShieldN", (info) => this.shieldActive1(info), this);
        GE.removeCustomEvent("ItemAttribute", (playerAttribute) => this.shieldActive2(playerAttribute), this)
        GE.removeCustomEvent("Damaged", this.onHurt, this);
        GE.removeCustomEvent("Flame_damage", this.onHurt, this);
        GE.removeCustomEvent("PlayerAttrubuteConstructed", this.onHurt, this);
    }
}