import { _decorator, BoxCollider2D, Collider2D, color, Component, Contact2DType, Enum, instantiate, log, Node, Prefab, Sprite, SpriteFrame } from 'cc';
import { SocketBridge } from '../../Plugins/SocketBridge';
import { InternalCMD } from '../Managers/InputManager';
const { ccclass, property, type } = _decorator;

export enum itemSprite {
    BOMBSUPPLIES = "BOMBSUPPLIES",
    FIRE = "FIRE",
    SHIELD = "SHIELD",
    SNEAKER = "SNEAKER",
    POWER = "POWER",
    COIN = "COIN",
}

@ccclass('GameItemController')
export class GameItemController extends Component {

    @property(SpriteFrame)
    bombsupplies: SpriteFrame;
    @property(SpriteFrame)
    fire: SpriteFrame;
    @property(SpriteFrame)
    shield: SpriteFrame;
    @property(SpriteFrame)
    sneaker: SpriteFrame;
    @property(SpriteFrame)
    power: SpriteFrame;
    @property(SpriteFrame)
    coin: SpriteFrame;

    @property(BoxCollider2D)
    collider: BoxCollider2D;

    @type(Enum(itemSprite))
    itemsprite: itemSprite;

    @property
    place = [];

    start() {

        let sprite = this.node.getComponent(Sprite);
        switch (this.itemsprite) {

            case itemSprite.BOMBSUPPLIES:
                sprite.spriteFrame = this.bombsupplies;
                break;

            case itemSprite.FIRE:
                sprite.spriteFrame = this.fire;
                break;

            case itemSprite.SHIELD:
                sprite.spriteFrame = this.shield;
                break;

            case itemSprite.SNEAKER:
                sprite.spriteFrame = this.sneaker;
                break;

            case itemSprite.POWER:
                sprite.spriteFrame = this.power;
                break;

            case itemSprite.COIN:
                sprite.spriteFrame = this.coin;
                break;
                
            default:
                sprite.spriteFrame = null;
                break;

        }

        this.collider.once(Contact2DType.BEGIN_CONTACT, this.onBeginContact, this);

    }

    update(deltaTime: number) {
        
    }

    onBeginContact (self: Collider2D, other: Collider2D){
        log(1);
        SocketBridge.sendString(`TOUCH_ITEM ${JSON.stringify({"itemplace": this.place})}`)
    }
}


