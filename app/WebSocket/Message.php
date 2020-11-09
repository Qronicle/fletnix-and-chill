<?php
/**
 * Message.php
 */

namespace App\WebSocket;

/**
 * Message
 *
 * @author  Ruud Seberechts
 * @package App\WebSocket
 * @since   2020-03-28 13:40
 */
class Message
{
    public static function alreadyConnected(): array
    {
        return [
            'type' => 'already_connected',
        ];
    }

    public static function cannotEnter(): array
    {
        return [
            'type' => 'room_unavailable',
        ];
    }
}