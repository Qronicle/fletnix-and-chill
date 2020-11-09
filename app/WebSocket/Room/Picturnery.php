<?php
/**
 * Picturnery.php
 */

namespace App\WebSocket\Room;

use App\WebSocket\Connection;
use App\WebSocket\Server;
use App\WebSocket\Words\Word;
use React\EventLoop\Factory;

/**
 * Picturnery
 *
 * @author  Ruud Seberechts
 * @package App\WebSocket\Room
 * @since   2020-03-28 13:34
 */
class Picturnery extends AbstractRoom
{
    const MODE_IDLE         = 'idle';
    const MODE_CHOICE       = 'choice';
    const MODE_DRAWING      = 'drawing';
    const MODE_DRAWING_DONE = 'drawing_done';
    const MODE_ROUND_DONE   = 'round_over';
    const MODE_GAME_DONE    = 'game_over';

    /** @var array */
    protected $currentDrawing = [];

    /** @var string */
    protected $mode = self::MODE_IDLE;

    /** @var Word */
    protected $currentWord;

    /** @var int */
    protected $currentRound = 0;

    /** @var Connection|null */
    protected $currentDrawer = null;

    /** @var Connection[] */
    protected $queuedDrawers = [];

    /** @var array[] */
    protected $guessers = [];

    /** @var float */
    protected $drawingStart;

    /** @var int[] */
    protected $points;

    /** @var */
    protected $timer;

    public $configuration = [
        'max_players' => 10,
        'num_rounds'  => 3,
        'draw_time'   => 90,
    ];

    public function __construct()
    {
        $this->timeout = Factory::create();
    }

    public function canPlayerEnter(): bool
    {
        return count($this->connections) < $this->configuration['max_players'];
    }

    public function onPlayerEnter(Connection $connection)
    {
        // Notify other players of new player
        Server::broadcastOthers($connection, [
            'type'    => 'new_user',
            'user'    => $connection->user->toArray(),
            'message' => [
                'text' => $connection->user->username . ' entered the room',
            ],
        ]);

        // If no points entry exists for this user, create one
        if (!isset($this->points[$connection->user->id])) {
            $this->points[$connection->user->id] = 0;
        }

        // Start the game when it was idle and there are at least 2 players
        if ($this->mode == self::MODE_IDLE && count($this->connections) > 1) {
            $this->startGame();
            return;
        }

        // If it's the first round, push the player to the drawer list
        if ($this->mode != self::MODE_IDLE && $this->currentRound == 1) {
            $this->queuedDrawers[$connection->id] = $connection;
        }

        // Pass the new connection all data
        Server::broadcastTo($connection, $this->getGameDataArray());
    }

    public function incomingMessage(Connection $connection, $message)
    {
        switch ($message->type) {
            case 'pick_word':
                $this->pickWord($connection, $message->word_id);
                break;
            case 'message':
                $this->addMessage($connection, $message->message);
                break;
            case 'drawing_update':
                $this->updateDrawing($connection, $message->data);
        }
    }

    public function startGame()
    {
        $this->currentRound = 1;
        $this->resetPoints();
        $this->queueDrawers();
        $this->startNewDrawing();
    }

    public function reset()
    {
        $this->cancelTimer();
        $this->mode = self::MODE_IDLE;
        $this->currentRound = 1;
        $this->resetPoints();
        $this->guessers = [];
        $this->currentDrawing = [];
        Server::broadcast($this, $this->getGameDataArray());
    }

    public function startNewDrawing()
    {
        // Pick new drawer
        $this->currentDrawing = [];
        $this->guessers = [];
        $this->currentDrawer = null;
        while (count($this->queuedDrawers)) {
            $this->currentDrawer = array_shift($this->queuedDrawers);
            if (!isset($this->connections[$this->currentDrawer->id])) {
                $this->currentDrawer = null;
            } else {
                break;
            }
        }

        // End round when no more drawers
        if (!$this->currentDrawer) {
            if ($this->currentRound < $this->configuration['num_rounds']) {
                $this->currentRound++;
                $this->queueDrawers();
                $this->startNewDrawing();
            } else {
                $this->endGame();
            }
            return;
        }

        // Start choice
        $this->mode = self::MODE_CHOICE;
        Server::broadcast($this, $this->getGameDataArray());
        Server::broadcastTo($this->currentDrawer, [
            'type'    => 'draw_choice',
            'options' => $this->getRandomWords(),
            'timer'   => 15,
        ]);

        $this->timer = Server::loop()->addTimer(15, [$this, 'skipChoice']);
    }

    public function skipChoice()
    {
        Server::broadcastTo($this->currentDrawer, $this->getGameDataArray());
        Server::broadcast($this, [
            'type'    => 'message',
            'message' => [
                'text' => $this->currentDrawer->user->username . ' skipped drawing',
            ],
        ]);
        $this->startNewDrawing();
    }

    public function pickWord(Connection $connection, $wordId)
    {
        // Ignore fake requests
        if ($this->currentDrawer->id != $connection->id) {
            return;
        }
        $this->cancelTimer();

        // Set new word
        $this->currentWord = Word::find($wordId);

        // When invalid word requested, skip to next drawer
        if (!$this->currentWord) {
            $this->startNewDrawing();
            return;
        }

        // Start drawing!
        Server::broadcastTo($connection, [
            'type'  => 'start_drawing',
            'word'  => $this->currentWord->name,
            'timer' => $this->configuration['draw_time'],
        ]);
        Server::broadcastOthers($connection, [
            'type'  => 'start_guessing',
            'hint'  => preg_replace('/\S/i', '_', $this->currentWord->name),
            'timer' => $this->configuration['draw_time'],
        ]);
        $this->mode = self::MODE_DRAWING;
        $this->drawingStart = microtime(true);
        $this->timer = Server::loop()->addTimer($this->configuration['draw_time'] + 1, [$this, 'endDrawing']);
    }

    public function updateDrawing(Connection $connection, array $data)
    {
        if ($this->mode != self::MODE_DRAWING || $this->currentDrawer->id != $connection->id) {
            return;
        }

        $this->currentDrawing = array_merge($this->currentDrawing, $data);
        Server::broadcastOthers($connection, [
            'type' => 'drawing_update',
            'data' => $data,
        ]);
    }

    public function endDrawing()
    {
        $this->cancelTimer();
        if ($this->mode != self::MODE_DRAWING) {
            return;
        }
        $this->mode = self::MODE_DRAWING_DONE;
        Server::broadcast($this, [
            'type'     => 'drawing_end',
            'users'    => $this->getUserArray(),
            'guessers' => array_values($this->guessers),
            'word'     => $this->currentWord->name,
            "message"  => [
                'text' => "Drawing finished! The word " . $this->currentWord->name . ' was guessed by ' . (count($this->guessers) == 1 ? reset($this->guessers)['username'] : count($this->guessers) . ' people'),
            ],
            'timer'    => 10,
        ]);
        $this->timer = Server::loop()->addTimer(10, [$this, 'startNewDrawing']);
    }

    public function endGame()
    {
        $this->cancelTimer();
        $this->mode = self::MODE_GAME_DONE;
        Server::broadcast($this, [
            'type'     => 'game_end',
            'users'    => $this->getUserArray(),
            'timer'    => 20,
        ]);
        $this->timer = Server::loop()->addTimer(20, [$this, 'startGame']);
    }

    public function addMessage(Connection $connection, string $message)
    {
        $broadCastMessage = [
            'type'    => 'message',
            'message' => [
                'username' => $connection->user->username,
                'text'     => $message,
            ],
        ];
        $endRound = false;
        if ($this->mode == self::MODE_DRAWING) {
            if (!$this->currentDrawer || $this->currentDrawer->id != $connection->id) {
                if (mb_stripos($message, $this->currentWord->name) !== false) {
                    unset($broadCastMessage['message']['username']);
                    $broadCastMessage['message']['text'] = $connection->user->username . ' guessed the word!';
                    $this->awardPoints($connection);
                    $endRound = $this->didAllUsersGuessWord();
                }
            }
        }
        Server::broadcast($this, $broadCastMessage);
        if ($endRound) {
            $this->endDrawing();
        }
    }

    protected function didAllUsersGuessWord()
    {
        $allCorrect = true;
        foreach ($this->connections as $connection) {
            if ($this->currentDrawer->id == $connection->id) continue;
            if (!isset($this->guessers[$connection->user->id])) {
                $allCorrect = false;
                break;
            }
        }
        return $allCorrect;
    }

    protected function awardPoints(Connection $connection)
    {
        if ($connection->id != $this->currentDrawer->id) {
            // Points for drawer
            $drawerPoints = [3];
            $points = $drawerPoints[count($this->guessers)] ?? 1;
            $this->points[$this->currentDrawer->user->id] += $points;

            // Points for guesser
            $time = microtime(true);
            $guesserPoints = [5,4,3,2];
            $points = $guesserPoints[count($this->guessers)] ?? 1;
            $this->guessers[$connection->user->id] = [
                'username' => $connection->user->username,
                'time'     => number_format($time - $this->drawingStart, 2) . 's',
                'points'   => $points,
            ];
            $this->points[$connection->user->id] += $points;
        }
    }

    protected function cancelTimer()
    {
        if ($this->timer) {
            Server::loop()->cancelTimer($this->timer);
        }
    }

    protected function getRandomWords(): array
    {
        return Word::getRandom(3);
    }

    protected function getGameDataArray()
    {
        return [
            'type'         => 'init',
            'users'        => $this->getUserArray(),
            'drawing'      => $this->currentDrawing,
            'round'        => $this->currentRound,
            'mode'         => $this->mode,
            'currentRound' => $this->currentRound,
            'totalRounds'  => $this->configuration['num_rounds'],
        ];
    }

    protected function resetPoints()
    {
        foreach ($this->points as $userId => $points) {
            $this->points[$userId] = 0;
        }
    }

    protected function queueDrawers()
    {
        $this->queuedDrawers = array_values($this->connections);
        shuffle($this->queuedDrawers);
    }

    public function onPlayerLeave(Connection $connection)
    {
        Server::broadcastOthers($connection, [
            'type'    => 'message',
            'message' => [
                'text' => $connection->user->username . ' left the game',
            ],
        ]);
        if (count($this->connections) < 2) {
            $this->reset();
        } elseif ($this->currentDrawer->id == $connection->id) {
            if ($this->mode == self::MODE_DRAWING) {
                $this->endDrawing();
            } else {
                $this->startNewDrawing();
            }
        }
    }

    protected function getUserArray(): array
    {
        $users = [];
        foreach ($this->connections as $connection) {
            $users[] = array_merge($connection->user->toArray(), [
                'points' => $this->points[$connection->user->id],
                'drawer' => $this->currentDrawer && $connection->id == $this->currentDrawer->id,
            ]);
        }
        usort($users, function($a, $b) {
            return $a['points'] < $b['points'] ? 1 : -1;
        });
        return $users;
    }

    public function toArray(): array
    {
        $array = parent::toArray();
        $array['description'] = 'Play some PICTURNERY™ ! Current players: ' . count($this->connections) . '/' . $this->configuration['max_players'];
        return $array;
    }
}