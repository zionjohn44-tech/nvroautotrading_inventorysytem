<?php
declare(strict_types=1);

namespace NVRO\Models;

/**
 * A vehicle in the dealership inventory.
 *
 * ENCAPSULATION: every attribute is private. Setters such as setPrice() and
 * setAvailabilityStatus() reject values that would make the record invalid.
 * ASSOCIATION: each Vehicle belongs to one Brand.
 */
final class Vehicle extends Entity
{
    public const TRANSMISSIONS = ['Automatic', 'Manual', 'CVT'];
    public const FUEL_TYPES = ['Gasoline', 'Diesel', 'Hybrid', 'Electric'];
    public const STATUSES = ['Available', 'Sold', 'Reserved'];

    private Brand $brand;
    private string $model = '';
    private int $year = 0;
    private string $color = '';
    private string $transmission = 'Automatic';
    private string $fuelType = 'Gasoline';
    private string $price = '0.00';
    private string $availabilityStatus = 'Available';
    private ?string $imagePath = null;

    public function __construct(?Brand $brand = null)
    {
        $this->brand = $brand ?? new Brand();
    }

    public static function fromRow(array $row): self
    {
        $brand = new Brand((string) ($row['Brand_Name'] ?? ''), (string) ($row['Country_Of_Origin'] ?? ''));
        $brand->setId(self::intOrNull($row, 'Brand_ID'));

        $vehicle = new self($brand);
        $vehicle->setId(self::intOrNull($row, 'Vehicle_ID'));
        $vehicle->model = (string) ($row['Model'] ?? '');
        $vehicle->year = (int) ($row['Year'] ?? 0);
        $vehicle->color = (string) ($row['Color'] ?? '');
        $vehicle->transmission = (string) ($row['Transmission'] ?? '');
        $vehicle->fuelType = (string) ($row['Fuel_Type'] ?? '');
        $vehicle->price = (string) ($row['Price'] ?? '0.00');
        $vehicle->availabilityStatus = (string) ($row['Availability_Status'] ?? 'Available');
        $vehicle->imagePath = $row['Image_Path'] ?? null;
        return $vehicle;
    }

    public function getBrand(): Brand
    {
        return $this->brand;
    }

    public function setBrand(Brand $brand): void
    {
        $this->brand = $brand;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function setModel(string $model): void
    {
        $this->model = $model;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function setYear(int $year): void
    {
        $this->year = $year;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): void
    {
        $this->color = $color;
    }

    public function getTransmission(): string
    {
        return $this->transmission;
    }

    public function setTransmission(string $transmission): void
    {
        self::assertOneOf($transmission, self::TRANSMISSIONS, 'transmission');
        $this->transmission = $transmission;
    }

    public function getFuelType(): string
    {
        return $this->fuelType;
    }

    public function setFuelType(string $fuelType): void
    {
        self::assertOneOf($fuelType, self::FUEL_TYPES, 'fuel type');
        $this->fuelType = $fuelType;
    }

    /** Price in Philippine pesos as a decimal string (for example "850000.00"). */
    public function getPrice(): string
    {
        return $this->price;
    }

    /** ENCAPSULATION: a zero or negative price is rejected before it is stored. */
    public function setPrice(string $price): void
    {
        if (!is_numeric($price) || (float) $price <= 0) {
            throw new \InvalidArgumentException('Price must be a positive amount.');
        }
        $this->price = $price;
    }

    public function getAvailabilityStatus(): string
    {
        return $this->availabilityStatus;
    }

    public function setAvailabilityStatus(string $status): void
    {
        self::assertOneOf($status, self::STATUSES, 'availability');
        $this->availabilityStatus = $status;
    }

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function setImagePath(?string $imagePath): void
    {
        $this->imagePath = $imagePath;
    }

    public function isAvailable(): bool
    {
        return $this->availabilityStatus === 'Available';
    }

    public function isSold(): bool
    {
        return $this->availabilityStatus === 'Sold';
    }

    /** Example: "#12 · Toyota Vios 1.3 XLE (2024)" */
    public function describe(): string
    {
        return '#' . $this->getId() . ' · ' . $this->brand->getName() . ' ' . $this->model . ' (' . $this->year . ')';
    }

    private static function assertOneOf(string $value, array $allowed, string $label): void
    {
        if (!in_array($value, $allowed, true)) {
            throw new \InvalidArgumentException("Select a valid $label.");
        }
    }
}
