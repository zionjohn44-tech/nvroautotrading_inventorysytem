<?php
declare(strict_types=1);

namespace NVRO\Algorithms;

final class VehicleTree
{
    private ?BstNode $root = null;

    public function __construct(array $vehicles)
    {
        $sorted = (new MergeSort())->sort($vehicles, static fn(array $a, array $b): int => (int) $a['Vehicle_ID'] <=> (int) $b['Vehicle_ID']);
        $this->root = $this->build($sorted, 0, count($sorted) - 1);
    }

    private function build(array &$items, int $start, int $end): ?BstNode
    {
        if ($start > $end) { return null; }
        $middle = intdiv($start + $end, 2);
        $node = new BstNode($items[$middle]);
        $node->left = $this->build($items, $start, $middle - 1);
        $node->right = $this->build($items, $middle + 1, $end);
        return $node;
    }

    public function find(int $id): ?array
    {
        $node = $this->root;
        while ($node !== null) {
            $key = (int) $node->vehicle['Vehicle_ID'];
            if ($key === $id) { return $node->vehicle; }
            $node = $id < $key ? $node->left : $node->right;
        }
        return null;
    }

    public function inOrder(): array
    {
        $result = [];
        $visit = function (?BstNode $node) use (&$visit, &$result): void {
            if ($node === null) { return; }
            $visit($node->left);
            $result[] = $node->vehicle;
            $visit($node->right);
        };
        $visit($this->root);
        return $result;
    }
}
