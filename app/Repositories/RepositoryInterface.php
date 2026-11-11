<?php
declare(strict_types=1);

namespace NVRO\Repositories;

interface RepositoryInterface
{
    public function all(): array;
    public function find(int $id): ?array;
}
