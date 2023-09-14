import { _decorator, Component } from 'cc';
const { ccclass } = _decorator;

@ccclass('GEDestroy')
export class GEDestroy extends Component {

    private _registeredArray: any[] = [];

    addEventParam(obj: any) {
        // 防止node沒有跑過onLoad
        if (!this._registeredArray)
            this._registeredArray = [];

        this._registeredArray.push(obj);
    }
    
    onDestroy() {
        this._registeredArray && this._registeredArray.forEach(cur => {
            GE.removeCustomEvent(cur.name, cur.func, cur.target);
        });
    }
}