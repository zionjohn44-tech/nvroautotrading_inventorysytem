<?php
declare(strict_types=1);

namespace NVRO\Repositories;

/** Reads staff accounts together with their linked employee details. */
final class UserRepository extends Repository
{
    private const SELECT = 'SELECT u.*, e.Employee_ID, e.First_Name, e.Last_Name, e.Contact_Number, e.Position
        FROM `user` u
        LEFT JOIN employee e ON e.User_ID = u.User_ID';

    public function all(): array
    {
        return $this->rows(self::SELECT . ' ORDER BY u.User_ID DESC');
    }

    public function find(int $id): ?array
    {
        return $this->row(self::SELECT . ' WHERE u.User_ID = ?', [$id]);
    }

    public function byUsername(string $username): ?array
    {
        return $this->row(self::SELECT . ' WHERE u.Username = ?', [$username]);
    }

    /** Number of accounts; zero means the first-administrator setup is still needed. */
    public function total(): int
    {
        return $this->count('SELECT COUNT(*) FROM `user`');
    }
}
