<?php
declare(strict_types=1);

namespace NVRO\Controllers;

use NVRO\Core\Database;
use NVRO\Repositories\CustomerRepository;
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
        $query = $db->prepare('SELECT s.*, CONCAT(c.First_Name, \' \', c.Last_Name) Customer_Name, CONCAT(e.First_Name, \' \', e.Last_Name) Employee_Name FROM sold_vehicle s JOIN customer c ON c.Customer_ID=s.Customer_ID JOIN employee e ON e.Employee_ID=s.Employee_ID WHERE s.Vehicle_ID=?');
        $query->execute([$vehicle['Vehicle_ID']]);
        $sale = $query->fetch() ?: null;
        $this->render('vehicle', ['title' => $vehicle['Brand_Name'] . ' ' . $vehicle['Model'], 'description' => 'Vehicle #' . $vehicle['Vehicle_ID'], 'vehicle' => $vehicle, 'inquiries' => $inquiries, 'drives' => $drives, 'sale' => $sale]);
    }

    public function edit(bool $new = false): void
    {
        $id = $new ? null : $this->id();
        $repository = new VehicleRepository();
        $brandRows = $repository->brands();
        $brands = array_column($brandRows, 'Brand_Name');
        $brandOrigins = array_column($brandRows, 'Country_Of_Origin', 'Brand_Name');
        $data = $new ? ['Availability_Status' => 'Available'] : $this->record($repository, $id);
        $options = static fn(array $values): array => array_combine($values, $values);
        $availability = ['Available', 'Reserved'];
        if (!$new && $data['Availability_Status'] === 'Sold') { $availability[] = 'Sold'; }
        $this->form([
            'title' => $new ? 'Add vehicle' : 'Edit vehicle', 'description' => 'Keep your inventory accurate with complete vehicle details.',
            'data' => $data, 'canMarkSold' => !$new && empty($data['Sold_Vehicle_ID']),
            'fields' => [
                ['name' => 'Brand_Name', 'label' => 'Brand', 'type' => 'brand', 'options' => $brands, 'origins' => $brandOrigins, 'hint' => 'Select a saved brand, or add a new one below. New brands are saved with the vehicle.'],
                ['name' => 'Country_Of_Origin', 'label' => 'Country of origin', 'max' => 50, 'placeholder' => 'e.g. Japan', 'hint' => 'Saved with the brand and shown on vehicle details.'],
                ['name' => 'Model', 'label' => 'Model', 'max' => 50, 'placeholder' => 'e.g. Vios 1.3 XLE'],
                ['name' => 'Year', 'label' => 'Year', 'type' => 'number', 'min' => 1901, 'max' => min(2155, (int) date('Y') + 1)],
                ['name' => 'Color', 'label' => 'Color', 'max' => 30],
                ['name' => 'Transmission', 'label' => 'Transmission', 'type' => 'select', 'options' => $options(['Automatic', 'Manual', 'CVT'])],
                ['name' => 'Fuel_Type', 'label' => 'Fuel type', 'type' => 'select', 'options' => $options(['Gasoline', 'Diesel', 'Hybrid', 'Electric'])],
                ['name' => 'Price', 'label' => 'Price (PHP)', 'type' => 'number', 'min' => '0.01', 'max' => '9999999999.99', 'step' => '0.01'],
                ['name' => 'Availability_Status', 'label' => 'Availability', 'type' => 'select', 'options' => $options($availability), 'hint' => 'Use Record sale to save the buyer and sale details. Complete or cancel active test drives before changing availability.'],
            ], 'vehiclePhotos' => true, 'returnRoute' => 'inventory', 'success' => $new ? 'Vehicle added to inventory.' : 'Vehicle updated.',
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
        $customers = [];
        foreach ((new CustomerRepository())->all() as $customer) {
            $customers[$customer['Customer_ID']] = $customer['First_Name'] . ' ' . $customer['Last_Name'] . ' · ' . $customer['Contact_Number'];
        }
        $this->form([
            'title' => 'Record vehicle sale', 'description' => 'Record the buyer and final sale details for this vehicle.',
            'data' => ['Customer_ID' => '', 'Sale_Date' => date('Y-m-d'), 'Selling_Price' => $vehicle['Price']],
            'fields' => [
                ['name' => 'Customer_ID', 'label' => 'Customer', 'type' => 'select', 'options' => $customers, 'hint' => 'Record a new customer from the inquiry form first, then return here.'],
                ['name' => 'Sale_Date', 'label' => 'Sale date', 'type' => 'date'],
                ['name' => 'Selling_Price', 'label' => 'Selling price (PHP)', 'type' => 'number', 'min' => '0.01', 'max' => '9999999999.99', 'step' => '0.01'],
            ], 'returnRoute' => 'inventory', 'cancelRoute' => 'vehicle', 'cancelParameters' => ['id' => $id], 'submit' => 'Record sale', 'success' => 'Sale recorded and vehicle marked as sold.',
        ], fn(array $input) => (new VehicleService())->markSold($id, $input, $this->user?->employeeId()));
    }

}
