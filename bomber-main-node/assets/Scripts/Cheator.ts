import { _decorator, Component, director, EventKeyboard, Input, input, KeyCode, log, Node } from 'cc';
import { SocketBridge } from '../Plugins/SocketBridge';
const { ccclass, property } = _decorator;
//Jason 
// 192.168.31.137
//jing-xiang
// 192.168.31.20
//Neo 
// 192.168.31.97
// 5000
@ccclass('Cheator')
export class Cheator extends Component {
    start() {
        director.addPersistRootNode(this.node);
        this.gameFlow();
    }

    update(deltaTime: number) {

    }

    *FlowCheator(): Generator<string> {
        yield `CONNECT {"message":"success","fd":123}`;
        yield `ROOM {"players":[{"name":"asd","fd":123,"isLeader":true,"isReady":false},{"name":"qwe","fd":456,"isLeader":false,"isReady":true}]}`;
        yield `ALLREADY {"message":"allReady"}`;
        yield `START {"message":"Start"}`;
        let MAP = {
            "map": {
                "width": 30,
                "height": 30,
                "grid": [
                    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 1, 0, 0, 0, 0, 1, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                ]
            },
            "playerinfo": [
                { "fd": 123, "position": { "x": 0, "y": 0 } },
                { "fd": 456, "position": { "x": 1, "y": 1 } }]
        }
        for (let i = 1; i >= 0; i--) {
            yield `MAP ${JSON.stringify(MAP)}`;
            yield `SHOP {"item":[2,1,5],"price":[600,600,1000],"money":1200}`;
            yield `BUY_STATE {"buySuccess":true, "money":87}`;
            yield `BUY_TIME {"time":5000}`;
            yield `BUY_TIME {"time":0}`;

            let player_attribute = {
                "player_attribute": {
                    "123": {
                        "passive": {
                            "max_health": 100,
                            "speed": 1,
                            "armor": 1,
                            "shield": 0,
                            "bomb_range": 3,
                            "bomb_power": 50,
                            "bomb_limit": 5,
                            "land_mine": 0
                        },
                        "active": {
                            "ice_attack": 0,
                            "shoot_bullet": 0,
                            "remote_trigger_bomb": 0,
                            "flash": 0,
                            "pass_through": 0,
                            "immune_star": 0,
                            "time_stop": 0,
                            "god_punish": 0,
                            "revive": 0
                        },
                        "current_health": 100
                    },
                    "456": {
                        "passive": {
                            "max_health": 100,
                            "speed": 1,
                            "armor": 1,
                            "shield": 0,
                            "bomb_range": 3,
                            "bomb_power": 50,
                            "bomb_limit": 5,
                            "land_mine": 0
                        },
                        "active": {
                            "ice_attack": 0,
                            "shoot_bullet": 0,
                            "remote_trigger_bomb": 0,
                            "flash": 0,
                            "pass_through": 0,
                            "immune_star": 0,
                            "time_stop": 0,
                            "god_punish": 0,
                            "revive": 0
                        },
                        "current_health": 100
                    }
                }
            };
            yield `PLAYER_ATTRIBUTE ${JSON.stringify(player_attribute)}`;
            yield `READY_TIME ${JSON.stringify({ "time": 2000 })}`;
            yield `READY_TIME ${JSON.stringify({ "time": 1000 })}`;
            yield `READY_TIME ${JSON.stringify({ "time": 0 })}`;
            yield `GAME_TIME ${JSON.stringify({ "time": 180000 })}`;
            yield `GAME_TIME ${JSON.stringify({ "time": 0 })}`;

            let ACHIEVEMENT = {
                "playerInfo": [{
                    "totalPlaceBomb": 0,
                    "totalDealDamage": 0,
                    "destroyedBuildings": 0,
                    "killedPlayers": 0,
                    "totalEarnedMoney": 1000,
                    "fd": 123,
                    "current_money": 1000
                }, {
                    "totalPlaceBomb": 3,
                    "totalDealDamage": 270,
                    "destroyedBuildings": 0,
                    "killedPlayers": 1,
                    "totalEarnedMoney": 1000,
                    "fd": 456,
                    "current_money": 500
                }]
            }
            let ROUND_END = {
                "survivorFd": [123, 12, 4],
                "timesUp": false,
                "nextRound": i
            }
            yield `ROUND_END ${JSON.stringify(ROUND_END)}`;
            yield `ACHIEVEMENT_DATA ${JSON.stringify(ACHIEVEMENT)}`;
        }
        yield `ROOM {"players":[{"name":"asd","fd":123,"isLeader":true,"isReady":false},{"name":"qwe","fd":456,"isLeader":false,"isReady":true}]}`;


    }
    _flowCheator = this.FlowCheator();
    private gameFlow() {
        input.on(Input.EventType.KEY_DOWN, (event: EventKeyboard) => {
            if (event.keyCode == KeyCode.NUM_DECIMAL) {
                let str = this._flowCheator.next().value;
                SocketBridge.sendStringToSelf(str);
                log(str);
            }
        }, this);
    }
}

