<?php
declare(strict_types=1);

namespace NVRO\Algorithms;

final class MergeSort
{
    public function sort(array $items, callable $compare): array
    {
        $count = count($items);
        if ($count < 2) { return $items; }
        $middle = intdiv($count, 2);
        $left = $this->sort(array_slice($items, 0, $middle), $compare);
        $right = $this->sort(array_slice($items, $middle), $compare);
        $result = [];
        $i = $j = 0;
        while ($i < count($left) && $j < count($right)) {
            $result[] = $compare($left[$i], $right[$j]) <= 0 ? $left[$i++] : $right[$j++];
        }
        while ($i < count($left)) { $result[] = $left[$i++]; }
        while ($j < count($right)) { $result[] = $right[$j++]; }
        return $result;
    }
}
