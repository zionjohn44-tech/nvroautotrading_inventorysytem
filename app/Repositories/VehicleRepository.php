<?php
declare(strict_types=1);

namespace NVRO\Repositories;

final class VehicleRepository extends Repository
{
    private const SELECT = 'SELECT v.*, b.Brand_Name, b.Country_Of_Origin, s.Sold_Vehicle_ID, s.Sale_Date, s.Selling_Price, s.Customer_ID, s.Employee_ID, CONCAT(c.First_Name, \' \', c.Last_Name) AS Customer_Name, CONCAT(e.First_Name, \' \', e.Last_Name) AS Employee_Name FROM vehicle v JOIN brand b ON b.Brand_ID = v.Brand_ID LEFT JOIN sold_vehicle s ON s.Vehicle_ID=v.Vehicle_ID LEFT JOIN customer c ON c.Customer_ID=s.Customer_ID LEFT JOIN employee e ON e.Employee_ID=s.Employee_ID';
    public function all(): array { return $this->rows(self::SELECT . ' ORDER BY v.Vehicle_ID DESC'); }
    public function sold(): array
    {
        return $this->rows('SELECT v.*, b.Brand_Name, b.Country_Of_Origin, s.Sold_Vehicle_ID, s.Customer_ID, s.Employee_ID, s.Sale_Date, s.Selling_Price, CONCAT(c.First_Name, \' \', c.Last_Name) AS Customer_Name, CONCAT(e.First_Name, \' \', e.Last_Name) AS Employee_Name FROM sold_vehicle s JOIN vehicle v ON v.Vehicle_ID=s.Vehicle_ID JOIN brand b ON b.Brand_ID=v.Brand_ID JOIN customer c ON c.Customer_ID=s.Customer_ID JOIN employee e ON e.Employee_ID=s.Employee_ID ORDER BY s.Sale_Date DESC, s.Sold_Vehicle_ID DESC');
    }
    public function find(int $id): ?array { return $this->row(self::SELECT . ' WHERE v.Vehicle_ID = ?', [$id]); }
    public function brands(): array { return $this->rows('SELECT * FROM brand ORDER BY Brand_Name'); }
}
