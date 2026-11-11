<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#0b0d11">
  <meta name="description" content="NVRO Auto Trading Inventory System — your workspace for inventory and daily operations.">
  <title>Log in | NVRO Auto Trading</title>
  <link rel="stylesheet" href="styles.css">
  <script src="script.js" defer></script>
<link rel="icon" type="image/svg+xml" href="assets/favicon.svg"></head>
<body>
  <main class="login-layout">
    <section class="brand-panel" aria-label="NVRO Auto Trading">
      <div class="brand-top"><span class="brand-mark" aria-hidden="true">N<span>V</span></span><span>BUILT TO KEEP YOU MOVING</span></div>
      <div class="brand-content">
        <img class="brand-logo" src="assets/nvrologo.png" alt="NVRO — Navarro Auto Trading" width="780" height="210">
        <div class="red-rule"></div>
        <p class="eyebrow">INVENTORY MANAGEMENT SYSTEM</p>
        <h1>Your inventory. <br>Your business. <br><span>In full control.</span></h1>
        <p class="brand-description">A single workspace to keep your inventory organized and your daily operations moving forward.</p>
      </div>
      <div class="brand-bottom"><span class="small-line"></span><span>PRECISION IN EVERY PART.</span><span class="edition">NVRO / 01</span></div>
      <div class="track-lines" aria-hidden="true"><i></i><i></i><i></i></div>
    </section>

    <section class="form-panel" aria-labelledby="login-heading">
      <div class="portal-label"><span></span> EMPLOYEE PORTAL</div>
      <div class="login-content">
        <div class="welcome-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M14 4h5v16h-5M3 12h12m-4-4 4 4-4 4" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
        <p class="eyebrow form-eyebrow">WELCOME TO NVRO</p>
        <h2 id="login-heading">Welcome back.</h2>
        <p class="form-description">Log in to your account to get started.</p>

        <form id="login-form" method="post" action="<?= e(url('login')) ?>" novalidate>
          <?= csrf() ?>
          <?php if ($errors): ?><div class="login-alert" role="alert"><?= e(reset($errors)) ?></div><?php endif; ?>
          <?php if ($flash): ?><div class="login-alert info" role="status"><?= e($flash['message']) ?></div><?php endif; ?>
          <div class="field-group">
            <label for="username">Username</label>
            <div class="input-wrap"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 21v-3a7 7 0 0 1 14 0v3"/></svg><input id="username" name="username" type="text" value="<?= e($_POST['username'] ?? '') ?>" maxlength="50" autocomplete="username" placeholder="Enter your username" required aria-describedby="username-error"></div>
            <p id="username-error" class="field-error"></p>
          </div>
          <div class="field-group">
            <label for="password">Password</label>
            <div class="input-wrap"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3"/></svg><input id="password" name="password" type="password" autocomplete="current-password" placeholder="Enter your password" required aria-describedby="password-error"><button class="password-toggle" id="password-toggle" type="button" aria-label="Show password" aria-pressed="false"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/><path class="eye-slash" d="m3 3 18 18"/></svg></button></div>
            <p id="password-error" class="field-error"></p>
          </div>
          <div class="form-options"><label class="remember"><input type="checkbox" id="remember" name="remember"> Remember username</label><button type="button" class="text-button" id="forgot-password">Forgot password?</button></div>
          <button class="login-button" type="submit">Log in <span aria-hidden="true">→</span></button>
          <p id="form-message" class="form-message" role="status" aria-live="polite"></p>
        </form>
        <div class="help-note"><span>Need access to the system?</span><button type="button" class="text-button" id="contact-admin">Contact your administrator <span aria-hidden="true">↗</span></button></div>
        <p class="access-note"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 8 3v6c0 4-8 9-8 9s-8-5-8-9V6Z"/><path d="m8 12 3 3 5-6"/></svg> For authorized personnel only</p>
      </div>
      <footer>© <span id="year"><?= date('Y') ?></span> NVRO Auto Trading.<span>All rights reserved.</span></footer>
    </section>
  </main>
</body>
</html>
