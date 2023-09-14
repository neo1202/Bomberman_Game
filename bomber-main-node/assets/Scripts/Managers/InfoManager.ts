import { _decorator, Component, instantiate, log, Node, Prefab } from 'cc';
import { PlayerManager } from './PlayerManager';
import { Infomation } from '../Infomation';
const { ccclass, property } = _decorator;

@ccclass('InfoManager')
export class InfoManager extends Component {

    @property(Prefab)
    infoBoard: Prefab;

    start() {

        const iterator = PlayerManager.instance.playerModelsMap.entries();
        for (let i = 0; i < PlayerManager.instance.playerModelsMap.size; i++) {
            let addboard = instantiate(this.infoBoard);
            addboard.parent = this.node;

            let x, y;
            if (i % 2 == 0)
                x = 400;
            else
                x = -400;

            if (i == 0 || i == 3)
                y = 120;
            else
                y = -120;
            addboard.setPosition(x, y);

            let info = addboard.getComponent(Infomation);
            info.fd = iterator.next().value[0];
        }

    }

    update(deltaTime: number) {

    }
}

