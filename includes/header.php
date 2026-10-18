<?php
require_once __DIR__ . '/functions.php';
if (session_status() === PHP_SESSION_NONE) {
    configure_secure_session();
    session_start();
}
apply_security_headers();
$logo = get_setting('logo_path', '');
$siteName = get_setting('site_name', 'Golden Power Security');
$currentPage = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($siteName) ?> — Private Armed Security &amp; Escort Services</title>
<meta name="description" content="Golden Power Security provides business, transport escort, farming and armed patrol security services across Southern Africa.">
<link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/favicon.png?v=1">
<link rel="shortcut icon" href="<?= BASE_URL ?>/assets/favicon.ico?v=1">
<link rel="apple-touch-icon" href="<?= BASE_URL ?>/assets/favicon.png?v=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Work+Sans:wght@400;500;600&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head>
<body>
<div class="dispatch-bar" aria-hidden="true">
  <div class="dispatch-track">
    <span>CHECKPOINT CLEAR</span><span>·</span><span>CONVOY EN ROUTE</span><span>·</span>
    <span>ESCORT ON STATION</span><span>·</span><span>DRC</span><span>·</span><span>ZAMBIA</span><span>·</span>
    <span>ZIMBABWE</span><span>·</span><span>MOZAMBIQUE</span><span>·</span><span>NAMIBIA</span><span>·</span>
    <span>TANZANIA</span><span>·</span><span>SOUTH AFRICA</span><span>·</span>
    <span>CHECKPOINT CLEAR</span><span>·</span><span>CONVOY EN ROUTE</span><span>·</span>
    <span>ESCORT ON STATION</span><span>·</span><span>DRC</span><span>·</span><span>ZAMBIA</span><span>·</span>
    <span>ZIMBABWE</span><span>·</span><span>MOZAMBIQUE</span><span>·</span><span>NAMIBIA</span><span>·</span>
    <span>TANZANIA</span><span>·</span><span>SOUTH AFRICA</span><span>·</span>
  </div>
</div>

<header class="site-nav" id="siteNav">
  <div class="nav-inner">
    <a href="<?= BASE_URL ?>/index.php" class="brand">
      <?php if ($logo): ?>
        <img src="<?= BASE_URL ?>/<?= e($logo) ?>" alt="<?= e($siteName) ?> logo" class="brand-logo">
      <?php else: ?>
        <span class="brand-mark" aria-hidden="true">GPS</span>
      <?php endif; ?>
      <span class="brand-text">
        <strong><?= e($siteName) ?></strong>
        <em>Armed Security &amp; Escort</em>
      </span>
    </a>
    <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
    <nav class="nav-links" id="navLinks">
      <a href="<?= BASE_URL ?>/index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">Home</a>
      <a href="<?= BASE_URL ?>/services.php" class="<?= $currentPage === 'services.php' ? 'active' : '' ?>">Services</a>
      <a href="<?= BASE_URL ?>/quote.php" class="<?= $currentPage === 'quote.php' ? 'active' : '' ?>">Get a Quote</a>
      <a href="<?= BASE_URL ?>/gallery.php" class="<?= $currentPage === 'gallery.php' ? 'active' : '' ?>">Gallery</a>
      <a href="<?= BASE_URL ?>/contact.php" class="nav-cta <?= $currentPage === 'contact.php' ? 'active' : '' ?>">Request Escort</a>
    </nav>
  </div>
</header>
