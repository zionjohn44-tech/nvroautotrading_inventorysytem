<?php
declare(strict_types=1);

namespace NVRO\Controllers;

use NVRO\Models\Customer;
use NVRO\Models\Vehicle;
use NVRO\Repositories\CustomerRepository;
use NVRO\Repositories\InquiryRepository;
use NVRO\Repositories\TestDriveRepository;
use NVRO\Repositories\VehicleRepository;
use NVRO\Services\VehicleService;

/** Vehicle inventory: list, details, add/edit, remove and record sale. */
final class InventoryController extends Controller
{
    public function index(): void
    {
        $this->render('inventory', [
            'title' => 'Vehicle inventory',
            'description' => 'Every vehicle, every detail. All in one place.',
        ] + $this->paginate((new VehicleService())->listing($_GET)));
    }

    public function show(): void
    {
        $repository = new VehicleRepository();
        $vehicle = $this->record($repository, $this->id());
        $vehicleId = (int) $vehicle['Vehicle_ID'];
        $this->render('vehicle', [
            'title' => $vehicle['Brand_Name'] . ' ' . $vehicle['Model'],
            'description' => 'Vehicle #' . $vehicle['Vehicle_ID'],
            'vehicle' => $vehicle,
            'inquiries' => (new InquiryRepository())->forVehicle($vehicleId),
            'drives' => (new TestDriveRepository())->forVehicle($vehicleId),
            'sale' => $repository->sale($vehicleId),
        ]);
    }

    public function edit(bool $new = false): void
    {
        $id = $new ? null : $this->id();
        $repository = new VehicleRepository();
        $brandRows = $repository->brands();
        $data = $new ? ['Availability_Status' => 'Available'] : $this->record($repository, $id);
        $availability = ['Available', 'Reserved'];
        if (!$new && $data['Availability_Status'] === 'Sold') {
            $availability[] = 'Sold';
        }
        $this->form([
            'title' => $new ? 'Add vehicle' : 'Edit vehicle',
            'description' => 'Keep your inventory accurate with complete vehicle details.',
            'data' => $data,
            'canMarkSold' => !$new && empty($data['Sold_Vehicle_ID']),
            'fields' => [
                ['name' => 'Brand_Name', 'label' => 'Brand', 'type' => 'brand',
                    'options' => array_column($brandRows, 'Brand_Name'),
                    'origins' => array_column($brandRows, 'Country_Of_Origin', 'Brand_Name'),
                    'hint' => 'Select a saved brand, or add a new one below. New brands are saved with the vehicle.'],
                ['name' => 'Country_Of_Origin', 'label' => 'Country of origin', 'max' => 50, 'placeholder' => 'e.g. Japan',
                    'hint' => 'Saved with the brand and shown on vehicle details.'],
                ['name' => 'Model', 'label' => 'Model', 'max' => 50, 'placeholder' => 'e.g. Vios 1.3 XLE'],
                ['name' => 'Year', 'label' => 'Year', 'type' => 'number', 'min' => 1901, 'max' => min(2155, (int) date('Y') + 1)],
                ['name' => 'Color', 'label' => 'Color', 'max' => 30],
                ['name' => 'Transmission', 'label' => 'Transmission', 'type' => 'select', 'options' => self::choices(Vehicle::TRANSMISSIONS)],
                ['name' => 'Fuel_Type', 'label' => 'Fuel type', 'type' => 'select', 'options' => self::choices(Vehicle::FUEL_TYPES)],
                ['name' => 'Price', 'label' => 'Price (PHP)', 'type' => 'number', 'min' => '0.01', 'max' => '9999999999.99', 'step' => '0.01'],
                ['name' => 'Availability_Status', 'label' => 'Availability', 'type' => 'select', 'options' => self::choices($availability),
                    'hint' => 'Use Record sale to save the buyer and sale details. Complete or cancel active test drives before changing availability.'],
            ],
            'vehiclePhotos' => true,
            'returnRoute' => 'inventory',
            'success' => $new ? 'Vehicle added to inventory.' : 'Vehicle updated.',
        ], fn(array $input) => (new VehicleService())->save($input, $id, $_FILES['Photos'] ?? []));
    }

    public function remove(): void
    {
        $this->action(fn() => (new VehicleService())->remove($this->id()), 'inventory', 'Vehicle removed.');
    }

    public function markSold(): void
    {
        $id = $this->id();
        $vehicle = $this->record(new VehicleRepository(), $id);
        $customers = array_map([Customer::class, 'fromRow'], (new CustomerRepository())->all());
        $this->form([
            'title' => 'Record vehicle sale',
            'description' => 'Record the buyer and final sale details for this vehicle.',
            'data' => ['Customer_ID' => '', 'Sale_Date' => date('Y-m-d'), 'Selling_Price' => $vehicle['Price']],
            'fields' => [
                ['name' => 'Customer_ID', 'label' => 'Customer', 'type' => 'select', 'options' => $this->options($customers),
                    'hint' => 'Record a new customer from the inquiry form first, then return here.'],
                ['name' => 'Sale_Date', 'label' => 'Sale date', 'type' => 'date'],
                ['name' => 'Selling_Price', 'label' => 'Selling price (PHP)', 'type' => 'number', 'min' => '0.01', 'max' => '9999999999.99', 'step' => '0.01'],
            ],
            'returnRoute' => 'inventory',
            'cancelRoute' => 'vehicle',
            'cancelParameters' => ['id' => $id],
            'submit' => 'Record sale',
            'success' => 'Sale recorded and vehicle marked as sold.',
        ], fn(array $input) => (new VehicleService())->markSold($id, $input, $this->user?->employeeId()));
    }

    /** ['A', 'B'] becomes ['A' => 'A', 'B' => 'B'] for select fields. */
    private static function choices(array $values): array
    {
        return array_combine($values, $values);
    }
}
