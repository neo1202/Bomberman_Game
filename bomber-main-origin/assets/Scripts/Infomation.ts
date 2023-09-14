import { _decorator, Color, Component, find, Label, log, Node, Sprite, SpriteAtlas, SpriteFrame } from 'cc';
import { PlayerManager } from './Managers/PlayerManager';
import { PlayerColor, PlayerModel } from './PlayerModel';
const { ccclass, property } = _decorator;

@ccclass('Infomation')
export class Infomation extends Component {

    public fd: number;

    @property(Label)
    nameLabel: Label;
    @property(Label)
    healthLabel: Label;
    @property(Label)
    maxhealthLabel: Label;
    @property(Label)
    bombNLabel: Label;
    @property(Label)
    bomblimitLabel: Label;
    @property(Label)
    shieldLabel: Label;
    @property(Label)
    speedLabel: Label;
    @property(Label)
    attackLabel: Label;
    @property(Label)
    armorLabel: Label;
    @property(Label)
    coinLabel: Label;

    @property(Sprite)
    picture: Sprite;

    @property(SpriteAtlas)
    wizard: SpriteAtlas;

    start() {

        log(this.fd);
        this.nameLabel.string = PlayerManager.instance.playerModelsMap.get(this.fd).name;

        let color = PlayerManager.instance.playerModelsMap.get(this.fd).color;
        this.picture.spriteFrame = this.wizard.getSpriteFrames()[color * 16]

        let bg = this.node.getComponent(Sprite);
        switch (color) {
            case PlayerColor.purple:
                bg.color = new Color(118, 136, 228, 230);
                break;

            case PlayerColor.red:
                bg.color = new Color(214, 81, 81, 230);
                break;

            case PlayerColor.cyan:
                bg.color = new Color(108, 211, 174, 230);
                break;

            case PlayerColor.brown:
                bg.color = new Color(213, 159, 109, 230);
                break;
        
            default:
                break;
        }

        GE.addListener("BombN", (bomb) => this.ChangeBombN(bomb), this);
        GE.addListener("Damaged", (damage) => this.ChangeHealth(damage), this);
        GE.addListener("Bomb&ShieldN", (info) => this.ChangeShieldN(info), this);
        GE.addListener("Flame_damage", (damage) => this.ChangeHealth(damage), this);
        GE.addListener("ItemAttribute", (playerAttribute) => this.ItemAttribute(playerAttribute), this);
        GE.addListener("Attribute", this.InitInfo, this);

    }

    update(deltaTime: number) {

    }

    /**開局已帶buff */
    InitInfo(PlayerAttribute: Array<{ fd: number, attribute }>) {
        for (let i = 0; i < PlayerAttribute.length; i++) {
            if (PlayerAttribute[i].fd != this.fd)
                continue;

            this.healthLabel.string = String(PlayerAttribute[i].attribute.currentHealth);
            this.maxhealthLabel.string = String(PlayerAttribute[i].attribute.maxHealth);
            this.bombNLabel.string = String(PlayerAttribute[i].attribute.bombLimit);
            this.bomblimitLabel.string = String(PlayerAttribute[i].attribute.bombLimit);
            this.shieldLabel.string = String(PlayerAttribute[i].attribute.shield);
            this.speedLabel.string = String("x" + (Math.round(PlayerAttribute[i].attribute.speed* 100) / 100));
            this.attackLabel.string = String(Math.round(PlayerAttribute[i].attribute.bombPower));
            this.armorLabel.string = "-" + String(Math.round((1 - PlayerAttribute[i].attribute.armor) * 100)) + "%";
            this.coinLabel.string = String(PlayerAttribute[i].attribute.money);
        }
    }

    /**放炸彈後可用炸彈數量改變 */
    ChangeBombN(bomb) {
        if (bomb.fd == this.fd) {
            this.bombNLabel.string = String(bomb.bomb_num);
        }
    }

    /**爆炸後&被火焰餘波燒到後血量與護盾改變 */
    ChangeHealth(damage) {
        for (let i = 0; i < damage.length; i++) {
            if (damage[i].fd == this.fd) {
                this.healthLabel.string = String(damage[i].current_health);

                if(damage[i].remaining_shield != null){
                    this.shieldLabel.string = String(damage[i].remaining_shield);
                }
            }
        }
    }

    /**爆炸後炸彈與盾牌數量改變 */
    ChangeShieldN(info) {
        for (let i = 0; i < info.length; i++) {
            if (info[i].fd == this.fd) {
                this.bombNLabel.string = String(info[i].bomb_num);
                this.shieldLabel.string = String(info[i].shield_num);
            }
        }
    }

    /**撿道具增益圖示 */
    ItemAttribute(playerAttribute) {
        for (let i = 0; i < playerAttribute.length; i++) {
            if (playerAttribute[i].fd == this.fd) {

                if(playerAttribute[i].attribute.passive.bomb_limit > Number(this.bomblimitLabel.string))
                    this.bombNLabel.string = String(Number(this.bombNLabel.string) + 1);

                this.bomblimitLabel.string = String(playerAttribute[i].attribute.passive.bomb_limit); //炸彈上限
                this.shieldLabel.string = String(playerAttribute[i].attribute.passive.shield); //防護罩數量
                this.speedLabel.string = String("x" + (Math.round(playerAttribute[i].attribute.passive.speed* 100) / 100));
                this.coinLabel.string = String(playerAttribute[i].attribute.money); //金幣數
                this.attackLabel.string = String(Math.round(playerAttribute[i].attribute.passive.bomb_power));

            }
        }
    }
}

