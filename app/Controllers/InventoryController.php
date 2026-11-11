<?php
declare(strict_types=1);

namespace NVRO\Controllers;

use NVRO\Core\Database;
use NVRO\Repositories\VehicleRepository;
use NVRO\Services\VehicleService;

final class InventoryController extends Controller
{
    public function index(): void
    {
        $this->render('inventory', ['title' => 'Vehicle inventory', 'description' => 'Every vehicle, every detail. All in one place.'] + $this->paginate((new VehicleService())->listing($_GET)));
    }

    public function show(): void
    {
        $vehicle = $this->record(new VehicleRepository(), $this->id());
        $db = Database::connection();
        $query = $db->prepare("SELECT i.*, CONCAT(c.First_Name,' ',c.Last_Name) Customer_Name FROM inquiry i JOIN customer c ON c.Customer_ID=i.Customer_ID WHERE Vehicle_ID=? ORDER BY Inquiry_Date DESC");
        $query->execute([$vehicle['Vehicle_ID']]);
        $inquiries = $query->fetchAll();
        $query = $db->prepare("SELECT t.*, CONCAT(c.First_Name,' ',c.Last_Name) Customer_Name FROM test_drive t JOIN customer c ON c.Customer_ID=t.Customer_ID WHERE Vehicle_ID=? ORDER BY Scheduled_Date DESC");
        $query->execute([$vehicle['Vehicle_ID']]);
        $drives = $query->fetchAll();
        $this->render('vehicle', ['title' => $vehicle['Brand_Name'] . ' ' . $vehicle['Model'], 'description' => 'Vehicle #' . $vehicle['Vehicle_ID'], 'vehicle' => $vehicle, 'inquiries' => $inquiries, 'drives' => $drives]);
    }

    public function edit(bool $new = false): void
    {
        $id = $new ? null : $this->id();
        $repository = new VehicleRepository();
        $brands = array_column($repository->brands(), 'Brand_Name');
        $data = $new ? ['Availability_Status' => 'Available'] : $this->record($repository, $id);
        $options = static fn(array $values): array => array_combine($values, $values);
        $this->form([
            'title' => $new ? 'Add vehicle' : 'Edit vehicle', 'description' => 'Keep your inventory accurate with complete vehicle details.',
            'data' => $data, 'canMarkSold' => !$new && $data['Availability_Status'] !== 'Sold',
            'fields' => [
                ['name' => 'Brand_Name', 'label' => 'Brand', 'type' => 'brand', 'options' => $brands, 'hint' => 'Select a saved brand, or add a new one below. New brands are saved with the vehicle.'],
                ['name' => 'Model', 'label' => 'Model', 'max' => 50, 'placeholder' => 'e.g. Vios 1.3 XLE'],
                ['name' => 'Year', 'label' => 'Year', 'type' => 'number', 'min' => 1901, 'max' => min(2155, (int) date('Y') + 1)],
                ['name' => 'Color', 'label' => 'Color', 'max' => 30],
                ['name' => 'Transmission', 'label' => 'Transmission', 'type' => 'select', 'options' => $options(['Automatic', 'Manual', 'CVT'])],
                ['name' => 'Fuel_Type', 'label' => 'Fuel type', 'type' => 'select', 'options' => $options(['Gasoline', 'Diesel', 'Hybrid', 'Electric'])],
                ['name' => 'Price', 'label' => 'Price (PHP)', 'type' => 'number', 'min' => '0.01', 'max' => '9999999999.99', 'step' => '0.01'],
                ['name' => 'Availability_Status', 'label' => 'Availability', 'type' => 'select', 'options' => $options(['Available', 'Sold', 'Reserved']), 'hint' => 'Complete or cancel active test drives before marking a vehicle sold or reserved.'],
            ], 'vehiclePhotos' => true, 'returnRoute' => 'inventory', 'success' => $new ? 'Vehicle added to inventory.' : 'Vehicle updated.',
        ], fn(array $input) => (new VehicleService())->save(
            !empty($input['mark_sold']) && !$new ? array_replace($input, ['Availability_Status' => 'Sold']) : $input,
            $id, $_FILES['Photos'] ?? []
        ));
    }

    public function remove(): void
    {
        $this->action(fn() => (new VehicleService())->remove($this->id()), 'inventory', 'Vehicle removed.');
    }

    public function markSold(): void
    {
        $this->action(fn() => (new VehicleService())->markSold($this->id()), 'inventory', 'Vehicle marked as sold.');
    }

}
