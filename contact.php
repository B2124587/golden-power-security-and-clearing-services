<?php
require_once __DIR__ . '/includes/header.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Your session expired — please try submitting again.';
    } else {
        $name    = trim($_POST['name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $service = trim($_POST['service_requested'] ?? '');
        $urgency = trim($_POST['urgency'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if ($name === '') $errors[] = 'Please enter your name.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
        if ($message === '') $errors[] = 'Please enter a short message.';

        if (!$errors) {
            $storedMessage = $urgency !== '' ? "[Urgency: {$urgency}]\n{$message}" : $message;
            $stmt = get_db()->prepare(
                'INSERT INTO inquiries (name, email, phone, service_requested, message) VALUES (:n,:e,:p,:s,:m)'
            );
            $stmt->execute(['n' => $name, 'e' => $email, 'p' => $phone, 's' => $service, 'm' => $storedMessage]);
            $success = true;
        }
    }
}

$offices = get_contact_info();
$services = get_active_services();
$selectedService = trim($_GET['service'] ?? $_POST['service_requested'] ?? '');

$contactBg = get_setting('contact_bg_path', '');
if (!$contactBg) {
    $contactBg = get_setting('hero_bg_path', 'uploads/hero/hero_73a45e791243.webp');
}
?>

<section class="section section-dark image-section contact-hero" style="--section-bg:url('<?= BASE_URL ?>/<?= e($contactBg) ?>')">
  <div class="container">
    <span class="eyebrow">Contact &amp; Inquire</span>
    <h1>Request an escort or protection quote.</h1>
    <p>Tell our operations team what you need protected, where support is required and how quickly you need a response.</p>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="contact-layout">
      <div class="request-panel" data-reveal>

      <?php if ($success): ?>
        <div class="alert">Thank you — your inquiry has been received. Our team will be in touch shortly.</div>
      <?php endif; ?>
      <?php foreach ($errors as $err): ?>
        <div class="alert error"><?= e($err) ?></div>
      <?php endforeach; ?>

      <?php if (!$success): ?>
      <noscript><style>.wizard-step{display:block !important} .wizard-progress,.wizard-back{display:none !important}</style></noscript>

      <div class="wizard-progress" id="wizardProgress" aria-hidden="true">
        <span class="current"></span><span></span><span></span>
      </div>

      <form method="post" class="form-grid wizard-steps" id="inquiryWizard">
        <?= csrf_field() ?>
        <input type="hidden" name="service_requested" id="wizardServiceInput" value="<?= e($selectedService) ?>">
        <input type="hidden" name="urgency" id="wizardUrgencyInput" value="">

        <div class="wizard-step is-active" data-step="1">
          <span class="form-kicker">Step 01 / Service</span>
          <h2>What do you need help with?</h2>
          <p class="wizard-intro">Choose the service that best matches your security requirement.</p>
          <div class="wizard-choices" data-wizard-choices data-target="wizardServiceInput" data-next="2">
            <?php foreach ($services as $s): ?>
              <button type="button" class="wizard-choice" data-value="<?= e($s['title']) ?>">
                <span class="wizard-code"><?= e($s['code']) ?></span>
                <strong><?= e($s['title']) ?></strong>
                <span><?= e(mb_strimwidth($s['description'], 0, 60, '…')) ?></span>
              </button>
            <?php endforeach; ?>
            <button type="button" class="wizard-choice" data-value="Other">
              <span class="wizard-code">GEN-01</span>
              <strong>Something Else</strong>
              <span>Not sure, or a general inquiry</span>
            </button>
          </div>
        </div>

        <div class="wizard-step" data-step="2">
          <a class="wizard-back" href="services.php">&larr; Change service</a>
          <label>How urgent is this?</label>
          <div class="wizard-choices" data-wizard-choices data-target="wizardUrgencyInput" data-next="3">
            <button type="button" class="wizard-choice" data-value="Emergency — need a response now">
              <strong>Emergency</strong>
              <span>Need a response right now</span>
            </button>
            <button type="button" class="wizard-choice" data-value="This week">
              <strong>This Week</strong>
              <span>Planning ahead, not urgent</span>
            </button>
            <button type="button" class="wizard-choice" data-value="Just exploring">
              <strong>Just Exploring</strong>
              <span>Gathering information for now</span>
            </button>
          </div>
        </div>

        <div class="wizard-step" data-step="3">
          <button type="button" class="wizard-back" data-back="2">&larr; Change urgency</button>
          <div class="wizard-summary-chips" id="wizardSummary" aria-hidden="true"></div>

          <div class="form-row">
            <div>
              <label for="name">Full Name</label>
              <input type="text" id="name" name="name" required value="<?= e($_POST['name'] ?? '') ?>">
            </div>
            <div>
              <label for="email">Email</label>
              <input type="email" id="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
            </div>
          </div>
          <div>
            <label for="phone">Phone Number</label>
            <input type="text" id="phone" name="phone" value="<?= e($_POST['phone'] ?? '') ?>">
          </div>
          <div>
            <label for="message">Message</label>
            <textarea id="message" name="message" required><?= e($_POST['message'] ?? '') ?></textarea>
          </div>
          <div><button type="submit" class="btn btn-primary">Submit Inquiry</button></div>
          <p class="form-note">Fields marked required must be completed. We typically respond within one business day.</p>
        </div>
      </form>
      <?php endif; ?>
    </div>

    <div class="checkpoint-grid" data-reveal>
      <?php foreach ($offices as $o): ?>
        <div class="checkpoint-card">
          <div class="icon"><?= svg_icon('pin') ?></div>
          <h4><?= e($o['region_label']) ?></h4>
          <p><?= e($o['address']) ?></p>
          <?php if ($o['phone1']): ?><p><a href="tel:<?= e(preg_replace('/\s+/', '', $o['phone1'])) ?>"><?= e($o['phone1']) ?></a></p><?php endif; ?>
          <?php if ($o['phone2']): ?><p><a href="tel:<?= e(preg_replace('/\s+/', '', $o['phone2'])) ?>"><?= e($o['phone2']) ?></a></p><?php endif; ?>
          <?php if ($o['email']): ?><p><a href="mailto:<?= e($o['email']) ?>"><?= e($o['email']) ?></a></p><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
    </div><!-- .contact-layout -->
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
