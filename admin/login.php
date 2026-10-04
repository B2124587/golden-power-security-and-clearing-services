<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Session expired — please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (is_login_locked_out($username)) {
            $error = 'Too many failed attempts. Please wait a few minutes and try again.';
        } else {
            $stmt = get_db()->prepare('SELECT id, username, password_hash, role FROM admins WHERE username = :u AND is_active = 1 LIMIT 1');
            $stmt->execute(['u' => $username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password_hash'])) {
                clear_failed_logins($username);
                session_regenerate_id(true);
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_role'] = $admin['role'] ?? 'administrator';
                audit_log('admin.login', 'admins', (string) $admin['id']);
                header('Location: index.php');
                exit;
            }
            record_failed_login($username);
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff Login — Golden Power Security</title>
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Work+Sans:wght@400;500;600&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-login-body">
  <div class="login-card">
    <span class="brand-mark" aria-hidden="true">GPS</span>
    <h1>Staff Login</h1>
    <p class="muted">Golden Power Security — Admin Panel</p>
    <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="form-grid">
      <?= csrf_field() ?>
      <div>
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required autofocus>
      </div>
      <div>
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%">Sign In</button>
    </form>
    <p class="form-note" style="margin-top:18px;text-align:center;"><a href="../index.php">← Back to public site</a></p>
  </div>
</body>
</html>
