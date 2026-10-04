<?php
require_once __DIR__ . '/auth.php';
$pagePermissions = [
  'operations.php' => 'operations.manage',
  'inquiries.php' => 'customers.manage',
  'services.php' => 'content.manage',
  'quotes.php' => 'content.manage',
  'testimonials.php' => 'content.manage',
  'achievements.php' => 'content.manage',
  'gallery.php' => 'content.manage',
  'contact.php' => 'content.manage',
  'pricing.php' => 'finance.manage',
  'settings.php' => 'settings.manage',
];
$requestedPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
if (isset($pagePermissions[$requestedPage])) {
  require_admin_permission($pagePermissions[$requestedPage]);
} else {
  require_admin_login();
}
$currentPage = basename($_SERVER['SCRIPT_NAME']);
$siteName = get_setting('site_name', 'Golden Power Security');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — <?= e($siteName) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Work+Sans:wght@400;500;600&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css?v=<?= filemtime(__DIR__ . '/../../assets/css/style.css') ?>">
<link rel="stylesheet" href="../assets/css/admin.css?v=<?= filemtime(__DIR__ . '/../../assets/css/admin.css') ?>">
</head>
<body class="admin-body">
<div class="admin-shell">
  <aside class="admin-sidebar">
    <div class="admin-brand">
      <span class="brand-mark small" aria-hidden="true">GPS</span>
      <div><strong>Admin Panel</strong><br><span class="muted" style="font-size:.78rem">Golden Power Security</span></div>
    </div>
    <nav class="admin-nav">
      <a href="index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">Overview</a>
      <a href="inquiries.php" class="<?= $currentPage === 'inquiries.php' ? 'active' : '' ?>">Inquiries</a>
      <a href="services.php" class="<?= $currentPage === 'services.php' ? 'active' : '' ?>">Services</a>
      <a href="operations.php" class="<?= $currentPage === 'operations.php' ? 'active' : '' ?>">Operations</a>
      <a href="pricing.php" class="<?= $currentPage === 'pricing.php' ? 'active' : '' ?>">Quote Pricing</a>
      <a href="quotes.php" class="<?= $currentPage === 'quotes.php' ? 'active' : '' ?>">Daily Quotes</a>
      <a href="testimonials.php" class="<?= $currentPage === 'testimonials.php' ? 'active' : '' ?>">Testimonials</a>
      <a href="achievements.php" class="<?= $currentPage === 'achievements.php' ? 'active' : '' ?>">Daily Achievements</a>
      <a href="gallery.php" class="<?= $currentPage === 'gallery.php' ? 'active' : '' ?>">Video Gallery</a>
      <a href="contact.php" class="<?= $currentPage === 'contact.php' ? 'active' : '' ?>">Contact &amp; Offices</a>
      <a href="settings.php" class="<?= $currentPage === 'settings.php' ? 'active' : '' ?>">Site Settings</a>
      <a href="change_password.php" class="<?= $currentPage === 'change_password.php' ? 'active' : '' ?>">Change Password</a>
    </nav>
    <div class="admin-sidebar-foot">
      <p class="muted" style="font-size:.82rem">Signed in as <strong><?= e($_SESSION['admin_username'] ?? '') ?></strong></p>
      <a href="logout.php" class="btn btn-outline" style="width:100%; color:var(--bone)">Log Out</a>
      <a href="../index.php" class="admin-view-site">View public site →</a>
    </div>
  </aside>
  <main class="admin-main">
