<?php

use PHPUnit\Framework\TestCase;

require_once 'src/Queue.php';

class QueueTest extends TestCase
{
    public function testEnqueue()
    {
        $queue = new Queue();
        $queue->enqueue('item1');
        $this->assertFalse($queue->isEmpty());
    }

    public function testDequeue()
    {
        $queue = new Queue();
        $queue->enqueue('item1');
        $this->assertSame('item1', $queue->dequeue());
    }

    public function testPeek()
    {
        $queue = new Queue();
        $queue->enqueue('item1');
        $this->assertSame('item1', $queue->peek());
    }

    public function testIsEmpty()
    {
        $queue = new Queue();
        $this->assertTrue($queue->isEmpty());
        $queue->enqueue('item1');
        $this->assertFalse($queue->isEmpty());
        $queue->dequeue();
        $this->assertTrue($queue->isEmpty());
    }
}
