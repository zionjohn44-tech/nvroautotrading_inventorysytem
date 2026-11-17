<?php
declare(strict_types=1);

namespace NVRO\Services;

use NVRO\Core\Database;
use NVRO\Core\Session;
use NVRO\Models\User;
use NVRO\Repositories\UserRepository;
use NVRO\Validation\ValidationException;
use PDO;

/**
 * Signs staff in and out, rebuilds the signed-in User object on every request,
 * limits failed logins, and creates the first administrator account.
 */
final class AuthService
{
    private const MAX_FAILURES = 5;
    private const LOCKOUT_SECONDS = 900; // 15 minutes

    /** Hash checked when the username does not exist, so timing stays the same. */
    private const DUMMY_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';

    /** Returns the signed-in user, or null when nobody is signed in. */
    public function user(): ?User
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }
        $row = (new UserRepository())->find((int) $_SESSION['user_id']);
        $sessionIsCurrent = $row
            && $row['Account_Status'] === 'Active'
            && (int) $row['Session_Version'] === ($_SESSION['version'] ?? 0);
        if (!$sessionIsCurrent) {
            Session::clear();
            return null;
        }
        // Polymorphism: returns an Administrator or a DealershipStaff object.
        return User::fromRow($row);
    }

    /** Checks the credentials and starts a new session, or throws a ValidationException. */
    public function login(string $username, string $password): void
    {
        $db = Database::connection();
        $key = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? 'local'));
        $lock = 'nvro_auth_' . substr($key, 0, 40);
        $this->acquireLock($db, $lock, 'login', 'Please try again in a moment.');
        try {
            $this->ensureNotLockedOut($db, $key);
            $user = (new UserRepository())->byUsername($username);
            $valid = password_verify($password, $user['Password_Hash'] ?? self::DUMMY_HASH);
            if (!$user || !$valid || $user['Account_Status'] !== 'Active') {
                $this->recordFailure($db, $key);
                throw new ValidationException(['login' => 'Incorrect username or password, or your account is inactive.']);
            }
            $db->prepare('DELETE FROM auth_attempts WHERE Attempt_Key = ?')->execute([$key]);
            Session::clear();
            $_SESSION['user_id'] = (int) $user['User_ID'];
            $_SESSION['version'] = (int) $user['Session_Version'];
            $_SESSION['last_seen'] = time();
        } finally {
            $this->releaseLock($db, $lock);
        }
    }

    /** Creates the very first administrator. Only allowed while no account exists. */
    public function createFirstAdministrator(array $input): void
    {
        $db = Database::connection();
        $lock = 'nvro_setup_' . $db->query('SELECT DATABASE()')->fetchColumn();
        $this->acquireLock($db, $lock, 'setup', 'Setup is already in progress. Please try again.');
        try {
            (new UserService())->save($input, null, null, true);
        } finally {
            $this->releaseLock($db, $lock);
        }
    }

    private function ensureNotLockedOut(PDO $db, string $key): void
    {
        $query = $db->prepare('SELECT * FROM auth_attempts WHERE Attempt_Key = ?');
        $query->execute([$key]);
        $attempt = $query->fetch();
        if ($attempt
            && strtotime($attempt['Window_Start']) > time() - self::LOCKOUT_SECONDS
            && (int) $attempt['Failures'] >= self::MAX_FAILURES) {
            throw new ValidationException(['login' => 'Too many unsuccessful attempts. Please wait 15 minutes before trying again.']);
        }
    }

    private function recordFailure(PDO $db, string $key): void
    {
        $db->prepare(
            'INSERT INTO auth_attempts (Attempt_Key, Failures, Window_Start) VALUES (?, 1, NOW())
             ON DUPLICATE KEY UPDATE
                Failures = IF(Window_Start < DATE_SUB(NOW(), INTERVAL 15 MINUTE), 1, Failures + 1),
                Window_Start = IF(Window_Start < DATE_SUB(NOW(), INTERVAL 15 MINUTE), NOW(), Window_Start)'
        )->execute([$key]);
    }

    /** MySQL named lock so two requests cannot run the same step at once. */
    private function acquireLock(PDO $db, string $lock, string $field, string $message): void
    {
        $query = $db->prepare('SELECT GET_LOCK(?, 5)');
        $query->execute([$lock]);
        if ((int) $query->fetchColumn() !== 1) {
            throw new ValidationException([$field => $message]);
        }
    }

    private function releaseLock(PDO $db, string $lock): void
    {
        $db->prepare('SELECT RELEASE_LOCK(?)')->execute([$lock]);
    }
}
