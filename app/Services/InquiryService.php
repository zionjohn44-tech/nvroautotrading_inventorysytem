<?php
declare(strict_types=1);

namespace NVRO\Services;

use NVRO\Core\Database;
use NVRO\Core\HttpException;
use NVRO\Models\User;
use NVRO\Repositories\CustomerRepository;
use NVRO\Repositories\InquiryRepository;
use NVRO\Repositories\VehicleRepository;
use NVRO\Validation\Validator;

final class InquiryService
{
    public function saveWithCustomer(array $input, ?int $id, User $user): int
    {
        return Database::transaction(function () use ($input, $id, $user): int {
            $customerId = null;
            if (($input['Customer_ID'] ?? '') !== '') {
                $validator = new Validator($input);
                $customerId = $validator->integer('Customer_ID', 'Customer');
                $validator->finish();
            }
            $input['Customer_ID'] = (string) (new CustomerService())->save($input, $customerId);
            return $this->save($input, $id, $user);
        });
    }

    public function save(array $input, ?int $id, User $user): int
    {
        $v = new Validator($input);
        $customer = $v->integer('Customer_ID', 'Customer');
        $vehicle = $v->integer('Vehicle_ID', 'Vehicle');
        $message = $v->text('Inquiry_Message', 'Inquiry message', 5000);
        $status = $v->choice('Inquiry_Status', 'inquiry status', ['Pending', 'In Progress', 'Resolved', 'Cancelled']);
        if (!(new CustomerRepository())->find($customer)) { $v->error('Customer_ID', 'Select an existing customer.'); }
        if (!(new VehicleRepository())->find($vehicle)) { $v->error('Vehicle_ID', 'Select an existing vehicle.'); }
        $v->finish();
        $db = Database::connection();
        if ($id === null) {
            $db->prepare('INSERT INTO inquiry (Customer_ID, Vehicle_ID, Employee_ID, Inquiry_Message, Inquiry_Status) VALUES (?,?,?,?,?)')->execute([$customer, $vehicle, $user->employeeId(), $message, $status]);
            return (int) $db->lastInsertId();
        }
        if (!(new InquiryRepository())->find($id)) { throw new HttpException(404, 'Inquiry not found.'); }
        $db->prepare('UPDATE inquiry SET Customer_ID=?, Vehicle_ID=?, Inquiry_Message=?, Inquiry_Status=? WHERE Inquiry_ID=?')->execute([$customer, $vehicle, $message, $status, $id]);
        return $id;
    }
}
