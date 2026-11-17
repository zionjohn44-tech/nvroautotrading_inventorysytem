<?php
declare(strict_types=1);

namespace NVRO\Controllers;

use NVRO\Core\HttpException;
use NVRO\Core\Session;
use NVRO\Repositories\UserRepository;
use NVRO\Services\AuthService;
use NVRO\Validation\ValidationException;

/** Login, logout and the one-time first-administrator setup. */
final class AuthController extends Controller
{
    public function login(): void
    {
        if ($this->user) {
            $this->redirect('dashboard');
        }
        if ((new UserRepository())->total() === 0) {
            $this->redirect('setup');
        }
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $username = trim($_POST['username'] ?? '');
                $password = $_POST['password'] ?? '';
                if ($username === '' || $password === '' || strlen($username) > 50 || strlen($password) > 72) {
                    throw new ValidationException(['login' => 'Enter a valid username and password.']);
                }
                (new AuthService())->login($username, $password);
                $this->redirect('dashboard');
            } catch (ValidationException $exception) {
                $errors = $exception->errors;
                http_response_code(422);
            }
        }
        $this->render('login', compact('errors'), true);
    }

    public function setup(): void
    {
        if ((new UserRepository())->total() > 0) {
            $this->redirect('login');
        }
        if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
            throw new HttpException(403, 'Create the first administrator from localhost on the XAMPP computer.');
        }
        $config = [
            'title' => 'Set up your workspace',
            'description' => 'Create the first NVRO administrator account. You can add staff after signing in.',
            'fields' => StaffController::fields(true),
            'returnRoute' => 'login',
            'success' => 'Administrator created. Log in with your new account.',
            'submit' => 'Create administrator',
        ];
        $this->form($config, fn(array $data) => (new AuthService())->createFirstAdministrator($data), true);
    }

    public function logout(): void
    {
        Session::clear();
        $this->redirect('login', 'You have been logged out.');
    }
}
