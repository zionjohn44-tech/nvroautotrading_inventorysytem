<?php
declare(strict_types=1);

namespace NVRO\Models;

/**
 * A staff account that can sign in to the system.
 *
 * ABSTRACTION: User is abstract; the system only creates Administrator or
 * DealershipStaff objects, through User::fromRow().
 * POLYMORPHISM: getRole() and canManageStaff() give different answers
 * depending on which subclass the object is.
 */
abstract class User extends Person
{
    public const ROLES = ['Administrator', 'Dealership Staff'];
    public const STATUSES = ['Active', 'Inactive'];

    private ?int $employeeId = null;
    private string $username = '';
    private string $accountStatus = 'Active';

    /** Creates the right subclass for the role stored in the database row. */
    public static function fromRow(array $row): self
    {
        $user = $row['Role'] === 'Administrator' ? new Administrator() : new DealershipStaff();
        $user->setId((int) $row['User_ID']);
        $user->fillPerson($row);
        $user->employeeId = self::intOrNull($row, 'Employee_ID');
        $user->username = (string) $row['Username'];
        $user->accountStatus = (string) ($row['Account_Status'] ?? 'Active');
        return $user;
    }

    /** Role name exactly as it is stored in the database. */
    abstract public function getRole(): string;

    /** Whether this account may open the Staff accounts module. */
    abstract public function canManageStaff(): bool;

    public function getEmployeeId(): ?int
    {
        return $this->employeeId;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getAccountStatus(): string
    {
        return $this->accountStatus;
    }

    public function isActive(): bool
    {
        return $this->accountStatus === 'Active';
    }

    /** Full name of the linked employee, or the username when no name is saved. */
    public function getDisplayName(): string
    {
        return $this->getFullName() ?: $this->username;
    }

    public function describe(): string
    {
        return $this->getDisplayName();
    }

    // Short aliases used by the controllers and view templates.

    public function id(): int
    {
        return (int) $this->getId();
    }

    public function employeeId(): ?int
    {
        return $this->employeeId;
    }

    public function isAdmin(): bool
    {
        return $this->canManageStaff();
    }

    public function role(): string
    {
        return $this->getRole();
    }

    public function name(): string
    {
        return $this->getDisplayName();
    }
}
