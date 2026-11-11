<?php
declare(strict_types=1);

namespace NVRO\Middleware;

use NVRO\Core\HttpException;
use NVRO\Models\User;
use NVRO\Services\AuthService;

final class Access
{
    public static function user(): User
    {
        $user = (new AuthService())->user();
        if (!$user) {
            header('Location: ' . \url('login'), true, 303);
            exit;
        }
        return $user;
    }

    public static function admin(User $user): void
    {
        if (!$user->isAdmin()) { throw new HttpException(403, 'Only administrators can manage staff accounts.'); }
    }
}
