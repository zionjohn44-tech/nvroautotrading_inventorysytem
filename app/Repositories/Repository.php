<?php
declare(strict_types=1);

namespace NVRO\Repositories;

use NVRO\Core\Database;
use PDO;

abstract class Repository implements RepositoryInterface
{
    protected PDO $db;

    public function __construct() { $this->db = Database::connection(); }

    protected function rows(string $sql, array $params = []): array
    {
        $query = $this->db->prepare($sql);
        $query->execute($params);
        return $query->fetchAll();
    }

    protected function row(string $sql, array $params = []): ?array
    {
        return $this->rows($sql, $params)[0] ?? null;
    }
}
