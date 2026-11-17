<?php
declare(strict_types=1);

namespace NVRO\Services;

use DateTimeImmutable;
use NVRO\Algorithms\RequestQueue;
use NVRO\Core\Database;
use NVRO\Core\HttpException;
use NVRO\Models\TestDrive;
use NVRO\Models\User;
use NVRO\Models\Vehicle;
use NVRO\Repositories\CustomerRepository;
use NVRO\Validation\Validator;
use NVRO\Validation\ValidationException;
use PDO;

/**
 * Schedules test drives, approves them in first-in, first-out (FIFO) order,
 * and moves them to Completed or Cancelled.
 */
final class TestDriveService
{
    /** Creates ($id = null) or reschedules a request. Both go back to the pending queue. */
    public function save(array $input, ?int $id, User $user): int
    {
        $v = new Validator($input);
        $customerId = $v->integer('Customer_ID', 'Customer');
        $vehicleId = $v->integer('Vehicle_ID', 'Vehicle');
        $scheduled = $v->text('Scheduled_Date', 'Scheduled date');
        $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $scheduled);
        if (!$date || $date->format('Y-m-d\TH:i') !== $scheduled || $date->getTimestamp() <= time()) {
            $v->error('Scheduled_Date', 'Choose a valid date and time in the future.');
        }
        if (!(new CustomerRepository())->find($customerId)) {
            $v->error('Customer_ID', 'Select an existing customer.');
        }
        $v->finish();

        return Database::transaction(function (PDO $db) use ($customerId, $vehicleId, $date, $id, $user): int {
            $vehicle = Vehicle::fromRow((new VehicleService())->lockVehicle($db, $vehicleId));
            if (!$vehicle->isAvailable()) {
                throw new ValidationException(['Vehicle_ID' => 'Only available vehicles can be scheduled for test drives.']);
            }
            if ($id !== null) {
                $current = $this->lockRequest($db, $id);
                if (!$current->isActive()) {
                    throw new ValidationException(['request' => 'Completed or cancelled requests cannot be rescheduled.']);
                }
            }
            $this->checkConflict($db, $vehicleId, $customerId, $date, $id ?? 0);

            $scheduledAt = $date->format('Y-m-d H:i:s');
            if ($id === null) {
                $db->prepare('INSERT INTO test_drive (Customer_ID, Vehicle_ID, Employee_ID, Scheduled_Date) VALUES (?, ?, ?, ?)')
                    ->execute([$customerId, $vehicleId, $user->employeeId(), $scheduledAt]);
                return (int) $db->lastInsertId();
            }
            // Rescheduling an approved request requires approval again; FIFO order is based on the original request ID.
            $db->prepare("UPDATE test_drive SET Customer_ID = ?, Vehicle_ID = ?, Scheduled_Date = ?, Request_Status = 'Pending' WHERE Test_Drive_ID = ?")
                ->execute([$customerId, $vehicleId, $scheduledAt, $id]);
            return $id;
        });
    }

    /** Approves the oldest pending request (front of the FIFO queue) and returns its ID. */
    public function processNext(User $user): int
    {
        return Database::transaction(function (PDO $db) use ($user): int {
            $candidate = $db->query("SELECT * FROM test_drive WHERE Request_Status = 'Pending' ORDER BY Test_Drive_ID LIMIT 1")->fetch();
            if (!$candidate) {
                throw new ValidationException(['queue' => 'There are no pending requests to process.']);
            }
            $vehicle = Vehicle::fromRow((new VehicleService())->lockVehicle($db, (int) $candidate['Vehicle_ID']));
            $pending = $db->query("SELECT * FROM test_drive WHERE Request_Status = 'Pending' ORDER BY Test_Drive_ID FOR UPDATE")->fetchAll();

            $queue = new RequestQueue($pending);
            $next = $queue->dequeue();
            if (!$next || (int) $next['Test_Drive_ID'] !== (int) $candidate['Test_Drive_ID']) {
                throw new ValidationException(['queue' => 'The queue changed. Please try again.']);
            }
            if (!$vehicle->isAvailable()) {
                throw new ValidationException(['queue' => 'The first request’s vehicle is unavailable. Cancel or reschedule that request first.']);
            }
            $request = TestDrive::fromRow($next);
            if ($request->hasStarted()) {
                throw new ValidationException(['queue' => 'The first request’s schedule has passed. Reschedule or cancel it before continuing.']);
            }
            $this->checkConflict(
                $db,
                $request->getVehicleId(),
                $request->getCustomerId(),
                new DateTimeImmutable($request->getScheduledDate()),
                (int) $request->getId()
            );
            $db->prepare("UPDATE test_drive SET Request_Status = 'Approved', Employee_ID = ? WHERE Test_Drive_ID = ?")
                ->execute([$user->employeeId(), $request->getId()]);
            return (int) $request->getId();
        });
    }

    /** Moves a request to Completed or Cancelled. Approval only happens through processNext(). */
    public function transition(int $id, string $status): void
    {
        if (!in_array($status, ['Completed', 'Cancelled'], true)) {
            throw new ValidationException(['status' => 'Use the FIFO queue to approve a request.']);
        }
        Database::transaction(function (PDO $db) use ($id, $status): void {
            $request = $this->lockRequest($db, $id);
            if (!$request->canMoveTo($status)) {
                throw new ValidationException(['status' => 'This request cannot move to the selected status.']);
            }
            if ($status === 'Completed' && !$request->hasStarted()) {
                throw new ValidationException(['status' => 'A test drive can be completed after its scheduled start time.']);
            }
            $db->prepare('UPDATE test_drive SET Request_Status = ? WHERE Test_Drive_ID = ?')->execute([$status, $id]);
        });
    }

    /** Reads and locks one request row for the rest of the transaction. */
    private function lockRequest(PDO $db, int $id): TestDrive
    {
        $query = $db->prepare('SELECT * FROM test_drive WHERE Test_Drive_ID = ? FOR UPDATE');
        $query->execute([$id]);
        $row = $query->fetch();
        if (!$row) {
            throw new HttpException(404, 'Test-drive request not found.');
        }
        return TestDrive::fromRow($row);
    }

    /** Stops the same vehicle or customer from being booked twice at the same time. */
    private function checkConflict(PDO $db, int $vehicleId, int $customerId, DateTimeImmutable $date, int $exceptId): void
    {
        // Locking the customer row also serializes bookings of different vehicles for the same customer.
        $db->prepare('SELECT Customer_ID FROM customer WHERE Customer_ID = ? FOR UPDATE')->execute([$customerId]);
        $query = $db->prepare(
            "SELECT Test_Drive_ID FROM test_drive
             WHERE (Vehicle_ID = ? OR Customer_ID = ?)
               AND Test_Drive_ID <> ?
               AND Request_Status IN ('Pending', 'Approved')
               AND Scheduled_Date = ?
             FOR UPDATE"
        );
        $query->execute([$vehicleId, $customerId, $exceptId, $date->format('Y-m-d H:i:s')]);
        if ($query->fetch()) {
            throw new ValidationException(['Scheduled_Date' => 'This vehicle or customer already has a test drive at that time. Choose another time.']);
        }
    }
}
