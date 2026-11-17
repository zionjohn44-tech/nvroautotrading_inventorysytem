<?php
declare(strict_types=1);

namespace NVRO\Models;

/** INHERITANCE: an Administrator is a User who can also manage staff accounts. */
final class Administrator extends User
{
    public function getRole(): string
    {
        return 'Administrator';
    }

    public function canManageStaff(): bool
    {
        return true;
    }
}
