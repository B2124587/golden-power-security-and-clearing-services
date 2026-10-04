<?php
require_once __DIR__ . '/includes/header.php';

$db = get_db();
$errors = [];
$success = '';

try {
    $pricing = $db->query('SELECT * FROM service_pricing ORDER BY category, label')->fetchAll();
} catch (Throwable $e) {
    $pricing = [];
    $errors[] = 'Pricing tables are not installed yet. Import sql/migrations/001_quote_foundation.sql first.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $base = filter_input(INPUT_POST, 'base_price', FILTER_VALIDATE_FLOAT);
    $unit = filter_input(INPUT_POST, 'unit_price', FILTER_VALIDATE_FLOAT);
    $active = isset($_POST['is_active']) ? 1 : 0;
    if (!$id || $base === false || $unit === false || $base < 0 || $unit < 0) {
        $errors[] = 'Enter valid non-negative prices.';
    } else {
        $stmt = $db->prepare('UPDATE service_pricing SET base_price = :base, unit_price = :unit, is_active = :active WHERE id = :id');
        $stmt->execute(['base' => $base, 'unit' => $unit, 'active' => $active, 'id' => $id]);
        $success = 'Pricing updated.';
        foreach ($pricing as &$row) {
            if ((int) $row['id'] === $id) {
                $row['base_price'] = $base;
                $row['unit_price'] = $unit;
                $row['is_active'] = $active;
            }
        }
        unset($row);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors[] = 'Your session expired. Please try again.';
}
?>
<div class="admin-topbar"><h1>Quote Pricing</h1></div>
<?php if ($success): ?><div class="alert"><?= e($success) ?></div><?php endif; ?>
<?php foreach ($errors as $error): ?><div class="alert error"><?= e($error) ?></div><?php endforeach; ?>
<div class="admin-panel">
  <p class="muted">Prices are estimates in ZMW. The final amount remains subject to site assessment and confirmation.</p>
  <div class="table-wrap">
    <?php foreach ($pricing as $row): ?><form id="pricing-<?= (int) $row['id'] ?>" method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $row['id'] ?>"></form><?php endforeach; ?>
    <table class="admin-table">
      <thead><tr><th>Service</th><th>Category</th><th>Unit</th><th>Base price</th><th>Unit price</th><th>Live</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($pricing as $row): ?>
        <tr>
            <td><?= e($row['label']) ?><br><small><?= e($row['pricing_key']) ?></small></td>
            <td><?= e(ucfirst($row['category'])) ?></td>
            <td><?= e($row['unit']) ?></td>
            <td><input form="pricing-<?= (int) $row['id'] ?>" type="number" name="base_price" min="0" step="0.01" value="<?= e($row['base_price']) ?>" aria-label="Base price for <?= e($row['label']) ?>"></td>
            <td><input form="pricing-<?= (int) $row['id'] ?>" type="number" name="unit_price" min="0" step="0.01" value="<?= e($row['unit_price']) ?>" aria-label="Unit price for <?= e($row['label']) ?>"></td>
            <td><input form="pricing-<?= (int) $row['id'] ?>" type="checkbox" name="is_active" value="1" <?= $row['is_active'] ? 'checked' : '' ?> aria-label="Enable <?= e($row['label']) ?>"></td>
            <td><button form="pricing-<?= (int) $row['id'] ?>" class="btn btn-primary" type="submit">Save</button></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$pricing): ?><tr><td colspan="7">No pricing options found.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
