import { _decorator, Button, CCInteger, CCString, Component, Label, log, Node, Sprite, SpriteAtlas } from 'cc';
import { PlayerManager } from '../Managers/PlayerManager';
const { ccclass, property } = _decorator;

@ccclass('ShopItem')
export class ShopItem extends Component {

    @property(Label)
    public itemLabel: Label

    @property(CCString)
    private item: string;

    @property(Label)
    public priceLabel: Label;

    @property(CCInteger)
    private price: number;

    @property(Sprite)
    public itemSprite: Sprite;

    @property(Button)
    public button: Button;

    public getItem(){
        return this.item;
    }

    public setItem(item:string | number){
        item = item as string;
        this.itemLabel.string = item;
        this.button.clickEvents[0].customEventData = item;
    }

    public getPrice(){
        return this.price;
    }

    public setPrice(newPrice: number) {
        this.price = newPrice;

        if (this.price > PlayerManager.instance.model.money) {
            this.button.interactable = false;
        }

        this.priceLabel.string = this.price.toString();
    }

  

    public playerUpdateMoney(){
        this.setPrice(this.price);
    }

    Buy(e: Event,customEventData:any): void {
        this.button.interactable = false;
        GE.dispatchCustomEvent("Buy", customEventData);
    }
}

