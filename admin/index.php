<?php
require_once __DIR__ . '/includes/header.php';

$db = get_db();
$totalInquiries = (int) $db->query('SELECT COUNT(*) FROM inquiries')->fetchColumn();
$pendingInquiries = (int) $db->query("SELECT COUNT(*) FROM inquiries WHERE status = 'Pending'")->fetchColumn();
$totalServices = (int) $db->query('SELECT COUNT(*) FROM services WHERE is_active = 1')->fetchColumn();
$totalVideos = (int) $db->query('SELECT COUNT(*) FROM videos WHERE is_active = 1')->fetchColumn();
$recent = $db->query('SELECT * FROM inquiries ORDER BY created_at DESC LIMIT 5')->fetchAll();
?>

<div class="admin-topbar">
  <h1>Overview</h1>
</div>

<div class="stat-grid">
  <div class="stat-card"><div class="num"><?= $totalInquiries ?></div><div class="lbl">Total Inquiries</div></div>
  <div class="stat-card"><div class="num"><?= $pendingInquiries ?></div><div class="lbl">Pending Follow-Up</div></div>
  <div class="stat-card"><div class="num"><?= $totalServices ?></div><div class="lbl">Live Services</div></div>
  <div class="stat-card"><div class="num"><?= $totalVideos ?></div><div class="lbl">Gallery Videos</div></div>
</div>

<div class="admin-panel">
  <h2>Recent Inquiries</h2>
  <div class="table-wrap">
    <table class="admin-table">
      <thead><tr><th>Date</th><th>Name</th><th>Contact</th><th>Service</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($recent as $r): ?>
          <tr>
            <td><?= e(date('d M Y', strtotime($r['created_at']))) ?></td>
            <td><?= e($r['name']) ?></td>
            <td><?= e($r['email']) ?><?= $r['phone'] ? '<br>' . e($r['phone']) : '' ?></td>
            <td><?= e($r['service_requested'] ?: '—') ?></td>
            <td><span class="badge badge-<?= strtolower($r['status']) ?>"><?= e($r['status']) ?></span></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$recent): ?><tr><td colspan="5">No inquiries yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <p style="margin-top:14px"><a href="inquiries.php" class="icon-btn">View all inquiries →</a></p>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
