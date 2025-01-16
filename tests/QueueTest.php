<?php

use GuiBranco\Pancake;
use PHPUnit\Framework\TestCase;

class QueueTest extends TestCase
{
    private $queue;

    protected function setUp(): void
    {
        $servers = [
            ['host' => 'server1', 'port' => 5672, 'user' => 'guest', 'password' => 'guest'],
            ['host' => 'server2', 'port' => 5672, 'user' => 'guest', 'password' => 'guest']
        ];
        $this->queue = new Queue($servers);
    }

    public function testPublishWithDLX()
    {
        $this->queue->publish('testQueue', 'testMessage', true);
        // Assertions to verify message was published with DLX
    }

    public function testPublishWithoutDLX()
    {
        $this->queue->publish('testQueue', 'testMessage', false);
        // Assertions to verify message was published without DLX
    }

    public function testConsumeWithDLX()
    {
        $callback = function ($msg) {
            // Process message
        };
        $this->queue->consume(30, 'testQueue', $callback, false, 10, true);
        // Assertions to verify messages are consumed with DLX
    }

    public function testConsumeWithoutDLX()
    {
        $callback = function ($msg) {
            // Process message
        };
        $this->queue->consume(30, 'testQueue', $callback, false, 10, false);
        // Assertions to verify messages are consumed without DLX
    }

    public function testConsumeWithDifferentQoS()
    {
        $callback = function ($msg) {
            // Process message
        };
        $this->queue->consume(30, 'testQueue', $callback, false, 5, true);
        // Assertions to verify messages are consumed with different QoS settings
    }

    // Additional test cases

}
