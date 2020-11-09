<?php
/**
 * Index.php
 */

namespace App\WebSocket\Room;

use App\WebSocket\Connection;
use App\WebSocket\Room;
use App\WebSocket\Server;

/**
 * Index
 *
 * @author  Ruud Seberechts
 * @package App\WebSocket\Room
 * @since   2020-03-28 12:46
 */
class Index extends AbstractRoom
{
    function onPlayerEnter(Connection $connection)
    {
        $rooms = Room::allNotOfType('index', true);
        Server::broadcastTo($connection, [
            'type'  => 'init',
            'rooms' => $rooms,
        ]);
    }

    public function incomingMessage(Connection $connection, $message)
    {
        switch ($message->type) {
            case 'create_room':
                Room::create([
                    'type' => $message->game,
                    'name' => $message->name,
                ]);
                $rooms = Room::allNotOfType('index', true);
                Server::broadcast($this, [
                    'type'  => 'init',
                    'rooms' => $rooms,
                ]);
                break;
        }
    }

    function onPlayerLeave(Connection $connection)
    {
        // TODO: Implement onPlayerLeave() method.
    }
}