import { _decorator, Component, instantiate, Label, Node, Prefab, tween } from 'cc';
const { ccclass, property } = _decorator;

@ccclass('AchievementBadge')
export class AchievementBadge extends Component {

    @property(Prefab)
    public Badge: Prefab;

    @property([Node])
    AnchorPoints: Node[] = [];

    @property(Node)
    public No1Node:Node;

    public counter = 0;

    start() {
    }

    update(deltaTime: number) {

    }

    /**
     * 為個人加入稱號，同時有動畫
     * @param title 稱號
     */
    GetTitle(title: string) {
        //不管了，直接生
        let badgeNode = instantiate(this.Badge);
        let label = badgeNode.getComponentInChildren(Label);
        badgeNode.parent = this.node.parent;
        label.string = title;
        this.MoveTo(badgeNode);
    }

    MoveTo(node:Node){
        let des = this.AnchorPoints[this.counter++].position;
        tween(node)
            .to(0.2,{position:des})
            .start();
    }

    isTheBest(){
        this.No1Node.active = true;
        GE.dispatchCustomEvent("Shack",1);
    }

}

