<?php
declare(strict_types=1);

namespace NVRO\Repositories;

/** Reads vehicles with their brand and, when sold, the sale details. */
final class VehicleRepository extends Repository
{
    private const SELECT = "SELECT v.*, b.Brand_Name, b.Country_Of_Origin,
            s.Sold_Vehicle_ID, s.Sale_Date, s.Selling_Price, s.Customer_ID, s.Employee_ID,
            CONCAT(c.First_Name, ' ', c.Last_Name) AS Customer_Name,
            CONCAT(e.First_Name, ' ', e.Last_Name) AS Employee_Name
        FROM vehicle v
        JOIN brand b ON b.Brand_ID = v.Brand_ID
        LEFT JOIN sold_vehicle s ON s.Vehicle_ID = v.Vehicle_ID
        LEFT JOIN customer c ON c.Customer_ID = s.Customer_ID
        LEFT JOIN employee e ON e.Employee_ID = s.Employee_ID";

    public function all(): array
    {
        return $this->rows(self::SELECT . ' ORDER BY v.Vehicle_ID DESC');
    }

    public function find(int $id): ?array
    {
        return $this->row(self::SELECT . ' WHERE v.Vehicle_ID = ?', [$id]);
    }

    /** Vehicles that have a sold_vehicle record, latest sale first (Sold tab). */
    public function sold(): array
    {
        return $this->rows(
            "SELECT v.*, b.Brand_Name, b.Country_Of_Origin,
                s.Sold_Vehicle_ID, s.Customer_ID, s.Employee_ID, s.Sale_Date, s.Selling_Price,
                CONCAT(c.First_Name, ' ', c.Last_Name) AS Customer_Name,
                CONCAT(e.First_Name, ' ', e.Last_Name) AS Employee_Name
             FROM sold_vehicle s
             JOIN vehicle v ON v.Vehicle_ID = s.Vehicle_ID
             JOIN brand b ON b.Brand_ID = v.Brand_ID
             JOIN customer c ON c.Customer_ID = s.Customer_ID
             JOIN employee e ON e.Employee_ID = s.Employee_ID
             ORDER BY s.Sale_Date DESC, s.Sold_Vehicle_ID DESC"
        );
    }

    /** The sale record of one vehicle, or null when it has not been sold. */
    public function sale(int $vehicleId): ?array
    {
        return $this->row(
            "SELECT s.*,
                CONCAT(c.First_Name, ' ', c.Last_Name) Customer_Name,
                CONCAT(e.First_Name, ' ', e.Last_Name) Employee_Name
             FROM sold_vehicle s
             JOIN customer c ON c.Customer_ID = s.Customer_ID
             JOIN employee e ON e.Employee_ID = s.Employee_ID
             WHERE s.Vehicle_ID = ?",
            [$vehicleId]
        );
    }

    public function brands(): array
    {
        return $this->rows('SELECT * FROM brand ORDER BY Brand_Name');
    }

    public function countAll(): int
    {
        return $this->count('SELECT COUNT(*) FROM vehicle');
    }

    public function countAvailable(): int
    {
        return $this->count("SELECT COUNT(*) FROM vehicle WHERE Availability_Status = 'Available'");
    }

    public function countSold(): int
    {
        return $this->count('SELECT COUNT(*) FROM sold_vehicle');
    }
}
