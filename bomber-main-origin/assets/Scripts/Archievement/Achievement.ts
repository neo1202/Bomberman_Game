import { _decorator, Color, Component, director, Input, instantiate, Label, log, math, Node, Prefab, Sprite, SpriteAtlas, SpriteFrame } from 'cc';
import { PlayerManager } from '../Managers/PlayerManager';
import { SocketBridge } from '../../Plugins/SocketBridge';
import { AchievementBadge } from './AchievementBadge';
const { ccclass, property } = _decorator;

@ccclass('Achievement')
export class Achievement extends Component {

    //XXX:偷懶寫法，見PlayerManager
    private _achievementDatas;
    private _roundEnd;

    @property(Node)
    public continueNode: Node;

    @property(Prefab)
    public archievementPrefab: Prefab;

    @property(Node)
    public layoutNode: Node;

    @property(Prefab)
    public No1: Prefab;

    @property(Label)
    public continueLabel: Label;

    @property(SpriteAtlas)
    public spriteAtlas: SpriteAtlas;

    @property(Label)
    public title: Label

    private _achievementNodes: Node[] = [];
    private _achievementBadges: AchievementBadge[] = [];


    protected onLoad(): void {
        SocketBridge.addSocketKeyDispatch(this, {
            "LOAD_TIME": this.countDownForceLoad,
        });
    }
    protected start(): void {
        this.continueLabel.string = "繼續";
        this._roundEnd = PlayerManager.instance.roundEnd;
        this._achievementDatas = PlayerManager.instance.achievements.playerInfo;
        if (this._roundEnd.nextRound == 0) {
            this.title.string = "看來我們有大贏家了";
        }
        else {
            this.title.string = "本回合結算";
        }
        for (let i = 0; i < this._achievementDatas.length; i++) {
            let playerNode = PlayerManager.instance.playerModelsMap.get(this._achievementDatas[i].fd);
            let playerName = playerNode.name;

            let str =
                `總殺敵數:${this._achievementDatas[i].killedPlayers}\n` +
                `造成傷害:${this._achievementDatas[i].totalDealDamage}\n` +
                `放置炸彈數:${this._achievementDatas[i].totalPlaceBomb}\n` +
                `物件摧毀數:${this._achievementDatas[i].destroyedBuildings}\n` +
                `獲得金額:${this._achievementDatas[i].totalEarnedMoney}\n` +
                `當前金額:${this._achievementDatas[i].current_money}`;
            // `攜帶道具${player.carryItem}`;

            //生成
            let archNode = instantiate(this.archievementPrefab);
            this._achievementNodes.push(archNode);
            //XXX: getChildByName超醜
            archNode.getChildByName("Name").getComponent(Label).string = playerName;

            //放正面人物圖

            archNode.getChildByName("Sprite").getComponent(Sprite).spriteFrame =
                this.spriteAtlas.getSpriteFrames()[(playerNode.color * 16) as number];

            let achievement = archNode.getChildByName("Achievement");
            this._achievementBadges.push(achievement.getComponent(AchievementBadge));
            achievement.getComponent(Label).string = str;

            archNode.parent = this.layoutNode;
        }


        this.scheduleOnce(() => {
            if (this._roundEnd.nextRound == 0) {
                this.FinalRound();
            }
        }, 0.5)

        this.scheduleOnce(() => {
            this.continueNode.active = true;
        }, 1)

    }

    public Continue() {
        if (this._roundEnd.nextRound > 0)
            SocketBridge.sendString(`LOADSCENE {"message":"Done"}`);
        else
            director.loadScene("room");
    }

    /**
     * 最後一回合把各種稱號跑出來
     */
    private FinalRound() {
        let infomations = PlayerManager.instance.achievementsString.final_achievement_info;
        let MVP:AchievementBadge;
        let MVPN = -1;
        for (let i = 0; i < infomations.length; i++) {
            for (let j = 0; j < infomations[i].achievement.length; j++) {
                let title = infomations[i].achievement[j];
                this._achievementBadges[i].GetTitle(title);
            }
            if(this._achievementBadges[i].counter>MVPN){
                MVP = this._achievementBadges[i];
                MVPN = this._achievementBadges[i].counter;
            }
        }
        MVP.isTheBest();
    }

    private countDownForceLoad(key: string, value: string) {
        let timeMS = JSON.parse(value).time as number;
        this.continueLabel.string = (timeMS / 1000).toString();
        if (timeMS == 0) {
            if (this._roundEnd.nextRound == 0) {
                director.loadScene("room");
            }
            else {
                director.loadScene("map");
            }
        }
    }

    protected onDisable(): void {
        SocketBridge.removeSocketKeyDispatch(this);
    }
}