<?php

namespace App\WebSocket\LowLevel;

/**
 * Class Server
 *
 * @package App\WebSocket\LowLevel
 * @author  Ruud Seberechts
 */
class Server
{
    const SERVER_PORT = '1337';

    protected $socket;

    protected $connections;

    public function run()
    {
        // Create TCP/IP stream socket
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        // Reusable port
        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);

        // Bind socket to specified host
        socket_bind($this->socket, 0, self::SERVER_PORT);
        // Listen to port
        socket_listen($this->socket);

        $this->log('Start Server');

        // Start server loop
        while (true) {
            // Check for new client connections
            $this->checkForNewConnections();
            // Check for client requests or disconnects
            $this->checkForClientRequestsOrDisconnects();
        }

        // Close the listening socket
        socket_close($this->socket);
    }

    protected function checkForNewConnections()
    {
        $null = null;
        $aSocket = [$this->socket];
        socket_select($aSocket, $null, $null, 0, 10);
        if ($aSocket) {
            $this->log('New socket connection');
            // Create new client socket connection
            $newConnection = socket_accept($this->socket);
            // Get headers
            $header = socket_read($newConnection, 2048);
            // Handshake baby!
            $this->handshake($header, $newConnection);
            // Send hello message
            $this->connections[] = $newConnection;
            $this->sendMessage($newConnection, 'Welcome!');
        }
    }

    protected function checkForClientRequestsOrDisconnects()
    {
        if (!$this->connections) return;

        // The changed clients will now only contain requests from client sockets
        $updatedConnections = $this->connections;
        socket_select($updatedConnections, $null, $null, 0, 10);

        // Loop through all updated client connections
        foreach ($updatedConnections as $connection) {
            // Check for incoming messages
            $input = '';
            while (socket_recv($connection, $buf, 1024, MSG_DONTWAIT)) {
                if (is_null($buf) || !$buf) break;
                $input .= $buf;
            }
            if ($input) {
                if ($requestMessage = trim($this->decodeMessage($input))) {
                    $this->log('Incoming message: ' . $requestMessage);
                    $this->sendMessage($connection, 'Hello yourself!');
                }
                continue;
            }
            // Check for closed connection
            $buf = @socket_read($connection, 1024, PHP_NORMAL_READ);
            if ($buf === false) {
                $this->closeConnection($connection);
            }
        }
    }

    protected function handshake($receivedHeader, $clientConnection)
    {
        $headers = [];
        $lines = preg_split("/\r\n/", $receivedHeader);
        foreach ($lines as $line) {
            $line = chop($line);
            if (preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {
                $headers[strtolower($matches[1])] = $matches[2];
            }
        }
        $secAccept = $this->encodeAcceptKey($headers['sec-websocket-key']);
        // handshake header response
        $response =
            "HTTP/1.1 101 Switching Protocols\r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "Sec-WebSocket-Accept: $secAccept\r\n\r\n";
        socket_write($clientConnection, $response, strlen($response));
    }

    public function sendMessage($connection, string $message)
    {
        $this->log('Send Message: ' . $message);
        $encodedMessage = $this->encodeMessage($message);
        socket_write($connection, $encodedMessage, strlen($encodedMessage));
    }

    protected function encodeAcceptKey(string $key): string
    {
        return base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
    }

    protected function encodeMessage(string $message)
    {
        $length = strlen($message);

        $bytesHeader = [];
        $bytesHeader[0] = 129; // 0x1 text frame (FIN + opcode)

        if ($length <= 125) {
            $bytesHeader[1] = $length;
        } else if ($length >= 126 && $length <= 65535) {
            $bytesHeader[1] = 126;
            $bytesHeader[2] = ($length >> 8) & 255;
            $bytesHeader[3] = ($length) & 255;
        } else {
            $bytesHeader[1] = 127;
            $bytesHeader[2] = ($length >> 56) & 255;
            $bytesHeader[3] = ($length >> 48) & 255;
            $bytesHeader[4] = ($length >> 40) & 255;
            $bytesHeader[5] = ($length >> 32) & 255;
            $bytesHeader[6] = ($length >> 24) & 255;
            $bytesHeader[7] = ($length >> 16) & 255;
            $bytesHeader[8] = ($length >> 8) & 255;
            $bytesHeader[9] = ($length) & 255;
        }

        return implode(array_map("chr", $bytesHeader)) . $message;
    }

    public function decodeMessage(string $message)
    {
        $message = array_map("ord", str_split($message));
        $l = $message[1] and 127;
        if ($l == 126) {
            $iFM = 4;
        } else if ($l == 127) {
            $iFM = 10;
        } else {
            $iFM = 2;
        }
        $masks = array_slice($message, $iFM, 4);
        $out = '';
        for ($i = $iFM + 4, $j = 0; $i < count($message); $i++, $j++) {
            $out .= chr($message[$i] ^ $masks[$j % 4]);
        }
        return $out;
    }

    protected function closeConnection($connection)
    {
        $this->log('Connection closed');
        foreach ($this->connections as $i => $conn) {
            if ($connection == $conn) {
                unset($this->connections[$i]);
                break;
            }
        }
        socket_close($connection);
    }

    public function log($message)
    {
        echo date('Y-m-d H:i:s') . '  ' . ((string)$message) . "\n";
    }
}
