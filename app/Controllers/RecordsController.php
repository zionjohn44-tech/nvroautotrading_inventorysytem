<?php
declare(strict_types=1);

namespace NVRO\Controllers;

use NVRO\Algorithms\RequestQueue;
use NVRO\Models\Customer;
use NVRO\Models\Inquiry;
use NVRO\Models\TestDrive;
use NVRO\Models\Vehicle;
use NVRO\Repositories\CustomerRepository;
use NVRO\Repositories\InquiryRepository;
use NVRO\Repositories\TestDriveRepository;
use NVRO\Repositories\VehicleRepository;
use NVRO\Services\InquiryService;
use NVRO\Services\TestDriveService;

/** Customer inquiries and test-drive requests (including the FIFO queue). */
final class RecordsController extends Controller
{
    public function inquiries(): void
    {
        $rows = $this->filterByStatus((new InquiryRepository())->all(), 'Inquiry_Status', Inquiry::STATUSES);
        $this->render('inquiries', [
            'title' => 'Customer inquiries',
            'description' => 'Keep every question and follow-up in view.',
        ] + $this->paginate($rows));
    }

    public function inquiryEdit(bool $new = false): void
    {
        $id = $new ? null : $this->id();
        $data = $new
            ? ['Inquiry_Status' => 'Pending', 'Vehicle_ID' => $_GET['vehicle'] ?? '']
            : $this->record(new InquiryRepository(), $id);
        if (!$new) {
            $data = array_replace($this->record(new CustomerRepository(), (int) $data['Customer_ID']), $data);
        }
        $this->form([
            'title' => $new ? 'Record inquiry' : 'Update inquiry',
            'description' => 'Record the customer’s question and keep its progress up to date.',
            'data' => $data,
            'inquiryCustomers' => (new CustomerRepository())->all(),
            'fields' => [
                ['name' => 'First_Name', 'label' => 'First name', 'max' => 50],
                ['name' => 'Last_Name', 'label' => 'Last name', 'max' => 50],
                ['name' => 'Email', 'label' => 'Email', 'type' => 'email', 'max' => 100],
                ['name' => 'Contact_Number', 'label' => 'Contact number', 'type' => 'tel', 'max' => 15],
                ['name' => 'Address', 'label' => 'Address', 'type' => 'textarea', 'max' => 1000],
                $this->vehicleField(),
                ['name' => 'Inquiry_Message', 'label' => 'Question or inquiry', 'type' => 'textarea', 'max' => 5000],
                ['name' => 'Inquiry_Status', 'label' => 'Status', 'type' => 'select', 'options' => array_combine(Inquiry::STATUSES, Inquiry::STATUSES)],
            ],
            'returnRoute' => 'inquiries',
            'success' => 'Inquiry saved.',
        ], fn(array $input) => (new InquiryService())->saveWithCustomer($input, $id, $this->user));
    }

    public function drives(): void
    {
        $repository = new TestDriveRepository();
        $pendingRequests = $repository->pending();
        $queue = new RequestQueue($pendingRequests);
        $rows = $this->filterByStatus($repository->all(), 'Request_Status', TestDrive::STATUSES);
        $this->render('drives', [
            'title' => 'Test drives',
            'description' => 'Organize requests, approve the next booking, and keep schedules clear.',
            'next' => $queue->peek(),
            'pendingCount' => $queue->count(),
            'pendingRequests' => $pendingRequests,
        ] + $this->paginate($rows));
    }

    public function driveEdit(bool $new = false): void
    {
        $id = $new ? null : $this->id();
        $data = $new ? ['Vehicle_ID' => $_GET['vehicle'] ?? ''] : $this->record(new TestDriveRepository(), $id);
        if (!$new) {
            $data['Scheduled_Date'] = date('Y-m-d\TH:i', strtotime($data['Scheduled_Date']));
        }
        $customers = array_map([Customer::class, 'fromRow'], (new CustomerRepository())->all());
        $this->form([
            'title' => $new ? 'New test-drive request' : 'Reschedule test drive',
            'description' => 'Schedules use Philippine time (UTC+8). New and rescheduled requests enter the pending queue.',
            'data' => $data,
            'fields' => [
                ['name' => 'Customer_ID', 'label' => 'Customer', 'type' => 'select', 'options' => $this->options($customers),
                    'link' => ['inquiry-new', 'Record a new customer with an inquiry']],
                $this->vehicleField(true),
                ['name' => 'Scheduled_Date', 'label' => 'Scheduled date and time', 'type' => 'datetime-local'],
            ],
            'returnRoute' => 'test-drives',
            'success' => 'Test-drive request saved to the pending queue.',
        ], fn(array $input) => (new TestDriveService())->save($input, $id, $this->user));
    }

    public function processNext(): void
    {
        $this->action(
            fn() => (new TestDriveService())->processNext($this->user),
            'test-drives',
            'The oldest pending test-drive request has been approved.'
        );
    }

    public function driveStatus(): void
    {
        $this->action(
            fn() => (new TestDriveService())->transition($this->id(), $_POST['status'] ?? ''),
            'test-drives',
            'Test-drive status updated.'
        );
    }

    /** Vehicle dropdown; test-drive bookings list only available vehicles. */
    private function vehicleField(bool $availableOnly = false): array
    {
        $vehicles = array_map([Vehicle::class, 'fromRow'], (new VehicleRepository())->all());
        if ($availableOnly) {
            $vehicles = array_filter($vehicles, static fn(Vehicle $vehicle): bool => $vehicle->isAvailable());
        }
        return [
            'name' => 'Vehicle_ID',
            'label' => 'Vehicle',
            'type' => 'select',
            'options' => $this->options($vehicles),
            'hint' => $availableOnly ? 'Only available vehicles can be booked.' : 'Select the vehicle this inquiry is about.',
        ];
    }

    /** Keeps only rows whose $column equals ?status=, when it is a known status. */
    private function filterByStatus(array $rows, string $column, array $statuses): array
    {
        $status = $_GET['status'] ?? '';
        if (!in_array($status, $statuses, true)) {
            return $rows;
        }
        return array_values(array_filter($rows, static fn(array $row): bool => $row[$column] === $status));
    }
}
