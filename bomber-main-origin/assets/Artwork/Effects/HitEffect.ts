import { _decorator, Component, Node, Sprite, tween, Material, Tween } from 'cc';
const { ccclass, property } = _decorator;

@ccclass('HitEffect')
export class HitEffect extends Component {
    private _material: Material = null!;
    private _tween: Tween<object>;

    start() {
        var sprite: Sprite = this.getComponent(Sprite);
        if (sprite) {
            this._material = sprite.materials[0];
            this._resetTween();
        }
    }

    // 受傷效果
    doHit() {
        if (this._material == null) return;

        this._resetTween();

        this._tween = tween(this.node)
            .call(() => {
                // 變色
                this._material.setProperty('u_dL', 0.5);
                this._material.setProperty('u_dS', 0.3);
            })
            .delay(0.05)
            .call(() => this._material.setProperty('u_dL', 0))
            .delay(0.05)
            .union()
            .repeat(5)
            .call(() => this._material.setProperty('u_dS', 0))
            .start()
    }

    // 重設Tween
    _resetTween() {
        if (this._material == null) return;

        // 先把前一個停掉，如果有的話
        if (this._tween) this._tween.stop();

        this._material.setProperty('u_dL', 0);
        this._material.setProperty('u_dS', 0);
    }

}

