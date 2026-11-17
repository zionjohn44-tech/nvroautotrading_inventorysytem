<?php
declare(strict_types=1);

namespace NVRO\Repositories;

/** Reads inquiries together with their customer, vehicle and staff names. */
final class InquiryRepository extends Repository
{
    private const SELECT = "SELECT i.*,
            CONCAT(c.First_Name, ' ', c.Last_Name) Customer_Name, c.Contact_Number,
            b.Brand_Name, v.Model,
            CONCAT(e.First_Name, ' ', e.Last_Name) Employee_Name
        FROM inquiry i
        JOIN customer c ON c.Customer_ID = i.Customer_ID
        JOIN vehicle v ON v.Vehicle_ID = i.Vehicle_ID
        JOIN brand b ON b.Brand_ID = v.Brand_ID
        LEFT JOIN employee e ON e.Employee_ID = i.Employee_ID";

    public function all(): array
    {
        return $this->rows(self::SELECT . ' ORDER BY i.Inquiry_Date DESC, i.Inquiry_ID DESC');
    }

    public function find(int $id): ?array
    {
        return $this->row(self::SELECT . ' WHERE i.Inquiry_ID = ?', [$id]);
    }

    /** Pending and In Progress inquiries (shown on the dashboard). */
    public function countOpen(): int
    {
        return $this->count("SELECT COUNT(*) FROM inquiry WHERE Inquiry_Status IN ('Pending','In Progress')");
    }

    /** Inquiries about one vehicle, newest first (vehicle details page). */
    public function forVehicle(int $vehicleId): array
    {
        return $this->rows(
            "SELECT i.*, CONCAT(c.First_Name,' ',c.Last_Name) Customer_Name
             FROM inquiry i
             JOIN customer c ON c.Customer_ID = i.Customer_ID
             WHERE Vehicle_ID = ?
             ORDER BY Inquiry_Date DESC",
            [$vehicleId]
        );
    }
}
