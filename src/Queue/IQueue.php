<?php

namespace GuiBranco\Pancake\Queue;

use PhpAmqpLib\Message\AMQPMessage;

interface IQueue
{
    /**
     * Publishes a message to a queue, connecting to a randomly chosen server.
     *
     * @param string $queueName The name of the queue to publish to.
     * @param string $message The message body.
     * @param bool $declareDlx Whether to declare the queue with a dead-letter companion queue
     *                         (default: true). Pass false to declare a plain durable queue instead.
     *
     * @throws QueueException If no server is configured or the publish fails.
     */
    public function publish(string $queueName, string $message, bool $declareDlx = true): void;

    /**
     * Consumes messages from a queue, looping through every configured server in turn.
     *
     * @param int $timeout Seconds of inactivity (or total time, if $resetTimeoutOnReceive is false)
     *                      to wait per server before moving on to the next one.
     * @param string $queueName The name of the queue to consume from.
     * @param callable $callback Invoked as `$callback(int $timeout, int $startTime, AMQPMessage $msg)` for
     *                           every received message.
     * @param bool $resetTimeoutOnReceive When true, receiving a message resets the inactivity clock instead
     *                                    of counting down from when consumption started (default: false).
     * @param int $qos Prefetch count passed to `basic_qos` (default: 10).
     * @param bool $declareDlx Whether to declare the queue with a dead-letter companion queue
     *                         (default: true). Pass false to declare a plain durable queue instead.
     *
     * @throws QueueException If no server is configured.
     */
    public function consume(
        int $timeout,
        string $queueName,
        callable $callback,
        bool $resetTimeoutOnReceive = false,
        int $qos = 10,
        bool $declareDlx = true
    ): void;

    /**
     * Re-publishes $message to a per-attempt retry queue with an exponentially increasing TTL, so it
     * dead-letters back to $queueName after the computed delay. Once the configured maximum number of
     * attempts is exceeded, the message is published to the terminal dead queue instead.
     *
     * @param string $queueName The name of the original queue the message came from.
     * @param AMQPMessage $message The message to retry.
     *
     * @return bool True if the message was scheduled for another retry attempt, false if it was moved
     *              to the dead queue instead.
     *
     * @throws QueueException If no server is configured or publishing fails.
     */
    public function retry(string $queueName, AMQPMessage $message): bool;

    /**
     * Reads the current retry attempt count from a message's headers (0 if it was never retried).
     */
    public function getRetryCount(AMQPMessage $message): int;
}
