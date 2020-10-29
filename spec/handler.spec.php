<?php

namespace SessionHandlerRedis;

use Kahlan\Plugin\Double;

describe(Handler::class, function () {
    beforeEach(function () {
        $this->client = Double::instance([
            'extends' => \Predis\Client::class,
        ]);
        allow($this->client)->toReceive('connect');

        $this->handler = new Handler($this->client);
    });

    it('is a SessionHandlerInterface', function () {
        expect($this->handler)->toBeAnInstanceOf(\SessionHandlerInterface::class);
    });

    describe('->__construct()', function () {
        it("should throw an exception if the redis connection doesn't work", function () {
            $exception = new \Predis\Connection\ConnectionException(Double::instance(['implements' => \Predis\Connection\NodeConnectionInterface::class]));
            allow($this->client)->toReceive('connect')->with()->andRun(function () use ($exception) {
                throw $exception;
            });

            expect(function () {
                new Handler($this->client);
            })->toThrow($exception);
        });
    });

    describe('->close()', function () {
        it('calls $client->disconnect()', function () {
            allow($this->client)->toReceive('disconnect');
            expect($this->client)->toReceive('disconnect')->with();
            $return = $this->handler->close();
            expect($return)->toBe(true);
        });
    });

    describe('->destroy()', function () {
        it('calls HDEL and returns true', function () {
            allow($this->client)->toReceive('hdel');
            expect($this->client)->toReceive('hdel')->with('lastupdated', 'session123');
            expect($this->client)->toReceive('hdel')->with('data', 'session123');
            $return = $this->handler->destroy('session123');
            expect($return)->toBe(true);
        });
    });

    describe('->gc()', function () {
        beforeEach(function () {
            // Current time
            allow('date')->toBeCalled()->with('c')->andReturn('Mon Jan 2 15:04:05 -0700 MST 2006');

            allow($this->client)->toReceive('hgetall')->with('lastupdated')->andReturn([
                'session123' => '2006-01-02T22:04:05+00:00', // current time
                'session456' => '2006-01-02T22:03:55+00:00', // current time -10
                'session789' => '2006-01-02T22:03:45+00:00', // current time -20
            ]);
        });

        it('deletes sessions older than 5 seconds', function () {
            allow($this->client)->toReceive('hdel');
            expect($this->client)->toReceive('hdel')->with('lastupdated', 'session456', 'session789');
            $return = $this->handler->gc(5);
            expect($return)->toBe(true);
        });

        it('deletes sessions older than 15 seconds', function () {
            allow($this->client)->toReceive('hdel');
            expect($this->client)->toReceive('hdel')->with('lastupdated', 'session789');
            $return = $this->handler->gc(15);
            expect($return)->toBe(true);
        });
    });

    describe('->open()', function () {
        it('does nothing', function () {
            $return = $this->handler->open('a', 'b');
            expect($return)->toBe(true);
        });
    });

    describe('->read()', function () {
        context('the value is unset', function () {
            it('returns the empty string', function () {
                allow($this->client)->toReceive('hget')->with('data', 'session123')->andReturn(null);
                $return = $this->handler->read('session123');
                expect($return)->toBe('');
            });
        });
        context('the value is set', function () {
            it('returns the appropriate value', function () {
                allow($this->client)->toReceive('hget')->with('data', 'session123')->andReturn('PHP_SERIALIZED_STUFF');
                $return = $this->handler->read('session123');
                expect($return)->toBe('PHP_SERIALIZED_STUFF');
            });
        });
    });

    describe('->write()', function () {
        it('stores the value and lastupdated time', function () {
            allow('date')->toBeCalled()->with('c')->andReturn('Mon Jan 2 15:04:05 -0700 MST 2006');

            allow($this->client)->toReceive('hset');
            expect($this->client)->toReceive('hset')->with('lastupdated', 'session123', '2006-01-02T22:04:05+00:00');
            expect($this->client)->toReceive('hset')->with('data', 'session123', 'PHP_SERIALIZED_STUFF');
            $return = $this->handler->write('session123', 'PHP_SERIALIZED_STUFF');
            expect($return)->toBe(true);
        });
    });
});
