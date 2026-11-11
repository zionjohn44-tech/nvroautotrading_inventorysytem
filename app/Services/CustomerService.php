<?php
declare(strict_types=1);

namespace NVRO\Services;

use NVRO\Core\Database;
use NVRO\Core\HttpException;
use NVRO\Repositories\CustomerRepository;
use NVRO\Validation\Validator;
use NVRO\Validation\ValidationException;

final class CustomerService
{
    public function save(array $input, ?int $id): int
    {
        $v = new Validator($input);
        $first = $v->text('First_Name', 'First name', 50);
        $last = $v->text('Last_Name', 'Last name', 50);
        $email = $v->text('Email', 'Email', 100);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $v->error('Email', 'Enter a valid email address.'); }
        $phone = $v->phone();
        $address = $v->text('Address', 'Address', 1000);
        $v->finish();
        $db = Database::connection();
        if ($id !== null && !(new CustomerRepository())->find($id)) { throw new HttpException(404, 'Customer not found.'); }
        $query = $db->prepare('SELECT Customer_ID FROM customer WHERE Email=? AND Customer_ID<>?');
        $query->execute([$email, $id ?? 0]);
        if ($query->fetch()) { throw new ValidationException(['Email' => 'A customer with this email already exists. Select or edit that customer.']); }
        if ($id === null) {
            $db->prepare('INSERT INTO customer (First_Name, Last_Name, Email, Contact_Number, Address) VALUES (?,?,?,?,?)')->execute([$first, $last, $email, $phone, $address]);
            return (int) $db->lastInsertId();
        }
        $db->prepare('UPDATE customer SET First_Name=?, Last_Name=?, Email=?, Contact_Number=?, Address=? WHERE Customer_ID=?')->execute([$first, $last, $email, $phone, $address, $id]);
        return $id;
    }
}
