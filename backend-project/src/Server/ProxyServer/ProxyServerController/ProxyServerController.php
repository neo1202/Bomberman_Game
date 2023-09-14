<?php

namespace MyApp\Server\ProxyServer\ProxyServerController;

use MyApp\Module\JsonLoader;
use MyApp\Module\PackageParser;

use MyApp\Enum\Enum;

class ProxyServerController
{
    public function handleClientData(&$proxy_server, &$client_lobby_server, &$client_game_server, &$client_dictionary, $fd, $data, $logger)
    {
        try {
            if (array_key_exists($fd, $client_dictionary)) {
                [$action, $message] = PackageParser::parseCommand($data);
                $message = json_decode($message);
                print_r($message);
                switch ($action) {
                    case 'BOMBER':
                    case 'READY':
                    case 'UNREADY':
                    case 'START':
                        if ($client_dictionary[$fd] === 'lobby') {
                            $message->client_fd = $fd;
                            $to_server_data = $action . ' ' . json_encode($message) . "\r\n";
                            $client_lobby_server->send($to_server_data);
                            echo "Message sent to lobby server : {$to_server_data}\n";
                        }
                        break;
                    case 'MOVE':
                    case 'PLACE_BOMB':
                    case 'GAME_CONDITION':
                    case 'LOADSCENE':
                    case 'BUY':
                    case 'TOUCH_ITEM':
                    case 'PLAYERS_CONDITION':
                    case 'ACHIEVEMENT_DONE':
                        if ($client_dictionary[$fd] === 'game') {
                            $message->client_fd = $fd;
                            $to_server_data = $action . ' ' . json_encode($message) . "\r\n";
                            $client_game_server->send($to_server_data);
                            echo "Message sent to game server : {$to_server_data}\n";
                        }
                        break;
                    case 'PING':
                        $action = 'PONG';
                        $message->fd = $fd;
                        $message->time = time();
                        $to_client_data = $action . ' ' . json_encode($message);
                        $proxy_server->push($fd, $to_client_data);
                        echo "Message sent to client : {$to_client_data}\n";
                        $send_msg = "Message sent to client : {$to_client_data}\n";
                        go(function () use ($send_msg, $logger) {
                            $logger['send_logger']->info($send_msg);
                        });
                        break;
                    default:
                        return;
                }
            }
        } catch (\Throwable $th) {
            echo $th->getMessage().PHP_EOL;
        }

    }

    public function handleLobbyResponse(&$server, &$client_lobby_server, &$client_game_server, &$client_dictionary, $data, $logger)
    {
        [$action, $message] = PackageParser::parseCommand($data);
        $message = json_decode($message);
        switch ($action) {
            case 'ROOM':
            case 'READY':
            case 'UNREADY':
            case 'ALLREADY':
            case 'CONNECT' :
            case 'CLOSE' :
                $client_fds = $message->client_fds;
                unset($message->client_fds);
                $to_client_data = $action . ' ' . json_encode($message);
                $this->toClient($server, $client_fds, $to_client_data, $logger);
                break;
            case 'START':
                $client_fds = $message->client_fds;
                unset($message->client_fds);
                $this->changeClientConnection($client_dictionary, $client_fds, 'game');
                $to_game_data = $action . ' ' . json_encode($message) . "\r\n";
                $client_game_server->send($to_game_data);
                echo "Message sent to game server : {$to_game_data}\n";
                break;
            default:
                return;
        }
    }

    public function handleGameResponse(&$server, &$client_lobby_server, &$client_game_server, &$client_dictionary, $data, $logger)
    {
        [$action, $message] = PackageParser::parseCommand($data);
        $message = json_decode($message);
        switch ($action) {
            case 'MAP':
            case 'UPDATE_POSITION':
            case 'BOMB':
            case 'START':
            case 'GAME_CONDITION' :
            case 'SHOP' :
            case 'FINAL_ACHIEVEMENT':
            case 'BUY_TIME' :
            case 'EXPLODE':
            case 'FLAME_SPREAD_PLAYER':
            case 'ACHIEVEMENT_DATA':
            case 'BUY_STATE':
            case 'PLAYER_ATTRIBUTE':
            case 'READY_TIME':
            case 'ITEM_PICKUP':
            case 'GAME_TIME':
            case 'ROUND_END':
            case 'PLAYERS_CONDITION':
            case 'LOAD_TIME':
            case 'LEAVE':
                $client_fds = $message->client_fds;
                unset($message->client_fds);
                $to_client_data = $action . ' ' . json_encode($message);
                $this->toClient($server, $client_fds, $to_client_data, $logger);
                break;
            case 'ROOM_GAME_END':
                $player = $message->client_fd;
                $this->changeClientConnection($client_dictionary, [$player], 'lobby');
                print_r($client_dictionary);
                $to_lobby_data = $action . ' ' . json_encode($message) . "\r\n";
                $client_lobby_server->send($to_lobby_data);
                echo "Message sent to lobby server : {$to_lobby_data}\n";
                break;
            case 'GAME_PLAYER_DISCONNECT':
                $to_lobby_data = $action . ' ' . json_encode($message) . "\r\n";
                $client_lobby_server->send($to_lobby_data);
                echo "Message sent to lobby server : {$to_lobby_data}\n";
                break;
            default:
                return;
        }
    }

    public function changeClientConnection(&$client_dictionary, $client_fds, string $connection)
    {
        foreach ($client_fds as $fd) {
            $client_dictionary[$fd] = $connection;
        }
    }

    public function handleConnect(&$client_lobby_server, $fd)
    {
        $action = 'CONNECT';
        $message = [
            'client_fd' => $fd
        ];
        $message = json_encode($message);
        $data = $action . ' ' . $message . "\r\n";
        echo "Message sent to lobby server : {$data}\n";
        $client_lobby_server->send($data);
    }

    public function handleClose(&$server, &$client_lobby_server, &$client_game_server, &$client_dictionary, $fd)
    {
        if (array_key_exists($fd, $client_dictionary)) {
            $action = 'CLOSE';
            $message = [
                'client_fd' => $fd
            ];
            $message = json_encode($message);
            $data = $action . ' ' . $message . "\r\n";
            if ($client_dictionary[$fd] === 'lobby') {
                $client_lobby_server->send($data);
                echo "Message sent to lobby server : {$data}\n";
            } else {
                $client_game_server->send($data);
                echo "Message sent to game server : {$data}\n";
            }
            if (isset($client_dictionary[$fd])) {
                unset($client_dictionary[$fd]);
            }
        }
    }

    public function toClient(&$server, $fds, $data, $logger)
    {
        foreach ($fds as $fd) {
            $server->push($fd, $data);
            if (!str_contains($data, 'GAME_TIME') && !str_contains($data, 'UPDATE_POSITION')) {
                echo "Message sent to client {$fd} : {$data}\n";
            }
            $send_msg = "Message sent to client {$fd} : {$data}\n";
            go(function () use ($send_msg, $logger) {
                $logger['send_logger']->info($send_msg);
            });
        }
    }

    public function parseIpPort(string $ip_port)
    {
        list($ip, $port) = explode(':', $ip_port);
        return [$ip, intval($port)];
    }
}
