<?php
require_once __DIR__ . '/includes/header.php';
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    set_setting('site_name', trim($_POST['site_name'] ?? get_setting('site_name')));
    set_setting('tagline', trim($_POST['tagline'] ?? get_setting('tagline')));
    set_setting('about_text', trim($_POST['about_text'] ?? get_setting('about_text')));
    set_setting('whatsapp_number', preg_replace('/[^0-9]/', '', $_POST['whatsapp_number'] ?? get_setting('whatsapp_number')));

    for ($i = 1; $i <= 4; $i++) {
        set_setting("stat{$i}_value", trim($_POST["stat{$i}_value"] ?? get_setting("stat{$i}_value")));
        set_setting("stat{$i}_label", trim($_POST["stat{$i}_label"] ?? get_setting("stat{$i}_label")));
    }

    $oldLogo = get_setting('logo_path', '');
    $logoPath = handle_image_upload('logo', 'logo');
    if ($logoPath) {
        set_setting('logo_path', $logoPath);
        if ($oldLogo) {
            $oldFile = __DIR__ . '/../' . $oldLogo;
            if (file_exists($oldFile)) @unlink($oldFile);
        }
    }

    $oldHero = get_setting('hero_bg_path', '');
    $heroPath = handle_image_upload('hero', 'hero');
    if ($heroPath) {
        set_setting('hero_bg_path', $heroPath);
        if ($oldHero) {
            $oldFile = __DIR__ . '/../' . $oldHero;
            if (file_exists($oldFile)) @unlink($oldFile);
        }
    }

    $oldGalleryBg = get_setting('gallery_bg_path', '');
    $galleryBgPath = handle_image_upload('gallery_bg', 'hero');
    if ($galleryBgPath) {
        set_setting('gallery_bg_path', $galleryBgPath);
        if ($oldGalleryBg) {
            $oldFile = __DIR__ . '/../' . $oldGalleryBg;
            if (file_exists($oldFile)) @unlink($oldFile);
        }
    }

    $oldServicesBg = get_setting('services_bg_path', '');
    $servicesBgPath = handle_image_upload('services_bg', 'hero');
    if ($servicesBgPath) {
        set_setting('services_bg_path', $servicesBgPath);
        if ($oldServicesBg) {
            $oldFile = __DIR__ . '/../' . $oldServicesBg;
            if (file_exists($oldFile)) @unlink($oldFile);
        }
    }

    $oldContactBg = get_setting('contact_bg_path', '');
    $contactBgPath = handle_image_upload('contact_bg', 'hero');
    if ($contactBgPath) {
        set_setting('contact_bg_path', $contactBgPath);
        if ($oldContactBg) {
            $oldFile = __DIR__ . '/../' . $oldContactBg;
            if (file_exists($oldFile)) @unlink($oldFile);
        }
    }

      $oldMissionBg = get_setting('mission_bg_path', '');
      $missionBgPath = handle_image_upload('mission_bg', 'hero');
      if ($missionBgPath) {
        set_setting('mission_bg_path', $missionBgPath);
        if ($oldMissionBg) {
          $oldFile = __DIR__ . '/../' . $oldMissionBg;
          if (file_exists($oldFile)) @unlink($oldFile);
        }
      }

      $oldValuesBg = get_setting('values_bg_path', '');
      $valuesBgPath = handle_image_upload('values_bg', 'hero');
      if ($valuesBgPath) {
        set_setting('values_bg_path', $valuesBgPath);
        if ($oldValuesBg) {
          $oldFile = __DIR__ . '/../' . $oldValuesBg;
          if (file_exists($oldFile)) @unlink($oldFile);
        }
      }

      $oldEscortBg = get_setting('escort_bg_path', '');
      $escortBgPath = handle_image_upload('escort_bg', 'hero');
      if ($escortBgPath) {
        set_setting('escort_bg_path', $escortBgPath);
        if ($oldEscortBg) {
          $oldFile = __DIR__ . '/../' . $oldEscortBg;
          if (file_exists($oldFile)) @unlink($oldFile);
        }
      }

    $notice = 'Settings saved.';
}

$logo = get_setting('logo_path', '');
$hero = get_setting('hero_bg_path', '');
$gallery_bg = get_setting('gallery_bg_path', '');
$services_bg = get_setting('services_bg_path', '');
$contact_bg = get_setting('contact_bg_path', '');
$mission_bg = get_setting('mission_bg_path', '');
$values_bg = get_setting('values_bg_path', '');
$escort_bg = get_setting('escort_bg_path', '');
?>

<div class="admin-topbar"><h1>Site Settings</h1></div>
<?php if ($notice): ?><div class="alert"><?= e($notice) ?></div><?php endif; ?>

<div class="admin-panel">
  <h2>Branding &amp; Content</h2>
  <form method="post" enctype="multipart/form-data" class="form-grid">
    <?= csrf_field() ?>
    <div class="form-row">
      <div>
        <label for="site_name">Site / Company Name</label>
        <input type="text" id="site_name" name="site_name" value="<?= e(get_setting('site_name')) ?>">
      </div>
      <div>
        <label for="tagline">Tagline</label>
        <input type="text" id="tagline" name="tagline" value="<?= e(get_setting('tagline')) ?>">
      </div>
    </div>
    <div>
      <label for="about_text">About / Mission Text</label>
      <textarea id="about_text" name="about_text" style="min-height:160px"><?= e(get_setting('about_text')) ?></textarea>
    </div>
    <div>
      <label for="whatsapp_number">WhatsApp Number (digits only, with country code)</label>
      <input type="text" id="whatsapp_number" name="whatsapp_number" value="<?= e(get_setting('whatsapp_number')) ?>" placeholder="e.g. 260971181142">
      <p class="form-note">Used for the floating "Chat on WhatsApp" button shown on every public page. No spaces, "+", or leading zeros.</p>
    </div>

    <div class="form-row">
      <div>
        <label for="logo">Company Logo</label>
        <?php if ($logo): ?><img src="../<?= e($logo) ?>" class="thumb" style="width:80px;height:80px;object-fit:contain;background:#fff;margin-bottom:8px"><?php endif; ?>
        <input type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/webp">
        <p class="form-note">PNG/JPG/WEBP, up to 5MB. Appears in the site header and footer.</p>
      </div>
      <div>
        <label for="hero">Homepage Background Image</label>
        <?php if ($hero): ?><img src="../<?= e($hero) ?>" class="thumb" style="width:120px;height:70px;margin-bottom:8px"><?php endif; ?>
        <input type="file" id="hero" name="hero" accept="image/png,image/jpeg,image/webp">
        <p class="form-note">Wide landscape image, up to 5MB. Displays behind the homepage hero section.</p>
      </div>
    </div>

    <div class="form-row">
      <div>
        <label for="gallery_bg">Gallery Background Image</label>
        <?php if ($gallery_bg): ?><img src="../<?= e($gallery_bg) ?>" class="thumb" style="width:120px;height:70px;margin-bottom:8px"><?php endif; ?>
        <input type="file" id="gallery_bg" name="gallery_bg" accept="image/png,image/jpeg,image/webp">
        <p class="form-note">Wide landscape image, up to 5MB. Displays behind the gallery section header.</p>
      </div>
      <div>
        <label for="services_bg">Services Background Image</label>
        <?php if ($services_bg): ?><img src="../<?= e($services_bg) ?>" class="thumb" style="width:120px;height:70px;margin-bottom:8px"><?php endif; ?>
        <input type="file" id="services_bg" name="services_bg" accept="image/png,image/jpeg,image/webp">
        <p class="form-note">Wide landscape image, up to 5MB. Displays behind the services section header.</p>
      </div>
    </div>

    <div class="form-row">
      <div>
        <label for="contact_bg">Contact Background Image</label>
        <?php if ($contact_bg): ?><img src="../<?= e($contact_bg) ?>" class="thumb" style="width:120px;height:70px;margin-bottom:8px"><?php endif; ?>
        <input type="file" id="contact_bg" name="contact_bg" accept="image/png,image/jpeg,image/webp">
        <p class="form-note">Wide landscape image, up to 5MB. Displays behind the contact section header.</p>
      </div>
      <div>
        <label for="mission_bg">Mission &amp; Vision Background Image</label>
        <?php if ($mission_bg): ?><img src="../<?= e($mission_bg) ?>" class="thumb" style="width:120px;height:70px;margin-bottom:8px"><?php endif; ?>
        <input type="file" id="mission_bg" name="mission_bg" accept="image/png,image/jpeg,image/webp">
        <p class="form-note">Wide landscape image, up to 5MB. Displays behind the Mission &amp; Vision section on the homepage.</p>
      </div>
      <div>
        <label for="values_bg">Our Values Background Image</label>
        <?php if ($values_bg): ?><img src="../<?= e($values_bg) ?>" class="thumb" style="width:120px;height:70px;margin-bottom:8px"><?php endif; ?>
        <input type="file" id="values_bg" name="values_bg" accept="image/png,image/jpeg,image/webp">
        <p class="form-note">Wide landscape image, up to 5MB. Displays behind the Our Values section on the homepage.</p>
      </div>
    </div>

    <div class="form-row">
      <div>
        <label for="escort_bg">Cross-Border Escorting Background Image</label>
        <?php if ($escort_bg): ?><img src="../<?= e($escort_bg) ?>" class="thumb" style="width:120px;height:70px;margin-bottom:8px"><?php endif; ?>
        <input type="file" id="escort_bg" name="escort_bg" accept="image/png,image/jpeg,image/webp">
        <p class="form-note">Wide landscape image, up to 5MB. Displays behind the countries we service section.</p>
      </div>
    </div>

    <div><button type="submit" class="btn btn-dark">Save Settings</button></div>
  </form>
</div>

<div class="admin-panel">
  <h2>Homepage Stat Counters</h2>
  <p class="muted" style="margin-top:-8px">Shown as animated counters on the homepage. Leave a value blank to hide that counter. Use a plain number — a "+" or "%" can go in the label if needed (e.g. label "Clients Protected+").</p>
  <form method="post" class="form-grid">
    <?= csrf_field() ?>
    <?php for ($i = 1; $i <= 4; $i++): ?>
      <div class="form-row">
        <div>
          <label for="stat<?= $i ?>_value">Stat <?= $i ?> — Value</label>
          <input type="text" id="stat<?= $i ?>_value" name="stat<?= $i ?>_value" value="<?= e(get_setting("stat{$i}_value")) ?>" placeholder="e.g. 500">
        </div>
        <div>
          <label for="stat<?= $i ?>_label">Stat <?= $i ?> — Label</label>
          <input type="text" id="stat<?= $i ?>_label" name="stat<?= $i ?>_label" value="<?= e(get_setting("stat{$i}_label")) ?>" placeholder="e.g. Clients Protected">
        </div>
      </div>
    <?php endfor; ?>
    <div><button type="submit" class="btn btn-dark">Save Stats</button></div>
  </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
