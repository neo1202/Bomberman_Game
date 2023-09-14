import { _decorator, Component, EventKeyboard, Input, input, instantiate, KeyCode, log, Node, Prefab, Vec3 } from 'cc';
import { WallController, wallSprite } from './WallController';
import { SocketBridge } from '../../Plugins/SocketBridge';
import { InputState } from '../Managers/InputManager';
const { ccclass, property } = _decorator;

@ccclass('GenerateMap')
export class GenerateMap extends Component {

    @property(Prefab)
    ground: Prefab;

    start() {
        SocketBridge.sendString(`LOADSCENE ${JSON.stringify({ "message": "loadscene_complete" })}`)
        GE.dispatchCustomEvent(InputState.Pause);
        log("send LOADSCENE");

        SocketBridge.addSocketKeyDispatch(this, {
            "MAP": this.cmd_MAP,
        })
    }

    update(deltaTime: number) {

    }

    /**處理MAP，生地圖*/
    cmd_MAP(key: string, value: string) {
        log("map");
        let map = JSON.parse(value);
        log(map);
        GE.dispatchCustomEvent("Start", map.playerinfo);

        //生地板
        for (let i = 0; i < map.map.height; i++) {
            for (let j = 0; j < map.map.width; j++) {


                let addGround = instantiate(this.ground);
                addGround.parent = this.node;
                addGround.setPosition(32 * j, - 32 * i, 0);

                let wall = addGround.getComponent(WallController);
                wall.grid = [i,j]
                wall.wallsprite = map.map.grid[i][j];

            }
        }

        //生牆壁
        //橫的
        for (let i = 0; i < ((map.map.width + 2) * 2); i++) {
            let addWall = instantiate(this.ground);
            addWall.parent = this.node;

            if (i < (map.map.width + 2))
                addWall.setPosition(-32 + 32 * i, 32, 0);
            else
                addWall.setPosition(-32 + 32 * (i - (map.map.width + 2)), -32 * map.map.height, 0);

            let wall = addWall.getComponent(WallController);
            wall.wallsprite = wallSprite.STONE;
        }
        //直的
        for (let i = 0; i < (map.map.height * 2); i++) {
            let addWall = instantiate(this.ground);
            addWall.parent = this.node;

            if (i < map.map.height)
                addWall.setPosition(-32, - 32 * i, 0);
            else
                addWall.setPosition(32 * map.map.width, -32 * (i - map.map.height), 0);

            let wall = addWall.getComponent(WallController);
            wall.wallsprite = wallSprite.STONE;
        }

        //地圖置中
        let mapPosX = (32 * map.map.width / 2) - 16;
        let mapPosY = (32 * map.map.height / 2) - 16;
        let mapPos = new Vec3(-mapPosX, mapPosY, 0)
        this.node.setPosition(mapPos);
        GE.dispatchCustomEvent("MapPos", mapPos);
    }
    protected onDisable(): void {
        SocketBridge.removeSocketKeyDispatch(this);
    }
}

