<?php
require_once __DIR__.'/../../vendor/autoload.php';

if ($_GET['handler'] === 'redis') {
    $client = new \Predis\Client([
        'scheme' => 'tcp',
        'host'   => 'redis',
        'port'   => 6379,
    ]);

    session_set_save_handler(new \SessionHandlerRedis\Handler($client));
}
