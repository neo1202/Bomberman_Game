import { _decorator, Component, Enum, Node, Sprite, SpriteFrame, Tween, tween, UIOpacity, Vec3} from 'cc';
const { ccclass, property, type } = _decorator;

export enum fireSprite {
    CENTER,
    STRAIGHT,
    END,
}

@ccclass('BombFire')
export class BombFire extends Component {

    @property(SpriteFrame)
    center: SpriteFrame;
    @property(SpriteFrame)
    straight: SpriteFrame;
    @property(SpriteFrame)
    end: SpriteFrame;

    @type(Enum(fireSprite))
    firesprite: fireSprite

    start() {
        var isCenter = false;
        let sprite = this.node.getComponent(Sprite);
        switch (this.firesprite) {

            case fireSprite.CENTER:
                isCenter = true;
                sprite.spriteFrame = this.center;
                break;

            case fireSprite.STRAIGHT:
                sprite.spriteFrame = this.straight;
                break;

            case fireSprite.END:
                sprite.spriteFrame = this.end;
                break;

            default:
                break;

        }
        var uiOpacity = this.node.addComponent(UIOpacity);

        if (!isCenter) {
            tween(this.node)
                .set({ scale: new Vec3(1, 0.8, 0) })
                .to(0.1, {
                    scale: new Vec3(1, 1.2, 1),
                })
                .sequence(
                    tween(this.node).to(0.05, {
                        scale: new Vec3(1, 1, 1),
                    }),
                    tween(this.node).to(0.05, {
                        scale: new Vec3(1, 1.2, 1),
                    })
                ).repeat(3)
                .to(0.1, {
                    scale: new Vec3(1, 0.8, 1),
                })
                .call(() => this.node.destroy())
                .start();
        } else {
            tween(this.node)
                .delay(0.5)
                .call(() => this.node.destroy())
                .start();
        }

        tween(uiOpacity)
            .to(0.1,
                { opacity: 200 }
            )
            .sequence(
                tween(uiOpacity).to(0.015,
                    { opacity: 200 }
                ),
                tween(uiOpacity).to(0.015,
                    { opacity: 255 }
                )
            ).repeatForever()
            .start();
    }

    update(deltaTime: number) {

    }
}

