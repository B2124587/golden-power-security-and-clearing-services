<?php
require_once __DIR__ . '/includes/header.php';
$db = get_db();
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $data = [
            'r' => trim($_POST['region_label'] ?? ''),
            'a' => trim($_POST['address'] ?? ''),
            'p1' => trim($_POST['phone1'] ?? ''),
            'p2' => trim($_POST['phone2'] ?? ''),
            'em' => trim($_POST['email'] ?? ''),
            's' => (int) ($_POST['sort_order'] ?? 0),
        ];
        if ($data['r'] !== '') {
            if ($action === 'create') {
                $stmt = $db->prepare('INSERT INTO contact_info (region_label, address, phone1, phone2, email, sort_order) VALUES (:r,:a,:p1,:p2,:em,:s)');
                $stmt->execute($data);
                $notice = 'Office added.';
            } else {
                $data['id'] = (int) $_POST['id'];
                $stmt = $db->prepare('UPDATE contact_info SET region_label=:r, address=:a, phone1=:p1, phone2=:p2, email=:em, sort_order=:s WHERE id=:id');
                $stmt->execute($data);
                $notice = 'Office details updated.';
            }
        }
    } elseif ($action === 'delete') {
        $stmt = $db->prepare('DELETE FROM contact_info WHERE id = :id');
        $stmt->execute(['id' => (int) $_POST['id']]);
        $notice = 'Office removed.';
    }
}

$offices = get_contact_info();
$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM contact_info WHERE id = :id');
    $stmt->execute(['id' => (int) $_GET['edit']]);
    $editing = $stmt->fetch();
}
?>

<div class="admin-topbar"><h1>Contact &amp; Offices</h1></div>
<?php if ($notice): ?><div class="alert"><?= e($notice) ?></div><?php endif; ?>

<div class="admin-panel">
  <h2><?= $editing ? 'Edit Office' : 'Add a Regional Office' ?></h2>
  <form method="post" class="form-grid">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
    <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int) $editing['id'] ?>"><?php endif; ?>
    <div class="form-row">
      <div>
        <label for="region_label">Office Label</label>
        <input type="text" id="region_label" name="region_label" required placeholder="e.g. Zambia Office" value="<?= e($editing['region_label'] ?? '') ?>">
      </div>
      <div>
        <label for="sort_order">Display Order</label>
        <input type="number" id="sort_order" name="sort_order" value="<?= e((string) ($editing['sort_order'] ?? 0)) ?>">
      </div>
    </div>
    <div>
      <label for="address">Address</label>
      <input type="text" id="address" name="address" value="<?= e($editing['address'] ?? '') ?>">
    </div>
    <div class="form-row">
      <div>
        <label for="phone1">Phone 1</label>
        <input type="text" id="phone1" name="phone1" value="<?= e($editing['phone1'] ?? '') ?>">
      </div>
      <div>
        <label for="phone2">Phone 2</label>
        <input type="text" id="phone2" name="phone2" value="<?= e($editing['phone2'] ?? '') ?>">
      </div>
    </div>
    <div>
      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="<?= e($editing['email'] ?? '') ?>">
    </div>
    <div class="form-inline">
      <button type="submit" class="btn btn-dark"><?= $editing ? 'Save Changes' : 'Add Office' ?></button>
      <?php if ($editing): ?><a href="contact.php" class="icon-btn">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>

<div class="admin-panel">
  <h2>All Offices</h2>
  <div class="table-wrap">
    <table class="admin-table">
      <thead><tr><th>Label</th><th>Address</th><th>Phones</th><th>Email</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($offices as $o): ?>
          <tr>
            <td><?= e($o['region_label']) ?></td>
            <td><?= e($o['address']) ?></td>
            <td><?= e($o['phone1']) ?><?= $o['phone2'] ? '<br>' . e($o['phone2']) : '' ?></td>
            <td><?= e($o['email']) ?></td>
            <td class="row-actions">
              <a class="icon-btn" href="contact.php?edit=<?= (int) $o['id'] ?>">Edit</a>
              <form method="post" style="display:inline" data-confirm="Remove this office?">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int) $o['id'] ?>">
                <button type="submit" class="icon-btn danger">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$offices): ?><tr><td colspan="5">No offices yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
