<?php
require_once __DIR__ . '/includes/header.php';
$services = get_active_services();
$countries = get_escort_countries();

$servicesBg = get_setting('services_bg_path', '');
if (!$servicesBg) {
    $servicesBg = get_setting('hero_bg_path', 'uploads/hero/hero_73a45e791243.webp');
}
$escortBg = get_setting('escort_bg_path', '') ?: get_setting('hero_bg_path', 'uploads/hero/hero_73a45e791243.webp');
?>

<section class="section section-dark image-section services-hero" style="--section-bg:url('<?= BASE_URL ?>/<?= e($servicesBg) ?>')">
  <div class="container">
    <span class="eyebrow">What We Do</span>
    <h1 style="font-size:clamp(2rem,4vw,2.8rem); max-width:20ch;">Broad-based security services, ensuring the highest standards at an affordable fee.</h1>
    <p class="muted" style="max-width:60ch">With many years of knowledge and experience, our highly trained team is proficient in executing superior safety solutions across business, transport, farming and patrol operations.</p>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="manifest-grid" data-reveal>
      <?php foreach ($services as $s): ?>
        <a class="manifest-card" href="contact.php?service=<?= e(rawurlencode($s['title'])) ?>#inquiryWizard">
          <div class="stitch"></div>
          <span class="manifest-tag"><?= e($s['code']) ?></span>
          <div class="icon"><?= svg_icon($s['icon']) ?></div>
          <h3><?= e($s['title']) ?></h3>
          <p><?= e($s['description']) ?></p>
          <span class="manifest-action">Request this service <span aria-hidden="true">&rarr;</span></span>
        </a>
      <?php endforeach; ?>
      <?php if (!$services): ?>
        <div class="empty-state">No services published yet — check back soon.</div>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="section section-dark image-section escort-hero" style="--section-bg:url('<?= BASE_URL ?>/<?= e($escortBg) ?>')">
  <div class="container">
    <div class="section-head" data-reveal>
      <span class="eyebrow"><?= svg_icon('route') ?> Cross-Border Escorting</span>
      <h2>Countries we service</h2>
      <p>Specialised armed convoy escorting for cargo transiting across Southern Africa's busiest freight corridors.</p>
    </div>
    <div class="route-strip" data-reveal>
      <div class="route-line"></div>
      <div class="route-chips">
        <?php foreach ($countries as $c): ?>
          <span class="route-chip"><?= e($c['country_name']) ?></span>
        <?php endforeach; ?>
      </div>
    </div>
    <div style="margin-top:44px"><a href="contact.php" class="btn btn-primary">Request an Escort Quote</a></div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
