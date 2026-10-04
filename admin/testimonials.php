<?php
require_once __DIR__ . '/includes/header.php';
$db = get_db();
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name  = trim($_POST['client_name'] ?? '');
        $title = trim($_POST['client_title'] ?? '');
        $quote = trim($_POST['quote'] ?? '');
        if ($name !== '' && $quote !== '') {
            $stmt = $db->prepare(
                'INSERT INTO testimonials (client_name, client_title, quote, is_active) VALUES (:n, :t, :q, 1)'
            );
            $stmt->execute(['n' => $name, 't' => $title, 'q' => $quote]);
            $notice = 'Testimonial added.';
        } else {
            $notice = 'Please provide at least a client name and a quote.';
        }
    } elseif ($action === 'toggle') {
        $id = (int) $_POST['id'];
        $stmt = $db->prepare('UPDATE testimonials SET is_active = 1 - is_active WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $notice = 'Testimonial updated.';
    } elseif ($action === 'delete') {
        $stmt = $db->prepare('DELETE FROM testimonials WHERE id = :id');
        $stmt->execute(['id' => (int) $_POST['id']]);
        $notice = 'Testimonial deleted.';
    }
}

$testimonials = get_all_testimonials();
?>

<div class="admin-topbar"><h1>Testimonials</h1></div>
<?php if ($notice): ?><div class="alert"><?= e($notice) ?></div><?php endif; ?>

<div class="admin-panel">
  <h2>Add a New Testimonial</h2>
  <form method="post" class="form-grid">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="create">
    <div class="form-row">
      <div>
        <label for="client_name">Client Name</label>
        <input type="text" id="client_name" name="client_name" required placeholder="e.g. Chanda M.">
      </div>
      <div>
        <label for="client_title">Title / Company (optional)</label>
        <input type="text" id="client_title" name="client_title" placeholder="e.g. Operations Manager, Acme Logistics">
      </div>
    </div>
    <div>
      <label for="quote">Testimonial</label>
      <textarea id="quote" name="quote" required placeholder="What the client said about working with us…"></textarea>
    </div>
    <div><button type="submit" class="btn btn-dark">Add Testimonial</button></div>
  </form>
</div>

<div class="admin-panel">
  <h2>All Testimonials</h2>
  <p class="muted" style="margin-top:-8px">Active testimonials appear in a rotating carousel on the homepage.</p>
  <div class="table-wrap">
    <table class="admin-table">
      <thead><tr><th>Client</th><th>Quote</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($testimonials as $t): ?>
          <tr>
            <td>
              <strong><?= e($t['client_name']) ?></strong>
              <?php if ($t['client_title']): ?><br><span class="muted" style="font-size:.82rem"><?= e($t['client_title']) ?></span><?php endif; ?>
            </td>
            <td><?= e($t['quote']) ?></td>
            <td><span class="badge <?= $t['is_active'] ? 'badge-live' : 'badge-off' ?>"><?= $t['is_active'] ? 'Live' : 'Hidden' ?></span></td>
            <td class="row-actions">
              <form method="post" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                <button type="submit" class="icon-btn"><?= $t['is_active'] ? 'Hide' : 'Show' ?></button>
              </form>
              <form method="post" style="display:inline" data-confirm="Delete this testimonial?">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                <button type="submit" class="icon-btn danger">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$testimonials): ?><tr><td colspan="4">No testimonials yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
