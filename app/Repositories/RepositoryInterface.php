<?php
declare(strict_types=1);

namespace NVRO\Repositories;

/**
 * ABSTRACTION: the contract every repository follows. Controllers can load any
 * record through find() without knowing which table or query is used.
 */
interface RepositoryInterface
{
    /** Returns every row of the repository's main table. */
    public function all(): array;

    /** Returns one row by its primary key, or null when it does not exist. */
    public function find(int $id): ?array;
}
