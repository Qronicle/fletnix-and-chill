<?php
/**
 * Connection.php
 */

namespace App\WebSocket;

use App\WebSocket\Room\AbstractRoom;
use Ratchet\ConnectionInterface;

/**
 * Connection
 *
 * @author  Ruud Seberechts
 * @package App\WebSocket
 * @since   2020-03-28 11:56
 */
class Connection
{
    /** @var int */
    public $id;

    /** @var ConnectionInterface */
    public $connection;

    /** @var User */
    public $user;

    /** @var AbstractRoom */
    public $room;

    /**
     * @param \stdClass $params
     * @throws \Exception
     */
    public function authenticate(\stdClass $params)
    {
        if ($this->user) {
            throw new \Exception("Could not authenticate connection - Connection already authenticated");
        }
        $this->user = User::where('id', $params->user_id)->where('secret', $params->user_secret)->first();
        if (!$this->user || !$this->user->id) {
            $this->user = null;
            throw new \Exception("Could not authenticate connection - Invalid user credentials");
        }
        $this->room = Room::get($params->room_id);
        echo "Authenticated connection $this\n";
        $this->room->addConnection($this);
    }

    public function __toString()
    {
        return $this->id . ($this->user ? " - User: $this->user" : '') . ($this->room ? " - Room: $this->room" : '');
    }

    // Static Methods //////////////////////////////////////////////////////////////////////////////////////////////////

    protected static $connections;

    public static function open(ConnectionInterface $conn): Connection
    {
        $id = $conn->resourceId;
        if (isset(self::$connections[$id])) {
            throw new \Exception("Error opening connection $id - Resource ID already assigned");
        }
        $connection = new Connection();
        $connection->id = $id;
        $connection->connection = $conn;
        self::$connections[$id] = $connection;
        echo "New incoming connection: $connection\n";
        return $connection;
    }

    public static function close(ConnectionInterface $conn): Connection
    {
        $connection = self::get($conn);
        unset(self::$connections[$connection->id]);
        echo "Closing connection: $connection\n";
        if ($connection->room) {
            $connection->room->closeConnection($connection);
        }
        $conn->close();
        return $connection;
    }

    public static function get(ConnectionInterface $conn): Connection
    {
        if (!isset(self::$connections[$conn->resourceId])) {
            throw new \Exception("Could not get existing connection");
        }
        return self::$connections[$conn->resourceId];
    }
}