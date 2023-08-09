# Redis Session Handler

This package allows you to replace PHP's default session handling with a Redis-based alternative.

## How to use

1. Require this package in your project: `composer require dxw/session-handler-redis`
1. Before you start your session, configure your Predis client, pass it to the Handler class, and call `session_set_handler()` with that Handler, e.g.
   ```php
   $client = new \Predis\Client([
     'scheme' => 'tcp',
     'host'   => 'redis',
     'port'   => 6379,
   ]);

   session_set_save_handler(new \SessionHandlerRedis\Handler($client));
   ```
1. Then call `session_start()` when needed

## Development

This package implements the script-to-rule-them-all pattern.

`script/update` to install the dependencies
`script/test` to run the tests
