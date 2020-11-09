<?php
/**
 * Room.php
 */

namespace App\WebSocket;

use App\WebSocket\Room\AbstractRoom;

/**
 * Room
 *
 * @author  Ruud Seberechts
 * @package App\WebSocket
 * @since   2020-03-28 11:34
 */
class Room
{
    const ROOM_INDEX = -1;

    /** @var AbstractRoom[] */
    protected static $rooms;

    /** @var int Internal counter */
    protected static $roomIndex = 0;

    public static function create(array $settings = null, $id = null): AbstractRoom
    {
        $roomType = ucfirst($settings['type'] ?? 'picturnery');
        $roomClassName = "App\WebSocket\Room\\$roomType";
        $room = new $roomClassName();
        $room->id = $id ?: (++self::$roomIndex/* . '.' . microtime(true)*/);
        $room->name = $settings['name'] ?? 'Room ' . self::$roomIndex;
        self::$rooms[$room->id] = $room;
        echo "Created room $room\n";
        return $room;
    }

    public static function close(string $id)
    {
        // @todo
    }

    public static function get(string $id): AbstractRoom
    {
        if (!isset(self::$rooms[$id])) {
            throw new \Exception("Could not get room $id");
        }
        return self::$rooms[$id];
    }

    public static function setupDefaultRooms()
    {
        // Create index room
        self::create([
            'type' => 'index',
            'name' => 'Room Overview'
        ], self::ROOM_INDEX);

        // Create default Picturnery room
        self::create([
            'type' => 'picturnery',
            'name' => 'Picasso Room',
        ]);

        // Create default Tetrus room
        self::create([
            'type' => 'tetrus',
            'name' => 'Gravity Room',
        ]);
    }

    public static function allNotOfType(string $type = null, bool $asArray = false): array
    {
        $rooms = [];
        foreach (self::$rooms as $room) {
            $class = explode('\\', get_class($room));
            if (array_pop($class) != ucfirst($type)) {
                $rooms[] = $asArray ? $room->toArray() : $room;
            }
        }
        return $rooms;
    }

    public static function allOfType(string $type = null, bool $asArray = false): array
    {
        $rooms = [];
        foreach (self::$rooms as $room) {
            $class = explode('\\', get_class($room));
            if (array_pop($class) == ucfirst($type)) {
                $rooms[] = $asArray ? $room->toArray() : $room;
            }
        }
        return $rooms;
    }
}
