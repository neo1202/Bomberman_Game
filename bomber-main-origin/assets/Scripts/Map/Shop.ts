import { _decorator, CCFloat, CCInteger, CCString, Component, EventTouch, Label, log, math, Node, Prefab, SpriteFrame, Tween, tween } from 'cc';
import { SocketBridge } from '../../Plugins/SocketBridge';
import { PlayerManager } from '../Managers/PlayerManager';
import { ShopItem } from './ShopItem';
const { ccclass, property } = _decorator;
let ItemMap = {
    0: "低級生命補給",
    1: "低級護甲補給",
    2: "低級炸彈威力補給",
    3: "機會---三倍金幣",
    4: "",
    5: "",
    6: "",
    7: "",
    8: "",
    9: "",
    10: "中級生命補給",
    11: "中級護甲補給",
    12: "中級炸彈威力補給",
    13: "低階全場緩速",
    14: "",
    15: "猛踢炸彈(被動)",
    16: "",
    17: "冰刺攻擊*2",
    18: "",
    19: "",
    20: "高級生命補給",
    21: "高級護甲補給",
    22: "高級炸彈威力補給",
    23: "高階全場緩速",
    24: "無敵星星*1",
    25: "",
    26: "",
    27: "",
    28: "",
    29: "眾生平等術",
    30: "復活(一次性被動)",
}

let reverseItemMap = {};
@ccclass('Shop')
export class Shop extends Component {

    @property([CCString])
    public shopItemNames: string[];

    @property(Label)
    public timeLabel: Label;

    @property(Label)
    public moneyLabel: Label;

    @property(Node)
    public layout: Node;

    @property(CCFloat)
    private timeMS: number = 10000;

    public items: ShopItem[] = [];

    @property([Node])
    public itemsNodes: Node[] = [];

    @property([SpriteFrame])
    public itemSprites: SpriteFrame[] = [];

    _isStarted = false;
    start() {
        for (const key in ItemMap) {
            const value = ItemMap[key];
            reverseItemMap[value] = key;
        }
        SocketBridge.addSocketKeyDispatch(this, {
            "SHOP": this.cmd_SHOP,
            "BUY_TIME": this.cmd_BUY_TIME,
            "BUY_STATE": this.cmd_BUY_STATE
        });
        GE.addListener("Buy", this.buy, this);

    }

    protected onDestroy(): void {
        GE.removeCustomEvent("Buy", this.buy, this);
        SocketBridge.removeSocketKeyDispatch(this);
    }

    /**
     * 持續更新購物倒數計時，時間到了開始遊戲
     * @param key 
     * @param value 
     */
    cmd_BUY_TIME(key: string, value: string) {
        //接到新的時間之後，馬上更新
        let timeObj = JSON.parse(value);
        this.timeMS = Number.parseInt(timeObj.time);
        this.timeLabel.string = "剩餘時間：" + (this.timeMS / 1000);
        //時間到了
        if (this.timeMS == 0) {
            this.node.active = false;
        }
    }

    /**
     * 滑鼠點擊或是選擇買了某東西
     */
    buy(customEventData: any) {
        let str = `BUY {"item":${reverseItemMap[customEventData]}}`;
        log(str)
        SocketBridge.sendString(str);
    }

    /**
     * 購買成功或失敗
     * @param key 
     * @param value 
     */
    cmd_BUY_STATE(key: string, value: string) {
        var obj = JSON.parse(value);
        let success = obj.buySuccess as boolean;
        if (success) {
            PlayerManager.instance.model.money = obj.money;
        } else {
        }
        this.items.forEach((item) => item.playerUpdateMoney());
        this.moneyLabel.string = "剩餘金錢:" + PlayerManager.instance.model.money.toString();
    }

    /**
     * 
     * @param key 
     * @param value 
     */
    cmd_SHOP(key: string, value: string) {

        this.layout.active = true;
        //XXX:剛好放滿三個
        for (let i = 0; i < this.itemsNodes.length; i++) {
            this.items.push(this.itemsNodes[i].getComponent(ShopItem));
        }
        this.node.children.forEach((childNode) => { childNode.active = true; });
        //JSON轉換
        let ShopObj = JSON.parse(value);
        let items = ShopObj.item as number[];
        let prices = ShopObj.price as number[];
        let money = ShopObj.money as number;
        PlayerManager.instance.model.money = money;
        this.moneyLabel.string = money.toString();

        for (let i = 0; i < items.length; i++) {
            this.items[i].setItem(ItemMap[items[i]]);
            this.items[i].itemSprite.spriteFrame = this.itemSprites[items[i]] ?? this.items[i].itemSprite.spriteFrame;

            //設定價格，item會自己改文字並且判斷玩家錢夠不夠，不夠就不會亮
            this.items[i].setPrice(prices[i]);
        }

    }
}



