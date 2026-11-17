<?php
declare(strict_types=1);

namespace NVRO\Models;

/**
 * Shared details of any person stored in the system.
 *
 * INHERITANCE: Customer and User (staff accounts) both extend Person, so the
 * name and contact-number code is written once and reused.
 */
abstract class Person extends Entity
{
    private string $firstName = '';
    private string $lastName = '';
    private string $contactNumber = '';

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getContactNumber(): string
    {
        return $this->contactNumber;
    }

    public function setContactNumber(string $contactNumber): void
    {
        $this->contactNumber = $contactNumber;
    }

    /** First and last name joined by a space, without surrounding blanks. */
    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    /** Copies the shared name and contact columns from a database row. */
    protected function fillPerson(array $row): void
    {
        $this->setFirstName((string) ($row['First_Name'] ?? ''));
        $this->setLastName((string) ($row['Last_Name'] ?? ''));
        $this->setContactNumber((string) ($row['Contact_Number'] ?? ''));
    }
}
