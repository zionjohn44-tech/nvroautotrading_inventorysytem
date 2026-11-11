<?php
declare(strict_types=1);

namespace NVRO\Models;

final class User
{
    private array $attributes;

    public function __construct(array $attributes) { $this->attributes = $attributes; }
    public function id(): int { return (int) $this->attributes['User_ID']; }
    public function employeeId(): ?int { return isset($this->attributes['Employee_ID']) ? (int) $this->attributes['Employee_ID'] : null; }
    public function isAdmin(): bool { return $this->attributes['Role'] === 'Administrator'; }
    public function role(): string { return $this->attributes['Role']; }
    public function name(): string { return trim(($this->attributes['First_Name'] ?? '') . ' ' . ($this->attributes['Last_Name'] ?? '')) ?: $this->attributes['Username']; }
    public function data(): array { return $this->attributes; }
}
