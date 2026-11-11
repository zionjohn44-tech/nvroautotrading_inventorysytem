<?php
declare(strict_types=1);

namespace NVRO\Algorithms;

final class LinearSearch
{
    public function search(array $vehicles, string $term): array
    {
        $term = mb_strtolower(trim($term));
        if ($term === '') { return $vehicles; }
        $matches = [];
        foreach ($vehicles as $vehicle) {
            if ((string) $vehicle['Vehicle_ID'] === $term ||
                mb_strpos(mb_strtolower($vehicle['Brand_Name']), $term) !== false ||
                mb_strpos(mb_strtolower($vehicle['Model']), $term) !== false) {
                $matches[] = $vehicle;
            }
        }
        return $matches;
    }
}
