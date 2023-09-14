import { _decorator, CCInteger, Component, Label, Node } from 'cc';
const { ccclass, property } = _decorator;

@ccclass('PlayerInRoom')
export class PlayerInRoom extends Component {

    @property
    n: string;
    @property(CCInteger)
    fd: number;
    @property
    isleader: boolean;
    @property
    isready: boolean;

    start() {

        this.node.getChildByName("PlayerName").getComponent(Label).string = this.n;

        //如果是房主
        if(this.isleader == true){
            this.node.getChildByName("Star").active = true;
        }

        GE.addListener("Ready", this.CheckMark, this);
        GE.addListener("Unready", this.CheckMark, this);

    }

    update(deltaTime: number) {
        
    }

    /**表示準備好的勾號要不要出現 */
    CheckMark(){
        if(this.isready == true){
            this.node.getChildByName("CheckMark").active = true;
        }
        else{
            this.node.getChildByName("CheckMark").active = false;
        }
    }
}

