import { WebSocketServer } from 'ws';



const wss = new WebSocketServer({ port: 8080 });
let players = new Map();
let bombs = new Map();
wss.on('connection', function connection(ws) {
    ws.on('error', console.error);
    ws.on('message', function message(data) {
        console.log('received: %s', data);
        // let rawStr = String(data).split(' ');
        // let key = rawStr[0];
        // let Object = data.slice(key.length);
        // switch (key) {
        //     case 'PLACE_BOMB':
        //         bombs.push(Object);
        //         //-1上帝模式 
        //         //直接放一個炸彈在中間
        //         ws.send(`BOMB {"BombID"=0,"fd"=123,"position":{"x":0,"y":0}}`);
        //         break;
        //     case 'MOVE'://收到直接發給大家更新
        //         //在原本的遊戲中找不到這人，好可憐，讓他加入遊戲
        //         if(!players.get(Object["fd"]))
        //             ws.send(`JOIN ${Object}`);
        //         players.set(Object["fd"], Object);
        //         ws.send(`UPDATE ${Object}`);
        //         break;
        //     case 'EXPLODE'://引爆某顆炸彈
        //         ws.send(`EXPLODE ${Object}`);
        //         break;
        //     case 'BOMBER'://JOIN GAME
        //         console.log(Object);
        //         //ws.send(`ROOM `);
        //         break;
        //     default:
        //         break;
        // }
    });
});
