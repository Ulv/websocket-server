<?php
/**
 * Websocket server
 * Идея следующая:
 * - данные, прилетающие через websocket пишутся в очередь redis
 * - асинхронный скрипт читает из очереди и сохраняет в базу
 */

const REDIS_HOST = '127.0.0.1';
const REDIS_PORT = 6379;
const QUEUE_NAME = 'ws_server';

require __DIR__ . '/vendor/autoload.php';

$redis = new \Redis();
$redis->pconnect(REDIS_HOST, REDIS_PORT);
$queue = new \Ulv\SimplestQueue\Queue(
    new \Ulv\SimplestQueue\RedisConnector($redis, QUEUE_NAME),
    new \Ulv\SimplestQueue\Serializer\DTOSerializer());

$logger = new \Monolog\Logger('ws_server');
$logger->pushHandler(new \Monolog\Handler\SyslogHandler('ws_server'));

$server = \Ratchet\Server\IoServer::factory(new \Ulv\WS\Server($queue, $logger), 8080);
$server->run();