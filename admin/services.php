<?php
require_once __DIR__ . '/includes/header.php';
$db = get_db();
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $title = trim($_POST['title'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $icon = $_POST['icon'] ?? 'shield';
        $sort = (int) ($_POST['sort_order'] ?? 0);

        if ($title !== '' && $description !== '') {
            if ($action === 'create') {
                $stmt = $db->prepare('INSERT INTO services (code, title, description, icon, sort_order) VALUES (:c,:t,:d,:i,:s)');
                $stmt->execute(['c' => $code ?: 'SVC-' . (string) (rand(10, 99)), 't' => $title, 'd' => $description, 'i' => $icon, 's' => $sort]);
                $notice = 'Service added.';
            } else {
                $id = (int) $_POST['id'];
                $stmt = $db->prepare('UPDATE services SET code=:c, title=:t, description=:d, icon=:i, sort_order=:s WHERE id=:id');
                $stmt->execute(['c' => $code, 't' => $title, 'd' => $description, 'i' => $icon, 's' => $sort, 'id' => $id]);
                $notice = 'Service updated.';
            }
        }
    } elseif ($action === 'delete') {
        $stmt = $db->prepare('DELETE FROM services WHERE id = :id');
        $stmt->execute(['id' => (int) $_POST['id']]);
        $notice = 'Service deleted.';
    } elseif ($action === 'toggle') {
        $stmt = $db->prepare('UPDATE services SET is_active = 1 - is_active WHERE id = :id');
        $stmt->execute(['id' => (int) $_POST['id']]);
        $notice = 'Visibility updated.';
    }
}

$services = get_all_services();
$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare('SELECT * FROM services WHERE id = :id');
    $stmt->execute(['id' => (int) $_GET['edit']]);
    $editing = $stmt->fetch();
}
$iconOptions = ['building', 'truck', 'leaf', 'shield', 'pin', 'phone', 'mail', 'route'];
?>

<div class="admin-topbar"><h1>Services</h1></div>
<?php if ($notice): ?><div class="alert"><?= e($notice) ?></div><?php endif; ?>

<div class="admin-panel">
  <h2><?= $editing ? 'Edit Service' : 'Add a New Service' ?></h2>
  <form method="post" class="form-grid">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
    <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int) $editing['id'] ?>"><?php endif; ?>
    <div class="form-row">
      <div>
        <label for="title">Title</label>
        <input type="text" id="title" name="title" required value="<?= e($editing['title'] ?? '') ?>">
      </div>
      <div>
        <label for="code">Manifest Tag</label>
        <input type="text" id="code" name="code" placeholder="e.g. SVC-05" value="<?= e($editing['code'] ?? '') ?>">
      </div>
    </div>
    <div>
      <label for="description">Description</label>
      <textarea id="description" name="description" required><?= e($editing['description'] ?? '') ?></textarea>
    </div>
    <div class="form-row">
      <div>
        <label for="icon">Icon</label>
        <select id="icon" name="icon">
          <?php foreach ($iconOptions as $opt): ?>
            <option value="<?= $opt ?>" <?= (($editing['icon'] ?? 'shield') === $opt) ? 'selected' : '' ?>><?= ucfirst($opt) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label for="sort_order">Display Order</label>
        <input type="number" id="sort_order" name="sort_order" value="<?= e((string) ($editing['sort_order'] ?? 0)) ?>">
      </div>
    </div>
    <div class="form-inline">
      <button type="submit" class="btn btn-dark"><?= $editing ? 'Save Changes' : 'Add Service' ?></button>
      <?php if ($editing): ?><a href="services.php" class="icon-btn">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>

<div class="admin-panel">
  <h2>All Services</h2>
  <div class="table-wrap">
    <table class="admin-table">
      <thead><tr><th>Tag</th><th>Title</th><th>Status</th><th>Order</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($services as $s): ?>
          <tr>
            <td><?= e($s['code']) ?></td>
            <td><?= e($s['title']) ?></td>
            <td><span class="badge <?= $s['is_active'] ? 'badge-live' : 'badge-off' ?>"><?= $s['is_active'] ? 'Live' : 'Hidden' ?></span></td>
            <td><?= (int) $s['sort_order'] ?></td>
            <td class="row-actions">
              <a class="icon-btn" href="services.php?edit=<?= (int) $s['id'] ?>">Edit</a>
              <form method="post" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                <button type="submit" class="icon-btn"><?= $s['is_active'] ? 'Hide' : 'Publish' ?></button>
              </form>
              <form method="post" style="display:inline" data-confirm="Delete this service permanently?">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                <button type="submit" class="icon-btn danger">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$services): ?><tr><td colspan="5">No services yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
