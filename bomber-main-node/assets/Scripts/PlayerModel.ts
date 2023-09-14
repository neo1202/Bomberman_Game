import { _decorator, CCInteger, Vec2, Vec3 } from "cc";
const { property } = _decorator;
let  PlayerEventArgs = { "difference": 0, "from": 0, "to": 0 };
let PlayerEventArgsVec = { "from": Vec2.ZERO, "to": Vec2.ZERO };
export enum PlayerColor{
    purple,
    red,
    cyan,
    brown
}
export class PlayerModel {
    constructor(fd?: number, name?: string) {
        this.fd = fd || 0;
        this.name = name;
    }
    //#region 被動數值
    public maxHealth = 100;
    public speed = 1;
    public armor = 1;
    public shield = 0;
    public position: Vec2;
    public bombRange = 3;
    public bombPower = 50;
    public bombLimit = 5;
    public landMine = 0;
    //#endregion

    //#region 
    public iceAttack = 0
    public shootBullet = 0
    public remoteTriggerBomb = 0
    public flash = 0
    public passThrough = 0
    public immuneStar = 0
    public timeStop = 0
    public godPunish = 0
    public revive = 0
    //#endregion

    public currentHealth = 100;

    //#region 雜項
    public faceDirection: Vec2 = new Vec2(0, 0);
    public moveVelocity: Vec2 = new Vec2(0, 0);
    public fd: number = 0;
    public name: string = "Hundo";
    public money: number = 0;
    //#endregion

    public color:PlayerColor;

    /**不確定有沒有更好的寫法 */
    public getPositionVec3(): Vec3 {
        return new Vec3(this.position.x, this.position.y, 0);
    }
    public getPower(): number {
        return this.bombPower;
    }

    /**
     * 完整版Json
     * @returns 
     */
    public getJson(): string {
        let str = JSON.stringify(this);
        return str;
    }

    public getJsonPosition(): string {
        let position = {
            position: {
                x: this.position.x,
                y: this.position.y
            }
        };
        return JSON.stringify(position);
    }
    /**
     * 從JSON轉換成model
     * @param JsonString 
     * @returns 
     */
    static Parse(JsonString: string): PlayerModel {
        return PlayerModel._clone(JSON.parse(JsonString) as PlayerModel);
    }

    /**deep clone */
    private static _clone(playerModel: PlayerModel): PlayerModel {
        let temp = new PlayerModel(playerModel.fd, playerModel.name);

        temp.position = new Vec2(playerModel.position.x, playerModel.position.y);
        temp.faceDirection = new Vec2(playerModel.faceDirection.x, playerModel.faceDirection.y);
        temp.moveVelocity = new Vec2(playerModel.moveVelocity.x, playerModel.moveVelocity.y);
        temp.bombPower = playerModel.bombPower;
        temp.currentHealth = playerModel.currentHealth;
        temp.money = playerModel.money;
        temp.color = playerModel.color;
        return temp;
    }


}