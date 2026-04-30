<?php
// Timezone settings page
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidenav.php';

$currentTz = date_default_timezone_get();
$currentTime = date('Y-m-d H:i:s');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['timezone'])) {
    $tz = $_POST['timezone'];
    if (in_array($tz, timezone_identifiers_list())) {
        // Save to .env
        $envPath = dirname(__DIR__, 2) . '/.env';
        $env = file_get_contents($envPath);
        $env = preg_replace('/^APP_TIMEZONE=.*$/m', 'APP_TIMEZONE=' . $tz, $env);
        file_put_contents($envPath, $env);
        date_default_timezone_set($tz);
        $currentTz = $tz;
        $currentTime = date('Y-m-d H:i:s');
        echo '<div class="alert alert-success">Timezone updated!</div>';
    }
}
?>
<div class="container mt-4">
  <h3>Timezone Settings</h3>
  <form method="post">
    <div class="mb-3">
      <label for="timezone" class="form-label">Select Timezone</label>
      <select class="form-select" id="timezone" name="timezone">
        <?php foreach (timezone_identifiers_list() as $tz): ?>
          <option value="<?= htmlspecialchars($tz) ?>" <?= $tz === $currentTz ? 'selected' : '' ?>><?= htmlspecialchars($tz) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="btn btn-primary">Update Timezone</button>
  </form>
  <div class="mt-3">
    <strong>Current Timezone:</strong> <?= htmlspecialchars($currentTz) ?><br>
    <strong>Current Time:</strong> <?= htmlspecialchars($currentTime) ?>
  </div>
</div>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
