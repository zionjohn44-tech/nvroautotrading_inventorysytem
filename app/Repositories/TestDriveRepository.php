<?php
declare(strict_types=1);

namespace NVRO\Repositories;

/** Reads test-drive requests together with their customer, vehicle and staff names. */
final class TestDriveRepository extends Repository
{
    private const SELECT = "SELECT t.*,
            CONCAT(c.First_Name, ' ', c.Last_Name) Customer_Name, c.Contact_Number,
            b.Brand_Name, v.Model, v.Year, v.Availability_Status,
            CONCAT(e.First_Name, ' ', e.Last_Name) Employee_Name
        FROM test_drive t
        JOIN customer c ON c.Customer_ID = t.Customer_ID
        JOIN vehicle v ON v.Vehicle_ID = t.Vehicle_ID
        JOIN brand b ON b.Brand_ID = v.Brand_ID
        LEFT JOIN employee e ON e.Employee_ID = t.Employee_ID";

    public function all(): array
    {
        return $this->rows(self::SELECT . ' ORDER BY t.Scheduled_Date DESC, t.Test_Drive_ID DESC');
    }

    public function find(int $id): ?array
    {
        return $this->row(self::SELECT . ' WHERE t.Test_Drive_ID = ?', [$id]);
    }

    /** Pending requests in arrival (FIFO) order. */
    public function pending(): array
    {
        return $this->rows(self::SELECT . " WHERE t.Request_Status = 'Pending' ORDER BY t.Test_Drive_ID");
    }

    /** The next five Pending or Approved appointments by date. */
    public function upcoming(): array
    {
        return $this->rows(
            self::SELECT . " WHERE t.Request_Status IN ('Pending','Approved') AND t.Scheduled_Date >= NOW()
            ORDER BY t.Scheduled_Date, t.Test_Drive_ID LIMIT 5"
        );
    }

    public function countPending(): int
    {
        return $this->count("SELECT COUNT(*) FROM test_drive WHERE Request_Status = 'Pending'");
    }

    /** Test drives of one vehicle, latest schedule first (vehicle details page). */
    public function forVehicle(int $vehicleId): array
    {
        return $this->rows(
            "SELECT t.*, CONCAT(c.First_Name,' ',c.Last_Name) Customer_Name
             FROM test_drive t
             JOIN customer c ON c.Customer_ID = t.Customer_ID
             WHERE Vehicle_ID = ?
             ORDER BY Scheduled_Date DESC",
            [$vehicleId]
        );
    }
}
