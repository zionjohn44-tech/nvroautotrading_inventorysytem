<?php
declare(strict_types=1);

namespace NVRO\Core;

final class Session
{
    public static function start(): void
    {
        ini_set('session.use_strict_mode', '1');
        session_name('nvro_session');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/') . '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        if (!session_start()) {
            throw new \RuntimeException('PHP could not open its session storage. Check the configured session.save_path permissions.');
        }
        if (isset($_SESSION['last_seen']) && time() - $_SESSION['last_seen'] > 1800) {
            self::clear();
            self::flash('Your session expired. Please log in again.', 'info');
        }
        $_SESSION['last_seen'] = time();
    }

    public static function token(): string
    {
        return $_SESSION['csrf'] ?? ($_SESSION['csrf'] = bin2hex(random_bytes(32)));
    }

    public static function verify(): void
    {
        if (!is_string($_POST['_token'] ?? null) || !hash_equals(self::token(), $_POST['_token'])) {
            throw new HttpException(419, 'Your form expired. Refresh the page and try again.');
        }
    }

    public static function clear(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
    }

    public static function flash(string $message, string $type = 'success'): void
    {
        $_SESSION['flash'] = compact('message', 'type');
    }

    public static function takeFlash(): ?array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }
}
