import { _decorator, Component, instantiate, log, Node, Prefab, sp } from 'cc';
import { WallController } from './Map/WallController';
import { GameItemController } from './Map/GameItemController';
import { SocketBridge } from '../Plugins/SocketBridge';
const { ccclass, property } = _decorator;

@ccclass('GenerateItem')
export class GenerateItem extends Component {

    @property(Node)
    map: Node;

    @property(Prefab)
    gameitem: Prefab;

    start() {

        GE.addListener("MapPos", (mapPos) => {
            this.node.setPosition(mapPos);
        }, this)

        GE.addListener("GenerateItem", (items) => {

            for (let i = 0; i < items.length; i++) {

                let additem = instantiate(this.gameitem);
                let item = additem.getComponent(GameItemController);

                for (let j = 0; j < this.map.children.length; j++) {

                    let ch = this.map.children[j];
                    let wall = ch.getComponent(WallController)

                    if (wall.grid[0] == items[i].place[0] && wall.grid[1] == items[i].place[1]) {
                        item.place[0] = wall.grid[0];
                        item.place[1] = wall.grid[1];
                        additem.setPosition(ch.position.x, ch.position.y);
                    }
                }
                additem.parent = this.node;
                item.itemsprite = items[i].item;

            }

        }, this);

        SocketBridge.addSocketKeyDispatch(this, {
            "ITEM_PICKUP": this.cmd_ITEMPICKUP,
        })

    }

    update(deltaTime: number) {

    }

    /**處理撿到道具 */
    cmd_ITEMPICKUP(key: string, value: string) {
        let itempickup = JSON.parse(value);
        log(itempickup);

        //效果
        GE.dispatchCustomEvent("ItemAttribute", itempickup.playerAttribute);

        //被撿到的道具消失
        let ch = this.node.children;
        for (let i = 0; i < ch.length; i++) {
            let controller = ch[i].getComponent(GameItemController);
            let place = controller.place;
            if (place[0] == itempickup.itemplace[0] && place[1] == itempickup.itemplace[1]) {
                ch[i].destroy();
            }
        }



    }

    protected onDisable(): void {
        SocketBridge.removeSocketKeyDispatch(this);
    }
}

