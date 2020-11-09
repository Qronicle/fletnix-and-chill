<?php
/**
 * WebSocketServer.php
 */

namespace App\Console\Commands;

use App\WebSocket\Server;
use Illuminate\Console\Command;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\StreamSelectLoop;

/**
 * WebSocketServer
 *
 * @author  Ruud Seberechts
 * @package App\Console\Commands
 * @since   2020-03-25 15:17
 */
class WebSocketServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'server:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start Web Socket Server';

    public function handle()
    {
        $this->info("Starting the WebSocket server on port 6001...");

        // Create server
        $fletnixAndChillServer = Server::get();
        $server = IoServer::factory(new HttpServer(new WsServer($fletnixAndChillServer)), 6001);
        /* @var StreamSelectLoop $server->loop */
        $fletnixAndChillServer->setLoop($server->loop);
        $server->run();
    }
}
