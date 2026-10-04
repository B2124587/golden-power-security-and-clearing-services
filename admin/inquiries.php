<?php
require_once __DIR__ . '/includes/header.php';
$db = get_db();
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_status') {
        $status = $_POST['status'] ?? 'Pending';
        if (in_array($status, ['Pending', 'Contacted', 'Closed'], true)) {
            $stmt = $db->prepare('UPDATE inquiries SET status = :s WHERE id = :id');
            $stmt->execute(['s' => $status, 'id' => (int) $_POST['id']]);
            $notice = 'Status updated.';
        }
    } elseif ($action === 'delete') {
        $stmt = $db->prepare('DELETE FROM inquiries WHERE id = :id');
        $stmt->execute(['id' => (int) $_POST['id']]);
        $notice = 'Inquiry deleted.';
    }
}

$search = trim($_GET['q'] ?? '');
$sql = 'SELECT * FROM inquiries';
$params = [];
if ($search !== '') {
    $sql .= ' WHERE name LIKE :q OR email LIKE :q OR service_requested LIKE :q';
    $params['q'] = '%' . $search . '%';
}
$sql .= ' ORDER BY created_at DESC';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$inquiries = $stmt->fetchAll();
?>

<div class="admin-topbar">
  <h1>Inquiries</h1>
  <div class="form-inline">
    <form method="get" class="form-inline">
      <input type="text" name="q" placeholder="Search name, email, service…" value="<?= e($search) ?>" style="width:240px">
      <button type="submit" class="icon-btn">Search</button>
    </form>
    <a href="inquiries_export.php<?= $search !== '' ? '?q=' . urlencode($search) : '' ?>" class="btn btn-dark">Export to CSV</a>
  </div>
</div>
<?php if ($notice): ?><div class="alert"><?= e($notice) ?></div><?php endif; ?>

<div class="admin-panel">
  <div class="table-wrap">
    <table class="admin-table">
      <thead><tr><th>Date</th><th>Client</th><th>Contact</th><th>Service</th><th>Message</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($inquiries as $r): ?>
          <tr>
            <td><?= e(date('d M Y H:i', strtotime($r['created_at']))) ?></td>
            <td><?= e($r['name']) ?></td>
            <td><?= e($r['email']) ?><?= $r['phone'] ? '<br>' . e($r['phone']) : '' ?></td>
            <td><?= e($r['service_requested'] ?: '—') ?></td>
            <td style="max-width:280px"><?= e($r['message']) ?></td>
            <td>
              <form method="post" class="form-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                <select name="status" onchange="this.form.submit()">
                  <?php foreach (['Pending', 'Contacted', 'Closed'] as $st): ?>
                    <option <?= $r['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                  <?php endforeach; ?>
                </select>
              </form>
            </td>
            <td>
              <form method="post" data-confirm="Delete this inquiry?">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                <button type="submit" class="icon-btn danger">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$inquiries): ?><tr><td colspan="7">No inquiries found.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
