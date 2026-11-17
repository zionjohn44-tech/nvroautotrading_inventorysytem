<?php
declare(strict_types=1);

namespace NVRO\Models;

/**
 * A customer's request to test-drive one vehicle at a scheduled time.
 *
 * The object owns its status rules: Pending -> Approved (through the FIFO
 * queue) -> Completed, and Pending or Approved -> Cancelled.
 */
final class TestDrive extends Entity
{
    public const STATUSES = ['Pending', 'Approved', 'Completed', 'Cancelled'];

    private int $customerId = 0;
    private int $vehicleId = 0;
    private string $scheduledDate = '';
    private string $status = 'Pending';

    public static function fromRow(array $row): self
    {
        $drive = new self();
        $drive->setId(self::intOrNull($row, 'Test_Drive_ID'));
        $drive->customerId = (int) ($row['Customer_ID'] ?? 0);
        $drive->vehicleId = (int) ($row['Vehicle_ID'] ?? 0);
        $drive->scheduledDate = (string) ($row['Scheduled_Date'] ?? '');
        $drive->status = (string) ($row['Request_Status'] ?? 'Pending');
        return $drive;
    }

    public function getCustomerId(): int
    {
        return $this->customerId;
    }

    public function getVehicleId(): int
    {
        return $this->vehicleId;
    }

    public function getScheduledDate(): string
    {
        return $this->scheduledDate;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    /** Pending and Approved requests still hold a time slot. */
    public function isActive(): bool
    {
        return in_array($this->status, ['Pending', 'Approved'], true);
    }

    /** True once the scheduled start time has been reached. */
    public function hasStarted(): bool
    {
        return strtotime($this->scheduledDate) <= time();
    }

    /** Whether staff may move this request to Completed or Cancelled. */
    public function canMoveTo(string $status): bool
    {
        if (!$this->isActive()) {
            return false;
        }
        return $status === 'Cancelled' || ($status === 'Completed' && $this->status === 'Approved');
    }

    public function describe(): string
    {
        return 'Test drive #' . $this->getId() . ' · ' . $this->status;
    }
}
