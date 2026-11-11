<?php
declare(strict_types=1);

namespace NVRO\Algorithms;

final class BstNode
{
    public array $vehicle;
    public ?BstNode $left = null;
    public ?BstNode $right = null;
    public function __construct(array $vehicle) { $this->vehicle = $vehicle; }
}
