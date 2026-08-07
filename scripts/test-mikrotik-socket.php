<?php

$host = '192.168.10.1';
$port = 8728;

function tryConnect(string $host, int $port, ?string $bindIp): string
{
    $options = [];
    if ($bindIp) {
        $options['socket'] = ['bindto' => $bindIp.':0'];
    }

    $ctx = stream_context_create($options);
    $errno = 0;
    $errstr = '';
    $socket = @stream_socket_client(
        "tcp://{$host}:{$port}",
        $errno,
        $errstr,
        5,
        STREAM_CLIENT_CONNECT,
        $ctx
    );

    if ($socket) {
        fclose($socket);

        return 'SUCCESS';
    }

    return "FAIL ({$errno}): {$errstr}";
}

echo "Default route: ".tryConnect($host, $port, null).PHP_EOL;
echo "Bind 192.168.10.254: ".tryConnect($host, $port, '192.168.10.254').PHP_EOL;
echo "Bind 192.168.1.182: ".tryConnect($host, $port, '192.168.1.182').PHP_EOL;
