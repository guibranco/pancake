<?php
require_once 'IQueue.php';

class Queue implements IQueue {
    private $items = [];

    public function enqueue($item): void {
        $this->items[] = $item;
    }

    public function dequeue() {
        return array_shift($this->items);
    }

    public function peek() {
        return $this->items[0];
    }

    public function isEmpty(): bool {
        return empty($this->items);
    }
}
?>