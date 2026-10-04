<?php
require_once __DIR__ . '/includes/header.php';

$db = get_db();
$notice = '';

$db->exec(
    'CREATE TABLE IF NOT EXISTS achievements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(150) NOT NULL,
        achievement_date DATE NOT NULL,
        start_point VARCHAR(150) NOT NULL,
        destination VARCHAR(150) NOT NULL,
        summary TEXT,
        is_active TINYINT(1) DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_achievements_active_date (is_active, achievement_date)
    ) ENGINE=InnoDB'
);
$db->exec(
    'CREATE TABLE IF NOT EXISTS achievement_photos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        achievement_id INT NOT NULL,
        image_path VARCHAR(500) NOT NULL,
        caption VARCHAR(300),
        sort_order INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_achievement_photos_achievement
            FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE
    ) ENGINE=InnoDB'
);

$savePhoto = static function (array $file, string $caption, string $achievementId) use ($db): bool {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return false;
    }

    $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
    $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    if (!isset($allowed[$extension]) || ($file['size'] ?? 0) > 5 * 1024 * 1024) {
        return false;
    }

    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($fileInfo, $file['tmp_name']);
    finfo_close($fileInfo);
    if ($mime !== $allowed[$extension]) {
        return false;
    }

    $directory = UPLOAD_DIR . '/achievements';
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    $filename = 'achievement_' . bin2hex(random_bytes(6)) . '.' . $extension;
    if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $filename)) {
        return false;
    }

    $stmt = $db->prepare(
        'INSERT INTO achievement_photos (achievement_id, image_path, caption, sort_order)
         VALUES (:achievement_id, :image_path, :caption, :sort_order)'
    );
    $photoCount = (int) $db->query(
        'SELECT COUNT(*) FROM achievement_photos WHERE achievement_id = ' . (int) $achievementId
    )->fetchColumn();
    $stmt->execute([
        'achievement_id' => $achievementId,
        'image_path' => 'uploads/achievements/' . $filename,
        'caption' => $caption,
        'sort_order' => $photoCount,
    ]);
    return true;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_achievement') {
        $title = trim($_POST['title'] ?? '');
        $date = trim($_POST['achievement_date'] ?? date('Y-m-d'));
        $startPoint = trim($_POST['start_point'] ?? '');
        $destination = trim($_POST['destination'] ?? '');
        $summary = trim($_POST['summary'] ?? '');
        $validDate = DateTime::createFromFormat('Y-m-d', $date);

        if ($title !== '' && $startPoint !== '' && $destination !== '' && $validDate && $validDate->format('Y-m-d') === $date) {
            $stmt = $db->prepare(
                'INSERT INTO achievements (title, achievement_date, start_point, destination, summary)
                 VALUES (:title, :achievement_date, :start_point, :destination, :summary)'
            );
            $stmt->execute([
                'title' => $title,
                'achievement_date' => $date,
                'start_point' => $startPoint,
                'destination' => $destination,
                'summary' => $summary,
            ]);
            $achievementId = (string) $db->lastInsertId();
            $uploaded = 0;
            foreach ($_FILES['journey_photos']['name'] ?? [] as $index => $unused) {
                $file = [
                    'name' => $_FILES['journey_photos']['name'][$index],
                    'type' => $_FILES['journey_photos']['type'][$index],
                    'tmp_name' => $_FILES['journey_photos']['tmp_name'][$index],
                    'error' => $_FILES['journey_photos']['error'][$index],
                    'size' => $_FILES['journey_photos']['size'][$index],
                ];
                if ($savePhoto($file, trim($_POST['photo_caption'] ?? ''), $achievementId)) {
                    $uploaded++;
                }
            }
            $notice = 'Achievement published with ' . $uploaded . ' journey photo' . ($uploaded === 1 ? '' : 's') . '.';
        } else {
            $notice = 'Please complete the title, date, starting point and destination.';
        }
    } elseif ($action === 'toggle_achievement') {
        $stmt = $db->prepare('UPDATE achievements SET is_active = 1 - is_active WHERE id = :id');
        $stmt->execute(['id' => (int) $_POST['id']]);
        $notice = 'Achievement visibility updated.';
    } elseif ($action === 'delete_achievement') {
        $stmt = $db->prepare('SELECT image_path FROM achievement_photos WHERE achievement_id = :id');
        $stmt->execute(['id' => (int) $_POST['id']]);
        foreach ($stmt->fetchAll() as $photo) {
            $file = __DIR__ . '/../' . $photo['image_path'];
            if (is_file($file)) {
                @unlink($file);
            }
        }
        $stmt = $db->prepare('DELETE FROM achievements WHERE id = :id');
        $stmt->execute(['id' => (int) $_POST['id']]);
        $notice = 'Achievement deleted.';
    }
}

$achievements = $db->query('SELECT * FROM achievements ORDER BY achievement_date DESC, id DESC')->fetchAll();
$photoStmt = $db->prepare('SELECT * FROM achievement_photos WHERE achievement_id = :id ORDER BY sort_order ASC, id ASC');
foreach ($achievements as &$achievement) {
    $photoStmt->execute(['id' => $achievement['id']]);
    $achievement['photos'] = $photoStmt->fetchAll();
}
unset($achievement);
?>

<div class="admin-topbar"><h1>Daily Achievements</h1></div>
<?php if ($notice): ?><div class="alert"><?= e($notice) ?></div><?php endif; ?>

<div class="admin-panel">
  <h2>Publish a Convoy Achievement</h2>
  <p class="muted">Post the operation, route and journey photos together. Published posts appear on the public home page.</p>
  <form method="post" enctype="multipart/form-data" class="form-grid">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="create_achievement">
    <div class="form-row">
      <div>
        <label for="title">Achievement title</label>
        <input type="text" id="title" name="title" required placeholder="e.g. Lusaka to Johannesburg Escort">
      </div>
      <div>
        <label for="achievement_date">Journey date</label>
        <input type="date" id="achievement_date" name="achievement_date" value="<?= e(date('Y-m-d')) ?>" required>
      </div>
    </div>
    <div class="form-row">
      <div>
        <label for="start_point">Starting point</label>
        <input type="text" id="start_point" name="start_point" required placeholder="e.g. Lusaka, Zambia">
      </div>
      <div>
        <label for="destination">Destination</label>
        <input type="text" id="destination" name="destination" required placeholder="e.g. Johannesburg, South Africa">
      </div>
    </div>
    <div>
      <label for="summary">Journey update</label>
      <textarea id="summary" name="summary" rows="4" placeholder="Share the convoy progress, checkpoints or delivery update."></textarea>
    </div>
    <div>
      <label for="journey_photos">Journey photos (jpg / png / webp, max 5 MB each)</label>
      <input type="file" id="journey_photos" name="journey_photos[]" accept="image/jpeg,image/png,image/webp" multiple>
    </div>
    <div>
      <label for="photo_caption">Caption for uploaded photos (optional)</label>
      <input type="text" id="photo_caption" name="photo_caption" placeholder="e.g. Convoy cleared the border checkpoint">
    </div>
    <div><button type="submit" class="btn btn-dark">Publish Achievement</button></div>
  </form>
</div>

<div class="admin-panel">
  <h2>Published and Draft Achievements</h2>
  <div class="table-wrap">
    <table class="admin-table">
      <thead><tr><th>Date</th><th>Achievement</th><th>Route</th><th>Photos</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($achievements as $achievement): ?>
          <tr>
            <td><?= e(date('d M Y', strtotime($achievement['achievement_date']))) ?></td>
            <td><strong><?= e($achievement['title']) ?></strong><br><span class="muted"><?= e($achievement['summary'] ?: 'No journey update') ?></span></td>
            <td><?= e($achievement['start_point']) ?><br>to <?= e($achievement['destination']) ?></td>
            <td><?= count($achievement['photos']) ?></td>
            <td><span class="badge <?= $achievement['is_active'] ? 'badge-live' : 'badge-off' ?>"><?= $achievement['is_active'] ? 'Live' : 'Hidden' ?></span></td>
            <td class="row-actions">
              <form method="post" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle_achievement">
                <input type="hidden" name="id" value="<?= (int) $achievement['id'] ?>">
                <button type="submit" class="icon-btn"><?= $achievement['is_active'] ? 'Hide' : 'Publish' ?></button>
              </form>
              <form method="post" style="display:inline" data-confirm="Delete this achievement and its photos?">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete_achievement">
                <input type="hidden" name="id" value="<?= (int) $achievement['id'] ?>">
                <button type="submit" class="icon-btn danger">Delete</button>
              </form>
            </td>
          </tr>
          <?php if ($achievement['photos']): ?>
            <tr class="achievement-photo-row"><td></td><td colspan="5">
              <?php foreach ($achievement['photos'] as $photo): ?>
                <img class="thumb" src="../<?= e($photo['image_path']) ?>" alt="<?= e($photo['caption'] ?: $achievement['title']) ?>">
              <?php endforeach; ?>
            </td></tr>
          <?php endif; ?>
        <?php endforeach; ?>
        <?php if (!$achievements): ?><tr><td colspan="6">No achievements published yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
