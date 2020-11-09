<?php
/**
 * Server.php
 */

namespace App\WebSocket;

use App\WebSocket\Room\AbstractRoom;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use React\EventLoop\LoopInterface;

/**
 * Server
 *
 * @author  Ruud Seberechts
 * @package App\WebSocket
 * @since   2020-03-28 13:24
 */
class Server implements MessageComponentInterface
{
    /** @var LoopInterface */
    protected $loop;

    public function init()
    {
        Room::setupDefaultRooms();
    }

    public function onOpen(ConnectionInterface $conn)
    {
        Connection::open($conn);
    }

    public function onClose(ConnectionInterface $conn)
    {
        Connection::close($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        throw $e;
        echo $e->getMessage() . "\n";
        Connection::close($conn);
    }

    public function onMessage(ConnectionInterface $conn, $msg)
    {
        $connection = Connection::get($conn);
        $message = json_decode($msg);
        switch ($message->type) {
            case 'auth':
                $connection->authenticate($message);
                break;
            case 'ping':
                $conn->send('pong');
                break;
            default:
                if ($connection->room) {
                    $connection->room->incomingMessage($connection, $message);
                }
        }
        /*$onlineUsers = [];
        foreach ($this->connections as $resourceId => &$connection) {
            $connection['conn']->send($msg);
            if ($conn->resourceId != $resourceId) {
                $onlineUsers[$resourceId] = $connection['user_id'];
            }
        }
        $conn->send(json_encode(['type' => 'users', 'online_users' => $onlineUsers]));*/
    }

    public function setLoop(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    // Static methods //////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Broadcast a message to all people in a room
     *
     * @param AbstractRoom $room
     * @param array        $message
     */
    public static function broadcast(AbstractRoom $room, array $message)
    {
        //echo "< " . $message['type'] . "\n";
        foreach ($room->connections as $connection) {
            $connection->connection->send(json_encode($message));
        }
    }

    /**
     * Broadcast a message to all other people in a room
     *
     * @param Connection $connection
     * @param array      $message
     */
    public static function broadcastOthers(Connection $connection, array $message)
    {
        if (!$connection->room) {
            return;
        }
        //echo "< " . $message['type'] . "\n";
        foreach ($connection->room->connections as $otherConnection) {
            if ($otherConnection->id == $connection->id) {
                continue;
            }
            $otherConnection->connection->send(json_encode($message));
        }
    }

    /**
     * Broadcast a message to a specific connection
     *
     * @param Connection $connection
     * @param array      $message
     */
    public static function broadcastTo(Connection $connection, array $message)
    {
        //echo "< " . $message['type'] . "\n";
        $connection->connection->send(json_encode($message));
    }

    public static function loop(): LoopInterface
    {
        return Server::get()->loop;
    }

    // Singleton implementation ////////////////////////////////////////////////////////////////////////////////////////

    protected static $server;

    public static function get(): Server
    {
        if (!self::$server) {
            self::$server = new Server();
            self::$server->init();
        }
        return self::$server;
    }
}