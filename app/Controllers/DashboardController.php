<?php
declare(strict_types=1);

namespace NVRO\Controllers;

use NVRO\Repositories\InquiryRepository;
use NVRO\Repositories\TestDriveRepository;
use NVRO\Repositories\VehicleRepository;

/** The home screen: summary counts, recent vehicles, queue preview and appointments. */
final class DashboardController extends Controller
{
    public function index(): void
    {
        $vehicles = new VehicleRepository();
        $drives = new TestDriveRepository();
        $stats = [
            'available' => $vehicles->countAvailable(),
            'sold' => $vehicles->countSold(),
            'inquiries' => (new InquiryRepository())->countOpen(),
            'drives' => $drives->countPending(),
            'total' => $vehicles->countAll(),
        ];
        $this->render('dashboard', [
            'title' => 'Dashboard',
            'description' => "Here's today's overview of your inventory, customer inquiries, and test drives.",
            'stats' => $stats,
            'vehicles' => array_slice($vehicles->all(), 0, 5),
            'pendingPreview' => array_slice($drives->pending(), 0, 5),
            'upcoming' => $drives->upcoming(),
        ]);
    }
}
