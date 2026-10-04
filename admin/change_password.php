<?php
require_once __DIR__ . '/includes/header.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Your session expired — please try again.';
    } else {
        $current  = $_POST['current_password'] ?? '';
        $new      = $_POST['new_password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        $stmt = get_db()->prepare('SELECT password_hash FROM admins WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $_SESSION['admin_id']]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($current, $admin['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($new) < 10) {
            $errors[] = 'New password must be at least 10 characters long.';
        } elseif ($new !== $confirm) {
            $errors[] = 'New password and confirmation do not match.';
        } elseif (password_verify($new, $admin['password_hash'])) {
            $errors[] = 'New password must be different from the current password.';
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $upd = get_db()->prepare('UPDATE admins SET password_hash = :h WHERE id = :id');
            $upd->execute(['h' => $hash, 'id' => $_SESSION['admin_id']]);
            $success = true;
        }
    }
}
?>

<div class="admin-topbar"><h1>Change Password</h1></div>

<?php if ($success): ?>
  <div class="alert">Password updated. Please log in again with your new password.</div>
  <script>
    setTimeout(function () { window.location.href = 'logout.php'; }, 2000);
  </script>
<?php else: ?>
  <?php foreach ($errors as $err): ?>
    <div class="alert error"><?= e($err) ?></div>
  <?php endforeach; ?>

  <div class="admin-panel">
    <form method="post" class="form-grid" style="max-width:420px">
      <?= csrf_field() ?>
      <div>
        <label for="current_password">Current Password</label>
        <input type="password" id="current_password" name="current_password" required autofocus>
      </div>
      <div>
        <label for="new_password">New Password</label>
        <input type="password" id="new_password" name="new_password" required minlength="10">
        <p class="form-note">At least 10 characters.</p>
      </div>
      <div>
        <label for="confirm_password">Confirm New Password</label>
        <input type="password" id="confirm_password" name="confirm_password" required minlength="10">
      </div>
      <div><button type="submit" class="btn btn-dark">Update Password</button></div>
    </form>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
