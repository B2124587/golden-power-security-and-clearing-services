<?php
require_once __DIR__ . '/includes/header.php';

$hero = get_setting('hero_bg_path', '');
$heroStyle = $hero ? ' style="--hero-img:url(\'' . BASE_URL . '/' . e($hero) . '\')"' : '';
$missionBg = get_setting('mission_bg_path', '') ?: get_setting('hero_bg_path', 'uploads/hero/hero_73a45e791243.webp');
$valuesBg = get_setting('values_bg_path', '') ?: get_setting('hero_bg_path', 'uploads/hero/hero_73a45e791243.webp');
$about = get_setting('about_text', '');
$quote = get_active_quote();
$services = array_slice(get_active_services(), 0, 4);
$achievements = get_active_achievements(3);
$testimonials = get_active_testimonials();
$stats = [];
for ($i = 1; $i <= 4; $i++) {
    $val = get_setting("stat{$i}_value", '');
    if ($val !== '') {
        $stats[] = ['value' => $val, 'label' => get_setting("stat{$i}_label", '')];
    }
}
?>

<section class="hero"<?= $heroStyle ?>>
  <div class="hero-stamp">GPS · EST. 2018 · ARMED &amp; ESCORT DIVISION</div>
  <div class="hero-inner">
    <span class="eyebrow">Golden Power Security</span>
    <h1>Proudly protecting <span class="gold">Southern Africa's</span> proudest possessions</h1>
    <p class="lead">A private armed security corporation delivering business protection, cross-border truck escorting, farming security and armed patrol services — built on integrity, service, excellence and teamwork.</p>
    <div class="hero-actions">
      <a href="services.php" class="btn btn-primary">View Our Services</a>
      <a href="contact.php" class="btn btn-outline">Request an Escort</a>
    </div>
    <div class="dispatch-quote" data-reveal>
      <span class="label"><span class="dot"></span>Daily Dispatch</span>
      "<?= e($quote) ?>"
    </div>
  </div>
</section>

<?php if ($achievements): ?>
<section class="section section-dark achievement-section">
  <div class="container">
    <div class="section-head" data-reveal>
      <span class="eyebrow">Daily Operations</span>
      <h2>On the road, in real time</h2>
      <p>Latest convoy achievements and journey updates from the GPS operations team.</p>
    </div>
    <div class="achievement-grid">
      <?php foreach ($achievements as $achievement): ?>
        <?php
          $journeyPhotos = array_map(static function (array $photo): array {
              return [
                  'src' => BASE_URL . '/' . $photo['image_path'],
                  'caption' => $photo['caption'] ?? '',
              ];
          }, $achievement['photos']);
          $journeyRoute = $achievement['start_point'] . ' to ' . $achievement['destination'];
        ?>
        <article class="achievement-card" data-reveal data-journey="<?= e(json_encode($journeyPhotos, JSON_UNESCAPED_SLASHES)) ?>" data-journey-title="<?= e($achievement['title']) ?>" data-journey-route="<?= e($journeyRoute) ?>" tabindex="0">
          <?php if (!empty($achievement['photos'])): ?>
            <div class="achievement-photo-strip">
              <?php foreach (array_slice($achievement['photos'], 0, 3) as $photo): ?>
                <img src="<?= BASE_URL . '/' . e($photo['image_path']) ?>" alt="<?= e($photo['caption'] ?: $achievement['title']) ?>" loading="lazy">
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <div class="achievement-content">
            <span class="achievement-date"><?= e(date('d M Y', strtotime($achievement['achievement_date']))) ?></span>
            <h3><?= e($achievement['title']) ?></h3>
            <div class="achievement-route"><strong><?= e($achievement['start_point']) ?></strong><span>→</span><strong><?= e($achievement['destination']) ?></strong></div>
            <?php if ($achievement['summary']): ?><p><?= nl2br(e($achievement['summary'])) ?></p><?php endif; ?>
            <span class="achievement-photo-count"><?= count($achievement['photos']) ?> journey photo<?= count($achievement['photos']) === 1 ? '' : 's' ?></span>
            <?php if ($achievement['photos']): ?><button type="button" class="journey-open">View journey photos <span aria-hidden="true">→</span></button><?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<div class="lightbox achievement-lightbox" id="achievementLightbox" role="dialog" aria-modal="true" aria-label="Achievement journey viewer">
  <div class="lightbox-inner">
    <button class="lightbox-close" id="achievementClose" aria-label="Close journey viewer">&times;</button>
    <div class="achievement-media" id="achievementMedia"></div>
    <div class="lightbox-cap">
      <div>
        <h4 id="achievementTitle"></h4>
        <p id="achievementCaption"></p>
      </div>
    </div>
  </div>
</div>

<?php if ($stats): ?>
<section class="section section-dark" id="stats">
  <div class="container">
    <div class="stats-grid" data-reveal data-counters>
      <?php foreach ($stats as $s): ?>
        <div class="stat-cell">
          <span class="stat-num" data-count-to="<?= (int) preg_replace('/[^0-9]/', '', $s['value']) ?>" data-count-suffix="<?= e(preg_replace('/[0-9]/', '', $s['value'])) ?>">0</span>
          <span class="stat-label"><?= e($s['label']) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section section-dark image-section mission-hero" id="mission" style="--section-bg:url('<?= BASE_URL ?>/<?= e($missionBg) ?>')">
  <div class="container mission-grid">
    <div data-reveal>
      <span class="eyebrow">Mission &amp; Vision</span>
      <h2>Excellence, expertise and quality assurance — every shift, every convoy.</h2>
      <p><?= nl2br(e($about)) ?></p>
      <p class="muted">Our vision is to become the number one choice for individual and corporate clients across Southern Africa, building long-lasting, mutually beneficial relationships with every client we serve.</p>
    </div>
    <ul data-reveal>
      <li>Professional, trusted private security for individuals, businesses and nonprofits</li>
      <li>Service delivered with excellence, expertise and quality assurance</li>
      <li>Graduating poverty and creating employment across the region</li>
      <li>A strong, reliable team with never-compromised quality</li>
    </ul>
  </div>
</section>

<section class="section section-dark image-section values-hero" style="--section-bg:url('<?= BASE_URL ?>/<?= e($valuesBg) ?>')">
  <div class="container">
    <div class="section-head" data-reveal>
      <span class="eyebrow">Our Values</span>
      <h2>What makes us different</h2>
      <p>Honesty, open communication and transparency — focused on building long-term professional relationships.</p>
    </div>
    <div class="value-grid" data-reveal>
      <div class="value-cell"><span class="num">01</span><h4>Integrity</h4></div>
      <div class="value-cell"><span class="num">02</span><h4>Service</h4></div>
      <div class="value-cell"><span class="num">03</span><h4>Excellence</h4></div>
      <div class="value-cell"><span class="num">04</span><h4>Teamwork</h4></div>
    </div>
  </div>
</section>

<?php if ($testimonials): ?>
<section class="section section-dark" id="testimonials">
  <div class="container">
    <div class="section-head" data-reveal>
      <span class="eyebrow">Client Trust</span>
      <h2>What our clients say</h2>
    </div>
    <div class="testimonial-carousel" data-reveal data-testimonials>
      <?php foreach ($testimonials as $i => $t): ?>
        <div class="testimonial-slide<?= $i === 0 ? ' is-active' : '' ?>">
          <p class="quote"><?= e($t['quote']) ?></p>
          <p class="who"><?= e($t['client_name']) ?><?php if ($t['client_title']): ?><span><?= e($t['client_title']) ?></span><?php endif; ?></p>
        </div>
      <?php endforeach; ?>
      <?php if (count($testimonials) > 1): ?>
        <div class="testimonial-dots">
          <?php foreach ($testimonials as $i => $t): ?>
            <button type="button" class="<?= $i === 0 ? 'is-active' : '' ?>" data-slide-to="<?= $i ?>" aria-label="Show testimonial <?= $i + 1 ?>"></button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section image-section" id="services-teaser" style="--section-bg:url('<?= BASE_URL ?>/uploads/hero/hero_73a45e791243.webp')">
  <div class="container">
    <div class="section-head" data-reveal>
      <span class="eyebrow">Broad-Based Security</span>
      <h2>Services built for the road, the yard and the perimeter</h2>
    </div>
    <div class="manifest-grid" data-reveal>
      <?php foreach ($services as $i => $s): ?>
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
        <div class="empty-state">Services will be listed here shortly.</div>
      <?php endif; ?>
    </div>
    <div style="margin-top:36px"><a href="services.php" class="btn btn-dark">See All Services →</a></div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
