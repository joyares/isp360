      <?php
      $navBasePath = isset($appBasePath) ? (string) $appBasePath : '';
      if ($navBasePath === '/') {
        $navBasePath = '';
      }
      ?>
        <nav class="navbar navbar-light navbar-vertical navbar-expand-xl">
          <script>
            var navbarStyle = localStorage.getItem("navbarStyle");
            if (navbarStyle && navbarStyle !== 'transparent') {
              document.querySelector('.navbar-vertical').classList.add(`navbar-${navbarStyle}`);
            }
          </script>
          <div class="d-flex align-items-center">
            <div class="toggle-icon-wrapper">
              <button class="btn navbar-toggler-humburger-icon navbar-toggler navbar-vertical-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#navbarVerticalCollapse" aria-controls="navbarVerticalCollapse" aria-expanded="false" aria-label="Toggle Navigation" title="Toggle Navigation"><span class="navbar-toggle-icon"><span class="toggle-line"></span></span></button>
            </div><a class="navbar-brand" href="<?= $navBasePath ?>/index.php">
              <?php
              $isCompanyContext = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'staff' && !empty($_SESSION['company_name']);
              
              if ($isCompanyContext) {
                  // Fetch the main logo directly from DB to prevent stale session issues
                  $mainLogoDb = '';
                  if (!empty($_SESSION['company_id'])) {
                      try {
                          $pdoSide = \App\Core\Database::getConnection();
                          $stmtSide = $pdoSide->prepare('SELECT logo_main FROM companies WHERE id = :id LIMIT 1');
                          $stmtSide->bindValue(':id', $_SESSION['company_id']);
                          $stmtSide->execute();
                          $mainLogoDb = (string) $stmtSide->fetchColumn();
                      } catch (\Throwable $e) {}
                  }

                  // Use DB value if found, otherwise session
                  $activeMainLogo = $mainLogoDb !== '' ? $mainLogoDb : ($_SESSION['company_logo_main'] ?? '');

                  if ($activeMainLogo !== '') {
                      $mainLogoUrl = $appBasePath . '/' . ltrim($activeMainLogo, '/');
                      echo '<div class="d-flex align-items-center py-3 justify-content-center" style="width: 100%;">';
                      echo '<img src="' . htmlspecialchars($mainLogoUrl, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($_SESSION['company_name'], ENT_QUOTES, 'UTF-8') . '" style="max-width: 100%; max-height: 40px; object-fit: contain;" />';
                      echo '</div>';
                  } else {
                      // Fallback if NO main logo exists at all
                      $sidebarCompanyName = $_SESSION['company_name'];
                      $sidebarCompanyLogo = !empty($_SESSION['company_logo']) ? $appBasePath . '/' . ltrim($_SESSION['company_logo'], '/') : $navBasePath . '/assets/img/icons/spot-illustrations/falcon.png';
                      ?>
                      <div class="d-flex align-items-center py-3">
                        <img class="me-2" src="<?= htmlspecialchars($sidebarCompanyLogo, ENT_QUOTES, 'UTF-8') ?>" alt="" style="height: 32px; width: auto; object-fit: contain;" />
                        <span class="font-sans-serif text-primary fw-bolder fs-6"><?= htmlspecialchars($sidebarCompanyName, ENT_QUOTES, 'UTF-8') ?></span>
                      </div>
                      <?php
                  }
              } else {
                  // Admin context
                  $sidebarCompanyName = 'isp360';
                  $sidebarCompanyLogo = $navBasePath . '/assets/img/icons/spot-illustrations/falcon.png';
                  ?>
                  <div class="d-flex align-items-center py-3">
                    <img class="me-2" src="<?= htmlspecialchars($sidebarCompanyLogo, ENT_QUOTES, 'UTF-8') ?>" alt="" style="height: 32px; width: auto; object-fit: contain;" />
                    <span class="font-sans-serif text-primary fw-bolder fs-6"><?= htmlspecialchars($sidebarCompanyName, ENT_QUOTES, 'UTF-8') ?></span>
                  </div>
                  <?php
              }
              ?>
            </a>
          </div>
          <div class="collapse navbar-collapse" id="navbarVerticalCollapse">
            <div class="navbar-vertical-content scrollbar">
              <ul class="navbar-nav flex-column mb-3" id="navbarVerticalNav" data-app-base="<?= htmlspecialchars($navBasePath, ENT_QUOTES, 'UTF-8') ?>">
                <!-- Dashboard section -->
                <?php if (has_permission('index') || has_permission('dashboard')): ?>
                <li class="nav-item">
                  <a class="nav-link" href="<?= $navBasePath ?>/index.php" role="button">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-chart-pie"></span></span><span class="nav-link-text ps-1">Dashboard</span></div>
                  </a>
                </li>
                <?php endif; ?>
                <!-- Support Desk section -->
                <?php if (has_permission('support_desk')): ?>
                <li class="nav-item">
                  <a class="nav-link dropdown-indicator collapsed" href="#support-desk" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="support-desk">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-ticket-alt"></span></span><span class="nav-link-text ps-1">Support desk</span></div>
                  </a>
                  <ul class="nav collapse" id="support-desk">
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/support-desk/all-tickets.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">All Tickets</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/support-desk/add-ticket.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Add Ticket</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/support-desk/ticket-mgmt.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Ticket Mgmt</span></div></a></li>
                  </ul>
                </li>
                <?php endif; ?>
                <?php if (has_permission('customer') || has_permission('customers')): ?>
                <li class="nav-item">
                  <a class="nav-link dropdown-indicator collapsed" href="#customer" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="customer">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-users"></span></span><span class="nav-link-text ps-1">Customer</span></div>
                  </a>
                  <ul class="nav collapse" id="customer">
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/support-desk/customers.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Customers</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/support-desk/customer-registration.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Customer Registration</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/support-desk/customer-details.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Customer details</span></div></a></li>
                  </ul>
                </li>
                <?php endif; ?>
                <?php if (has_permission('inventory')): ?>
                <li class="nav-item">
                  <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
                  <div class="col-auto navbar-vertical-label">App</div>
                  <div class="col ps-0">
                    <hr class="mb-0 navbar-vertical-divider">
                  </div>
                </div>
                  <a class="nav-link dropdown-indicator collapsed" href="#e-commerce" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="e-commerce"><div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-shopping-cart"></span></span><span class="nav-link-text ps-1">Inventory</span></div></a>
                  <ul class="nav collapse" id="e-commerce">
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/inventory/vendors-suppliers.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Vendors & Suppliers</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/inventory/product-categories.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Product Categories</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/inventory/products.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Products</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/inventory/add-stock.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Add Stock</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/inventory/manage-stock.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Manage Stock</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/inventory/stock-checkout.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Stock Checkout</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/inventory/stock-checkin.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Stock Checkin</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/inventory/edit-stock.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Edit Invoice</span></div></a></li>

                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/inventory/stock-upload-history.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Stock Upload History</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/inventory/stock-items.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Stock Items</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/inventory/bulk-transfer.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Bulk Transfer</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/inventory/transfer-history.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Transfer History</span></div></a></li>
                  </ul>
                </li>
                <?php endif; ?>

                <?php if (has_permission('finance')): ?>
                <li class="nav-item"><a class="nav-link dropdown-indicator collapsed" href="#finance" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="finance"><div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-wallet"></span></span><span class="nav-link-text ps-1">Finance</span></div></a>
                  <ul class="nav collapse" id="finance">
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/finance/expense-management.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Expense Management</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/finance/income-management.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Income Management</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/finance/category.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Category</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/finance/account-list.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Account List</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/finance/transactions.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Transactions</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/finance/transfer.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Transfer</span></div></a></li>
                  </ul>
                </li>
                <?php endif; ?>
                <?php if (has_permission('hr_payroll')): ?>
                <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/hr-payroll/hr-payroll.php"><div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-users-cog"></span></span><span class="nav-link-text ps-1">HR &amp; Payroll</span></div></a></li>
                <?php endif; ?>
                <?php if (has_permission('reports')): ?>
                <li class="nav-item"><a class="nav-link dropdown-indicator collapsed" href="#reports" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="reports"><div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-file-alt"></span></span><span class="nav-link-text ps-1">Reports</span></div></a>
                  <ul class="nav collapse" id="reports">
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/reports/proforma-invoices.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Proforma Invoices</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/reports/payment-history.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Payment History</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/reports/credit-debit-notes.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Credit &amp; Debit Notes</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/reports/online-payments.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Online Payments</span></div></a></li>
                  </ul>
                </li>
                <?php endif; ?>
                <?php if (has_permission('logs')): ?>
                <li class="nav-item"><a class="nav-link dropdown-indicator collapsed" href="#logs" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="logs"><div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-history"></span></span><span class="nav-link-text ps-1">Logs</span></div></a>
                  <ul class="nav collapse" id="logs">
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/logs/audit-logs.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Audit Logs</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/logs/login-history.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Login History</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/logs/login-fail-attempts.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Login Fail Attempts</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/logs/tickets-logs.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Tickets Logs</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/logs/user-logs.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">User logs</span></div></a></li>
                  </ul>
                </li>
                <?php endif; ?>
                <?php if (has_permission('administration')): ?>
                <li class="nav-item"><a class="nav-link dropdown-indicator collapsed" href="#administration" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="administration"><div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-user-shield"></span></span><span class="nav-link-text ps-1">Administration</span></div></a>
                  <ul class="nav collapse" id="administration">
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/administration/locations.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Locations</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/administration/roles.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Roles</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/administration/admin-users.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Admin Users</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/administration/staff-users.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Staff Users</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/administration/time-zone.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Time Zone</span></div></a></li>
                  </ul>
                </li>
                <?php endif; ?>
                <?php if (has_permission('company_mgmt')): ?>
                <li class="nav-item"><a class="nav-link dropdown-indicator collapsed" href="#company-mgmt" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="company-mgmt"><div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-building"></span></span><span class="nav-link-text ps-1">Company Mgmt</span></div></a>
                  <ul class="nav collapse" id="company-mgmt">
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/administration/my-company.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">My Company</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/administration/branches.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Branches</span></div></a></li>
                  </ul>
                </li>
                <?php endif; ?>
                <?php if (has_permission('my_profile') || has_permission('profile')): ?>
                <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/pages/user/profile.php"><div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-user-circle"></span></span><span class="nav-link-text ps-1">My Profile</span></div></a></li>
                <?php endif; ?>
                  <?php if (has_permission('settings') || has_permission('settings_html')): ?>
                  <li class="nav-item">
                    <a class="nav-link dropdown-indicator collapsed" href="#settingsMenu" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="settingsMenu">
                      <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-cogs"></span></span><span class="nav-link-text ps-1">Settings</span></div>
                    </a>
                    <ul class="nav collapse" id="settingsMenu">
                      <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/settings/general.php">General</a></li>
                      <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/settings/notifications.php">Notifications</a></li>
                      <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/settings/sms.php">SMS</a></li>
                      <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/settings/email.php">Email</a></li>
                    </ul>
                  </li>
                  <?php endif; ?>
              </ul>
            </div>
          </div>
          <script>
          (function () {
            var navRoot = document.getElementById('navbarVerticalNav');
            if (!navRoot) return;

            var basePath = navRoot.getAttribute('data-app-base') || '';
            if (basePath) {
              var oldPrefix = '/isp-views/';
              var newPrefix = basePath.replace(/\/+$/, '') + '/';
              navRoot.querySelectorAll('a[href^="' + oldPrefix + '"]').forEach(function (link) {
                var href = link.getAttribute('href');
                if (!href) return;
                link.setAttribute('href', href.replace(oldPrefix, newPrefix));
              });
            }

            var currentPath = window.location.pathname.toLowerCase().replace(/\/+$/, '');
            if (currentPath === '') currentPath = '/';

            var links = navRoot.querySelectorAll('a.nav-link[href]');
            var activeLink = null;

            for (var i = 0; i < links.length; i++) {
              var link = links[i];
              var href = link.getAttribute('href') || '';
              if (!href || href.charAt(0) === '#') continue;

              var linkPath = new URL(href, window.location.origin).pathname.toLowerCase().replace(/\/+$/, '');
              if (linkPath === '') linkPath = '/';

              if (linkPath === currentPath) {
                activeLink = link;
                break;
              }
            }

            if (!activeLink) return;

            activeLink.classList.add('active');
            activeLink.setAttribute('aria-current', 'page');

            var parentCollapse = activeLink.closest('ul.collapse');
            while (parentCollapse && parentCollapse.id) {
              parentCollapse.classList.add('show');

              var toggle = navRoot.querySelector('a.nav-link.dropdown-indicator[href="#' + parentCollapse.id + '"]');
              if (!toggle) {
                break;
              }

              toggle.classList.remove('collapsed');
              toggle.setAttribute('aria-expanded', 'true');
              parentCollapse = toggle.closest('ul.collapse');
            }
          })();
          </script>
        </nav>




