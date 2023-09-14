import { _decorator, Component, Enum, instantiate, log, Node, Prefab, Vec2, Vec3 } from 'cc';
import { SocketBridge } from '../Plugins/SocketBridge';
import { Bomb } from './Actions/Bomb/Bomb';
import { WallController } from './Map/WallController';
import { BombFire, fireSprite } from './BombFire';
const { ccclass, property, type } = _decorator;

@ccclass('GenerateBomb')
export class GenerateBomb extends Component {

    @property(Node)
    map: Node;

    @property(Prefab)
    bomb: Prefab;
    @property(Prefab)
    explodefire: Prefab;

    start() {

        SocketBridge.addSocketKeyDispatch(this, {
            "BOMB": this.cmd_BOMB,
            "EXPLODE": this.cmd_EXPLODE,
            "FLAME_SPREAD_PLAYER": this.cmd_FLAME_SPREAD_PLAYER,
        })

        GE.addListener("MapPos", (mapPos) => {
            this.node.setPosition(mapPos);
        }, this)


    }

    update(deltaTime: number) {

    }

    /**處理BOMB，炸彈出現 */
    cmd_BOMB(key: string, value: string) {
        let bomb = JSON.parse(value);
        log(bomb);

        let addbomb = instantiate(this.bomb);
        let bombinfo = addbomb.getComponent(Bomb);
        bombinfo.id = bomb.bombID;

        for (let i = 0; i < this.map.children.length; i++) {

            let ch = this.map.children[i];
            let wall = ch.getComponent(WallController)

            if (wall.grid[0] == bomb.place[0] && wall.grid[1] == bomb.place[1]) {
                addbomb.setPosition(ch.position.x, ch.position.y);
            }

        }
        addbomb.parent = this.node;

        //炸彈數量減少
        GE.dispatchCustomEvent("BombN", bomb);
    }

    /**處理爆炸之後丟過來的包 */
    cmd_EXPLODE(key: string, value: string) {
        let explode = JSON.parse(value);
        log(explode);

        //找哪個炸彈爆
        GE.dispatchCustomEvent("ExplodeID", explode.bombID);

        //炸彈爆炸影響範圍
        let explodeRange = [];
        let center = [explode.explodeEffectRange.left[0], explode.explodeEffectRange.top[1], fireSprite.CENTER];
        explodeRange.push(center);

        for (let i = explode.explodeEffectRange.left[1]; i < center[1]; i++) {
            if (i == explode.explodeEffectRange.left[1])
                explodeRange.push([center[0], i, fireSprite.END, 0]);
            else
                explodeRange.push([center[0], i, fireSprite.STRAIGHT, 0]);
        }//左到中
        for (let i = explode.explodeEffectRange.right[1]; i > center[1]; i--) {
            if (i == explode.explodeEffectRange.right[1])
                explodeRange.push([center[0], i, fireSprite.END, 180]);
            else
                explodeRange.push([center[0], i, fireSprite.STRAIGHT, 180]);
        }//右到中
        for (let i = explode.explodeEffectRange.top[0]; i < center[0]; i++) {
            if (i == explode.explodeEffectRange.top[0])
                explodeRange.push([i, center[1], fireSprite.END, -90]);
            else
                explodeRange.push([i, center[1], fireSprite.STRAIGHT, -90]);
        }//上到中
        for (let i = explode.explodeEffectRange.bottom[0]; i > center[0]; i--) {
            if (i == explode.explodeEffectRange.bottom[0])
                explodeRange.push([i, center[1], fireSprite.END, 90]);
            else
                explodeRange.push([i, center[1], fireSprite.STRAIGHT, 90]);
        }//下到中

        log(explodeRange);

        //生火焰
        for (let i = 0; i < explodeRange.length; i++) {

            let addfire = instantiate(this.explodefire);
            addfire.parent = this.node;
            
            for (let j = 0; j < this.map.children.length; j++) {
                let ch = this.map.children[j];
                let wall = ch.getComponent(WallController)

                if (explodeRange[i][0] == wall.grid[0] && explodeRange[i][1] == wall.grid[1]) {
                    addfire.setPosition(ch.position.x, ch.position.y);
                }
            }

            let fire = addfire.getComponent(BombFire);
            fire.firesprite = explodeRange[i][2];

            addfire.setRotationFromEuler(new Vec3(0, 0, explodeRange[i][3]));
        }

        //誰死掉
        GE.dispatchCustomEvent("Deads", explode.deads as number[]);

        //找哪個牆壁碎掉
        GE.dispatchCustomEvent("DestroyWall", explode.destroy);

        //生道具
        this.scheduleOnce(() => {
            GE.dispatchCustomEvent("GenerateItem", explode.gameItem);
        }, 0.3)

        //玩家受到傷害
        GE.dispatchCustomEvent("Damaged", explode.damagedPlayers);

        //炸彈與盾牌數量改變
        GE.dispatchCustomEvent("Bomb&ShieldN", explode.playerInfo);
    }

    cmd_FLAME_SPREAD_PLAYER(key: string, value: string){
        let flame = JSON.parse(value);
        log(flame);

        GE.dispatchCustomEvent("Flame_damage", flame.damagedPlayers);

        //誰死掉
        GE.dispatchCustomEvent("Flame_dead", flame.deadPlayers);
    }

    protected onDisable(): void {
        SocketBridge.removeSocketKeyDispatch(this);
    }

}

