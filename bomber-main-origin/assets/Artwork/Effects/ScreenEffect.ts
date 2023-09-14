import { _decorator, Component, Node, Material, tween, Sprite, Tween } from 'cc';
const { ccclass, property } = _decorator;

@ccclass('ScreenEffect')
export class ScreenEffect extends Component {
    private _numH: number = 0;
    private _material: Material = null!;

    private _tween: Tween<object>;

    start() {
        var sprite: Sprite = this.getComponent(Sprite);
        if (sprite) {
            this._material = sprite.materials[0];
            this._initEffect();
        }
    }

    _initEffect() {
        this._material.setProperty('u_dH', 0);
        this._material.setProperty('u_dS', 0);
        this._material.setProperty('u_dL', 0);

        let dh = { v: 0 };
        this._tween = tween(dh)
            .sequence(
                tween(dh).to(0.5, { v: 10 }, {
                    onUpdate: () => {
                        this._material.setProperty('u_dH', dh.v);
                        this._material.setProperty('u_dS', dh.v / 200);
                    }
                }),
                tween(dh).to(0.5, { v: 0 }, {
                    onUpdate: () => {
                        this._material.setProperty('u_dH', dh.v);
                        this._material.setProperty('u_dS', dh.v / 200);
                    }
                }),
            ).repeatForever()
            .start()

    }

    onDestroy(): void {
        this._tween.stop();
    }
}

