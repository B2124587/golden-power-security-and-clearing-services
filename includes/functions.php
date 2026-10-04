<?php
require_once __DIR__ . '/db.php';

/**
 * Apply baseline security response headers. Safe to call multiple times
 * (e.g. once from the public header and once from the admin header).
 */
function apply_security_headers(): void
{
    static $applied = false;
    if ($applied || headers_sent()) return;
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header("Content-Security-Policy: frame-ancestors 'none'");
    header('Referrer-Policy: strict-origin-when-cross-origin');
    $applied = true;
}

/**
 * Configure session cookie flags before a session is started. Call this
 * immediately before session_start() / session_name() + session_start().
 */
function configure_secure_session(): void
{
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $secure,
    ]);
}

/** Escape a string for safe HTML output. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** Fetch one site_settings value by key, with an optional fallback. */
function get_setting(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        $stmt = get_db()->query('SELECT setting_key, setting_value FROM site_settings');
        foreach ($stmt->fetchAll() as $row) {
            $cache[$row['setting_key']] = $row['setting_value'];
        }
    }
    return isset($cache[$key]) && $cache[$key] !== '' ? $cache[$key] : $default;
}

/** Update (or insert) a site_settings value. */
function set_setting(string $key, string $value): void
{
    $stmt = get_db()->prepare(
        'INSERT INTO site_settings (setting_key, setting_value) VALUES (:k, :v)
         ON DUPLICATE KEY UPDATE setting_value = :v2'
    );
    $stmt->execute(['k' => $key, 'v' => $value, 'v2' => $value]);
}

/** All active services, ordered for display. */
function get_active_services(): array
{
    return get_db()
        ->query('SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order ASC, id ASC')
        ->fetchAll();
}

/** All services (active + inactive) for the admin table. */
function get_all_services(): array
{
    return get_db()
        ->query('SELECT * FROM services ORDER BY sort_order ASC, id ASC')
        ->fetchAll();
}

/** Countries serviced for cross-border escorting. */
function get_escort_countries(): array
{
    $countries = get_db()
        ->query('SELECT * FROM escort_countries WHERE is_active = 1 ORDER BY sort_order ASC')
        ->fetchAll();

    $unique = [];
    $seen = [];
    foreach ($countries as $country) {
        $key = strtolower(trim($country['country_name']));
        if ($key === '' || isset($seen[$key])) continue;
        $seen[$key] = true;
        $unique[] = $country;
    }
    return $unique;
}

/** The currently active daily quote (falls back to the tagline). */
function get_active_quote(): string
{
    $row = get_db()->query('SELECT quote_text FROM quotes WHERE is_active = 1 ORDER BY id DESC LIMIT 1')->fetch();
    return $row['quote_text'] ?? get_setting('tagline', 'Proudly protecting Southern Africa\'s proudest possessions');
}

/** All quotes, newest first, for the admin manager. */
function get_all_quotes(): array
{
    return get_db()->query('SELECT * FROM quotes ORDER BY id DESC')->fetchAll();
}

/** All regional offices, ordered for display. */
function get_contact_info(): array
{
    return get_db()->query('SELECT * FROM contact_info ORDER BY sort_order ASC, id ASC')->fetchAll();
}

/**
 * Handle an uploaded image (logo / hero / service icon).
 * Returns the relative path to store in the DB, or null on failure / no file.
 */
function handle_image_upload(string $fieldName, string $subDir): ?string
{
    if (empty($_FILES[$fieldName]['name']) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
    $ext = strtolower(pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION));

    if (!array_key_exists($ext, $allowed)) {
        return null;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $_FILES[$fieldName]['tmp_name']);
    finfo_close($finfo);
    if ($mime !== $allowed[$ext]) {
        return null;
    }

    if ($_FILES[$fieldName]['size'] > 5 * 1024 * 1024) { // 5MB cap
        return null;
    }

    $targetDir = UPLOAD_DIR . '/' . $subDir;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $filename = $subDir . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $destination = $targetDir . '/' . $filename;

    if (!move_uploaded_file($_FILES[$fieldName]['tmp_name'], $destination)) {
        return null;
    }

    return 'uploads/' . $subDir . '/' . $filename;
}

/** Simple CSRF token helpers shared by every admin form. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): bool
{
    return isset($_POST['csrf_token'], $_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

/** Record sensitive administrator actions without exposing audit data publicly. */
function audit_log(string $action, ?string $entityType = null, ?string $entityId = null, ?array $previous = null, ?array $new = null): void
{
    try {
        $stmt = get_db()->prepare(
            'INSERT INTO audit_logs
                (actor_admin_id, action, entity_type, entity_id, previous_value, new_value, ip_address, user_agent)
             VALUES (:actor, :action, :entity_type, :entity_id, :previous_value, :new_value, :ip, :user_agent)'
        );
        $stmt->execute([
            'actor' => $_SESSION['admin_id'] ?? null,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'previous_value' => $previous === null ? null : json_encode($previous, JSON_THROW_ON_ERROR),
            'new_value' => $new === null ? null : json_encode($new, JSON_THROW_ON_ERROR),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
        ]);
    } catch (Throwable $e) {
        // Audit failures must not break the business action being recorded.
    }
}

/* ── Admin login rate limiting ───────────────────────────────────────── */
const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_LOCKOUT_SECONDS = 300; // 5 minutes

/** Build a stable identifier for the current client + username being tried. */
function login_attempt_identifier(string $username): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    return $ip . '|' . strtolower($username);
}

/** Ensure the login_attempts table exists (migration-safe for older installs). */
function ensure_login_attempts_table(): void
{
    static $done = false;
    if ($done) return;
    try {
        get_db()->exec(
            'CREATE TABLE IF NOT EXISTS login_attempts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                identifier VARCHAR(150) NOT NULL,
                attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX (identifier, attempted_at)
            ) ENGINE=InnoDB'
        );
    } catch (\Throwable $e) { /* already exists */ }
    $done = true;
}

/** True if this identifier has too many recent failed attempts. */
function is_login_locked_out(string $username): bool
{
    ensure_login_attempts_table();
    $id = login_attempt_identifier($username);
    $lockoutSeconds = (int) LOGIN_LOCKOUT_SECONDS;
    $stmt = get_db()->prepare(
        'SELECT COUNT(*) AS c FROM login_attempts
         WHERE identifier = :id AND attempted_at > DATE_SUB(NOW(), INTERVAL ' . $lockoutSeconds . ' SECOND)'
    );
    $stmt->bindValue(':id', $id);
    $stmt->execute();
    return (int) $stmt->fetch()['c'] >= LOGIN_MAX_ATTEMPTS;
}

/** Record a failed login attempt for this identifier. */
function record_failed_login(string $username): void
{
    ensure_login_attempts_table();
    $stmt = get_db()->prepare('INSERT INTO login_attempts (identifier) VALUES (:id)');
    $stmt->execute(['id' => login_attempt_identifier($username)]);
}

/** Clear failed attempts for this identifier after a successful login. */
function clear_failed_logins(string $username): void
{
    ensure_login_attempts_table();
    $stmt = get_db()->prepare('DELETE FROM login_attempts WHERE identifier = :id');
    $stmt->execute(['id' => login_attempt_identifier($username)]);
}

/** Minimal inline-SVG line icons, keyed by name, used across the public site. */
function svg_icon(string $name): string
{
    $icons = [
        'building' => '<path d="M4 21V6l8-3 8 3v15" /><path d="M9 21v-6h6v6" /><path d="M9 10h.01M15 10h.01M9 14h.01M15 14h.01" />',
        'truck'    => '<path d="M1 14h13V6H1v8Z"/><path d="M14 9h4l3 3v5h-7V9Z"/><circle cx="5.5" cy="18" r="1.8"/><circle cx="17.5" cy="18" r="1.8"/>',
        'leaf'     => '<path d="M4 20c8 0 14-6 15-15C10 6 4 12 4 20Z"/><path d="M4 20c3-6 7-9 11-11"/>',
        'shield'   => '<path d="M12 2 4 5v6c0 5 3.6 8.7 8 11 4.4-2.3 8-6 8-11V5l-8-3Z"/><path d="M9 12l2 2 4-4"/>',
        'pin'      => '<path d="M12 22s7-6.6 7-12A7 7 0 0 0 5 10c0 5.4 7 12 7 12Z"/><circle cx="12" cy="10" r="2.4"/>',
        'phone'    => '<path d="M6 3h3l1.5 5-2.3 1.6a12 12 0 0 0 6.2 6.2L16 13.5l5 1.5v3a2 2 0 0 1-2.2 2A17 17 0 0 1 4 5.2 2 2 0 0 1 6 3Z"/>',
        'mail'     => '<rect x="3" y="5" width="18" height="14" rx="1.5"/><path d="m3.5 6 8.5 7 8.5-7"/>',
        'route'    => '<circle cx="5" cy="6" r="2.2"/><circle cx="19" cy="18" r="2.2"/><path d="M5 8.2V13a4 4 0 0 0 4 4h6"/>',
    ];
    $path = $icons[$name] ?? $icons['shield'];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
}

/** Active videos for the public gallery. */
function get_active_videos(): array
{
    return get_db()->query('SELECT * FROM videos WHERE is_active = 1 ORDER BY sort_order ASC, id DESC')->fetchAll();
}

/** All videos for the admin manager. */
function get_all_videos(): array
{
    return get_db()->query('SELECT * FROM videos ORDER BY sort_order ASC, id DESC')->fetchAll();
}

/** Active gallery images for the public gallery. */
function get_active_gallery_images(): array
{
    try {
        return get_db()->query('SELECT * FROM gallery_images WHERE is_active = 1 ORDER BY sort_order ASC, id DESC')->fetchAll();
    } catch (\Throwable $e) {
        return []; // table may not exist yet on older installs
    }
}

/** Latest published daily achievements, including their journey photos. */
function get_active_achievements(int $limit = 3): array
{
    $limit = max(1, min($limit, 20));
    $stmt = get_db()->query(
        'SELECT * FROM achievements
         WHERE is_active = 1
         ORDER BY achievement_date DESC, id DESC
         LIMIT ' . $limit
    );
    $achievements = $stmt->fetchAll();
    if (!$achievements) {
        return [];
    }

    $photoStmt = get_db()->prepare(
        'SELECT * FROM achievement_photos
         WHERE achievement_id = :id
         ORDER BY sort_order ASC, id ASC'
    );
    foreach ($achievements as &$achievement) {
        $photoStmt->execute(['id' => $achievement['id']]);
        $achievement['photos'] = $photoStmt->fetchAll();
    }
    unset($achievement);
    return $achievements;
}

/** All gallery images for the admin manager. */
function get_all_gallery_images(): array
{
    try {
        return get_db()->query('SELECT * FROM gallery_images ORDER BY sort_order ASC, id DESC')->fetchAll();
    } catch (\Throwable $e) {
        return [];
    }
}

/**
 * True if the URL is a recognized YouTube/Vimeo link or a direct
 * mp4/webm/ogg file (including our own uploaded ones). Anything else
 * (javascript:, data:, arbitrary iframes) is rejected before storage.
 */
function is_allowed_video_url(string $url): bool
{
    if (str_starts_with($url, 'uploads/')) {
        return (bool) preg_match('~\.(mp4|webm|ogg)$~i', $url);
    }
    if (!preg_match('~^https://~i', $url)) {
        return false;
    }
    return (bool) preg_match('~^https://(www\.)?(youtube\.com/watch\?v=|youtu\.be/|player\.vimeo\.com/video/|vimeo\.com/)~i', $url)
        || is_direct_video($url);
}

/** Turn a plain YouTube/Vimeo watch URL into an embeddable iframe src. */
function to_embed_url(string $url): string
{
    if (preg_match('~youtu\.be/([\w-]+)~', $url, $m) || preg_match('~youtube\.com/watch\?v=([\w-]+)~', $url, $m)) {
        return 'https://www.youtube.com/embed/' . $m[1];
    }
    if (preg_match('~vimeo\.com/(\d+)~', $url, $m)) {
        return 'https://player.vimeo.com/video/' . $m[1];
    }
    return $url; // already an embed URL, or a direct uploaded file
}

/** True if the URL/path looks like a direct video file rather than a host page. */
function is_direct_video(string $url): bool
{
    return (bool) preg_match('~\.(mp4|webm|ogg)$~i', $url);
}

/** Active testimonials for the public homepage. */
function get_active_testimonials(): array
{
    try {
        return get_db()->query('SELECT * FROM testimonials WHERE is_active = 1 ORDER BY sort_order ASC, id DESC')->fetchAll();
    } catch (\Throwable $e) {
        return []; // table may not exist yet on older installs
    }
}

/** All testimonials for the admin manager. */
function get_all_testimonials(): array
{
    try {
        return get_db()->query('SELECT * FROM testimonials ORDER BY sort_order ASC, id DESC')->fetchAll();
    } catch (\Throwable $e) {
        return [];
    }
}

/** Redirect helper. */
function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}
