<?php


require_once __DIR__ . '/vendor/autoload.php';

use MyApp\Server\LobbyServer\LobbyServer;
use MyApp\Server\GameServer\GameServer;
use MyApp\Server\ProxyServer\ProxyServer;


$lobby_server = new LobbyServer();
