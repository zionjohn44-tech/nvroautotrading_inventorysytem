<?php
declare(strict_types=1);

namespace NVRO\Controllers;

use NVRO\Repositories\UserRepository;
use NVRO\Services\UserService;

final class StaffController extends Controller
{
    public function index(): void
    {
        $this->render('staff', ['title' => 'Staff accounts', 'description' => 'Manage the people who keep your dealership moving.'] + $this->paginate((new UserRepository())->all()));
    }

    public static function fields(bool $initial = false, bool $editing = false): array
    {
        $fields = [
            ['name' => 'First_Name', 'label' => 'First name', 'max' => 50],
            ['name' => 'Last_Name', 'label' => 'Last name', 'max' => 50],
            ['name' => 'Contact_Number', 'label' => 'Contact number', 'type' => 'tel', 'max' => 15],
            ['name' => 'Username', 'label' => 'Username', 'max' => 50, 'autocomplete' => 'username', 'hint' => '3–50 letters, numbers, dots, underscores, or hyphens.'],
            ['name' => 'Password', 'label' => $editing ? 'New password (optional)' : 'Password', 'type' => 'password', 'required' => !$editing, 'autocomplete' => 'new-password', 'hint' => $editing ? 'Leave blank to keep the current password.' : 'Use 12–72 bytes. Passwords are managed here in Staff accounts.'],
        ];
        if ($initial) {
            $fields[4]['hint'] = 'Use 12–72 bytes. A long passphrase works well.';
            $fields[] = ['name' => 'Password_Confirmation', 'label' => 'Confirm password', 'type' => 'password', 'autocomplete' => 'new-password'];
        } else {
            $fields[] = ['name' => 'Role', 'label' => 'Role', 'type' => 'select', 'options' => ['Dealership Staff' => 'Dealership Staff', 'Administrator' => 'Administrator']];
            $fields[] = ['name' => 'Account_Status', 'label' => 'Account status', 'type' => 'select', 'options' => ['Active' => 'Active', 'Inactive' => 'Inactive']];
        }
        return $fields;
    }

    public function edit(bool $new = false): void
    {
        $id = $new ? null : $this->id();
        $this->form([
            'title' => $new ? 'Add staff account' : 'Edit staff account', 'description' => 'Only administrators can create accounts or change access.',
            'data' => $new ? ['Role' => 'Dealership Staff', 'Account_Status' => 'Active'] : $this->record(new UserRepository(), $id),
            'fields' => self::fields(false, !$new), 'returnRoute' => 'staff', 'success' => $new ? 'Staff account created.' : 'Account updated. Existing sessions for this account have been signed out.',
        ], fn(array $input) => (new UserService())->save($input, $id, $this->user));
    }
}
