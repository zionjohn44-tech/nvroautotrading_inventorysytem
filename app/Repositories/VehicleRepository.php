<?php
declare(strict_types=1);

namespace NVRO\Repositories;

final class VehicleRepository extends Repository
{
    private const SELECT = 'SELECT v.*, b.Brand_Name, b.Country_Of_Origin FROM vehicle v JOIN brand b ON b.Brand_ID = v.Brand_ID';
    public function all(): array { return $this->rows(self::SELECT . ' ORDER BY v.Vehicle_ID DESC'); }
    public function find(int $id): ?array { return $this->row(self::SELECT . ' WHERE v.Vehicle_ID = ?', [$id]); }
    public function brands(): array { return $this->rows('SELECT * FROM brand ORDER BY Brand_Name'); }
}
