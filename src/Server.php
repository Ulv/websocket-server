<?php

namespace Ulv\WS;

use Psr\Log\LoggerInterface;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Ulv\SimplestQueue\QueueInterface;
use Ulv\SimplestQueue\SimplestDto;

class Server implements MessageComponentInterface
{
    /** @var  \SplObjectStorage */
    protected $clients;

    /** @var  QueueInterface */
    protected $queue;

    /** @var  LoggerInterface */
    protected $logger;

    public function __construct(QueueInterface $queue, LoggerInterface $logger)
    {
        $this->clients = new \SplObjectStorage();
        $this->queue = $queue;
        $this->logger = $logger;
    }

    function onOpen(ConnectionInterface $conn)
    {
        $this->logger->debug(__METHOD__ . ' client connected ' . json_encode([
                'client_id' => $conn->resourceId,
            ], JSON_PRETTY_PRINT));
        $this->clients->attach($conn);
    }

    function onMessage(ConnectionInterface $conn, $msg)
    {
        $this->logger->error(__METHOD__ . ' recv message ' . json_encode([
                'client_id' => $conn->resourceId,
                'message' => $msg,
            ], JSON_PRETTY_PRINT));

        $dto = new SimplestDto([
            'client_id' => $conn->resourceId,
            'message' => $msg,
        ]);

        $this->queue->push($dto);
    }

    function onClose(ConnectionInterface $conn)
    {
        $this->logger->debug(__METHOD__ . ' client disconnected ' . json_encode([
                'client_id' => $conn->resourceId,
            ], JSON_PRETTY_PRINT));
        $this->clients->detach($conn);
    }

    function onError(ConnectionInterface $conn, \Exception $e)
    {
        $this->logger->error(__METHOD__ . ' client error ' . json_encode([
                'client_id' => $conn->resourceId,
                'error' => $e->getMessage(),
            ], JSON_PRETTY_PRINT));
        $conn->close();
    }
}