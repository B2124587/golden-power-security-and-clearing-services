<?php
require_once __DIR__ . '/../../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    configure_secure_session();
    session_name(ADMIN_SESSION_NAME);
    session_start();
}
apply_security_headers();

/** Call at the top of every protected admin page. */
function require_admin_login(): void
{
    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
}

function admin_has_permission(string $permission): bool
{
    if (empty($_SESSION['admin_id'])) return false;
    if (($_SESSION['admin_role'] ?? '') === 'super_admin') return true;
    try {
        $stmt = get_db()->prepare(
            'SELECT 1 FROM role_permissions rp
             INNER JOIN permissions p ON p.id = rp.permission_id
             WHERE rp.role = :role AND p.permission_key = :permission LIMIT 1'
        );
        $stmt->execute(['role' => $_SESSION['admin_role'] ?? 'administrator', 'permission' => $permission]);
        return (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function require_admin_permission(string $permission): void
{
    require_admin_login();
    if (!admin_has_permission($permission)) {
        http_response_code(403);
        exit('Access denied.');
    }
}

