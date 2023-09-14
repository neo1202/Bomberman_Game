import { _decorator, Component, director, EventKeyboard, Input, input, KeyCode, log, Node } from 'cc';
import { SocketBridge } from '../Plugins/SocketBridge';
const { ccclass, property } = _decorator;

@ccclass('TestSendToSelf')
export class TestSendToSelf extends Component {
    start() {

        director.addPersistRootNode(this.node);

        let connectjs = {
            "fd": 1,
        }

        let roomjson = {
            "players": [
                {
                    "name": "哈哈哈",
                    "fd": 1,
                    "isLeader": true,
                    "isReady": false,
                },
                {
                    "name": "嘻嘻嘻",
                    "fd": 2,
                    "isLeader": false,
                    "isReady": false,
                }
            ]
        }

        let room2json = {
            "players": [
                {
                    "name": "哈哈哈",
                    "fd": 1,
                    "isLeader": false,
                    "isReady": false,
                },
                {
                    "name": "嘻嘻嘻",
                    "fd": 2,
                    "isLeader": true,
                    "isReady": false,
                }
            ]
        }

        let allreadyjs = {
            "message": "allReady"
        }

        let startjson = {
            "message": "Start"
        }

        let mapjson = {
            "map": {
                "width": 14,
                "height": 14,
                "grid": [
                    [0, 1, 1, 2, 1, 0, 2, 1, 2, 0, 1, 0, 2, 0],
                    [1, 2, 2, 1, 0, 0, 2, 1, 2, 0, 0, 1, 0, 1],
                    [0, 1, 1, 0, 2, 1, 0, 1, 1, 0, 2, 1, 2, 0],
                    [2, 1, 2, 0, 0, 1, 2, 1, 0, 0, 1, 0, 0, 1],
                    [2, 1, 0, 2, 1, 2, 0, 0, 2, 1, 2, 0, 0, 2],
                    [1, 1, 0, 2, 1, 0, 1, 1, 1, 0, 1, 1, 0, 2],
                    [0, 0, 1, 2, 1, 0, 0, 1, 0, 0, 0, 1, 0, 0],
                    [1, 0, 1, 1, 0, 2, 1, 2, 0, 0, 1, 1, 0, 1],
                    [0, 2, 1, 2, 0, 1, 0, 2, 0, 1, 2, 0, 0, 2],
                    [0, 1, 2, 1, 0, 0, 1, 0, 0, 2, 1, 0, 1, 1],
                    [0, 0, 1, 2, 1, 0, 0, 1, 0, 0, 1, 2, 1, 0],
                    [0, 1, 1, 1, 0, 1, 1, 0, 2, 1, 0, 0, 1, 0],
                    [0, 0, 1, 2, 1, 0, 0, 1, 2, 0, 0, 2, 1, 0],
                    [0, 2, 1, 2, 0, 0, 2, 1, 2, 0, 0, 0, 2, 1]]
            },
            "playerinfo": [
                { "fd": 1, "position": { "x": 0, "y": 0 } },
                { "fd": 2, "position": { "x": 0, "y": 0 } }]
        }

        let buytimejs = {
            "time": 0,
        }

        let bombjson = {
            "bombID": 0,
            "fd": 1,
            "place": [6, 9],
            "bomb_num": 0,
        }

        let explodejson = {
            "bombID": 0,
            "deads": [],
            "destroy": [
                [4, 9],
                [8, 9],
                [6, 7],
                [6, 11]],
            "gameItem": [
                {
                    "place": [4, 9],
                    "item": "SHIELD"
                },
                {
                    "place": [8, 9],
                    "item": "BOMBSUPPLIES"
                }],
            "explodeEffectRange": {
                "left": [6, 8],
                "right": [6, 10],
                "top": [5, 9],
                "bottom": [7, 9],
            },
            "damagedPlayers": [
                {
                    'fd': 1,
                    'current_health': 60,
                },
                {
                    'fd': 2,
                    'current_health': 60,
                }],
            "playerInfo": [
                {
                    "fd": 1,
                    "bomb_num": 1,
                    "shield_num": 0,
                },
                {
                    'fd': 2,
                    "bomb_num": 2,
                    "shield_num": 1
                }]
        }

        let pickitemjs = {
            "itemplace": [4, 9],
            "playerAttribute": [
                {
                    "fd": 1,
                    "attribute":
                    {
                        "active": {},
                        "passive":
                        {
                            "bomb_limit": 2,
                            "bomb_power": 50,
                            "bomb_range": 3,
                            "land_mine": 0,
                            "max_health": 100,
                            "shield": 2,
                            "speed": 1,
                        },
                    },
                    "money": 100,
                },
                {
                    "fd": 2,
                    "attribute":
                    {
                        "active": {},
                        "passive":
                        {
                            "bomb_limit": 2,
                            "bomb_power": 50,
                            "bomb_range": 3,
                            "land_mine": 0,
                            "max_health": 100,
                            "shield": 0,
                            "speed": 1,
                        },
                    },
                    "money": 2000,
                }],
            "itemOwner": 1,
        }

        let flamespreadjs = {
            "damagedPlayers": [
                {
                    'fd': 1,
                    'current_health': 40,
                    "remaining_shield":0,
                },
                {
                    'fd': 2,
                    'current_health': 40,
                    "remaining_shield":0,
                }],
        }

        let roundendjs = {
            "survivorFD": [123, 12, 4],
            "timesUp": false,
            "nextRound": 2,
        }

        let attributejs = {
            "player_attribute": {
                "1": {
                    "passive": {
                        "max_health": 100,
                        "speed": 1,
                        "armor": 1,
                        "shield": 1,
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
                "2": {
                    "passive": {
                        "max_health": 100,
                        "speed": 1,
                        "armor": 1,
                        "shield": 3,
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
        }

        input.on(Input.EventType.KEY_DOWN, (event: EventKeyboard) => {

            switch (event.keyCode) {

                case KeyCode.NUM_1:
                    log(1);
                    SocketBridge.sendStringToSelf(`CONNECT ${JSON.stringify(connectjs)}`);
                    break;

                case KeyCode.NUM_2:
                    log(2);
                    SocketBridge.sendStringToSelf(`ROOM ${JSON.stringify(roomjson)}`);
                    break;

                case KeyCode.NUM_DIVIDE:
                    log("/");
                    SocketBridge.sendStringToSelf(`ROOM ${JSON.stringify(room2json)}`);
                    break;

                case KeyCode.NUM_3:
                    log(3);
                    SocketBridge.sendStringToSelf(`ALLREADY ${JSON.stringify(allreadyjs)}`);
                    break;

                case KeyCode.NUM_4:
                    log(4);
                    SocketBridge.sendStringToSelf(`START ${JSON.stringify(startjson)}`);
                    break;

                case KeyCode.NUM_5:
                    log(5);
                    SocketBridge.sendStringToSelf(`MAP ${JSON.stringify(mapjson)}`);
                    break;

                case KeyCode.NUM_6:
                    log(6);
                    SocketBridge.sendStringToSelf(`BUY_TIME ${JSON.stringify(buytimejs)}`);
                    break;

                case KeyCode.NUM_7:
                    log(7);
                    SocketBridge.sendStringToSelf(`BOMB ${JSON.stringify(bombjson)}`)
                    break;

                case KeyCode.NUM_8:
                    log(8);
                    SocketBridge.sendStringToSelf(`EXPLODE ${JSON.stringify(explodejson)}`)
                    break;

                case KeyCode.NUM_9:
                    log(9);
                    SocketBridge.sendStringToSelf(`ITEM_PICKUP ${JSON.stringify(pickitemjs)}`)
                    break;

                case KeyCode.NUM_0:
                    log(0);
                    SocketBridge.sendStringToSelf(`FLAME_SPREAD_PLAYER ${JSON.stringify(flamespreadjs)}`)
                    break;

                case KeyCode.NUM_MULTIPLY:
                    log("*");
                    SocketBridge.sendStringToSelf(`ROUND_END ${JSON.stringify(roundendjs)}`)
                    break;

                case KeyCode.NUM_SUBTRACT:
                    log("-");
                    SocketBridge.sendStringToSelf(`PLAYER_ATTRIBUTE ${JSON.stringify(attributejs)}`)
                    break;

                default:
                    break;
            }

        }, this)

    }

    update(deltaTime: number) {

    }
}

