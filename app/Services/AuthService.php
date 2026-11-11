<?php
declare(strict_types=1);

namespace NVRO\Services;

use NVRO\Core\Database;
use NVRO\Core\Session;
use NVRO\Models\User;
use NVRO\Repositories\UserRepository;
use NVRO\Validation\ValidationException;

final class AuthService
{
    public function user(): ?User
    {
        if (empty($_SESSION['user_id'])) { return null; }
        $row = (new UserRepository())->find((int) $_SESSION['user_id']);
        if (!$row || $row['Account_Status'] !== 'Active' || (int) $row['Session_Version'] !== ($_SESSION['version'] ?? 0)) {
            Session::clear();
            return null;
        }
        return new User($row);
    }

    public function login(string $username, string $password): void
    {
        $db = Database::connection();
        $key = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? 'local'));
        $lock = 'nvro_auth_' . substr($key, 0, 40);
        $query = $db->prepare('SELECT GET_LOCK(?, 5)');
        $query->execute([$lock]);
        if ((int) $query->fetchColumn() !== 1) { throw new ValidationException(['login' => 'Please try again in a moment.']); }
        try {
            $query = $db->prepare('SELECT * FROM auth_attempts WHERE Attempt_Key = ?');
            $query->execute([$key]);
            $attempt = $query->fetch();
            if ($attempt && strtotime($attempt['Window_Start']) > time() - 900 && (int) $attempt['Failures'] >= 5) {
                throw new ValidationException(['login' => 'Too many unsuccessful attempts. Please wait 15 minutes before trying again.']);
            }
            $user = (new UserRepository())->byUsername($username);
            $hash = $user['Password_Hash'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
            $valid = password_verify($password, $hash);
            if (!$user || !$valid || $user['Account_Status'] !== 'Active') {
                $query = $db->prepare('INSERT INTO auth_attempts (Attempt_Key, Failures, Window_Start) VALUES (?,1,NOW()) ON DUPLICATE KEY UPDATE Failures=IF(Window_Start < DATE_SUB(NOW(), INTERVAL 15 MINUTE),1,Failures+1), Window_Start=IF(Window_Start < DATE_SUB(NOW(), INTERVAL 15 MINUTE),NOW(),Window_Start)');
                $query->execute([$key]);
                throw new ValidationException(['login' => 'Incorrect username or password, or your account is inactive.']);
            }
            $db->prepare('DELETE FROM auth_attempts WHERE Attempt_Key = ?')->execute([$key]);
            Session::clear();
            $_SESSION['user_id'] = (int) $user['User_ID'];
            $_SESSION['version'] = (int) $user['Session_Version'];
            $_SESSION['last_seen'] = time();
        } finally {
            $db->prepare('SELECT RELEASE_LOCK(?)')->execute([$lock]);
        }
    }
}
