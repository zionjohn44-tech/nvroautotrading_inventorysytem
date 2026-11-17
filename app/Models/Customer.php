<?php
declare(strict_types=1);

namespace NVRO\Models;

/**
 * A dealership customer. Customers do not log in; staff record them together
 * with an inquiry, and they can book test drives or buy vehicles.
 */
final class Customer extends Person
{
    private string $email = '';
    private string $address = '';

    public static function fromRow(array $row): self
    {
        $customer = new self();
        $customer->setId(self::intOrNull($row, 'Customer_ID'));
        $customer->fillPerson($row);
        $customer->email = (string) ($row['Email'] ?? '');
        $customer->address = (string) ($row['Address'] ?? '');
        return $customer;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    /** ENCAPSULATION: an invalid email address can never be stored. */
    public function setEmail(string $email): void
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException('Enter a valid email address.');
        }
        $this->email = $email;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    /** Example: "Juan Dela Cruz · 0917 123 4567" */
    public function describe(): string
    {
        return $this->getFirstName() . ' ' . $this->getLastName() . ' · ' . $this->getContactNumber();
    }
}
