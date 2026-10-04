<?php
require_once __DIR__ . '/includes/header.php';
$db = get_db();
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $text = trim($_POST['quote_text'] ?? '');
        if ($text !== '') {
            $stmt = $db->prepare('INSERT INTO quotes (quote_text, is_active) VALUES (:t, 0)');
            $stmt->execute(['t' => $text]);
            $notice = 'Quote added.';
        }
    } elseif ($action === 'activate') {
        $id = (int) $_POST['id'];
        $db->exec('UPDATE quotes SET is_active = 0');
        $stmt = $db->prepare('UPDATE quotes SET is_active = 1 WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $notice = 'Quote is now live on the homepage.';
    } elseif ($action === 'delete') {
        $stmt = $db->prepare('DELETE FROM quotes WHERE id = :id');
        $stmt->execute(['id' => (int) $_POST['id']]);
        $notice = 'Quote deleted.';
    }
}

$quotes = get_all_quotes();
?>

<div class="admin-topbar"><h1>Daily Quotes</h1></div>
<?php if ($notice): ?><div class="alert"><?= e($notice) ?></div><?php endif; ?>

<div class="admin-panel">
  <h2>Add a New Quote</h2>
  <form method="post" class="form-grid">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="create">
    <div>
      <label for="quote_text">Quote</label>
      <textarea id="quote_text" name="quote_text" required placeholder="A motivational or safety-related message for site visitors…"></textarea>
    </div>
    <div><button type="submit" class="btn btn-dark">Add Quote</button></div>
  </form>
</div>

<div class="admin-panel">
  <h2>All Quotes</h2>
  <p class="muted" style="margin-top:-8px">Only one quote is shown on the public home page at a time. Click "Set Live" to feature it.</p>
  <div class="table-wrap">
    <table class="admin-table">
      <thead><tr><th>Quote</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($quotes as $q): ?>
          <tr>
            <td><?= e($q['quote_text']) ?></td>
            <td><span class="badge <?= $q['is_active'] ? 'badge-live' : 'badge-off' ?>"><?= $q['is_active'] ? 'Live' : 'Inactive' ?></span></td>
            <td class="row-actions">
              <?php if (!$q['is_active']): ?>
                <form method="post" style="display:inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="activate">
                  <input type="hidden" name="id" value="<?= (int) $q['id'] ?>">
                  <button type="submit" class="icon-btn">Set Live</button>
                </form>
              <?php endif; ?>
              <form method="post" style="display:inline" data-confirm="Delete this quote?">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int) $q['id'] ?>">
                <button type="submit" class="icon-btn danger">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$quotes): ?><tr><td colspan="3">No quotes yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
