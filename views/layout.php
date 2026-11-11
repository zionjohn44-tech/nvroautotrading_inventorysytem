<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="theme-color" content="#10141d"><title><?= e($title) ?> | NVRO Inventory</title><script src="assets/theme.js"></script><link rel="stylesheet" href="assets/app.css"><?php if (($_GET['r'] ?? '') === 'test-drives'): ?><link rel="stylesheet" href="assets/queue.css"><?php endif; ?><link rel="stylesheet" href="assets/workspace.css"><?php if (($_GET['r'] ?? '') === 'dashboard'): ?><link rel="stylesheet" href="assets/dashboard.css"><?php endif; ?><script src="assets/app.js" defer></script><link rel="icon" type="image/svg+xml" href="assets/favicon.svg"></head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<div class="app-shell">
<aside class="sidebar" id="sidebar">
    <a class="sidebar-brand" aria-label="NVRO dashboard" href="<?= e(url('dashboard')) ?>"><img class="brand-mark" src="assets/favicon.svg" alt=""><img class="brand-wordmark" src="assets/nvrologo.png" alt="NVRO Navarro Auto Trading"><span>INVENTORY MANAGEMENT</span></a>
    <div class="nav-caption">WORKSPACE</div>
    <nav aria-label="Main navigation">
    <?php
    $activeRoute = $_GET['r'] ?? 'dashboard';
    $navigation = [
        ['dashboard', 'Dashboard', '◫', ['dashboard']],
        ['inventory', 'Vehicle inventory', '▱', ['inventory', 'vehicle', 'vehicle-new', 'vehicle-edit']],
        ['inquiries', 'Customer inquiries', '▤', ['inquiries', 'inquiry-new', 'inquiry-edit']],
        ['test-drives', 'Test drives', '◷', ['test-drives', 'test-drive-new', 'test-drive-edit']],
    ];
    if ($user->isAdmin()) { $navigation[] = ['staff', 'Staff accounts', '⊞', ['staff', 'staff-new', 'staff-edit']]; }
    foreach ($navigation as [$target, $label, $icon, $routes]): $active = in_array($activeRoute, $routes, true); ?>
        <a href="<?= e(url($target)) ?>" title="<?= e($label) ?>" aria-label="<?= e($label) ?>" class="nav-item <?= $active ? 'active' : '' ?>" <?= $active ? 'aria-current="page"' : '' ?>><span class="nav-icon" aria-hidden="true"><?= icon($target) ?></span><span class="nav-label"><?= e($label) ?></span><?php if ($active): ?><span class="nav-dot" aria-hidden="true"></span><?php endif; ?></a>
    <?php endforeach; ?>
    </nav>
    <div class="sidebar-bottom"><div class="workspace-note"><span class="live-dot"></span> NVRO AUTO TRADING<small>Your dealership. In full control.</small></div><button type="button" id="sidebar-toggle" class="nav-item sidebar-toggle" aria-controls="sidebar" aria-expanded="false" aria-label="Expand navigation" title="Expand navigation"><span class="nav-icon"><?= icon('sidebar') ?></span><span class="nav-label">Compact navigation</span></button><form method="post" action="<?= e(url('logout')) ?>"><?= csrf() ?><button class="nav-item logout" type="submit" aria-label="Log out" title="Log out"><span class="nav-icon"><?= icon('logout') ?></span><span class="nav-label">Log out</span></button></form></div>
</aside><button type="button" id="nav-backdrop" class="nav-backdrop" aria-label="Close navigation" hidden></button>
<div class="main-shell">
    <div class="ambient-light" aria-hidden="true"><span class="ambient-light-glow"></span></div>
    <header class="topbar"><button id="menu-toggle" class="menu-toggle" aria-label="Toggle navigation" aria-controls="sidebar" aria-expanded="false">☰</button><div class="breadcrumb">Workspace <span>/</span> <strong><?= e($title) ?></strong></div><form class="topbar-search" method="get" action="index.php"><input type="hidden" name="r" value="inventory"><label class="sr-only" for="workspace-search">Search vehicles</label><?= icon('search') ?><input id="workspace-search" name="q" placeholder="Search vehicles..." value="<?= e($activeRoute === 'inventory' ? ($_GET['q'] ?? '') : '') ?>"><button type="submit" class="sr-only">Search inventory</button></form><time class="topbar-date" datetime="<?= date('Y-m-d') ?>" data-today><?= e(date('D, M j, Y')) ?></time><button type="button" id="theme-toggle" class="theme-toggle" aria-label="Switch to dark mode" aria-pressed="false" title="Switch to dark mode"><span data-theme-icon aria-hidden="true">&#9790;</span><span class="theme-label">Dark</span></button><div class="account"><div class="account-copy"><strong><?= e($user->name()) ?></strong><small><?= e($user->role()) ?></small></div><span class="avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($user->name(), 0, 1))) ?></span></div></header>
    <main id="main" class="main-content"><div class="page-heading"><div><h1><?= e($activeRoute === 'dashboard' ? 'Welcome back, ' . (trim((string) ($user->data()['First_Name'] ?? '')) ?: $user->name()) . '.' : $title) ?></h1><p><?= e($description ?? '') ?></p></div><?php if ($activeRoute === 'dashboard'): ?><a class="button primary" href="<?= e(url('vehicle-new')) ?>">+ Add vehicle</a><?php endif; ?></div>
    <?php if ($flash): ?><div class="alert <?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div><?php endif; ?>
    <?= $content ?>
    </main><footer class="app-footer"><span>© <?= date('Y') ?> NVRO Auto Trading</span><span>Inventory Management System</span></footer>
</div>
</div>
<dialog id="confirm-dialog"><form method="dialog"><div class="dialog-icon">!</div><h2>Confirm this action</h2><p id="confirm-message"></p><div class="form-actions"><button class="button secondary" value="cancel">Go back</button><button class="button danger" value="confirm">Confirm</button></div></form></dialog>
</body></html>
