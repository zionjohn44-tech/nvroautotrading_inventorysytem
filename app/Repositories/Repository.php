<?php
declare(strict_types=1);

namespace NVRO\Repositories;

use NVRO\Core\Database;
use PDO;

/**
 * ABSTRACTION + INHERITANCE: shared database helpers for all repositories.
 * Subclasses only write their SQL; connecting and running prepared
 * statements is handled here once.
 */
abstract class Repository implements RepositoryInterface
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** Runs a parameterized query and returns all rows. */
    protected function rows(string $sql, array $params = []): array
    {
        $query = $this->db->prepare($sql);
        $query->execute($params);
        return $query->fetchAll();
    }

    /** Runs a parameterized query and returns the first row, or null. */
    protected function row(string $sql, array $params = []): ?array
    {
        return $this->rows($sql, $params)[0] ?? null;
    }

    /** Runs a parameterized COUNT(*) style query and returns the number. */
    protected function count(string $sql, array $params = []): int
    {
        $query = $this->db->prepare($sql);
        $query->execute($params);
        return (int) $query->fetchColumn();
    }
}
