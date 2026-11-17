<?php
declare(strict_types=1);

namespace NVRO\Services;

use NVRO\Core\Database;
use NVRO\Core\HttpException;
use NVRO\Models\User;
use NVRO\Validation\Validator;
use NVRO\Validation\ValidationException;
use PDO;

/** Creates and updates staff accounts (the user table and its linked employee row). */
final class UserService
{
    /**
     * Creates ($id = null) or updates a staff account and returns the user ID.
     *
     * @param User|null $actor   the administrator making the change
     * @param bool      $initial true only for the one-time first-administrator setup
     */
    public function save(array $input, ?int $id, ?User $actor = null, bool $initial = false): int
    {
        if (!$initial && (!$actor || !$actor->canManageStaff())) {
            throw new HttpException(403, 'Administrator access is required.');
        }
        $v = new Validator($input);
        $first = $v->text('First_Name', 'First name', 50);
        $last = $v->text('Last_Name', 'Last name', 50);
        $phone = $v->phone();
        $username = $v->text('Username', 'Username', 50);
        if (!preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $username)) {
            $v->error('Username', 'Use 3–50 letters, numbers, dots, underscores, or hyphens.');
        }
        $role = $initial ? 'Administrator' : $v->choice('Role', 'role', User::ROLES);
        $status = $initial ? 'Active' : $v->choice('Account_Status', 'account status', User::STATUSES);
        $password = is_string($input['Password'] ?? null) ? $input['Password'] : '';
        if (($id === null || $password !== '') && (strlen($password) < 12 || strlen($password) > 72)) {
            $v->error('Password', 'Use a password between 12 and 72 bytes.');
        }
        if ($initial && $password !== ($input['Password_Confirmation'] ?? '')) {
            $v->error('Password_Confirmation', 'The passwords do not match.');
        }
        $v->finish();

        $account = compact('first', 'last', 'phone', 'username', 'role', 'status', 'password');
        return Database::transaction(function (PDO $db) use ($id, $actor, $initial, $account): int {
            // Lock all accounts in one order to protect the last active administrator.
            $accounts = $db->query('SELECT User_ID, Role, Account_Status FROM `user` ORDER BY User_ID FOR UPDATE')->fetchAll();
            if ($initial && count($accounts) !== 0) {
                throw new HttpException(403, 'Initial setup has already been completed.');
            }
            if ($id !== null) {
                $this->checkAdministratorRules($accounts, $id, $actor, $account['role'], $account['status']);
            }
            $query = $db->prepare('SELECT User_ID FROM `user` WHERE Username = ? AND User_ID <> ?');
            $query->execute([$account['username'], $id ?? 0]);
            if ($query->fetch()) {
                throw new ValidationException(['Username' => 'That username is already in use.']);
            }
            return $id === null ? $this->insert($db, $account) : $this->update($db, $id, $account);
        });
    }

    /** Administrators cannot demote themselves, and one active administrator must remain. */
    private function checkAdministratorRules(array $accounts, int $id, ?User $actor, string $role, string $status): void
    {
        $current = null;
        $activeAdmins = 0;
        foreach ($accounts as $account) {
            if ((int) $account['User_ID'] === $id) {
                $current = $account;
            }
            if ($account['Role'] === 'Administrator' && $account['Account_Status'] === 'Active') {
                $activeAdmins++;
            }
        }
        if (!$current) {
            throw new HttpException(404, 'Account not found.');
        }
        $losesAdminAccess = $role !== 'Administrator' || $status !== 'Active';
        if ($actor && $actor->id() === $id && $losesAdminAccess) {
            throw new ValidationException(['Account_Status' => 'You cannot deactivate or demote your own account.']);
        }
        $isLastAdmin = $current['Role'] === 'Administrator' && $current['Account_Status'] === 'Active' && $activeAdmins <= 1;
        if ($isLastAdmin && $losesAdminAccess) {
            throw new ValidationException(['Role' => 'Keep at least one active administrator.']);
        }
    }

    private function insert(PDO $db, array $account): int
    {
        $db->prepare('INSERT INTO `user` (Username, Password_Hash, Role, Account_Status, Must_Change_Password) VALUES (?, ?, ?, ?, ?)')
            ->execute([$account['username'], password_hash($account['password'], PASSWORD_DEFAULT), $account['role'], $account['status'], 0]);
        $id = (int) $db->lastInsertId();
        $db->prepare('INSERT INTO employee (User_ID, First_Name, Last_Name, Contact_Number, Position) VALUES (?, ?, ?, ?, ?)')
            ->execute([$id, $account['first'], $account['last'], $account['phone'], $account['role']]);
        return $id;
    }

    private function update(PDO $db, int $id, array $account): int
    {
        // Increasing Session_Version signs the account out of every open session.
        $db->prepare('UPDATE `user` SET Username = ?, Role = ?, Account_Status = ?, Session_Version = Session_Version + 1 WHERE User_ID = ?')
            ->execute([$account['username'], $account['role'], $account['status'], $id]);
        if ($account['password'] !== '') {
            $db->prepare('UPDATE `user` SET Password_Hash = ?, Must_Change_Password = 0 WHERE User_ID = ?')
                ->execute([password_hash($account['password'], PASSWORD_DEFAULT), $id]);
        }
        $db->prepare(
            'INSERT INTO employee (User_ID, First_Name, Last_Name, Contact_Number, Position) VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE First_Name = VALUES(First_Name), Last_Name = VALUES(Last_Name),
                Contact_Number = VALUES(Contact_Number), Position = VALUES(Position)'
        )->execute([$id, $account['first'], $account['last'], $account['phone'], $account['role']]);
        return $id;
    }
}
