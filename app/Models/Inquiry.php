<?php
declare(strict_types=1);

namespace NVRO\Models;

/** A customer's question about one vehicle, recorded by a staff member. */
final class Inquiry extends Entity
{
    public const STATUSES = ['Pending', 'In Progress', 'Resolved', 'Cancelled'];

    private int $customerId = 0;
    private int $vehicleId = 0;
    private ?int $employeeId = null;
    private string $message = '';
    private string $status = 'Pending';

    public static function fromRow(array $row): self
    {
        $inquiry = new self();
        $inquiry->setId(self::intOrNull($row, 'Inquiry_ID'));
        $inquiry->customerId = (int) ($row['Customer_ID'] ?? 0);
        $inquiry->vehicleId = (int) ($row['Vehicle_ID'] ?? 0);
        $inquiry->employeeId = self::intOrNull($row, 'Employee_ID');
        $inquiry->message = (string) ($row['Inquiry_Message'] ?? '');
        $inquiry->status = (string) ($row['Inquiry_Status'] ?? 'Pending');
        return $inquiry;
    }

    public function getCustomerId(): int
    {
        return $this->customerId;
    }

    public function setCustomerId(int $customerId): void
    {
        $this->customerId = $customerId;
    }

    public function getVehicleId(): int
    {
        return $this->vehicleId;
    }

    public function setVehicleId(int $vehicleId): void
    {
        $this->vehicleId = $vehicleId;
    }

    public function getEmployeeId(): ?int
    {
        return $this->employeeId;
    }

    public function setEmployeeId(?int $employeeId): void
    {
        $this->employeeId = $employeeId;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        if (!in_array($status, self::STATUSES, true)) {
            throw new \InvalidArgumentException('Select a valid inquiry status.');
        }
        $this->status = $status;
    }

    public function describe(): string
    {
        return 'Inquiry #' . $this->getId() . ' · ' . $this->status;
    }
}
