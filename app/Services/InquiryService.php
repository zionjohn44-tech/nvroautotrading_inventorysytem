<?php
declare(strict_types=1);

namespace NVRO\Services;

use NVRO\Core\Database;
use NVRO\Core\HttpException;
use NVRO\Models\Inquiry;
use NVRO\Models\User;
use NVRO\Repositories\CustomerRepository;
use NVRO\Repositories\InquiryRepository;
use NVRO\Repositories\VehicleRepository;
use NVRO\Validation\Validator;

/** Records customer inquiries and keeps their status up to date. */
final class InquiryService
{
    /** Saves the customer and the inquiry together in one transaction. */
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

    /** Creates ($id = null) or updates an inquiry and returns its ID. */
    public function save(array $input, ?int $id, User $user): int
    {
        $v = new Validator($input);
        $customerId = $v->integer('Customer_ID', 'Customer');
        $vehicleId = $v->integer('Vehicle_ID', 'Vehicle');
        $message = $v->text('Inquiry_Message', 'Inquiry message', 5000);
        $status = $v->choice('Inquiry_Status', 'inquiry status', Inquiry::STATUSES);
        if (!(new CustomerRepository())->find($customerId)) {
            $v->error('Customer_ID', 'Select an existing customer.');
        }
        if (!(new VehicleRepository())->find($vehicleId)) {
            $v->error('Vehicle_ID', 'Select an existing vehicle.');
        }
        $v->finish();

        $inquiry = new Inquiry();
        $inquiry->setId($id);
        $inquiry->setCustomerId($customerId);
        $inquiry->setVehicleId($vehicleId);
        $inquiry->setEmployeeId($user->employeeId());
        $inquiry->setMessage($message);
        $inquiry->setStatus($status);

        return $this->persist($inquiry);
    }

    private function persist(Inquiry $inquiry): int
    {
        $db = Database::connection();
        $id = $inquiry->getId();
        if ($id === null) {
            $db->prepare('INSERT INTO inquiry (Customer_ID, Vehicle_ID, Employee_ID, Inquiry_Message, Inquiry_Status) VALUES (?, ?, ?, ?, ?)')
                ->execute([
                    $inquiry->getCustomerId(),
                    $inquiry->getVehicleId(),
                    $inquiry->getEmployeeId(),
                    $inquiry->getMessage(),
                    $inquiry->getStatus(),
                ]);
            return (int) $db->lastInsertId();
        }
        if (!(new InquiryRepository())->find($id)) {
            throw new HttpException(404, 'Inquiry not found.');
        }
        $db->prepare('UPDATE inquiry SET Customer_ID = ?, Vehicle_ID = ?, Inquiry_Message = ?, Inquiry_Status = ? WHERE Inquiry_ID = ?')
            ->execute([
                $inquiry->getCustomerId(),
                $inquiry->getVehicleId(),
                $inquiry->getMessage(),
                $inquiry->getStatus(),
                $id,
            ]);
        return $id;
    }
}
