<?php
require_once __DIR__ . '/../../includes/header.php';

$currentTz = date_default_timezone_get();
$currentTime = date('Y-m-d H:i:s');

$alert = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['timezone'])) {
    $tz = $_POST['timezone'];
    if (in_array($tz, timezone_identifiers_list())) {
        // Save to .env
        $envPath = dirname(__DIR__, 2) . '/.env';
        if (file_exists($envPath)) {
            $env = file_get_contents($envPath);
            if (preg_match('/^APP_TIMEZONE=/m', $env)) {
                $env = preg_replace('/^APP_TIMEZONE=.*$/m', 'APP_TIMEZONE=' . $tz, $env);
            } else {
                $env .= "\nAPP_TIMEZONE=" . $tz;
            }
            file_put_contents($envPath, $env);
        }
        
        date_default_timezone_set($tz);
        $currentTz = $tz;
        $currentTime = date('Y-m-d H:i:s');
        $alert = ['type' => 'success', 'message' => 'Timezone updated to ' . $tz];
    } else {
        $alert = ['type' => 'danger', 'message' => 'Invalid timezone selected.'];
    }
}
?>

<div class="card mb-3">
  <div class="card-header">
    <h5 class="mb-0">Timezone Settings</h5>
  </div>
  <div class="card-body bg-body-tertiary">
    <?php if ($alert): ?>
      <div class="alert alert-<?= $alert['type'] ?> py-2" role="alert">
        <?= $alert['message'] ?>
      </div>
    <?php endif; ?>

    <form method="post" class="row g-3">
            <?= ispts_csrf_field() ?>
      <div class="col-md-6">
        <label for="timezone" class="form-label">Select Timezone</label>
        <select class="form-select js-choice" id="timezone" name="timezone" data-options='{"removeItemButton":true,"placeholder":true}'>
          <?php foreach (timezone_identifiers_list() as $tz): ?>
            <option value="<?= htmlspecialchars($tz) ?>" <?= $tz === $currentTz ? 'selected' : '' ?>><?= htmlspecialchars($tz) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12">
        <button type="submit" class="btn btn-primary">Update Timezone</button>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <div class="row">
      <div class="col-auto">
        <h6 class="text-700">Current Timezone:</h6>
        <p class="mb-0 fw-bold"><?= htmlspecialchars($currentTz) ?></p>
      </div>
      <div class="col-auto">
        <h6 class="text-700">Current Server Time:</h6>
        <p class="mb-0 fw-bold"><?= htmlspecialchars($currentTime) ?></p>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
