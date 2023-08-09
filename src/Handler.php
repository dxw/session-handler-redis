<?php

namespace SessionHandlerRedis;

class Handler implements \SessionHandlerInterface
{
	/** @var \Predis\Client */
	private $client;

	public function __construct(\Predis\Client $client)
	{
		$this->client = $client;
		$this->client->connect();
	}

	public function close(): bool
	{
		$this->client->disconnect();
		return true;
	}

	/** @param string $sessionId */
	public function destroy($sessionId): bool
	{
		$this->client->hdel('lastupdated', [$sessionId]);
		$this->client->hdel('data', [$sessionId]);
		return true;
	}

	/** @param int $maxLifetime */
	public function gc($maxLifetime): int
	{
		$sessions = $this->client->hgetall('lastupdated');
		$cutoff = $this->getTimestamp(-$maxLifetime);
		$toDelete = [];
		foreach ($sessions as $id => $timestamp) {
			if ($timestamp < $cutoff) {
				$toDelete[] = $id;
			}
		}

		return ($this->client->hdel('lastupdated', $toDelete));
	}

	/**
	 * This function does nothing.
	 *
	 * @param string $savePath
	 * @param string $sessionName
	 */
	public function open($savePath, $sessionName): bool
	{
		return true;
	}

	/** @param string $sessionId */
	public function read($sessionId): string
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
	public function write($sessionId, $sessionData): bool
	{
		$this->client->hset('lastupdated', $sessionId, $this->getTimestamp(0));
		$this->client->hset('data', $sessionId, $sessionData);
		return true;
	}

	/**
	 * Returns a date formatted like '2020-10-28T14:42:49+00:00'
	 */
	private function getTimestamp(int $offset): string
	{
		// Use `date()` so we can mock the date
		$currentTime = (new \DateTimeImmutable(date('c')));
		$currentTimeUtc = $currentTime->setTimezone(new \DateTimeZone('Etc/UTC'));
		$offsettedTime = $currentTimeUtc->modify("$offset second");
		// In theory we could get a false value here, but it's hard to see it ever happening
		// If it does, we probably want to fail loudly
		if ($offsettedTime === false) {
			throw new \UnexpectedValueException("Unable to produce datetime with given offset");
		} else {
			return $offsettedTime->format('c');
		}
	}
}
