<?php

namespace GuiBranco\Pancake\Queue;

/**
 * QueueOptions holds tunable settings for {@see Queue}.
 *
 * @package GuiBranco\Pancake\Queue
 */
class QueueOptions
{
    public float $connectionTimeout;
    public float $readWriteTimeout;
    public int $dlxRetryTtlMs;
    public int $maxRetries;
    public int $initialRetryDelayMs;
    public float $retryMultiplier;
    public string $retryQueueSuffix;
    public string $deadQueueSuffix;

    /**
     * QueueOptions constructor.
     *
     * @param float $connectionTimeout  AMQP connection timeout in seconds (default: 10.0).
     * @param float $readWriteTimeout   AMQP read/write timeout in seconds (default: 10.0).
     * @param int $dlxRetryTtlMs        TTL, in milliseconds, of the simple `{queue}-retry` companion queue
     *                                  declared by {@see Queue::declareQueueWithDLX()} before a message
     *                                  bounces back to the original queue (default: 3600000 = 1 hour).
     * @param int $maxRetries           Maximum number of exponential-backoff retry attempts performed by
     *                                  {@see Queue::retry()} before the message is moved to the dead queue
     *                                  (default: 5).
     * @param int $initialRetryDelayMs  Delay, in milliseconds, before the first retry attempt (default: 1000).
     * @param float $retryMultiplier    Multiplier applied to the delay on each subsequent retry attempt
     *                                  (default: 2.0, i.e. the delay doubles every attempt).
     * @param string $retryQueueSuffix  Suffix appended to the queue name to build each per-attempt retry
     *                                  queue name (default: '-retry').
     * @param string $deadQueueSuffix   Suffix appended to the queue name to build the terminal dead queue
     *                                  name used once $maxRetries is exceeded (default: '-failed').
     */
    public function __construct(
        float $connectionTimeout = 10.0,
        float $readWriteTimeout = 10.0,
        int $dlxRetryTtlMs = 3_600_000,
        int $maxRetries = 5,
        int $initialRetryDelayMs = 1000,
        float $retryMultiplier = 2.0,
        string $retryQueueSuffix = '-retry',
        string $deadQueueSuffix = '-failed'
    ) {
        $this->connectionTimeout = $connectionTimeout;
        $this->readWriteTimeout = $readWriteTimeout;
        $this->dlxRetryTtlMs = $dlxRetryTtlMs;
        $this->maxRetries = $maxRetries;
        $this->initialRetryDelayMs = $initialRetryDelayMs;
        $this->retryMultiplier = $retryMultiplier;
        $this->retryQueueSuffix = $retryQueueSuffix;
        $this->deadQueueSuffix = $deadQueueSuffix;
    }
}
