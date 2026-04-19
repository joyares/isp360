      <?php
      $navBasePath = isset($appBasePath) ? $appBasePath : '';
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
              <div class="d-flex align-items-center py-3"><img class="me-2" src="<?= $navBasePath ?>/assets/img/icons/spot-illustrations/falcon.png" alt="" width="40" /><span class="font-sans-serif text-primary">isp360</span></div>
            </a>
          </div>
          <div class="collapse navbar-collapse" id="navbarVerticalCollapse">
            <div class="navbar-vertical-content scrollbar">
              <ul class="navbar-nav flex-column mb-3" id="navbarVerticalNav" data-app-base="<?= htmlspecialchars($navBasePath, ENT_QUOTES, 'UTF-8') ?>">
                <!-- Dashboard section -->
                <li class="nav-item">
                  <a class="nav-link" href="<?= $navBasePath ?>/index.php" role="button">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-chart-pie"></span></span><span class="nav-link-text ps-1">Dashboard</span></div>
                  </a>
                </li>
                <!-- Support Desk section -->
                <li class="nav-item">
                  <a class="nav-link dropdown-indicator collapsed" href="#support-desk" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="support-desk">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-ticket-alt"></span></span><span class="nav-link-text ps-1">Support desk</span></div>
                  </a>
                  <ul class="nav collapse" id="support-desk">
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/support-desk/all-tickets.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">All Tickets</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/support-desk/add-ticket.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Add Ticket</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/support-desk/ticket-mgmt.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Ticket Mgmt</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/support-desk/card-view.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Card view</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/support-desk/customers.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Customers</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/support-desk/customer-details.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Customer details</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/support-desk/tickets-preview.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Tickets preview</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/support-desk/quick-links.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Quick links</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/support-desk/reports.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Reports</span></div></a></li>
                  </ul>
                </li>
                <li class="nav-item">
                  <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
                  <div class="col-auto navbar-vertical-label">Apppp</div>
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
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/inventory/inventory-locations.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Inventory Locations</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/inventory/stock-upload-history.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Stock Upload History</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/inventory/stock-items.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Stock Items</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/inventory/bulk-transfer.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Bulk Transfer</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/inventory/transfer-history.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Transfer History</span></div></a></li>
                  </ul>
                </li>
                <li class="nav-item"><a class="nav-link dropdown-indicator collapsed" href="#user" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="user"><div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-user"></span></span><span class="nav-link-text ps-1">Partners</span></div></a>
                  <ul class="nav collapse" id="user">
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/partners/partner-mgmt.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Partner Mgmt</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/partners/partner-list.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Partner List</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/partners/add-partner.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Add Partner</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/partners/deposit-reports.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Deposit Reports</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/partners/payment-reports.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Payment Reports</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/partners/partner-wallet.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Partner Wallet</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/partners/partner-settlement.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Partner Settlement</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/partners/revenue-report.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Revenue Report</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/partners/share-price-mgmt.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Share &amp; Price Mgmt</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/partners/renewal-report.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Renewal Report</span></div></a></li>
                  </ul>
                </li>
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
                <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/hr-payroll/hr-payroll.php"><div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-users-cog"></span></span><span class="nav-link-text ps-1">HR &amp; Payroll</span></div></a></li>
                <li class="nav-item"><a class="nav-link dropdown-indicator collapsed" href="#reports" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="reports"><div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-file-alt"></span></span><span class="nav-link-text ps-1">Reports</span></div></a>
                  <ul class="nav collapse" id="reports">
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/reports/proforma-invoices.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Proforma Invoices</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/reports/payment-history.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Payment History</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/reports/credit-debit-notes.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Credit &amp; Debit Notes</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/reports/online-payments.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Online Payments</span></div></a></li>
                  </ul>
                </li>
                <li class="nav-item"><a class="nav-link dropdown-indicator collapsed" href="#logs" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="logs"><div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-history"></span></span><span class="nav-link-text ps-1">Logs</span></div></a>
                  <ul class="nav collapse" id="logs">
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/logs/audit-logs.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Audit Logs</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/logs/login-history.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Login History</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/logs/login-fail-attempts.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Login Fail Attempts</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/logs/tickets-logs.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Tickets Logs</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/logs/user-logs.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">User logs</span></div></a></li>
                  </ul>
                </li>
                <li class="nav-item"><a class="nav-link dropdown-indicator" href="#administration" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="administration"><div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-user-shield"></span></span><span class="nav-link-text ps-1">Administration</span></div></a>
                  <ul class="nav collapse" id="administration">
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/administration/branches.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Branches</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/administration/locations.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Locations</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/administration/roles.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Roles</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/administration/admin-users.php"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Admin Users</span></div></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/app/administration/admin-users.php#add-admin-user"><div class="d-flex align-items-center"><span class="nav-link-text ps-1">Add Admin User</span></div></a></li>
                  </ul>
                </li>
                <li class="nav-item"><a class="nav-link" href="<?= $navBasePath ?>/pages/user/settings.html"><div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-cog"></span></span><span class="nav-link-text ps-1">Settings</span></div></a></li>
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
            if (!parentCollapse || !parentCollapse.id) return;

            parentCollapse.classList.add('show');

            var toggle = navRoot.querySelector('a.nav-link.dropdown-indicator[href="#' + parentCollapse.id + '"]');
            if (!toggle) return;

            toggle.classList.remove('collapsed');
            toggle.setAttribute('aria-expanded', 'true');
          })();
          </script>
        </nav>




