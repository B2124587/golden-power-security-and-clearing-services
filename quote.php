<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/quotes.php';

$errors = [];
$success = null;
$options = get_quote_pricing_options();
$selectedKey = trim($_POST['pricing_key'] ?? $_GET['service'] ?? '');
$selectedPricing = null;
foreach ($options as $option) {
    if ($option['pricing_key'] === $selectedKey) {
        $selectedPricing = $option;
        break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Your session expired. Please submit the form again.';
    }
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $location = trim($_POST['location'] ?? '');
    if (!$selectedPricing) $errors[] = 'Please choose a service.';
    if ($name === '' || mb_strlen($name) > 120) $errors[] = 'Please enter your full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if ($location === '' || mb_strlen($location) > 180) $errors[] = 'Please enter your service location.';

    $requirements = [
        'quantity' => $_POST['quantity'] ?? 1,
        'hours' => $_POST['hours'] ?? 1,
        'days' => $_POST['days'] ?? 1,
        'frequency' => $_POST['frequency'] ?? 'one_off',
        'notes' => trim($_POST['notes'] ?? ''),
    ];
    if (!$errors) {
        try {
            $estimate = calculate_quote_estimate($selectedPricing, $requirements);
            $serviceId = null;
            if (!empty($selectedPricing['service_id'])) $serviceId = (int) $selectedPricing['service_id'];
            $requestNumber = create_quote_request([
                'service_id' => $serviceId,
                'pricing_key' => $selectedPricing['pricing_key'],
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'location' => $location,
                'requirements' => $requirements,
            ], $estimate);
            $success = ['number' => $requestNumber, 'estimate' => $estimate];
        } catch (Throwable $e) {
          $errors[] = 'Quote request could not be saved. Please try again or contact our team.';
        }
    }
}
?>
<section class="section section-dark image-section contact-hero">
  <div class="container">
    <span class="eyebrow">Quote Desk</span>
    <h1>Build a service estimate.</h1>
    <p>Choose a service and tell us what you need. Your estimate is indicative and subject to confirmation by our operations team.</p>
  </div>
</section>
<section class="section">
  <div class="container quote-layout">
    <div class="request-panel">
      <?php foreach ($errors as $error): ?><div class="alert error"><?= e($error) ?></div><?php endforeach; ?>
      <?php if ($success): ?>
        <div class="alert">Request <?= e($success['number']) ?> received. Estimated cost: <strong><?= e($success['estimate']['currency']) ?> <?= e(number_format($success['estimate']['amount'], 2)) ?></strong>. We will confirm the final price after review.</div>
        <p><a class="btn btn-primary" href="services.php">Explore services</a></p>
      <?php elseif (!$options): ?>
        <div class="alert error">The quote calculator is being configured. Please contact our team for an estimate.</div>
      <?php else: ?>
      <form method="post" class="form-grid" id="quoteForm">
        <?= csrf_field() ?>
        <div>
          <label for="pricing_key">Service</label>
          <select id="pricing_key" name="pricing_key" required>
            <option value="">Choose a service</option>
            <?php foreach ($options as $option): ?>
              <option value="<?= e($option['pricing_key']) ?>" <?= $selectedKey === $option['pricing_key'] ? 'selected' : '' ?>><?= e($option['label']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-row">
          <div><label for="name">Full name</label><input id="name" name="name" required maxlength="120" value="<?= e($_POST['name'] ?? '') ?>"></div>
          <div><label for="email">Email</label><input id="email" type="email" name="email" required maxlength="150" value="<?= e($_POST['email'] ?? '') ?>"></div>
        </div>
        <div class="form-row">
          <div><label for="phone">Phone</label><input id="phone" name="phone" maxlength="40" value="<?= e($_POST['phone'] ?? '') ?>"></div>
          <div><label for="location">Service location</label><input id="location" name="location" required maxlength="180" value="<?= e($_POST['location'] ?? '') ?>"></div>
        </div>
        <div class="form-row">
          <div><label for="quantity">Guards / rooms / visits</label><input id="quantity" type="number" name="quantity" min="1" max="1000" value="<?= e($_POST['quantity'] ?? '1') ?>"></div>
          <div><label for="hours">Hours per day</label><input id="hours" type="number" name="hours" min="1" max="24" value="<?= e($_POST['hours'] ?? '1') ?>"></div>
        </div>
        <div class="form-row">
          <div><label for="days">Number of days</label><input id="days" type="number" name="days" min="1" max="366" value="<?= e($_POST['days'] ?? '1') ?>"></div>
          <div><label for="frequency">Frequency</label><select id="frequency" name="frequency"><option value="one_off">One-off</option><option value="weekly">Weekly</option><option value="monthly">Monthly</option></select></div>
        </div>
        <div><label for="notes">Requirements and notes</label><textarea id="notes" name="notes" maxlength="2000" placeholder="Shift type, site details, access requirements, or anything else we should know."><?= e($_POST['notes'] ?? '') ?></textarea></div>
        <button type="submit" class="btn btn-primary">Calculate and submit request</button>
      </form>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
