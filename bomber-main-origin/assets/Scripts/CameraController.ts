import { _decorator, Camera, Component, EventKeyboard, find, Input, KeyCode, log, Node, Quat, random, randomRangeInt, tween, Vec2, Vec3 } from 'cc';
const { ccclass, property } = _decorator;

@ccclass('CameraController')
export class CameraController extends Component {
    // @property(Node)

    public followTarget: Node;

    @property(Camera)
    public camera:Camera;

    public ShackPower = 10;
    start() {
        // GE.addListener("Shake", (power: number) => {
        //     this.shack(power);
        // }, this)

        
        // this.scheduleOnce(this.shack,1);

    }


    shack(power) {
        // log("Shack")

        // let originP = this.node.position;
        // let originR = this.node.angle;
        // let rdnX = randomRangeInt(originP.x - power * this.ShackPower, originP.x + power * this.ShackPower);
        // let rdnY = randomRangeInt(originP.y - power * this.ShackPower, originP.y + power * this.ShackPower);
        // let rdnZ = randomRangeInt(originP.z - power * this.ShackPower, originP.z + power * this.ShackPower);
        // let newP = new Vec3(rdnX, rdnY, rdnZ);
        // let temp: Quat;
        // this.node.position = newP;
        // this.node.angle = rdnZ;
        // this.scheduleOnce(()=>{
        //     this.node.position = originP;
        //     this.node.angle = originR;
        // },0.1)
    }

    update(deltaTime: number) {
    }
    protected lateUpdate(dt: number): void {

    }
}

