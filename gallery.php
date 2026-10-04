<?php
require_once __DIR__ . '/includes/header.php';
$videos = get_active_videos();
$images = get_active_gallery_images();

$galleryBg = get_setting('gallery_bg_path', '');
if (!$galleryBg) {
  $galleryBg = get_setting('hero_bg_path', 'uploads/hero/hero_73a45e791243.webp');
}
?>

<section class="section section-dark image-section gallery-hero" style="--section-bg:url('<?= BASE_URL ?>/<?= e($galleryBg) ?>')">
  <div class="container">
    <span class="eyebrow" data-reveal>Achievements &amp; Gallery</span>
    <h1 data-reveal style="font-size:clamp(2rem,4vw,2.8rem); max-width:22ch;">
      Convoy operations, training seminars and<br>
      <span style="color:var(--gold)">offloading verifications.</span>
    </h1>
    <p data-reveal class="muted" style="max-width:52ch;margin-top:.5em;">
      A live record of Golden Power Security operations — cross-border escorts,
      business patrols and active deployments across Southern Africa.
    </p>
  </div>
</section>

<section class="section">
  <div class="container">

    <?php
    // Build combined list: images + videos, tag each type
    $hasImages = !empty($images);
    $hasVideos = !empty($videos);
    $showTabs  = $hasImages && $hasVideos; // show tabs only if both types present
    ?>

    <?php if ($showTabs): ?>
    <div class="gallery-tabs" role="tablist" aria-label="Gallery filters">
      <button class="gallery-tab active" data-filter="all"    role="tab" aria-selected="true">All</button>
      <button class="gallery-tab"        data-filter="photo"  role="tab" aria-selected="false">Photos</button>
      <button class="gallery-tab"        data-filter="video"  role="tab" aria-selected="false">Videos</button>
    </div>
    <?php endif; ?>

    <div class="gallery-grid" id="galleryGrid">

      <?php
      $cardIdx = 0;

      // ── Photos ─────────────────────────────────────────────────────────
      foreach ($images as $img):
        $src   = BASE_URL . '/' . e($img['image_path']);
        $title = e($img['title']);
        $cap   = e($img['caption'] ?? '');
      ?>
        <div class="gallery-card"
             data-type="photo"
             data-lb="<?= $src ?>"
             data-lb-title="<?= $title ?>"
             data-lb-caption="<?= $cap ?>"
             style="--i:<?= $cardIdx++ ?>"
             role="button"
             aria-label="View photo: <?= $title ?>">
          <div class="gallery-frame img-frame">
            <img src="<?= $src ?>" alt="<?= $title ?>" loading="lazy">
            <div class="gallery-overlay">
              <span class="gallery-overlay-label">View Photo</span>
            </div>
            <span class="gallery-type-badge">Photo</span>
          </div>
          <div class="cap">
            <h4><?= $title ?></h4>
            <?php if ($cap): ?><p><?= $cap ?></p><?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>

      <?php
      // ── Videos ─────────────────────────────────────────────────────────
      foreach ($videos as $v):
        $isFile   = is_direct_video($v['video_url']);
        $embedSrc = $isFile ? e($v['video_url']) : e(to_embed_url($v['video_url']));
        $lbSrc    = $isFile ? BASE_URL . '/' . e($v['video_url']) : e(to_embed_url($v['video_url']));
        $title    = e($v['title']);
        $desc     = e($v['description'] ?? '');
      ?>
        <div class="gallery-card"
             data-type="video"
             data-lb="<?= $lbSrc ?>"
             data-lb-title="<?= $title ?>"
             data-lb-caption="<?= $desc ?>"
             style="--i:<?= $cardIdx++ ?>">
          <div class="gallery-frame vid-frame">
            <?php if ($isFile): ?>
              <video src="<?= $embedSrc ?>" controls preload="metadata"></video>
            <?php else: ?>
              <iframe src="<?= $embedSrc ?>"
                      title="<?= $title ?>"
                      allowfullscreen loading="lazy"></iframe>
            <?php endif; ?>
            <span class="gallery-type-badge">Video</span>
          </div>
          <div class="cap">
            <h4><?= $title ?></h4>
            <?php if ($desc): ?><p><?= $desc ?></p><?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>

      <?php if (!$images && !$videos): ?>
        <div class="empty-state">No footage or photos uploaded yet — check back soon.</div>
      <?php endif; ?>

    </div><!-- .gallery-grid -->

  </div>
</section>

<!-- ── Lightbox ─────────────────────────────────────────────────────────── -->
<div class="lightbox" id="galleryLightbox" role="dialog" aria-modal="true" aria-label="Media viewer">
  <div class="lightbox-inner">
    <button class="lightbox-close" id="lbClose" aria-label="Close lightbox">&times;</button>
    <button class="lightbox-prev"  id="lbPrev"  aria-label="Previous">&#8592;</button>
    <button class="lightbox-next"  id="lbNext"  aria-label="Next">&#8594;</button>

    <div class="lightbox-media" id="lbMedia"></div>
    <div class="lightbox-cap">
      <div>
        <h4 id="lbTitle"></h4>
        <p  id="lbCaption"></p>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
