<?php

namespace GuiBranco\Pancake\Queue;

/**
 * Interface IQueue
 *
 * Contract for AMQP queue operations: publishing messages and consuming them
 * with optional dead-letter exchange (DLX) support and exponential-backoff retries.
 *
 * @package GuiBranco\Pancake\Queue
 */
interface IQueue
{
    /**
     * Publishes a message to the specified queue.
     *
     * Picks a server at random from the configured pool for load balancing.
     *
     * @param string $queueName Target queue name.
     * @param string $message   Message body (typically JSON-encoded).
     * @param bool   $withDlx   When true, the queue is declared together with its
     *                          retry and failed queues (exponential-backoff topology).
     *
     * @throws QueueException On connection or publish failure.
     */
    public function publish($queueName, $message, $withDlx = true);

    /**
     * Consumes messages from the specified queue until the timeout expires.
     *
     * Loops through every configured server so that messages hosted on any node
     * are processed. The callable receives the raw AMQPMessage; returning
     * <code>false</code> (or throwing) triggers the retry strategy.
     *
     * @param int      $timeout               Seconds before the consumer loop exits.
     * @param string   $queueName             Queue to consume from.
     * @param callable $callback              Message handler. Return false to nack/retry.
     * @param bool     $withDlx               When true, retry and failed queues are also declared.
     * @param bool     $resetTimeoutOnReceive Reset the timeout clock on each received message.
     * @param int      $qosCount              QoS prefetch count (unacknowledged message limit).
     *
     * @throws QueueException On connection or consume failure.
     */
    public function consume($timeout, $queueName, $callback, $withDlx = true, $resetTimeoutOnReceive = false, $qosCount = 10);
}
