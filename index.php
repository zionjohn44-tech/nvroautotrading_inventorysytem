<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use NVRO\Controllers\AuthController;
use NVRO\Controllers\DashboardController;
use NVRO\Controllers\InventoryController;
use NVRO\Controllers\RecordsController;
use NVRO\Controllers\StaffController;
use NVRO\Core\HttpException;
use NVRO\Core\Session;
use NVRO\Middleware\Access;
use NVRO\Services\AuthService;

ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/storage/php-error.log');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; font-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'");
header('Cache-Control: no-store');

try {
    Session::start();
    foreach ([$_GET, $_POST] as $parameters) {
        foreach ($parameters as $value) {
            if (!is_string($value)) { throw new HttpException(400, 'Invalid request data.'); }
        }
    }
    $method = $_SERVER['REQUEST_METHOD'];
    if (!in_array($method, ['GET', 'POST'], true)) { throw new HttpException(405, 'This request method is not supported.'); }
    if ($method === 'POST') { Session::verify(); }
    $route = $_GET['r'] ?? 'login';
    $user = (new AuthService())->user();
    if (!in_array($route, ['login', 'setup'], true)) {
        $user = Access::user();
    }
    if (in_array($route, ['staff', 'staff-new', 'staff-edit'], true)) { Access::admin($user); }
    $auth = new AuthController($user);
    $inventory = new InventoryController($user);
    $records = new RecordsController($user);
    $staff = new StaffController($user);
    $routes = [
        'login' => ['GET,POST', fn() => $auth->login()],
        'setup' => ['GET,POST', fn() => $auth->setup()],
        'logout' => ['POST', fn() => $auth->logout()],
        'dashboard' => ['GET', fn() => (new DashboardController($user))->index()],
        'inventory' => ['GET', fn() => $inventory->index()],
        'vehicle' => ['GET', fn() => $inventory->show()],
        'vehicle-new' => ['GET,POST', fn() => $inventory->edit(true)],
        'vehicle-edit' => ['GET,POST', fn() => $inventory->edit()],
        'vehicle-sold' => ['POST', fn() => $inventory->markSold()],
        'vehicle-delete' => ['POST', fn() => $inventory->remove()],
        'inquiries' => ['GET', fn() => $records->inquiries()],
        'inquiry-new' => ['GET,POST', fn() => $records->inquiryEdit(true)],
        'inquiry-edit' => ['GET,POST', fn() => $records->inquiryEdit()],
        'test-drives' => ['GET', fn() => $records->drives()],
        'test-drive-new' => ['GET,POST', fn() => $records->driveEdit(true)],
        'test-drive-edit' => ['GET,POST', fn() => $records->driveEdit()],
        'test-drive-next' => ['POST', fn() => $records->processNext()],
        'test-drive-status' => ['POST', fn() => $records->driveStatus()],
        'staff' => ['GET', fn() => $staff->index()],
        'staff-new' => ['GET,POST', fn() => $staff->edit(true)],
        'staff-edit' => ['GET,POST', fn() => $staff->edit()],
    ];
    if (!isset($routes[$route])) { throw new HttpException(404, 'Page not found.'); }
    if (!in_array($method, explode(',', $routes[$route][0]), true)) { throw new HttpException(405, 'This action requires a different request method.'); }
    $routes[$route][1]();
} catch (Throwable $exception) {
    $status = $exception instanceof HttpException ? $exception->status : 500;
    http_response_code($status);
    if ($status === 500) { error_log((string) $exception); }
    $message = $status === 500 ? 'The system could not complete this request. Please try again. If this continues, ask your administrator to check the database connection and application log.' : $exception->getMessage();
    require __DIR__ . '/views/error.php';
}
