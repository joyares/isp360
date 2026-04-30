<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Helpers' . DIRECTORY_SEPARATOR . 'rbac_helper.php';

$navBasePath = $navBasePath ?? '';
?>
<nav class="navbar navbar-light navbar-vertical navbar-expand-xl">
  <div class="d-flex align-items-center">
    <a class="navbar-brand" href="<?= htmlspecialchars($navBasePath, ENT_QUOTES, 'UTF-8') ?>/index.php">
      <div class="d-flex align-items-center py-3">
        <span class="font-sans-serif text-primary">isp360</span>
      </div>
    </a>
  </div>

  <div class="collapse navbar-collapse show" id="navbarVerticalCollapse">
    <div class="navbar-vertical-content scrollbar">
      <ul class="navbar-nav flex-column mb-3" id="navbarVerticalNav">
        <li class="nav-item">
          <a class="nav-link" href="<?= htmlspecialchars($navBasePath, ENT_QUOTES, 'UTF-8') ?>/index.php" role="button">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-chart-pie"></span></span>
              <span class="nav-link-text ps-1">Dashboard</span>
            </div>
          </a>
        </li>

        <?php if (has_permission('inventory_add')): ?>
          <li class="nav-item">
            <a class="nav-link" href="<?= htmlspecialchars($navBasePath, ENT_QUOTES, 'UTF-8') ?>/app/inventory/products.php">
              <div class="d-flex align-items-center">
                <span class="nav-link-icon"><span class="fas fa-shopping-cart"></span></span>
                <span class="nav-link-text ps-1">Inventory</span>
              </div>
            </a>
          </li>
        <?php endif; ?>

        <?php if (has_permission('finance_edit_invoice')): ?>
          <li class="nav-item">
            <a class="nav-link" href="<?= htmlspecialchars($navBasePath, ENT_QUOTES, 'UTF-8') ?>/app/finance/transactions.php">
              <div class="d-flex align-items-center">
                <span class="nav-link-icon"><span class="fas fa-wallet"></span></span>
                <span class="nav-link-text ps-1">Finance</span>
              </div>
            </a>
          </li>
        <?php endif; ?>
      </ul>
      <ul class="navbar-nav flex-column mb-3">
        <?php if (has_permission('settings_manage')): ?>
        <li class="nav-item">
          <a class="nav-link" href="#adminMenu" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="adminMenu">
            <div class="d-flex align-items-center">
              <span class="nav-link-icon"><span class="fas fa-user-cog"></span></span>
              <span class="nav-link-text ps-1">Administration</span>
            </div>
          </a>
          <div class="collapse" id="adminMenu">
            <ul class="nav flex-column ms-3">

                <li class="nav-item">
                  <a class="nav-link" href="<?= htmlspecialchars($navBasePath, ENT_QUOTES, 'UTF-8') ?>/app/administration/staff-users.php">
                    <span class="nav-link-text">Staff Users</span>
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="<?= htmlspecialchars($navBasePath, ENT_QUOTES, 'UTF-8') ?>/app/administration/time-zone.php">
                    <span class="nav-link-text">Time zone</span>
                  </a>
                </li>

            </ul>
          </div>
        </li>
        <?php endif; ?>
      </ul>
        <ul class="navbar-nav flex-column mb-3">
          <?php if (has_permission('settings_manage')): ?>
          <li class="nav-item">
            <a class="nav-link" href="#settingsMenu" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="settingsMenu">
              <div class="d-flex align-items-center">
                <span class="nav-link-icon"><span class="fas fa-cogs"></span></span>
                <span class="nav-link-text ps-1">Settings</span>
              </div>
            </a>
            <ul class="nav collapse ms-3" id="settingsMenu">
              <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($navBasePath, ENT_QUOTES, 'UTF-8') ?>/app/settings/general.php">General</a></li>
              <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($navBasePath, ENT_QUOTES, 'UTF-8') ?>/app/settings/notifications.php">Notifications</a></li>
              <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($navBasePath, ENT_QUOTES, 'UTF-8') ?>/app/settings/sms.php">SMS</a></li>
              <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($navBasePath, ENT_QUOTES, 'UTF-8') ?>/app/settings/email.php">Email</a></li>
            </ul>
          </li>
          <?php endif; ?>
        </ul>
    </div>
  </div>
</nav>
