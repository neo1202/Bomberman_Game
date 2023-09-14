import { _decorator, BoxCollider2D, Color, Component, Node, Sprite, SpriteFrame, tween, Animation, log, instantiate, Prefab, NodeEventType, Enum, CCInteger } from 'cc';
const { ccclass, property, type } = _decorator;

export enum wallSprite {
    ROAD,
    WOOD,
    STONE,
}

@ccclass('WallController')
export class WallController extends Component {

    //接收有改變的地形的資料 > 找出地形的位置 > 找到方塊 > 執行牆壁碎裂動畫

    @property(SpriteFrame)
    road: SpriteFrame;
    @property(SpriteFrame)
    Ydestroy: SpriteFrame;
    @property(SpriteFrame)
    Ndestroy: SpriteFrame;

    @property(Prefab)
    wall: Prefab;
    @property(Prefab)
    gameitem: Prefab;

    @property([CCInteger])
    grid = [];

    @type(Enum(wallSprite))
    wallsprite: wallSprite;

    start() {

        let sprite = this.node.getComponent(Sprite);
        let collider = this.node.getComponent(BoxCollider2D);

        switch (this.wallsprite) {

            case wallSprite.ROAD:
                sprite.spriteFrame = this.road;
                sprite.color = new Color(79, 162, 100);
                collider.enabled = false;
                break;

            case wallSprite.WOOD:
                sprite.spriteFrame = this.road;
                sprite.color = new Color(79, 162, 100);
                let addWall = instantiate(this.wall);
                addWall.parent = this.node;
                break;

            case wallSprite.STONE:
                sprite.spriteFrame = this.Ndestroy;
                break;

        }

        //碎牆壁
        if (this.wallsprite == wallSprite.WOOD) {

            let detroyAnim = this.node.getChildByName("canDestroyWall").getComponent(Animation);

            GE.addListener("DestroyWall", (destroy) => {
                for (let i = 0; i < destroy.length; i++) {
                    if (destroy[i][0] == this.grid[0] && destroy[i][1] == this.grid[1]) {
                        
                        detroyAnim.play('WallDestroy');
                    }
                }
            }, this);

            /*this.node.once(Node.EventType.MOUSE_UP, () => {

                detroyAnim.play('WallDestroy');

                tween(sprite)
                    .to(0.15, { color: new Color(0, 0, 0, 150) })
                    .to(0.1, { color: new Color(0, 0, 0, 200) })
                    .to(0.1, { color: new Color(0, 0, 0, 80) })
                    .to(0.05, { color: new Color(0, 0, 0, 120) })
                    .to(0.05, { color: new Color(0, 0, 0, 0) })
                    .call(() => {
                        
                        sprite.spriteFrame = this.road;
                        collider.enabled = false;
                    })
                    .to(0.1, { color: new Color(255, 255, 255, 255) })
                    .start();

            }, this);*/

            detroyAnim.on(Animation.EventType.FINISHED, () => {
                collider.enabled = false;
            })

        }


    }

    update(deltaTime: number) {

    }

}

