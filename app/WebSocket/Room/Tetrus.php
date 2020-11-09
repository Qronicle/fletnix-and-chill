<?php
/**
 * Tetrus.php
 */

namespace App\WebSocket\Room;

use App\WebSocket\Connection;
use App\WebSocket\Server;

/**
 * Tetrus
 *
 * @author  Ruud Seberechts
 * @package App\WebSocket\Room
 * @since   2020-04-14 11:43
 */
class Tetrus extends AbstractRoom
{
    const SHAPE_I = 0;
    const SHAPE_J = 1;
    const SHAPE_L = 2;
    const SHAPE_O = 3;
    const SHAPE_S = 4;
    const SHAPE_T = 5;
    const SHAPE_Z = 6;

    protected $speed;

    protected $shapes = [];

    protected $shapeIndex = [];
    protected $points = [];

    protected $timer;

    protected $pointsPerLine = [
        0 => 0,
        1 => 40,
        2 => 100,
        3 => 300,
        4 => 1200,
    ];

    protected $settings = [
        'fieldWidth'  => 10,
        'fieldHeight' => 20,
    ];

    public function startGame()
    {
        $this->cancelTimer();
        // Reset speed
        $this->speed = 500;
        // Reset shapes
        $this->shapes = [];
        $this->addShapes();
        // Reset players
        foreach ($this->connections as $connection) {
            $this->points[$connection->id] = 0;
            $this->shapeIndex[$connection->id] = 0;
        }
        // Start!
        $connections = array_values($this->connections);
        foreach ($connections as $i => $connection) {
            $opponent = $connections[$i == 0 ? 1 : 0];
            Server::broadcastTo($connection, [
                'type'     => 'init',
                'player'   => $connection->user->toArray(),
                'opponent' => $opponent->user->toArray(),
                'speed'    => $this->speed,
                'queue'    => [$this->shapes[0], $this->shapes[1], $this->shapes[2]],
            ]);
        }
        $this->timer = Server::loop()->addTimer(3, [$this, 'startGameForReal']);
    }

    public function startGameForReal()
    {
        Server::broadcast($this, [
            'type' => 'start',
        ]);
    }

    protected function endGame()
    {
        $this->cancelTimer();
        Server::broadcast($this, [
            'type' => 'end',
        ]);
    }

    protected function addShapes()
    {
        $newShapes = [0, 1, 2, 3, 4, 5, 6];
        shuffle($newShapes);
        foreach ($newShapes as $shape) {
            $this->shapes[] = $shape;
        }
    }

    public function incomingMessage(Connection $connection, $message)
    {
        //echo "> " . $message->type . "\n";
        switch ($message->type) {
            case 'block_placed':
                $this->blockPlaced($connection, $message);
                break;
            case 'block_moved':
                Server::broadcastOthers($connection, [
                    'type'      => 'foe_block_moved',
                    'tetromino' => $message->tetromino,
                    'lines'     => $message->lines ?? null,
                ]);
                break;
            case 'game_over':
                if ($opponent = $this->getOpponent($connection)) {
                    Server::broadcast($this, [
                        'type'   => 'game_finished',
                        'winner' => $opponent->user->toArray(),
                    ]);
                    $this->timer = Server::loop()->addTimer(5, [$this, 'startGame']);
                }
                break;
        }
    }

    protected function getOpponent(Connection $connection)
    {
        foreach ($this->connections as $otherConnection) {
            if ($connection->id != $otherConnection->id) {
                return $otherConnection;
            }
        }
        return null;
    }

    protected function blockPlaced(Connection $connection, $message)
    {
        $this->addPoints($connection, $message);
        $opponentLines = [];
        for ($l = 1; $l < $message->numLines; $l++) {
            $line = array_fill(0, $this->settings['fieldWidth'], 'X');
            $line[rand(0, $this->settings['fieldWidth'] - 1)] = 0;
            $opponentLines[] = $line;
        }
        $shapeIndex = ++$this->shapeIndex[$connection->id];
        Server::broadcastTo($connection, [
            'type'   => 'self_update',
            'points' => $this->points[$connection->id],
            'queue'  => $this->getShapeAtIndex($shapeIndex + 2),
        ]);
        Server::broadcastOthers($connection, [
            'type'      => 'foe_block_placed',
            'points'    => $this->points[$connection->id],
            'tetromino' => $message->tetromino,
            'lines'     => $opponentLines,
            'queue'     => $this->getShapeAtIndex($shapeIndex + 2),
        ]);
    }

    protected function getShapeAtIndex(int $index): int
    {
        while (!isset($this->shapes[$index])) {
            $this->addShapes();
        }
        return $this->shapes[$index];
    }

    protected function addPoints(Connection $connection, $message)
    {
        $level = 1; // Sure
        // Block placed
        $newPoints = 1; // min(1, $this->settings['fieldHeight'] - $message->tetromino->position[1]);
        // Lines formed
        $newPoints += $level * $this->pointsPerLine[$message->numLines ?? 0];
        // Add to total
        $this->points[$connection->id] += $newPoints;
    }

    public function onPlayerEnter(Connection $connection)
    {
        if (count($this->connections) == 2) {
            $this->startGame();
        }
    }

    public function onPlayerLeave(Connection $connection)
    {
        $this->endGame();
    }

    public function canPlayerEnter(): bool
    {
        return count($this->connections) < 2;
    }

    protected function cancelTimer()
    {
        if ($this->timer) {
            Server::loop()->cancelTimer($this->timer);
        }
    }

    public function toArray(): array
    {
        $array = parent::toArray();
        $array['description'] = 'Play some TETRUS™ ! Current players: ' . count($this->connections) . '/2';
        return $array;
    }
}