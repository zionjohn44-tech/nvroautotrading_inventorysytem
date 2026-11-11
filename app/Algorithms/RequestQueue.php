<?php
declare(strict_types=1);

namespace NVRO\Algorithms;

use SplQueue;

final class RequestQueue
{
    private SplQueue $items;

    public function __construct(array $requests = [])
    {
        $this->items = new SplQueue();
        foreach ($requests as $request) { $this->enqueue($request); }
    }
    public function enqueue(array $request): void { $this->items->enqueue($request); }
    public function dequeue(): ?array { return $this->items->isEmpty() ? null : $this->items->dequeue(); }
    public function peek(): ?array { return $this->items->isEmpty() ? null : $this->items->bottom(); }
    public function count(): int { return $this->items->count(); }
}
