<?php
declare(strict_types=1);

namespace NVRO\Controllers;

use NVRO\Core\Database;
use NVRO\Repositories\VehicleRepository;
use NVRO\Repositories\TestDriveRepository;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $db = Database::connection();
        $stats = [
            'available' => (int) $db->query("SELECT COUNT(*) FROM vehicle WHERE Availability_Status='Available'")->fetchColumn(),
            'sold' => (int) $db->query("SELECT COUNT(*) FROM vehicle WHERE Availability_Status='Sold'")->fetchColumn(),
            'inquiries' => (int) $db->query("SELECT COUNT(*) FROM inquiry WHERE Inquiry_Status IN ('Pending','In Progress')")->fetchColumn(),
            'drives' => (int) $db->query("SELECT COUNT(*) FROM test_drive WHERE Request_Status='Pending'")->fetchColumn(),
            'total' => (int) $db->query('SELECT COUNT(*) FROM vehicle')->fetchColumn(),
        ];
        $upcoming = (new TestDriveRepository())->upcoming();
        $this->render('dashboard', ['title' => 'Dashboard', 'description' => "Here's today's overview of your inventory, customer inquiries, and test drives.", 'stats' => $stats, 'vehicles' => array_slice((new VehicleRepository())->all(), 0, 5), 'pendingPreview' => array_slice((new TestDriveRepository())->pending(), 0, 5), 'upcoming' => $upcoming]);
    }
}
