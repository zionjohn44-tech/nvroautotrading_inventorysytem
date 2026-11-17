<?php
declare(strict_types=1);

namespace NVRO\Middleware;

use NVRO\Core\HttpException;
use NVRO\Models\User;
use NVRO\Services\AuthService;

/** Route guards: require a signed-in user, or an administrator. */
final class Access
{
    /** Returns the signed-in user, or redirects to the login page. */
    public static function user(): User
    {
        $user = (new AuthService())->user();
        if (!$user) {
            header('Location: ' . \url('login'), true, 303);
            exit;
        }
        return $user;
    }

    /** Stops dealership staff from opening administrator-only pages (polymorphic check). */
    public static function admin(User $user): void
    {
        if (!$user->canManageStaff()) {
            throw new HttpException(403, 'Only administrators can manage staff accounts.');
        }
    }
}
