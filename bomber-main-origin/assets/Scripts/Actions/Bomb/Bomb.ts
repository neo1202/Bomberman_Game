import { _decorator, BoxCollider2D, Collider2D, Color, Component, Contact2DType, ICollisionEvent, IPhysics2DContact, ITriggerEvent, log, math, Node, PhysicsSystem2D, Prefab, resources, RigidBody2D, Sprite, SpriteAtlas, SpriteFrame, sys, Tween, tween, Vec3 } from 'cc';
import { PoolManager } from '../../Managers/PoolManager';
import { PlayerModel } from '../../PlayerModel';
const { ccclass, property } = _decorator;


@ccclass('Bomb')
export class Bomb extends Component {

    /**請使用playerModel.clone來賦值，避免引用 */
    source: PlayerModel;
    power: number;

    private _bombFrames: SpriteFrame[]

    @property(BoxCollider2D)
    collider: BoxCollider2D;

    @property(Sprite)
    sprite: Sprite;

    @property([SpriteFrame])
    blastSprite: SpriteFrame[];

    @property(Node)
    bombNode: Node;

    @property(Node)
    bombShadowNode: Node;

    @property
    id: number;

    private color1 = new Color(255, 255, 255, 255);

    protected onLoad(): void {
        this._bombTween();
    }

    protected start(): void {
        this.collider.once(Contact2DType.END_CONTACT, this.onEndContact, this);

        GE.addListener("ExplodeID", (id: number) => {
            if (id == this.id) {
                this.node.destroy();
            }
        }, this)
    }

    private onEndContact(selfCollider: Collider2D, otherCollider: Collider2D, contact: IPhysics2DContact | null) {
        //FIXME:任何人離開都會讓他變成false，導致角色被炸彈包圍後連續擠壓到安全的位置
        this._setPass(false);
    }

    protected onEnable(): void {
        let count = 0;
        this._setPass(true);
    }

    private _setPass(isPass: boolean) {
        //開關sensor模式，變成一般的炸彈或是可通過，需要apply讓他重讀
        this.collider.sensor = isPass;
        this.collider.apply();
    }

    protected onDisable(): void {
        this.collider.off(Contact2DType.END_CONTACT, this.onEndContact, this);
        this._setPass(true);
    }

    private _bombTween() {
        // 炸彈Tween
        if (this.bombNode) {
            // 炸彈呼吸
            tween(this.bombNode)
                .sequence(
                    tween(this.node).to(0.25, {
                        scale: new Vec3(1.05, 1.05, 1),
                    }),
                    tween(this.node).to(0.25, {
                        scale: new Vec3(1, 1, 1),
                    })
                ).repeat(3)
                .sequence(
                    tween(this.node).to(0.125, {
                        scale: new Vec3(1.1, 1.1, 1),
                    }),
                    tween(this.node).to(0.125, {
                        scale: new Vec3(1, 1, 1),
                    })
                ).repeat(99)
                .start();

            // 炸彈影子呼吸
            tween(this.bombShadowNode)
                .sequence(
                    tween(this.node).to(0.25, {
                        scale: new Vec3(1.03, 0.3, 1),
                    }),
                    tween(this.node).to(0.25, {
                        scale: new Vec3(1, 0.3, 1),
                    })
                ).repeat(3)
                .sequence(
                    tween(this.node).to(0.125, {
                        scale: new Vec3(1.08, 0.3, 1),
                    }),
                    tween(this.node).to(0.125, {
                        scale: new Vec3(1, 0.3, 1),
                    })
                ).repeat(99)
                .start();

            // 顏色
            var bombSprite = this.bombNode.getComponent(Sprite)!;

            var colorOffset = 0;
            tween(this.color1)
                .sequence(
                    tween(this.color1).to(0.25, { r: 255, g: 255, b: 255 }, {
                        onUpdate: () => {
                            if (this.color1)
                                bombSprite.color = this.color1;
                        }
                    }),
                    tween(this.color1).to(0.25, { r: 200, g: 200, b: 200 }, {
                        onUpdate: () => {
                            if (this.color1)
                                bombSprite.color = this.color1;
                        }
                    }),
                ).repeat(3)
                .sequence(
                    tween(this.color1).to(0.125, { r: 255, g: 50, b: 50 }, {
                        onUpdate: () => {
                            if (this.color1)
                                bombSprite.color = this.color1;
                        }
                    }),
                    tween(this.color1).to(0.125, { r: 255, g: 200, b: 200 }, {
                        onUpdate: () => {
                            if (this.color1)
                                bombSprite.color = this.color1;
                        }
                    }),
                ).repeat(99) // 不能用repeatForever會有bug
                .start();
        }
    }
}

