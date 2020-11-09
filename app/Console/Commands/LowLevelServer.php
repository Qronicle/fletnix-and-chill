<?php

namespace App\Console\Commands;

use App\WebSocket\LowLevel\Server;
use Illuminate\Console\Command;

/**
 * Class LowLevelServer
 *
 * @package App\Console\Commands
 * @author  Ruud Seberechts
 */
class LowLevelServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'server:demo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start Demo Web Socket Server';

    public function handle()
    {
        $this->info("Starting the Demo WebSocket server on port 1337...");
        $this->info("Check out the demo at http://127.0.0.1:8888/low-level-test.html");

        $server = new Server();
        $server->run();
    }
}
