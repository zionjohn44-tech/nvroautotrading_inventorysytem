<?php
declare(strict_types=1);

namespace NVRO\Repositories;

final class CustomerRepository extends Repository
{
    public function all(): array { return $this->rows('SELECT * FROM customer ORDER BY Last_Name, First_Name, Customer_ID'); }
    public function find(int $id): ?array { return $this->row('SELECT * FROM customer WHERE Customer_ID = ?', [$id]); }
}
