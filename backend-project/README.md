# Bomberman backend 

> Explosion Euphoria - Gorge of Rage
<i>(start from 2023.07.20) </i>

## Version Info

- PHP 版本：8.0.29
- Swoole 版本：4.7.0

Welcome to the Awesome Bomb Man Game! This is an exciting multiplayer game where players plant bombs to blow up their opponents and navigate through obstacles.

## Features

- LobbyServer
    - Have Room object to let people match with others.
    - Room have leader to start the game, all member can leave the room or ready for the game.
    - When all the players are ready, room leader can start the game.
- GameServer
    - Plant bombs strategically to eliminate opponents.
    - Explore various game levels with different terrains.
    - Chain Bomb Explode within 0.3 seconds.
    - Multiplayer mode for intense battles with friends.
    - Buy different item using Coins to strengthen yourself.
- ProxyServer
    - Established in TCP connection.
    - Client directly connect to proxy server.
    - Proxy help redirect and handle the communication with LobbyServer, GameServer.

## Getting Started

Follow these steps to get started with the Bomb Man Game:

1. Clone this repository to your local machine.
2. Install the required dependencies using `composer dump-autoload -o`.
3. Configure the game settings in `config.php`.
4. Run the Lobby server using `php lobbyServer.php`.
5. Run the Game server using `php gameServer.php`.
6. Run the Proxy server using `php proxyServer.php`.
7. Enjoy the game by connecting to the proxyServer!
8. Our proxy port is on 5000, client can connect to port 5000.

## Game Controls

- WASD / Arrow keys: Move your character.
- Spacebar: Plant a bomb.

## Contributing

Contributions are welcome! If you have any ideas for improvements or new features, feel free to submit a pull request.

### Contributor

<b>Neo.Wu吳驊祐, Jing-Xiang.Yang楊景翔, Jason.Wang王錦盛</b>
