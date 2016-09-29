<?php

namespace App\Server;


use App\Enums\DownloadStatus;
use App\Ficsave\Download;
use Cache;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Server implements MessageComponentInterface
{
    /** @var Client[] */
    private $clients;

    public function __construct() {
        $this->clients = [];
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->addClient($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $msg = json_decode($msg, true);
        $type = $msg['type'];
        $data = $msg['data'];
        switch ($type) {
            case 'heartbeat':
                $this->handleHeartbeat($from, $data);
                break;
            case 'update':
                if (isset($msg['server']) && $msg['server'] == env('APP_KEY')) {
                    $this->handleUpdate($msg['data']['id'], $msg['data']['downloads']);
                }
                break;
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->removeClient($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    function addClient(ConnectionInterface $conn) {
        $this->clients[$conn->resourceId] = new Client($conn);
    }

    function removeClient(ConnectionInterface $conn) {
        unset($this->clients[$conn->resourceId]);
    }

    /**
     * @param ConnectionInterface $conn
     * @return Client
     */
    function getClient(ConnectionInterface $conn) {
        return $this->clients[$conn->resourceId];
    }

    function send(ConnectionInterface $from, $msg) {
        if (is_array($msg)) $msg = json_encode($msg);
        foreach ($this->clients as $client) {
            if ($from === $client->getConnection()) continue;
            $client->getConnection()->send($msg);
        }
    }

    function sendToSession($sessionId, $msg) {
        if (is_array($msg)) $msg = json_encode($msg);
        foreach ($this->clients as $client) {
            if ($sessionId != $client->getSessionId()) continue;
            $client->getConnection()->send($msg);
            return true;
        }
        return false;
    }

    function reply(ConnectionInterface $from, $msg) {
        if (is_array($msg)) $msg = json_encode($msg);
        foreach ($this->clients as $client) {
            if ($from !== $client->getConnection()) continue;
            $client->getConnection()->send($msg);
            return true;
        }
        return false;
    }

    function handleHeartbeat(ConnectionInterface $conn, $id) {
        $client = $this->getClient($conn);
        $client->setSessionId($id);
        $userKey = 'user_'.$id;
        $data = [];
        if (Cache::has($userKey)) {
            /** @var Download[] $data */
            $data = Cache::get($userKey);
            $changed = false;
            foreach ($data as $key => $download) {
                if (time() - $download->getTimestamp() > 15 * 60 || $download->getStatus() == DownloadStatus::ERROR) {
                    unset($data[$key]);
                    $changed = true;
                }
            }
            if ($changed) {
                Cache::put($userKey, $data, 15);
            }
        }
        $this->handleUpdate($id, $data);
    }

    function handleUpdate($sessionId, $downloads) {
        $this->sendToSession($sessionId, [
            'type' => 'update',
            'data' => $downloads
        ]);
    }
}
