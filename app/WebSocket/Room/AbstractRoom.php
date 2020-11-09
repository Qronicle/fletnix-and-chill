<?php
/**
 * AbstractRoom.php
 */

namespace App\WebSocket\Room;

use App\WebSocket\Connection;
use App\WebSocket\Message;
use App\WebSocket\Server;

/**
 * AbstractRoom
 *
 * @author  Ruud Seberechts
 * @package App\WebSocket\Room
 * @since   2020-03-28 12:47
 */
abstract class AbstractRoom
{
    /** @var string */
    public $id;

    /** @var string */
    public $name;

    /** @var Connection[] */
    public $connections = [];

    public function addConnection(Connection $connection)
    {
        // Check whether user is not yet connected
        foreach ($this->connections as $otherConnection) {
            if ($connection->user->id == $otherConnection->user->id) {
                Server::broadcastTo($connection, Message::alreadyConnected());
                $connection->connection->close();
            }
        }
        // Add connection and do whatever needs to happen for this room
        if ($this->canPlayerEnter()) {
            $this->connections[$connection->id] = $connection;
            $this->onPlayerEnter($connection);
        } else {
            Server::broadcastTo($connection, Message::cannotEnter());
            $connection->connection->close();
        }
    }

    public function closeConnection(Connection $connection)
    {
        unset($this->connections[$connection->id]);
        $this->onPlayerLeave($connection);
    }

    public function toArray(): array
    {
        $type = explode('\\', get_class($this));
        $game = end($type);
        $type = strtolower($game);
        $players = [];
        foreach ($this->connections as $connection) {
            $players[] = $connection->user->username;
        }
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'game'        => $game,
            'type'        => $type,
            'players'     => $players,
            'full'        => !$this->canPlayerEnter(),
            'description' => '',
        ];
    }

    public function canPlayerEnter(): bool
    {
        return true;
    }

    public function incomingMessage(Connection $connection, $message)
    {
        // Do nothing by default
    }

    public function __toString()
    {
        return $this->id . ' (' . $this->name . ')';
    }

    abstract public function onPlayerEnter(Connection $connection);

    abstract public function onPlayerLeave(Connection $connection);
}