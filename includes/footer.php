<?php
$pages = [
    'index.php' => 'Home',
    'services.php' => 'Services',
    'gallery.php' => 'Gallery',
    'contact.php' => 'Request Escort'
];
$pageKeys = array_keys($pages);
$currentIdx = array_search(basename($_SERVER['SCRIPT_NAME']), $pageKeys);
if ($currentIdx === false) $currentIdx = 0;

$prevIdx = ($currentIdx - 1 + count($pageKeys)) % count($pageKeys);
$nextIdx = ($currentIdx + 1) % count($pageKeys);

$prevPage = $pageKeys[$prevIdx];
$nextPage = $pageKeys[$nextIdx];
$prevTitle = $pages[$prevPage];
$nextTitle = $pages[$nextPage];
?>
<div class="page-loop-nav">
  <div class="container" style="display:flex; justify-content:space-between; align-items:center;">
    <a href="<?= BASE_URL ?>/<?= $prevPage ?>" class="btn btn-outline">&larr; Prev: <?= $prevTitle ?></a>
    <a href="<?= BASE_URL ?>/<?= $nextPage ?>" class="btn btn-primary">Next: <?= $nextTitle ?> &rarr;</a>
  </div>
</div>

<?php
$waNumber = get_setting('whatsapp_number', '');
$firstOffice = get_contact_info()[0] ?? null;
$callNumber = $firstOffice['phone1'] ?? '';
if ($waNumber || $callNumber):
?>
<div class="floating-contact" aria-label="Quick contact">
  <?php if ($waNumber): ?>
    <a href="https://wa.me/<?= e($waNumber) ?>" class="floating-btn floating-whatsapp" target="_blank" rel="noopener" aria-label="Chat on WhatsApp">
      <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2c-5.5 0-9.96 4.46-9.96 9.96 0 1.76.46 3.48 1.34 5L2 22l5.2-1.36a9.9 9.9 0 0 0 4.84 1.23h.01c5.5 0 9.96-4.46 9.96-9.96S17.54 2 12.04 2Zm5.83 14.24c-.25.7-1.24 1.29-1.98 1.44-.52.11-1.2.19-3.48-.75-2.92-1.2-4.8-4.16-4.95-4.35-.14-.2-1.18-1.57-1.18-3 0-1.42.75-2.12 1.02-2.41.25-.27.55-.34.73-.34.19 0 .37.002.53.01.17.008.4-.065.62.48.25.6.85 2.08.92 2.23.07.15.12.33.02.53-.09.2-.14.32-.28.5-.14.17-.3.38-.42.51-.14.15-.29.31-.13.6.17.29.75 1.24 1.62 2.01 1.12.99 2.06 1.3 2.35 1.44.29.14.46.12.63-.07.17-.2.72-.84.92-1.13.19-.29.39-.24.65-.14.27.1 1.71.81 2 .96.29.14.48.21.55.33.07.12.07.68-.18 1.38Z"/></svg>
    </a>
  <?php endif; ?>
  <?php if ($callNumber): ?>
    <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $callNumber)) ?>" class="floating-btn floating-call" aria-label="Call us">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 3h3l1.5 5-2.3 1.6a12 12 0 0 0 6.2 6.2L16 13.5l5 1.5v3a2 2 0 0 1-2.2 2A17 17 0 0 1 4 5.2 2 2 0 0 1 6 3Z"/></svg>
    </a>
  <?php endif; ?>
</div>
<?php endif; ?>

<footer class="site-footer">
  <div class="footer-inner">
    <div class="footer-col footer-brand">
      <span class="brand-mark small" aria-hidden="true">GPS</span>
      <p><?= e(get_setting('site_name', 'Golden Power Security')) ?></p>
      <p class="muted">Est. 2018 · Private Armed Security Corporation</p>
    </div>
    <div class="footer-col">
      <h4>Values</h4>
      <ul class="value-list">
        <li>Integrity</li>
        <li>Service</li>
        <li>Excellence</li>
        <li>Teamwork</li>
      </ul>
    </div>
    <div class="footer-col">
      <h4>Offices</h4>
      <?php foreach (get_contact_info() as $office): ?>
        <p class="muted"><strong><?= e($office['region_label']) ?>:</strong> <?= e($office['phone1']) ?></p>
      <?php endforeach; ?>
    </div>
    <div class="footer-col">
      <h4>Admin</h4>
      <p class="muted"><a href="<?= BASE_URL ?>/admin/login.php">Staff login →</a></p>
    </div>
  </div>
  <div class="footer-bottom">
    <p>&copy; <?= date('Y') ?> <?= e(get_setting('site_name', 'Golden Power Security')) ?>. All rights reserved.</p>
  </div>
</footer>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
