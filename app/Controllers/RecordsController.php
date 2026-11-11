<?php
declare(strict_types=1);

namespace NVRO\Controllers;

use NVRO\Algorithms\RequestQueue;
use NVRO\Repositories\CustomerRepository;
use NVRO\Repositories\InquiryRepository;
use NVRO\Repositories\TestDriveRepository;
use NVRO\Repositories\VehicleRepository;
use NVRO\Services\InquiryService;
use NVRO\Services\TestDriveService;

final class RecordsController extends Controller
{
    public function inquiries(): void
    {
        $rows = (new InquiryRepository())->all();
        if (in_array($_GET['status'] ?? '', ['Pending', 'In Progress', 'Resolved', 'Cancelled'], true)) {
            $rows = array_values(array_filter($rows, static fn(array $row): bool => $row['Inquiry_Status'] === $_GET['status']));
        }
        $this->render('inquiries', ['title' => 'Customer inquiries', 'description' => 'Keep every question and follow-up in view.'] + $this->paginate($rows));
    }

    private function vehicleField(bool $availableOnly = false): array
    {
        $vehicles = [];
        foreach ((new VehicleRepository())->all() as $v) {
            if ($availableOnly && $v['Availability_Status'] !== 'Available') { continue; }
            $vehicles[$v['Vehicle_ID']] = '#' . $v['Vehicle_ID'] . ' · ' . $v['Brand_Name'] . ' ' . $v['Model'] . ' (' . $v['Year'] . ')';
        }
        return ['name' => 'Vehicle_ID', 'label' => 'Vehicle', 'type' => 'select', 'options' => $vehicles, 'hint' => $availableOnly ? 'Only available vehicles can be booked.' : 'Select the vehicle this inquiry is about.'];
    }

    public function inquiryEdit(bool $new = false): void
    {
        $id = $new ? null : $this->id();
        $data = $new ? ['Inquiry_Status' => 'Pending', 'Vehicle_ID' => $_GET['vehicle'] ?? ''] : $this->record(new InquiryRepository(), $id);
        if (!$new) { $data = array_replace($this->record(new CustomerRepository(), (int) $data['Customer_ID']), $data); }
        $this->form([
            'title' => $new ? 'Record inquiry' : 'Update inquiry', 'description' => 'Record the customer’s question and keep its progress up to date.',
            'data' => $data, 'inquiryCustomers' => (new CustomerRepository())->all(),
            'fields' => array_merge([
                ['name' => 'First_Name', 'label' => 'First name', 'max' => 50],
                ['name' => 'Last_Name', 'label' => 'Last name', 'max' => 50],
                ['name' => 'Email', 'label' => 'Email', 'type' => 'email', 'max' => 100],
                ['name' => 'Contact_Number', 'label' => 'Contact number', 'type' => 'tel', 'max' => 15],
                ['name' => 'Address', 'label' => 'Address', 'type' => 'textarea', 'max' => 1000],
            ], [$this->vehicleField()], [
                ['name' => 'Inquiry_Message', 'label' => 'Question or inquiry', 'type' => 'textarea', 'max' => 5000],
                ['name' => 'Inquiry_Status', 'label' => 'Status', 'type' => 'select', 'options' => array_combine(['Pending', 'In Progress', 'Resolved', 'Cancelled'], ['Pending', 'In Progress', 'Resolved', 'Cancelled'])],
            ]), 'returnRoute' => 'inquiries', 'success' => 'Inquiry saved.',
        ], fn(array $input) => (new InquiryService())->saveWithCustomer($input, $id, $this->user));
    }

    public function drives(): void
    {
        $repository = new TestDriveRepository();
        $pendingRequests = $repository->pending();
        $queue = new RequestQueue($pendingRequests);
        $rows = $repository->all();
        if (in_array($_GET['status'] ?? '', ['Pending', 'Approved', 'Completed', 'Cancelled'], true)) {
            $rows = array_values(array_filter($rows, static fn(array $row): bool => $row['Request_Status'] === $_GET['status']));
        }
        $this->render('drives', ['title' => 'Test drives', 'description' => 'Organize requests, approve the next booking, and keep schedules clear.', 'next' => $queue->peek(), 'pendingCount' => $queue->count(), 'pendingRequests' => $pendingRequests] + $this->paginate($rows));
    }

    public function driveEdit(bool $new = false): void
    {
        $id = $new ? null : $this->id();
        $data = $new ? ['Duration_Minutes' => 30, 'Vehicle_ID' => $_GET['vehicle'] ?? ''] : $this->record(new TestDriveRepository(), $id);
        if (!$new) { $data['Scheduled_Date'] = date('Y-m-d\TH:i', strtotime($data['Scheduled_Date'])); }
        $customers = [];
        foreach ((new CustomerRepository())->all() as $c) { $customers[$c['Customer_ID']] = $c['First_Name'] . ' ' . $c['Last_Name'] . ' · ' . $c['Contact_Number']; }
        $this->form([
            'title' => $new ? 'New test-drive request' : 'Reschedule test drive',
            'description' => 'Schedules use Philippine time (UTC+8). New and rescheduled requests enter the pending queue.',
            'data' => $data,
            'fields' => [
                ['name' => 'Customer_ID', 'label' => 'Customer', 'type' => 'select', 'options' => $customers, 'link' => ['inquiry-new', 'Record a new customer with an inquiry']],
                $this->vehicleField(true),
                ['name' => 'Scheduled_Date', 'label' => 'Scheduled date and time', 'type' => 'datetime-local'],
                ['name' => 'Duration_Minutes', 'label' => 'Duration (minutes)', 'type' => 'number', 'min' => 15, 'max' => 120, 'hint' => '15–120 minutes. Overlapping vehicle or customer bookings are blocked.'],
            ], 'returnRoute' => 'test-drives', 'success' => 'Test-drive request saved to the pending queue.',
        ], fn(array $input) => (new TestDriveService())->save($input, $id, $this->user));
    }

    public function processNext(): void
    {
        $this->action(fn() => (new TestDriveService())->processNext($this->user), 'test-drives', 'The oldest pending test-drive request has been approved.');
    }

    public function driveStatus(): void
    {
        $this->action(fn() => (new TestDriveService())->transition($this->id(), $_POST['status'] ?? ''), 'test-drives', 'Test-drive status updated.');
    }
}
