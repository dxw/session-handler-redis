<?php

namespace SessionHandlerRedis;

class Handler implements \SessionHandlerInterface
{
    /** @var \Predis\Client */
    private $client;

    public function __construct(\Predis\Client $client)
    {
        $this->client = $client;
    }

    public function close() : bool
    {
        $this->client->disconnect();
        return true;
    }

    /** @param string $sessionId */
    public function destroy($sessionId) : bool
    {
        $this->client->hdel('lastupdated', $sessionId);
        $this->client->hdel('data', $sessionId);
        return true;
    }

    /** @param int $maxLifetime */
    public function gc($maxLifetime) : bool
    {
        $sessions = $this->client->hgetall('lastupdated');
        $cutoff = $this->getTimestamp(-$maxLifetime);
        $toDelete = [];
        foreach ($sessions as $id => $timestamp) {
            if ($timestamp < $cutoff) {
                $toDelete[] = $id;
            }
        }

        $this->client->hdel('lastupdated', ...$toDelete);
        return true;
    }

    /**
     * This function does nothing.
     *
     * @param string $savePath
     * @param string $sessionName
     */
    public function open($savePath, $sessionName) : bool
    {
        return true;
    }

    /** @param string $sessionId */
    public function read($sessionId) : string
    {
        $value = $this->client->hget('data', $sessionId);
        if ($value === null) {
            return '';
        }

        return $value;
    }

    /**
     * @param string $sessionId
     * @param string $sessionData
     */
    public function write($sessionId, $sessionData) : bool
    {
        $this->client->hset('lastupdated', $sessionId, $this->getTimestamp(0));
        $this->client->hset('data', $sessionId, $sessionData);
        return true;
    }

    /**
     * Returns a date formatted like '2020-10-28T14:42:49+00:00'
     */
    private function getTimestamp(int $offset) : string
    {
        // Use `date()` so we can mock the date
        $currentTime = (new \DateTimeImmutable(date('c')));
        $currentTimeUtc = $currentTime->setTimezone(new \DateTimeZone('Etc/UTC'));
        $offsettedTime = $currentTimeUtc->modify("$offset second");
        return $offsettedTime->format('c');
    }
}
