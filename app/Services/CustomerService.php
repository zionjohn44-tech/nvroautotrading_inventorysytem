<?php
declare(strict_types=1);

namespace NVRO\Services;

use NVRO\Core\Database;
use NVRO\Core\HttpException;
use NVRO\Models\Customer;
use NVRO\Repositories\CustomerRepository;
use NVRO\Validation\Validator;
use NVRO\Validation\ValidationException;

/** Validates customer details and saves them as a Customer object. */
final class CustomerService
{
    /** Creates ($id = null) or updates a customer and returns the customer ID. */
    public function save(array $input, ?int $id): int
    {
        $v = new Validator($input);
        $first = $v->text('First_Name', 'First name', 50);
        $last = $v->text('Last_Name', 'Last name', 50);
        $email = $v->text('Email', 'Email', 100);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $v->error('Email', 'Enter a valid email address.');
        }
        $phone = $v->phone();
        $address = $v->text('Address', 'Address', 1000);
        $v->finish();

        // Encapsulation: the validated values go into the object through its setters.
        $customer = new Customer();
        $customer->setId($id);
        $customer->setFirstName($first);
        $customer->setLastName($last);
        $customer->setEmail($email);
        $customer->setContactNumber($phone);
        $customer->setAddress($address);

        return $this->persist($customer);
    }

    private function persist(Customer $customer): int
    {
        $db = Database::connection();
        $id = $customer->getId();
        if ($id !== null && !(new CustomerRepository())->find($id)) {
            throw new HttpException(404, 'Customer not found.');
        }

        $query = $db->prepare('SELECT Customer_ID FROM customer WHERE Email = ? AND Customer_ID <> ?');
        $query->execute([$customer->getEmail(), $id ?? 0]);
        if ($query->fetch()) {
            throw new ValidationException(['Email' => 'A customer with this email already exists. Select or edit that customer.']);
        }

        $values = [
            $customer->getFirstName(),
            $customer->getLastName(),
            $customer->getEmail(),
            $customer->getContactNumber(),
            $customer->getAddress(),
        ];
        if ($id === null) {
            $db->prepare('INSERT INTO customer (First_Name, Last_Name, Email, Contact_Number, Address) VALUES (?, ?, ?, ?, ?)')
                ->execute($values);
            return (int) $db->lastInsertId();
        }
        $values[] = $id;
        $db->prepare('UPDATE customer SET First_Name = ?, Last_Name = ?, Email = ?, Contact_Number = ?, Address = ? WHERE Customer_ID = ?')
            ->execute($values);
        return $id;
    }
}
