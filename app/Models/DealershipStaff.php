<?php
declare(strict_types=1);

namespace NVRO\Models;

/** INHERITANCE: dealership staff manage records, but not staff accounts. */
final class DealershipStaff extends User
{
    public function getRole(): string
    {
        return 'Dealership Staff';
    }

    public function canManageStaff(): bool
    {
        return false;
    }
}
