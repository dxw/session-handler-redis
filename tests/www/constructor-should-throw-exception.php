<?php

require_once __DIR__.'/../../vendor/autoload.php';

if ($_GET['handler'] === 'redis') {
	$client = new \Predis\Client([
		'scheme' => 'tcp',
		'host'   => 'DOESNOTEXIST',
		'port'   => 6379,
	]);

	try {
		new \SessionHandlerRedis\Handler($client);
		echo 'FAIL';
	} catch (\Predis\Connection\ConnectionException $e) {
		echo 'PASS';
	}
	return;
}

echo 'PASS';
