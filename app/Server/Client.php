<?php

namespace App\Server;


use Ratchet\ConnectionInterface;

class Client
{
    private $connection;
    private $sessionId;

    public function __construct(ConnectionInterface $conn)
    {
        $this->connection = $conn;
    }

    /**
     * @return ConnectionInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @return string
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * @param string $sessionId
     * @return Client
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;
        return $this;
    }
}
