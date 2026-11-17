<?php
declare(strict_types=1);

namespace NVRO\Models;

/**
 * Base class for every record the system manages (vehicles, customers, users...).
 *
 * ABSTRACTION: callers work with "an entity" through getId() and describe()
 * without knowing which concrete record type they hold.
 * ENCAPSULATION: the identifier is private and is only reachable through
 * getId() / setId().
 */
abstract class Entity
{
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        if ($id !== null && $id < 1) {
            throw new \InvalidArgumentException('An identifier must be a positive number.');
        }
        $this->id = $id;
    }

    /**
     * Short, human-readable label for the record (used in dropdown lists).
     * POLYMORPHISM: every subclass overrides this with its own format.
     */
    abstract public function describe(): string;

    /** Reads an optional integer column from a database row. */
    protected static function intOrNull(array $row, string $column): ?int
    {
        return isset($row[$column]) && $row[$column] !== '' ? (int) $row[$column] : null;
    }
}
