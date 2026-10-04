<?php
require_once __DIR__ . '/includes/header.php';
$db = get_db();
$notice = '';

/* ── Ensure gallery_images table exists (migration-safe) ─────────────────── */
try {
    $db->exec(
        'CREATE TABLE IF NOT EXISTS gallery_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(150) NOT NULL,
            caption VARCHAR(400),
            image_path VARCHAR(500) NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            sort_order INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB'
    );
} catch (\Throwable $e) { /* already exists */ }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $action = $_POST['action'] ?? '';

    /* ── Video actions ───────────────────────────────────────────────────── */
    if ($action === 'create_video') {
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $url         = trim($_POST['video_url'] ?? '');

        // Allow direct file upload (mp4/webm/ogg)
        if (empty($url) && !empty($_FILES['video_file']['name'])) {
            $ext = strtolower(pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['mp4', 'webm', 'ogg'], true) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
                $dir = UPLOAD_DIR . '/videos';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $fname = 'video_' . bin2hex(random_bytes(6)) . '.' . $ext;
                if (move_uploaded_file($_FILES['video_file']['tmp_name'], $dir . '/' . $fname)) {
                    $url = 'uploads/videos/' . $fname;
                }
            }
        }

        if ($title !== '' && $url !== '' && is_allowed_video_url($url)) {
            $stmt = $db->prepare('INSERT INTO videos (title, description, video_url) VALUES (:t,:d,:u)');
            $stmt->execute(['t' => $title, 'd' => $description, 'u' => $url]);
            $notice = 'Video added.';
        } elseif ($title !== '' && $url !== '') {
            $notice = 'Please provide a YouTube/Vimeo link or a direct video file — other URL types are not allowed.';
        } else {
            $notice = 'Please provide a title and either a video URL or an uploaded file.';
        }

    } elseif ($action === 'toggle_video') {
        $stmt = $db->prepare('UPDATE videos SET is_active = 1 - is_active WHERE id = :id');
        $stmt->execute(['id' => (int) $_POST['id']]);
        $notice = 'Visibility updated.';

    } elseif ($action === 'delete_video') {
        // If it's a locally uploaded file (not an external YouTube/Vimeo URL), remove it from disk too.
        $row = $db->prepare('SELECT video_url FROM videos WHERE id = :id');
        $row->execute(['id' => (int) $_POST['id']]);
        $data = $row->fetch();
        if ($data && $data['video_url'] && str_starts_with($data['video_url'], 'uploads/')) {
            $file = __DIR__ . '/../' . $data['video_url'];
            if (file_exists($file)) @unlink($file);
        }
        $stmt = $db->prepare('DELETE FROM videos WHERE id = :id');
        $stmt->execute(['id' => (int) $_POST['id']]);
        $notice = 'Video deleted.';

    /* ── Photo (gallery_images) actions ─────────────────────────────────── */
    } elseif ($action === 'create_image') {
        $title   = trim($_POST['img_title'] ?? '');
        $caption = trim($_POST['img_caption'] ?? '');
        $path    = handle_image_upload('gallery_image', 'gallery');

        if ($title !== '' && $path !== null) {
            $stmt = $db->prepare('INSERT INTO gallery_images (title, caption, image_path) VALUES (:t,:c,:p)');
            $stmt->execute(['t' => $title, 'c' => $caption, 'p' => $path]);
            $notice = 'Photo added to gallery.';
        } else {
            $notice = 'Please provide a title and a valid image file (jpg, png, webp — max 5 MB).';
        }

    } elseif ($action === 'toggle_image') {
        $stmt = $db->prepare('UPDATE gallery_images SET is_active = 1 - is_active WHERE id = :id');
        $stmt->execute(['id' => (int) $_POST['id']]);
        $notice = 'Photo visibility updated.';

    } elseif ($action === 'delete_image') {
        // Optionally delete file from disk too
        $row = $db->prepare('SELECT image_path FROM gallery_images WHERE id = :id');
        $row->execute(['id' => (int) $_POST['id']]);
        $data = $row->fetch();
        if ($data && $data['image_path']) {
            $file = __DIR__ . '/../' . $data['image_path'];
            if (file_exists($file)) @unlink($file);
        }
        $stmt = $db->prepare('DELETE FROM gallery_images WHERE id = :id');
        $stmt->execute(['id' => (int) $_POST['id']]);
        $notice = 'Photo deleted.';
    }
}

$videos = get_all_videos();
$images = get_all_gallery_images();
?>

<div class="admin-topbar"><h1>Gallery Manager</h1></div>
<?php if ($notice): ?><div class="alert"><?= e($notice) ?></div><?php endif; ?>

<!-- ══════════════════════ ADD VIDEO ═══════════════════════════════ -->
<div class="admin-panel">
  <h2>Add a Video</h2>
  <form method="post" enctype="multipart/form-data" class="form-grid">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="create_video">
    <div>
      <label for="title">Title</label>
      <input type="text" id="title" name="title" required placeholder="e.g. DRC Convoy — March Operation">
    </div>
    <div>
      <label for="description">Description (optional)</label>
      <input type="text" id="description" name="description">
    </div>
    <div class="form-row">
      <div>
        <label for="video_url">YouTube / Vimeo URL</label>
        <input type="url" id="video_url" name="video_url" placeholder="https://youtube.com/watch?v=…">
      </div>
      <div>
        <label for="video_file">…or upload an MP4 file</label>
        <input type="file" id="video_file" name="video_file" accept="video/mp4,video/webm,video/ogg">
      </div>
    </div>
    <p class="form-note">Provide either a video link or an uploaded file — you don't need both.</p>
    <div><button type="submit" class="btn btn-dark">Add Video</button></div>
  </form>
</div>

<!-- ══════════════════════ ADD PHOTO ═══════════════════════════════ -->
<div class="admin-panel">
  <h2>Add a Gallery Photo</h2>
  <form method="post" enctype="multipart/form-data" class="form-grid">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="create_image">
    <div class="form-row">
      <div>
        <label for="img_title">Photo Title</label>
        <input type="text" id="img_title" name="img_title" required placeholder="e.g. Zambia Escort Team">
      </div>
      <div>
        <label for="img_caption">Caption (optional)</label>
        <input type="text" id="img_caption" name="img_caption" placeholder="Short description shown in gallery">
      </div>
    </div>
    <div>
      <label for="gallery_image">Image File (jpg / png / webp — max 5 MB)</label>
      <input type="file" id="gallery_image" name="gallery_image" accept="image/jpeg,image/png,image/webp" required>
    </div>
    <div><button type="submit" class="btn btn-dark">Upload Photo</button></div>
  </form>
</div>

<!-- ══════════════════════ ALL VIDEOS ══════════════════════════════ -->
<div class="admin-panel">
  <h2>All Videos</h2>
  <div class="table-wrap">
    <table class="admin-table">
      <thead><tr><th>Title</th><th>Source</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($videos as $v): ?>
          <tr>
            <td><?= e($v['title']) ?><?= $v['description'] ? '<br><span class="muted">' . e($v['description']) . '</span>' : '' ?></td>
            <td style="word-break:break-all;max-width:260px"><?= e($v['video_url']) ?></td>
            <td><span class="badge <?= $v['is_active'] ? 'badge-live' : 'badge-off' ?>"><?= $v['is_active'] ? 'Live' : 'Hidden' ?></span></td>
            <td class="row-actions">
              <form method="post" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle_video">
                <input type="hidden" name="id" value="<?= (int) $v['id'] ?>">
                <button type="submit" class="icon-btn"><?= $v['is_active'] ? 'Hide' : 'Publish' ?></button>
              </form>
              <form method="post" style="display:inline" data-confirm="Delete this video?">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete_video">
                <input type="hidden" name="id" value="<?= (int) $v['id'] ?>">
                <button type="submit" class="icon-btn danger">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$videos): ?><tr><td colspan="4">No videos yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ══════════════════════ ALL PHOTOS ══════════════════════════════ -->
<div class="admin-panel">
  <h2>All Gallery Photos</h2>
  <div class="table-wrap">
    <table class="admin-table">
      <thead><tr><th>Preview</th><th>Title</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($images as $img): ?>
          <tr>
            <td style="width:80px">
              <img src="../<?= e($img['image_path']) ?>"
                   alt="<?= e($img['title']) ?>"
                   style="width:72px;height:52px;object-fit:cover;border-radius:2px;display:block;">
            </td>
            <td><?= e($img['title']) ?><?= $img['caption'] ? '<br><span class="muted">' . e($img['caption']) . '</span>' : '' ?></td>
            <td><span class="badge <?= $img['is_active'] ? 'badge-live' : 'badge-off' ?>"><?= $img['is_active'] ? 'Live' : 'Hidden' ?></span></td>
            <td class="row-actions">
              <form method="post" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle_image">
                <input type="hidden" name="id" value="<?= (int) $img['id'] ?>">
                <button type="submit" class="icon-btn"><?= $img['is_active'] ? 'Hide' : 'Publish' ?></button>
              </form>
              <form method="post" style="display:inline" data-confirm="Delete this photo? This cannot be undone.">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete_image">
                <input type="hidden" name="id" value="<?= (int) $img['id'] ?>">
                <button type="submit" class="icon-btn danger">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$images): ?><tr><td colspan="4">No photos uploaded yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
