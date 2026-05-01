<?php
/**
 * Coming Soon Module Placeholder
 * 
 * Include this partial from any stub page. It renders a clean "Coming Soon"
 * card inside the standard header/footer layout.
 *
 * Usage:
 *   $pageTitle = 'Audit Logs';
 *   $pageIcon  = 'fas fa-clipboard-list';   // optional
 *   $pageHint  = 'Track all system events.'; // optional
 *   require '../../includes/coming-soon.php';
 *
 * Prerequisites: auth.php must already be included so $appBasePath is available.
 */

// Require header (handles sidebar + topnav)
require __DIR__ . '/header.php';
?>

<nav class="mb-2" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
    <li class="breadcrumb-item active"><?= htmlspecialchars($pageTitle ?? 'Module') ?></li>
  </ol>
</nav>

<div class="card mt-3">
  <div class="card-body text-center py-6">
    <div class="mb-4">
      <span class="<?= htmlspecialchars($pageIcon ?? 'fas fa-tools') ?> text-400" style="font-size: 4rem;"></span>
    </div>
    <h3 class="text-800 fw-semi-bold">Coming Soon</h3>
    <p class="text-600 mb-1 fs-10"><?= htmlspecialchars($pageTitle ?? 'This module') ?> is under development.</p>
    <?php if (!empty($pageHint)): ?>
      <p class="text-500 fs-11 mb-0"><?= htmlspecialchars($pageHint) ?></p>
    <?php endif; ?>
    <a href="javascript:history.back()" class="btn btn-falcon-default btn-sm mt-4">
      <span class="fas fa-arrow-left me-1"></span>Go Back
    </a>
  </div>
</div>

<?php
require __DIR__ . '/footer.php';
