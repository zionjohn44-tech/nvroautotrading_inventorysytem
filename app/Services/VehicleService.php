<?php
declare(strict_types=1);

namespace NVRO\Services;

use NVRO\Algorithms\LinearSearch;
use NVRO\Algorithms\MergeSort;
use NVRO\Algorithms\VehicleTree;
use NVRO\Core\Database;
use NVRO\Core\HttpException;
use NVRO\Models\Brand;
use NVRO\Models\Vehicle;
use NVRO\Repositories\VehicleRepository;
use NVRO\Validation\Validator;
use NVRO\Validation\ValidationException;
use PDO;

/** Inventory rules: listing, saving, removing and recording vehicle sales. */
final class VehicleService
{
    private const SORT_KEYS = ['Vehicle_ID', 'Price', 'Brand_Name', 'Model', 'Year'];

    /**
     * Returns the inventory list after search, status filter and sort.
     * Uses the BST for exact-ID search and ID order, linear search for keywords,
     * and merge sort for the other columns.
     */
    public function listing(array $filters): array
    {
        $repository = new VehicleRepository();
        $vehicles = ($filters['status'] ?? '') === 'Sold' ? $repository->sold() : $repository->all();

        $term = is_string($filters['q'] ?? null) ? trim($filters['q']) : '';
        if (($filters['search_mode'] ?? '') === 'id') {
            $match = ctype_digit($term) ? (new VehicleTree($vehicles))->find((int) $term) : null;
            $vehicles = $match ? [$match] : [];
        } else {
            $vehicles = (new LinearSearch())->search($vehicles, $term);
        }

        $status = $filters['status'] ?? '';
        if (in_array($status, Vehicle::STATUSES, true)) {
            $vehicles = array_values(array_filter(
                $vehicles,
                static fn(array $v): bool => $v['Availability_Status'] === $status
            ));
        }

        $key = $filters['sort'] ?? 'Vehicle_ID';
        $key = in_array($key, self::SORT_KEYS, true) ? $key : 'Vehicle_ID';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 1 : -1;
        if ($key === 'Vehicle_ID') {
            $vehicles = (new VehicleTree($vehicles))->inOrder();
            return $direction === 1 ? $vehicles : array_reverse($vehicles);
        }
        return (new MergeSort())->sort($vehicles, static function (array $a, array $b) use ($key, $direction): int {
            $comparison = in_array($key, ['Price', 'Year'], true)
                ? (float) $a[$key] <=> (float) $b[$key]
                : strcasecmp($a[$key], $b[$key]);
            return $comparison * $direction;
        });
    }

    /** Creates ($id = null) or updates a vehicle, its brand, and its optional photo. */
    public function save(array $input, ?int $id, array $files = []): int
    {
        if (($input['Existing_Brand'] ?? '') !== '') {
            $brands = array_column((new VehicleRepository())->brands(), 'Brand_Name');
            if (!in_array($input['Existing_Brand'], $brands, true)) {
                throw new ValidationException(['Brand_Name' => 'Choose a saved brand or add a new one.']);
            }
            $input['Brand_Name'] = $input['Existing_Brand'];
        }
        $vehicle = $this->buildVehicle($input, $id);

        $photos = (new VehiclePhotos())->upload($files);
        $oldPhotos = [];
        try {
            $result = Database::transaction(function (PDO $db) use ($vehicle, $photos, &$oldPhotos): int {
                return $this->persist($db, $vehicle, $photos, $oldPhotos);
            });
        } catch (\Throwable $exception) {
            VehiclePhotos::remove($photos);
            throw $exception;
        }
        VehiclePhotos::remove($oldPhotos);
        return $result;
    }

    /** Deletes a vehicle that is not sold and has no linked records. */
    public function remove(int $id): void
    {
        $photos = Database::transaction(function (PDO $db) use ($id): array {
            $vehicle = $this->lockVehicle($db, $id);
            if (Vehicle::fromRow($vehicle)->isSold()) {
                throw new ValidationException(['vehicle' => 'Sold vehicle records are retained for historical reference.']);
            }
            foreach (['inquiry', 'test_drive', 'sold_vehicle'] as $table) {
                $query = $db->prepare("SELECT COUNT(*) FROM $table WHERE Vehicle_ID = ?");
                $query->execute([$id]);
                if ($query->fetchColumn()) {
                    throw new ValidationException(['vehicle' => 'This vehicle has linked records and cannot be removed.']);
                }
            }
            $db->prepare('DELETE FROM vehicle WHERE Vehicle_ID = ?')->execute([$id]);
            return VehiclePhotos::paths($vehicle);
        });
        VehiclePhotos::remove($photos);
    }

    /** Saves a sold_vehicle record and marks the vehicle Sold in one transaction. */
    public function markSold(int $id, array $input, ?int $employeeId): void
    {
        $v = new Validator($input);
        $customerId = $v->integer('Customer_ID', 'Customer');
        $saleDate = $v->text('Sale_Date', 'Sale date', 10);
        $sellingPrice = $v->price('Selling_Price', 'Selling price');
        if (!$employeeId) {
            $v->error('Employee_ID', 'The signed-in account must be linked to an employee record to record a sale.');
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $saleDate);
        if (!$date || $date->format('Y-m-d') !== $saleDate) {
            $v->error('Sale_Date', 'Enter a valid sale date.');
        }
        $v->finish();

        Database::transaction(function (PDO $db) use ($id, $customerId, $saleDate, $sellingPrice, $employeeId): void {
            $this->lockVehicle($db, $id);
            $existingSale = $db->prepare('SELECT Sold_Vehicle_ID FROM sold_vehicle WHERE Vehicle_ID = ? FOR UPDATE');
            $existingSale->execute([$id]);
            if ($existingSale->fetchColumn()) {
                throw new ValidationException(['vehicle' => 'This vehicle already has a sale record.']);
            }
            $this->ensureNoActiveRequests($db, $id);
            $customer = $db->prepare('SELECT Customer_ID FROM customer WHERE Customer_ID = ? FOR UPDATE');
            $customer->execute([$customerId]);
            if (!$customer->fetchColumn()) {
                throw new ValidationException(['Customer_ID' => 'Select an existing customer.']);
            }
            $db->prepare('INSERT INTO sold_vehicle (Vehicle_ID, Customer_ID, Employee_ID, Sale_Date, Selling_Price) VALUES (?, ?, ?, ?, ?)')
                ->execute([$id, $customerId, $employeeId, $saleDate, $sellingPrice]);
            $db->prepare("UPDATE vehicle SET Availability_Status = 'Sold' WHERE Vehicle_ID = ?")->execute([$id]);
        });
    }

    /** Reads and locks one vehicle row for the rest of the transaction. */
    public function lockVehicle(PDO $db, int $id): array
    {
        $query = $db->prepare('SELECT * FROM vehicle WHERE Vehicle_ID = ? FOR UPDATE');
        $query->execute([$id]);
        $row = $query->fetch();
        if (!$row) {
            throw new HttpException(404, 'Vehicle not found.');
        }
        return $row;
    }

    /** Validates the form input and returns a Vehicle object filled through its setters. */
    private function buildVehicle(array $input, ?int $id): Vehicle
    {
        $v = new Validator($input);
        $brandName = $v->text('Brand_Name', 'Brand', 50);
        $brandCountry = $v->text('Country_Of_Origin', 'Country of origin', 50);
        $model = $v->text('Model', 'Model', 50);
        $year = $v->integer('Year', 'Year', 1901, min(2155, (int) date('Y') + 1));
        $color = $v->text('Color', 'Color', 30);
        $transmission = $v->choice('Transmission', 'transmission', Vehicle::TRANSMISSIONS);
        $fuel = $v->choice('Fuel_Type', 'fuel type', Vehicle::FUEL_TYPES);
        $price = $v->price('Price', 'Price');
        $status = $v->choice('Availability_Status', 'availability', Vehicle::STATUSES);
        $v->finish();

        $vehicle = new Vehicle(new Brand($brandName, $brandCountry));
        $vehicle->setId($id);
        $vehicle->setModel($model);
        $vehicle->setYear($year);
        $vehicle->setColor($color);
        $vehicle->setTransmission($transmission);
        $vehicle->setFuelType($fuel);
        $vehicle->setPrice($price);
        $vehicle->setAvailabilityStatus($status);
        return $vehicle;
    }

    /** Writes the vehicle (and its brand) inside the open transaction. */
    private function persist(PDO $db, Vehicle $vehicle, array $photos, array &$oldPhotos): int
    {
        $id = $vehicle->getId();
        $status = $vehicle->getAvailabilityStatus();
        $soldMessage = 'Use the Mark as sold action to record the customer and sale details.';
        if ($id !== null) {
            $current = $this->lockVehicle($db, $id);
            if ($photos) {
                $oldPhotos = VehiclePhotos::paths($current);
            }
            if ($status !== 'Available') {
                $this->ensureNoActiveRequests($db, $id);
            }
            $query = $db->prepare('SELECT COUNT(*) FROM sold_vehicle WHERE Vehicle_ID = ?');
            $query->execute([$id]);
            if ($query->fetchColumn() && $status !== 'Sold') {
                throw new ValidationException(['Availability_Status' => 'This vehicle has a recorded sale and must remain Sold.']);
            }
            if ($current['Availability_Status'] !== 'Sold' && $status === 'Sold') {
                throw new ValidationException(['Availability_Status' => $soldMessage]);
            }
        } elseif ($status === 'Sold') {
            throw new ValidationException(['Availability_Status' => $soldMessage]);
        }

        // Saves a new brand, or updates the country of an existing one, and returns its ID.
        $brand = $vehicle->getBrand();
        $db->prepare(
            'INSERT INTO brand (Brand_Name, Country_Of_Origin) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE Country_Of_Origin = VALUES(Country_Of_Origin), Brand_ID = LAST_INSERT_ID(Brand_ID)'
        )->execute([$brand->getName(), $brand->getCountryOfOrigin()]);
        $brandId = (int) $db->lastInsertId();
        $brand->setId($brandId ?: null);

        $values = [
            $brandId,
            $vehicle->getModel(),
            $vehicle->getYear(),
            $vehicle->getColor(),
            $vehicle->getTransmission(),
            $vehicle->getFuelType(),
            $vehicle->getPrice(),
            $status,
        ];
        if ($id === null) {
            $db->prepare('INSERT INTO vehicle (Brand_ID, Model, Year, Color, Transmission, Fuel_Type, Price, Availability_Status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute($values);
            $id = (int) $db->lastInsertId();
            $vehicle->setId($id);
        } else {
            $values[] = $id;
            $db->prepare('UPDATE vehicle SET Brand_ID = ?, Model = ?, Year = ?, Color = ?, Transmission = ?, Fuel_Type = ?, Price = ?, Availability_Status = ? WHERE Vehicle_ID = ?')
                ->execute($values);
        }
        if ($photos) {
            $vehicle->setImagePath($photos[0]);
            $db->prepare('UPDATE vehicle SET Image_Path = ? WHERE Vehicle_ID = ?')->execute([$vehicle->getImagePath(), $id]);
        }
        return $id;
    }

    /** A vehicle with Pending or Approved test drives cannot change availability or be sold. */
    private function ensureNoActiveRequests(PDO $db, int $id): void
    {
        $query = $db->prepare("SELECT Test_Drive_ID FROM test_drive WHERE Vehicle_ID = ? AND Request_Status IN ('Pending', 'Approved') FOR UPDATE");
        $query->execute([$id]);
        if ($query->fetch()) {
            throw new ValidationException(['Availability_Status' => 'Complete or cancel this vehicle’s active test drives before changing its availability.']);
        }
    }
}
