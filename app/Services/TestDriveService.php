<?php
declare(strict_types=1);

namespace NVRO\Services;

use DateTimeImmutable;
use NVRO\Algorithms\RequestQueue;
use NVRO\Core\Database;
use NVRO\Core\HttpException;
use NVRO\Models\User;
use NVRO\Repositories\CustomerRepository;
use NVRO\Validation\Validator;
use NVRO\Validation\ValidationException;
use PDO;

final class TestDriveService
{
    public function save(array $input, ?int $id, User $user): int
    {
        $v = new Validator($input);
        $customer = $v->integer('Customer_ID', 'Customer');
        $vehicleId = $v->integer('Vehicle_ID', 'Vehicle');
        $scheduled = $v->text('Scheduled_Date', 'Scheduled date');
        $duration = $v->integer('Duration_Minutes', 'Duration', 15, 120);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $scheduled);
        if (!$date || $date->format('Y-m-d\TH:i') !== $scheduled || $date->getTimestamp() <= time()) { $v->error('Scheduled_Date', 'Choose a valid date and time in the future.'); }
        if (!(new CustomerRepository())->find($customer)) { $v->error('Customer_ID', 'Select an existing customer.'); }
        $v->finish();
        return Database::transaction(function (PDO $db) use ($customer, $vehicleId, $date, $duration, $id, $user): int {
            $vehicle = (new VehicleService())->lockVehicle($db, $vehicleId);
            if ($vehicle['Availability_Status'] !== 'Available') { throw new ValidationException(['Vehicle_ID' => 'Only available vehicles can be scheduled for test drives.']); }
            if ($id !== null) {
                $query = $db->prepare('SELECT * FROM test_drive WHERE Test_Drive_ID=? FOR UPDATE');
                $query->execute([$id]);
                $current = $query->fetch();
                if (!$current) { throw new HttpException(404, 'Test-drive request not found.'); }
                if (!in_array($current['Request_Status'], ['Pending', 'Approved'], true)) { throw new ValidationException(['request' => 'Completed or cancelled requests cannot be rescheduled.']); }
            }
            $this->checkConflict($db, $vehicleId, $customer, $date, $duration, $id ?? 0);
            if ($id === null) {
                $db->prepare('INSERT INTO test_drive (Customer_ID, Vehicle_ID, Employee_ID, Scheduled_Date, Duration_Minutes) VALUES (?,?,?,?,?)')->execute([$customer, $vehicleId, $user->employeeId(), $date->format('Y-m-d H:i:s'), $duration]);
                return (int) $db->lastInsertId();
            }
            // Rescheduling an approved request requires approval again; FIFO is based on original submission ID.
            $db->prepare("UPDATE test_drive SET Customer_ID=?, Vehicle_ID=?, Scheduled_Date=?, Duration_Minutes=?, Request_Status='Pending' WHERE Test_Drive_ID=?")->execute([$customer, $vehicleId, $date->format('Y-m-d H:i:s'), $duration, $id]);
            return $id;
        });
    }

    private function checkConflict(PDO $db, int $vehicle, int $customer, DateTimeImmutable $date, int $duration, int $except): void
    {
        // Customer lock also serializes concurrent bookings of different vehicles for the same customer.
        $db->prepare('SELECT Customer_ID FROM customer WHERE Customer_ID=? FOR UPDATE')->execute([$customer]);
        $end = $date->modify("+$duration minutes");
        $query = $db->prepare("SELECT Test_Drive_ID FROM test_drive WHERE (Vehicle_ID=? OR Customer_ID=?) AND Test_Drive_ID<>? AND Request_Status IN ('Pending','Approved') AND Scheduled_Date < ? AND DATE_ADD(Scheduled_Date, INTERVAL Duration_Minutes MINUTE) > ? FOR UPDATE");
        $query->execute([$vehicle, $customer, $except, $end->format('Y-m-d H:i:s'), $date->format('Y-m-d H:i:s')]);
        if ($query->fetch()) { throw new ValidationException(['Scheduled_Date' => 'This vehicle or customer already has an overlapping test drive. Choose another time.']); }
    }

    public function processNext(User $user): int
    {
        return Database::transaction(function (PDO $db) use ($user): int {
            $candidate = $db->query("SELECT * FROM test_drive WHERE Request_Status='Pending' ORDER BY Test_Drive_ID LIMIT 1")->fetch();
            if (!$candidate) { throw new ValidationException(['queue' => 'There are no pending requests to process.']); }
            $vehicle = (new VehicleService())->lockVehicle($db, (int) $candidate['Vehicle_ID']);
            $pending = $db->query("SELECT * FROM test_drive WHERE Request_Status='Pending' ORDER BY Test_Drive_ID FOR UPDATE")->fetchAll();
            $queue = new RequestQueue($pending);
            $next = $queue->dequeue();
            if (!$next || (int) $next['Test_Drive_ID'] !== (int) $candidate['Test_Drive_ID']) { throw new ValidationException(['queue' => 'The queue changed. Please try again.']); }
            if ($vehicle['Availability_Status'] !== 'Available') { throw new ValidationException(['queue' => 'The first request’s vehicle is unavailable. Cancel or reschedule that request first.']); }
            $date = new DateTimeImmutable($next['Scheduled_Date']);
            if ($date->getTimestamp() <= time()) { throw new ValidationException(['queue' => 'The first request’s schedule has passed. Reschedule or cancel it before continuing.']); }
            $this->checkConflict($db, (int) $next['Vehicle_ID'], (int) $next['Customer_ID'], $date, (int) $next['Duration_Minutes'], (int) $next['Test_Drive_ID']);
            $db->prepare("UPDATE test_drive SET Request_Status='Approved', Employee_ID=? WHERE Test_Drive_ID=?")->execute([$user->employeeId(), $next['Test_Drive_ID']]);
            return (int) $next['Test_Drive_ID'];
        });
    }

    public function transition(int $id, string $status): void
    {
        if (!in_array($status, ['Completed', 'Cancelled'], true)) { throw new ValidationException(['status' => 'Use the FIFO queue to approve a request.']); }
        Database::transaction(function (PDO $db) use ($id, $status): void {
            $query = $db->prepare('SELECT * FROM test_drive WHERE Test_Drive_ID=? FOR UPDATE');
            $query->execute([$id]);
            $request = $query->fetch();
            if (!$request) { throw new HttpException(404, 'Test-drive request not found.'); }
            if (!in_array($request['Request_Status'], ['Pending', 'Approved'], true) || ($status === 'Completed' && $request['Request_Status'] !== 'Approved')) {
                throw new ValidationException(['status' => 'This request cannot move to the selected status.']);
            }
            if ($status === 'Completed' && strtotime($request['Scheduled_Date']) + (int) $request['Duration_Minutes'] * 60 > time()) { throw new ValidationException(['status' => 'A test drive can be completed after its scheduled end time.']); }
            $db->prepare('UPDATE test_drive SET Request_Status=? WHERE Test_Drive_ID=?')->execute([$status, $id]);
        });
    }
}
