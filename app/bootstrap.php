<?php
declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'NVRO\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

date_default_timezone_set('Asia/Manila');

function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $route, array $parameters = []): string
{
    return 'index.php?' . http_build_query(['r' => $route] + $parameters);
}

function csrf(): string
{
    return '<input type="hidden" name="_token" value="' . e(\NVRO\Core\Session::token()) . '">';
}

function money($amount): string
{
    return '₱' . number_format((float) $amount, 2);
}

function selected($value, $expected): string
{
    return (string) $value === (string) $expected ? ' selected' : '';
}

function badge(string $status): string
{
    $colors = ['Available' => 'green', 'Active' => 'green', 'Approved' => 'blue',
        'Pending' => 'amber', 'In Progress' => 'blue', 'Sold' => 'navy',
        'Resolved' => 'green', 'Completed' => 'green', 'Reserved' => 'amber'];
    return '<span class="badge ' . ($colors[$status] ?? 'gray') . '">' . e($status) . '</span>';
}

function icon(string $name): string
{
    $paths = [
        'dashboard' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'inventory' => '<path d="m4 10 2-6h12l2 6M3 10h18v8H3zM6 18v3m12-3v3M6 14h2m8 0h2"/>',
        'inquiries' => '<path d="M21 12a9 9 0 0 1-9 9H3l2-5a9 9 0 1 1 16-4Z"/><path d="M8 9h8m-8 4h5"/>',
        'test-drives' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'staff' => '<circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 5a3 3 0 0 1 0 6m2 4a4 4 0 0 1 3 4v2"/>',
        'logout' => '<path d="M10 4H4v16h6m4-12 4 4-4 4m-6-4h13"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'sidebar' => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M9 4v16m5-11 3 3-3 3"/>',
        'search' => '<circle cx="10" cy="10" r="6"/><path d="m15 15 5 5"/>',
        'sold' => '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
        'arrow' => '<path d="M5 12h14m-6-6 6 6-6 6"/>',
        'spark' => '<path d="m12 3 2.5 6.5L21 12l-6.5 2.5L12 21l-2.5-6.5L3 12l6.5-2.5L12 3Z"/>',
    ];
    return '<svg class="ui-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? $paths['dashboard']) . '</svg>';
}
