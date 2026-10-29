<?php

/*
 * PHPacto - Contract testing solution
 *
 * Copyright (c) Damian Długosz
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Simple HTTP mock server for tests.
 * Returns a valid JSON response to any GET request, then exits.
 *
 * Usage: php tests/bin/mock_server.php [port]
 */

$port = $argv[1] ?? 8889;

$response = sprintf(
    "HTTP/1.1 200 OK\r\nContent-Type: application/json\r\nContent-Length: %d\r\nConnection: close\r\n\r\n%s",
    strlen(json_response()),
    json_response()
);

$socket = stream_socket_server("tcp://127.0.0.1:{$port}", $errno, $errstr);
if (!$socket) {
    exit(1);
}

// Listen for up to 30 seconds for a request
stream_set_timeout($socket, 30);
$client = stream_socket_accept($socket, 30);
if ($client) {
    stream_socket_sendall($client, $response);
    fclose($client);
}

fclose($socket);

function json_response(): string
{
    return json_encode([
        'id' => 1,
        'title' => 'Post Title',
        'body' => 'This is a mocked response for testing.',
        'userId' => 1,
    ]);
}
