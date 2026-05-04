<?php
// Main Dashboard Page using modular partials
$skipSharedChrome = true;
require 'includes/header.php';

// Fetch real data for Total Payment Chart
$pdo = App\Core\Database::getConnection();
ispts_ensure_customers_table($pdo);

// Fetch active accounts for the filter
$accounts = $pdo->query("SELECT account_id, account_name FROM finance_accounts WHERE status = 1 ORDER BY account_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Determine selected account (Default to FO main account)
$selectedAccountId = isset($_GET['account_id']) ? (int) $_GET['account_id'] : null;
if (!$selectedAccountId) {
  // Find "Friendsonline Main Account" as default
  foreach ($accounts as $acc) {
    if (stripos($acc['account_name'], 'Friendsonline Main Account') !== false || stripos($acc['account_name'], 'FO main') !== false) {
      $selectedAccountId = (int) $acc['account_id'];
      break;
    }
  }
}
// Fallback to first account if default not found
if (!$selectedAccountId && !empty($accounts)) {
  $selectedAccountId = (int) $accounts[0]['account_id'];
}

$currentYear = (int) date('Y');
$stmt = $pdo->prepare("
    SELECT 
        MONTH(income_date) as m, 
        DAY(income_date) as d, 
        SUM(grand_total) as total
    FROM finance_incomes
    WHERE payment_status = 'paid' AND status = 1 
      AND YEAR(income_date) = :year 
      AND account_id = :account_id
    GROUP BY m, d
    ORDER BY m, d
");
$stmt->execute([':year' => $currentYear, ':account_id' => $selectedAccountId]);
$dbData = $stmt->fetchAll(PDO::FETCH_ASSOC);

$formattedChartData = [];
for ($m = 1; $m <= 12; $m++) {
  $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $m, $currentYear);
  $monthLabels = [];
  $monthValues = [];

  $dayMap = [];
  foreach ($dbData as $row) {
    if ((int) $row['m'] === $m) {
      $dayMap[(int) $row['d']] = (float) $row['total'];
    }
  }

  for ($d = 1; $d <= $daysInMonth; $d++) {
    $monthLabels[] = sprintf('%04d-%02d-%02d', $currentYear, $m, $d);
    $monthValues[] = $dayMap[$d] ?? 0;
  }

  $formattedChartData[$m - 1] = [
    'labels' => $monthLabels,
    'values' => $monthValues
  ];
}
$chartDataJson = json_encode($formattedChartData);

// Fetch real data for Total Expense Chart
$expenseStmt = $pdo->prepare("
    SELECT 
        MONTH(expense_date) as m, 
        DAY(expense_date) as d, 
        SUM(amount) as total
    FROM finance_expenses
    WHERE YEAR(expense_date) = :year 
      AND account_id = :account_id
    GROUP BY m, d
    ORDER BY m, d
");
$expenseStmt->execute([':year' => $currentYear, ':account_id' => $selectedAccountId]);
$dbExpenseData = $expenseStmt->fetchAll(PDO::FETCH_ASSOC);

$formattedExpenseChartData = [];
for ($m = 1; $m <= 12; $m++) {
  $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $m, $currentYear);
  $monthLabels = [];
  $monthValues = [];

  $dayMap = [];
  foreach ($dbExpenseData as $row) {
    if ((int) $row['m'] === $m) {
      $dayMap[(int) $row['d']] = (float) $row['total'];
    }
  }

  for ($d = 1; $d <= $daysInMonth; $d++) {
    $monthLabels[] = sprintf('%04d-%02d-%02d', $currentYear, $m, $d);
    $monthValues[] = $dayMap[$d] ?? 0;
  }

  $formattedExpenseChartData[$m - 1] = [
    'labels' => $monthLabels,
    'values' => $monthValues
  ];
}
$expenseChartDataJson = json_encode($formattedExpenseChartData);

// Fetch real data for Total Customers Chart
$customerStats = $pdo->query("
    SELECT 
        COALESCE(c.company_id, b.partner_id) as company_id,
        SUM(CASE WHEN c.status = 1 THEN 1 ELSE 0 END) as active
    FROM customers c
    LEFT JOIN branches b ON c.branch_id = b.branch_id
    WHERE COALESCE(c.company_id, b.partner_id) IN (1, 2)
    GROUP BY 1
")->fetchAll(PDO::FETCH_ASSOC);

$friendsonlineActive = 0;
$bestnetActive = 0;

foreach ($customerStats as $stat) {
  if ((int) $stat['company_id'] === 1)
    $friendsonlineActive = (int) $stat['active'];
  if ((int) $stat['company_id'] === 2)
    $bestnetActive = (int) $stat['active'];
}

$totalActiveCustomers = $friendsonlineActive + $bestnetActive;

// Fetch Total Payment (all time) for selected account
$totalPaymentAllTimeStmt = $pdo->prepare("SELECT SUM(grand_total) FROM finance_incomes WHERE payment_status = 'paid' AND status = 1 AND account_id = :account_id");
$totalPaymentAllTimeStmt->execute(['account_id' => $selectedAccountId]);
$totalPaymentAllTime = (float) $totalPaymentAllTimeStmt->fetchColumn();

// Fetch Weekly Payment Data (Last 7 days) for selected account
$weeklyPaymentStmt = $pdo->prepare("
    SELECT 
        DATE(income_date) as income_date, 
        SUM(grand_total) as total
    FROM finance_incomes
    WHERE payment_status = 'paid' AND status = 1 
      AND account_id = :account_id
      AND income_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 DAY)
    GROUP BY DATE(income_date)
    ORDER BY income_date ASC
");
$weeklyPaymentStmt->execute(['account_id' => $selectedAccountId]);
$weeklyPaymentRaw = $weeklyPaymentStmt->fetchAll(PDO::FETCH_ASSOC);

$weeklyPaymentValues = [];
$weeklyPaymentLabels = [];
for ($i = 0; $i <= 6; $i++) {
  $dateObj = new DateTime("-$i days");
  $date = $dateObj->format('Y-m-d');
  $label = $dateObj->format('D');
  $weeklyPaymentLabels[] = $label;
  $val = 0;
  foreach ($weeklyPaymentRaw as $row) {
    if ($row['income_date'] === $date) {
      $val = (float) $row['total'];
      break;
    }
  }
  $weeklyPaymentValues[] = $val;
}
$weeklyPaymentTotal = array_sum($weeklyPaymentValues);
$weeklyPaymentJson = json_encode($weeklyPaymentValues);
$weeklyLabelsJson = json_encode($weeklyPaymentLabels);

// Fetch Total Expense (all time)
$totalExpenseAllTime = (float) $pdo->query("SELECT SUM(amount) FROM finance_expenses WHERE 1=1")->fetchColumn();

// Fetch Weekly Expense Data (Last 7 days)
$weeklyExpenseStmt = $pdo->query("
    SELECT 
        DATE(expense_date) as expense_date, 
        SUM(amount) as total
    FROM finance_expenses
    WHERE 1=1 
      AND expense_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 DAY)
    GROUP BY DATE(expense_date)
    ORDER BY expense_date ASC
");
$weeklyExpenseRaw = $weeklyExpenseStmt->fetchAll(PDO::FETCH_ASSOC);

$weeklyExpenseValues = [];
$weeklyExpenseLabels = [];
for ($i = 0; $i <= 6; $i++) {
  $dateObj = new DateTime("-$i days");
  $date = $dateObj->format('Y-m-d');
  $label = $dateObj->format('D');
  $weeklyExpenseLabels[] = $label;
  $val = 0;
  foreach ($weeklyExpenseRaw as $row) {
    if ($row['expense_date'] === $date) {
      $val = (float) $row['total'];
      break;
    }
  }
  $weeklyExpenseValues[] = $val;
}
$weeklyExpenseTotal = array_sum($weeklyExpenseValues);
$weeklyExpenseJson = json_encode($weeklyExpenseValues);
$weeklyExpenseLabelsJson = json_encode($weeklyExpenseLabels);

$marketShareData = [
  ['value' => $friendsonlineActive, 'name' => 'Friendsonline'],
  ['value' => $bestnetActive, 'name' => 'Bestnet']
];
$marketShareJson = json_encode($marketShareData);
?>
<?php require 'includes/sidenav.php'; ?>

<div class="content">
  <script>
    if (localStorage.getItem('theme') === 'auto') {
      var preferredTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
      localStorage.setItem('theme', preferredTheme);
      document.documentElement.setAttribute('data-bs-theme', preferredTheme);
    }
  </script>
  <nav class="navbar navbar-light navbar-glass navbar-top navbar-expand">
    <button class="btn navbar-toggler-humburger-icon navbar-toggler me-1 me-sm-3" type="button"
      data-bs-toggle="collapse" data-bs-target="#navbarVerticalCollapse" aria-controls="navbarVerticalCollapse"
      aria-expanded="false" aria-label="Toggle Navigation"><span class="navbar-toggle-icon"><span
          class="toggle-line"></span></span></button>
    <a class="navbar-brand me-1 me-sm-3" href="index-2.html">
      <div class="d-flex align-items-center"><img class="me-2" src="assets/img/icons/spot-illustrations/falcon.png"
          alt="" width="40" /><span class="font-sans-serif text-primary">isp360</span></div>
    </a>
    <ul class="navbar-nav align-items-center d-none d-lg-block">
      <li class="nav-item">
        <div class="search-box" data-list='{"valueNames":["title"]}'>
          <form class="position-relative" data-bs-toggle="search" data-bs-display="static"><input
              class="form-control search-input fuzzy-search" type="search" placeholder="Search..."
              aria-label="Search" />
            <span class="fas fa-search search-box-icon"></span>
          </form>
          <div class="btn-close-falcon-container position-absolute end-0 top-50 translate-middle shadow-none"
            data-bs-dismiss="search"><button class="btn btn-link btn-close-falcon p-0" aria-label="Close"></button>
          </div>
          <div class="dropdown-menu border font-base start-0 mt-2 py-0 overflow-hidden w-100">
            <div class="scrollbar list py-3" style="max-height: 24rem;">
              <h6 class="dropdown-header fw-medium text-uppercase px-x1 fs-11 pt-0 pb-2">Recently Browsed</h6><a
                class="dropdown-item fs-10 px-x1 py-1 hover-primary" href="app/events/event-detail.html">
                <div class="d-flex align-items-center">
                  <span class="fas fa-circle me-2 text-300 fs-11"></span>
                  <div class="fw-normal title">Pages <span class="fas fa-chevron-right mx-1 text-500 fs-11"
                      data-fa-transform="shrink-2"></span> Events</div>
                </div>
              </a>
              <a class="dropdown-item fs-10 px-x1 py-1 hover-primary" href="app/e-commerce/customers.html">
                <div class="d-flex align-items-center">
                  <span class="fas fa-circle me-2 text-300 fs-11"></span>
                  <div class="fw-normal title">E-commerce <span class="fas fa-chevron-right mx-1 text-500 fs-11"
                      data-fa-transform="shrink-2"></span> Customers</div>
                </div>
              </a>
              <hr class="text-200 dark__text-900" />
              <h6 class="dropdown-header fw-medium text-uppercase px-x1 fs-11 pt-0 pb-2">Suggested Filter</h6><a
                class="dropdown-item px-x1 py-1 fs-9" href="app/e-commerce/customers.html">
                <div class="d-flex align-items-center"><span
                    class="badge fw-medium text-decoration-none me-2 badge-subtle-warning">customers:</span>
                  <div class="flex-1 fs-10 title">All customers list</div>
                </div>
              </a>
              <a class="dropdown-item px-x1 py-1 fs-9" href="app/events/event-detail.html">
                <div class="d-flex align-items-center"><span
                    class="badge fw-medium text-decoration-none me-2 badge-subtle-success">events:</span>
                  <div class="flex-1 fs-10 title">Latest events in current month</div>
                </div>
              </a>
              <a class="dropdown-item px-x1 py-1 fs-9" href="app/e-commerce/product/product-grid.html">
                <div class="d-flex align-items-center"><span
                    class="badge fw-medium text-decoration-none me-2 badge-subtle-info">products:</span>
                  <div class="flex-1 fs-10 title">Most popular products</div>
                </div>
              </a>
              <hr class="text-200 dark__text-900" />
              <h6 class="dropdown-header fw-medium text-uppercase px-x1 fs-11 pt-0 pb-2">Files</h6><a
                class="dropdown-item px-x1 py-2" href="#!">
                <div class="d-flex align-items-center">
                  <div class="file-thumbnail me-2"><img class="border h-100 w-100 object-fit-cover rounded-3"
                      src="assets/img/products/3-thumb.png" alt="" /></div>
                  <div class="flex-1">
                    <h6 class="mb-0 title">iPhone</h6>
                    <p class="fs-11 mb-0 d-flex"><span class="fw-semi-bold">Antony</span><span
                        class="fw-medium text-600 ms-2">27 Sep at 10:30 AM</span></p>
                  </div>
                </div>
              </a>
              <a class="dropdown-item px-x1 py-2" href="#!">
                <div class="d-flex align-items-center">
                  <div class="file-thumbnail me-2"><img class="img-fluid" src="assets/img/icons/zip.png" alt="" /></div>
                  <div class="flex-1">
                    <h6 class="mb-0 title">Falcon v1.8.2</h6>
                    <p class="fs-11 mb-0 d-flex"><span class="fw-semi-bold">John</span><span
                        class="fw-medium text-600 ms-2">30 Sep at 12:30 PM</span></p>
                  </div>
                </div>
              </a>
              <hr class="text-200 dark__text-900" />
              <h6 class="dropdown-header fw-medium text-uppercase px-x1 fs-11 pt-0 pb-2">Members</h6><a
                class="dropdown-item px-x1 py-2" href="pages/user/profile.html">
                <div class="d-flex align-items-center">
                  <div class="avatar avatar-l status-online me-2">
                    <img class="rounded-circle" src="assets/img/team/1.jpg" alt="" />
                  </div>
                  <div class="flex-1">
                    <h6 class="mb-0 title">Anna Karinina</h6>
                    <p class="fs-11 mb-0 d-flex">Technext Limited</p>
                  </div>
                </div>
              </a>
              <a class="dropdown-item px-x1 py-2" href="pages/user/profile.html">
                <div class="d-flex align-items-center">
                  <div class="avatar avatar-l me-2">
                    <img class="rounded-circle" src="assets/img/team/2.jpg" alt="" />
                  </div>
                  <div class="flex-1">
                    <h6 class="mb-0 title">Antony Hopkins</h6>
                    <p class="fs-11 mb-0 d-flex">Brain Trust</p>
                  </div>
                </div>
              </a>
              <a class="dropdown-item px-x1 py-2" href="pages/user/profile.html">
                <div class="d-flex align-items-center">
                  <div class="avatar avatar-l me-2">
                    <img class="rounded-circle" src="assets/img/team/3.jpg" alt="" />
                  </div>
                  <div class="flex-1">
                    <h6 class="mb-0 title">Emma Watson</h6>
                    <p class="fs-11 mb-0 d-flex">Google</p>
                  </div>
                </div>
              </a>
            </div>
            <div class="text-center mt-n3">
              <p class="fallback fw-bold fs-8 d-none">No Result Found.</p>
            </div>
          </div>
        </div>
      </li>
    </ul>
    <ul class="navbar-nav navbar-nav-icons ms-auto flex-row align-items-center">
      <li class="nav-item ps-2 pe-0">
        <div class="dropdown theme-control-dropdown"><a
            class="nav-link d-flex align-items-center dropdown-toggle fa-icon-wait fs-9 pe-1 py-0" href="#"
            role="button" id="themeSwitchDropdown" data-bs-toggle="dropdown" aria-haspopup="true"
            aria-expanded="false"><span class="fas fa-sun fs-7" data-fa-transform="shrink-2"
              data-theme-dropdown-toggle-icon="light"></span><span class="fas fa-moon fs-7" data-fa-transform="shrink-3"
              data-theme-dropdown-toggle-icon="dark"></span></a>
          <div class="dropdown-menu dropdown-menu-end dropdown-caret border py-0 mt-3"
            aria-labelledby="themeSwitchDropdown">
            <div class="bg-white dark__bg-1000 rounded-2 py-2"><button
                class="dropdown-item d-flex align-items-center gap-2" type="button" value="light"
                data-theme-control="theme"><span class="fas fa-sun"></span>Light<span
                  class="fas fa-check dropdown-check-icon ms-auto text-600"></span></button>
              <button class="dropdown-item d-flex align-items-center gap-2" type="button" value="dark"
                data-theme-control="theme"><span class="fas fa-moon" data-fa-transform=""></span>Dark<span
                  class="fas fa-check dropdown-check-icon ms-auto text-600"></span></button>
            </div>
          </div>
        </div>
      </li>
      <li class="nav-item dropdown">
        <a class="nav-link notification-indicator notification-indicator-primary px-0 fa-icon-wait"
          id="navbarDropdownNotification" role="button" data-bs-toggle="dropdown" aria-haspopup="true"
          aria-expanded="false" data-hide-on-body-scroll="data-hide-on-body-scroll"><span class="fas fa-bell"
            data-fa-transform="shrink-6" style="font-size: 33px;"></span></a>
        <div
          class="dropdown-menu dropdown-caret dropdown-caret dropdown-menu-end dropdown-menu-card dropdown-menu-notification dropdown-caret-bg"
          aria-labelledby="navbarDropdownNotification">
          <div class="card card-notification shadow-none">
            <div class="card-header">
              <div class="row justify-content-between align-items-center">
                <div class="col-auto">
                  <h6 class="card-header-title mb-0">Notifications</h6>
                </div>
                <div class="col-auto ps-0 ps-sm-3"><a class="card-link fw-normal" href="#">Mark all as read</a></div>
              </div>
            </div>
            <div class="scrollbar-overlay" style="max-height:19rem">
              <div class="list-group list-group-flush fw-normal fs-10">
                <div class="list-group-title border-bottom">NEW</div>
                <div class="list-group-item">
                  <a class="notification notification-flush notification-unread" href="#!">
                    <div class="notification-avatar">
                      <div class="avatar avatar-2xl me-3">
                        <img class="rounded-circle" src="assets/img/team/1-thumb.png" alt="" />
                      </div>
                    </div>
                    <div class="notification-body">
                      <p class="mb-1"><strong>Emma Watson</strong> replied to your comment : "Hello world 😍"</p>
                      <span class="notification-time"><span class="me-2" role="img" aria-label="Emoji">💬</span>Just
                        now</span>
                    </div>
                  </a>
                </div>
                <div class="list-group-item">
                  <a class="notification notification-flush notification-unread" href="#!">
                    <div class="notification-avatar">
                      <div class="avatar avatar-2xl me-3">
                        <div class="avatar-name rounded-circle"><span>AB</span></div>
                      </div>
                    </div>
                    <div class="notification-body">
                      <p class="mb-1"><strong>Albert Brooks</strong> reacted to <strong>Mia Khalifa's</strong> status
                      </p>
                      <span class="notification-time"><span class="me-2 fab fa-gratipay text-danger"></span>9hr</span>
                    </div>
                  </a>
                </div>
                <div class="list-group-title border-bottom">EARLIER</div>
                <div class="list-group-item">
                  <a class="notification notification-flush" href="#!">
                    <div class="notification-avatar">
                      <div class="avatar avatar-2xl me-3">
                        <img class="rounded-circle" src="assets/img/icons/weather-sm.jpg" alt="" />
                      </div>
                    </div>
                    <div class="notification-body">
                      <p class="mb-1">The forecast today shows a low of 20&#8451; in California. See today's weather.
                      </p>
                      <span class="notification-time"><span class="me-2" role="img"
                          aria-label="Emoji">🌤️</span>1d</span>
                    </div>
                  </a>
                </div>
                <div class="list-group-item">
                  <a class="border-bottom-0 notification-unread  notification notification-flush" href="#!">
                    <div class="notification-avatar">
                      <div class="avatar avatar-xl me-3">
                        <img class="rounded-circle" src="assets/img/logos/oxford.png" alt="" />
                      </div>
                    </div>
                    <div class="notification-body">
                      <p class="mb-1"><strong>University of Oxford</strong> created an event : "Causal Inference Hilary
                        2019"</p>
                      <span class="notification-time"><span class="me-2" role="img"
                          aria-label="Emoji">✌️</span>1w</span>
                    </div>
                  </a>
                </div>
                <div class="list-group-item">
                  <a class="border-bottom-0 notification notification-flush" href="#!">
                    <div class="notification-avatar">
                      <div class="avatar avatar-xl me-3">
                        <img class="rounded-circle" src="assets/img/team/10.jpg" alt="" />
                      </div>
                    </div>
                    <div class="notification-body">
                      <p class="mb-1"><strong>James Cameron</strong> invited to join the group: United Nations
                        International Children's Fund</p>
                      <span class="notification-time"><span class="me-2" role="img"
                          aria-label="Emoji">🙋‍</span>2d</span>
                    </div>
                  </a>
                </div>
              </div>
            </div>
            <div class="card-footer text-center border-top"><a class="card-link d-block"
                href="app/social/notifications.html">View all</a></div>
          </div>
        </div>
      </li>
      <li class="nav-item dropdown px-1">
        <a class="nav-link fa-icon-wait nine-dots p-1" id="navbarDropdownMenu" role="button"
          data-hide-on-body-scroll="data-hide-on-body-scroll" data-bs-toggle="dropdown" aria-haspopup="true"
          aria-expanded="false"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="43" viewBox="0 0 16 16"
            fill="none">
            <circle cx="2" cy="2" r="2" fill="#6C6E71"></circle>
            <circle cx="2" cy="8" r="2" fill="#6C6E71"></circle>
            <circle cx="2" cy="14" r="2" fill="#6C6E71"></circle>
            <circle cx="8" cy="8" r="2" fill="#6C6E71"></circle>
            <circle cx="8" cy="14" r="2" fill="#6C6E71"></circle>
            <circle cx="14" cy="8" r="2" fill="#6C6E71"></circle>
            <circle cx="14" cy="14" r="2" fill="#6C6E71"></circle>
            <circle cx="8" cy="2" r="2" fill="#6C6E71"></circle>
            <circle cx="14" cy="2" r="2" fill="#6C6E71"></circle>
          </svg></a>
        <div class="dropdown-menu dropdown-caret dropdown-caret dropdown-menu-end dropdown-menu-card dropdown-caret-bg"
          aria-labelledby="navbarDropdownMenu">
          <div class="card shadow-none">
            <div class="scrollbar-overlay nine-dots-dropdown">
              <div class="card-body px-3">
                <div class="row text-center gx-0 gy-0">
                  <div class="col-4"><a
                      class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none"
                      href="pages/user/profile.html" target="_blank">
                      <div class="avatar avatar-2xl"> <img class="rounded-circle" src="assets/img/team/3.jpg" alt="" />
                      </div>
                      <p class="mb-0 fw-medium text-800 text-truncate fs-11">Account</p>
                    </a></div>
                  <div class="col-4"><a
                      class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none"
                      href="https://themewagon.com/" target="_blank"><img class="rounded"
                        src="assets/img/nav-icons/themewagon.png" alt="" width="40" height="40" />
                      <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">Themewagon</p>
                    </a></div>
                  <div class="col-4"><a
                      class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none"
                      href="https://mailbluster.com/" target="_blank"><img class="rounded"
                        src="assets/img/nav-icons/mailbluster.png" alt="" width="40" height="40" />
                      <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">Mailbluster</p>
                    </a></div>
                  <div class="col-4"><a
                      class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="#!"
                      target="_blank"><img class="rounded" src="assets/img/nav-icons/google.png" alt="" width="40"
                        height="40" />
                      <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">Google</p>
                    </a></div>
                  <div class="col-4"><a
                      class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="#!"
                      target="_blank"><img class="rounded" src="assets/img/nav-icons/spotify.png" alt="" width="40"
                        height="40" />
                      <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">Spotify</p>
                    </a></div>
                  <div class="col-4"><a
                      class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="#!"
                      target="_blank"><img class="rounded" src="assets/img/nav-icons/steam.png" alt="" width="40"
                        height="40" />
                      <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">Steam</p>
                    </a></div>
                  <div class="col-4"><a
                      class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="#!"
                      target="_blank"><img class="rounded" src="assets/img/nav-icons/github-light.png" alt="" width="40"
                        height="40" />
                      <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">Github</p>
                    </a></div>
                  <div class="col-4"><a
                      class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="#!"
                      target="_blank"><img class="rounded" src="assets/img/nav-icons/discord.png" alt="" width="40"
                        height="40" />
                      <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">Discord</p>
                    </a></div>
                  <div class="col-4"><a
                      class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="#!"
                      target="_blank"><img class="rounded" src="assets/img/nav-icons/xbox.png" alt="" width="40"
                        height="40" />
                      <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">xbox</p>
                    </a></div>
                  <div class="col-4"><a
                      class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="#!"
                      target="_blank"><img class="rounded" src="assets/img/nav-icons/trello.png" alt="" width="40"
                        height="40" />
                      <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">Kanban</p>
                    </a></div>
                  <div class="col-4"><a
                      class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="#!"
                      target="_blank"><img class="rounded" src="assets/img/nav-icons/hp.png" alt="" width="40"
                        height="40" />
                      <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">Hp</p>
                    </a></div>
                  <div class="col-12">
                    <hr class="my-3 mx-n3 bg-200" />
                  </div>
                  <div class="col-4"><a
                      class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="#!"
                      target="_blank"><img class="rounded" src="assets/img/nav-icons/linkedin.png" alt="" width="40"
                        height="40" />
                      <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">Linkedin</p>
                    </a></div>
                  <div class="col-4"><a
                      class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="#!"
                      target="_blank"><img class="rounded" src="assets/img/nav-icons/twitter.png" alt="" width="40"
                        height="40" />
                      <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">Twitter</p>
                    </a></div>
                  <div class="col-4"><a
                      class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="#!"
                      target="_blank"><img class="rounded" src="assets/img/nav-icons/facebook.png" alt="" width="40"
                        height="40" />
                      <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">Facebook</p>
                    </a></div>
                  <div class="col-4"><a
                      class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="#!"
                      target="_blank"><img class="rounded" src="assets/img/nav-icons/instagram.png" alt="" width="40"
                        height="40" />
                      <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">Instagram</p>
                    </a></div>
                  <div class="col-4"><a
                      class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="#!"
                      target="_blank"><img class="rounded" src="assets/img/nav-icons/pinterest.png" alt="" width="40"
                        height="40" />
                      <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">Pinterest</p>
                    </a></div>
                  <div class="col-4"><a
                      class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="#!"
                      target="_blank"><img class="rounded" src="assets/img/nav-icons/slack.png" alt="" width="40"
                        height="40" />
                      <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">Slack</p>
                    </a></div>
                  <div class="col-4"><a
                      class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="#!"
                      target="_blank"><img class="rounded" src="assets/img/nav-icons/deviantart.png" alt="" width="40"
                        height="40" />
                      <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">Deviantart</p>
                    </a></div>
                  <div class="col-4"><a
                      class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none"
                      href="app/events/event-detail.html" target="_blank">
                      <div class="avatar avatar-2xl">
                        <div class="avatar-name rounded-circle bg-primary-subtle text-primary"><span
                            class="fs-7">E</span></div>
                      </div>
                      <p class="mb-0 fw-medium text-800 text-truncate fs-11">Events</p>
                    </a></div>
                  <div class="col-12"><a class="btn btn-outline-primary btn-sm mt-4" href="#!">Show more</a></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </li>
      <li class="nav-item dropdown"><a class="nav-link pe-0 ps-2" id="navbarDropdownUser" role="button"
          data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <div class="avatar avatar-xl">
            <img class="rounded-circle" src="assets/img/team/3-thumb.png" alt="" />
          </div>
        </a>
        <div class="dropdown-menu dropdown-caret dropdown-caret dropdown-menu-end py-0"
          aria-labelledby="navbarDropdownUser">
          <div class="bg-white dark__bg-1000 rounded-2 py-2">
            <a class="dropdown-item fw-bold text-warning" href="#!"><span class="fas fa-crown me-1"></span><span>Go
                Pro</span></a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="#!">Set status</a>
            <a class="dropdown-item" href="pages/user/profile.html">Profile &amp; account</a>
            <a class="dropdown-item" href="#!">Feedback</a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="pages/user/settings.html">Settings</a>
            <a class="dropdown-item" href="pages/authentication/split/logout.php">Logout</a>
          </div>
        </div>
      </li>
    </ul>
  </nav>
  <nav class="navbar navbar-light navbar-glass navbar-top navbar-expand-lg" style="display: none;"
    data-move-target="#navbarVerticalNav" data-navbar-top="combo">
    <button class="btn navbar-toggler-humburger-icon navbar-toggler me-1 me-sm-3" type="button"
      data-bs-toggle="collapse" data-bs-target="#navbarVerticalCollapse" aria-controls="navbarVerticalCollapse"
      aria-expanded="false" aria-label="Toggle Navigation"><span class="navbar-toggle-icon"><span
          class="toggle-line"></span></span></button>
    <a class="navbar-brand me-1 me-sm-3" href="index-2.html">
      <div class="d-flex align-items-center"><img class="me-2" src="assets/img/icons/spot-illustrations/falcon.png"
          alt="" width="40" /><span class="font-sans-serif text-primary">isp360</span></div>
    </a>
    <div class="collapse navbar-collapse scrollbar" id="navbarStandard">
      <ul class="navbar-nav" data-top-nav-dropdowns="data-top-nav-dropdowns">
        <li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" role="button"
            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" id="dashboards">Dashboard</a>
          <div class="dropdown-menu dropdown-caret dropdown-menu-card border-0 mt-0" aria-labelledby="dashboards">
            <div class="bg-white dark__bg-1000 rounded-3 py-2"><a class="dropdown-item link-600 fw-medium"
                href="index-2.html">Default</a><a class="dropdown-item link-600 fw-medium"
                href="dashboard/analytics.html">Analytics</a><a class="dropdown-item link-600 fw-medium"
                href="dashboard/crm.html">CRM</a><a class="dropdown-item link-600 fw-medium"
                href="dashboard/e-commerce.html">E commerce</a><a class="dropdown-item link-600 fw-medium"
                href="dashboard/lms.html">LMS<span class="badge rounded-pill ms-2 badge-subtle-success">New</span></a><a
                class="dropdown-item link-600 fw-medium" href="dashboard/project-management.html">Management</a><a
                class="dropdown-item link-600 fw-medium" href="dashboard/saas.html">SaaS</a><a
                class="dropdown-item link-600 fw-medium" href="dashboard/support-desk.html">Support desk<span
                  class="badge rounded-pill ms-2 badge-subtle-success">New</span></a></div>
          </div>
        </li>
        <li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" role="button"
            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" id="apps">App</a>
          <div class="dropdown-menu dropdown-caret dropdown-menu-card border-0 mt-0" aria-labelledby="apps">
            <div class="card navbar-card-app shadow-none dark__bg-1000">
              <div class="card-body scrollbar max-h-dropdown"><img class="img-dropdown"
                  src="assets/img/icons/spot-illustrations/authentication-corner.png" width="130" alt="" />
                <div class="row">
                  <div class="col-6 col-md-4">
                    <div class="nav flex-column"><a class="nav-link py-1 link-600 fw-medium"
                        href="app/calendar.html">Calendar</a><a class="nav-link py-1 link-600 fw-medium"
                        href="app/chat.html">Chat</a><a class="nav-link py-1 link-600 fw-medium"
                        href="app/kanban.html">Kanban</a>
                      <p class="nav-link text-700 mb-0 fw-bold">Social</p><a class="nav-link py-1 link-600 fw-medium"
                        href="app/social/feed.html">Feed</a><a class="nav-link py-1 link-600 fw-medium"
                        href="app/social/activity-log.html">Activity log</a><a class="nav-link py-1 link-600 fw-medium"
                        href="app/social/notifications.html">Notifications</a><a
                        class="nav-link py-1 link-600 fw-medium" href="app/social/followers.html">Followers</a>
                      <p class="nav-link text-700 mb-0 fw-bold">Support Desk</p><a
                        class="nav-link py-1 link-600 fw-medium" href="app/support-desk/table-view.php">Table view</a><a
                        class="nav-link py-1 link-600 fw-medium" href="app/support-desk/card-view.php">Card view</a><a
                        class="nav-link py-1 link-600 fw-medium" href="app/support-desk/contacts.php">Contacts</a><a
                        class="nav-link py-1 link-600 fw-medium" href="app/support-desk/contact-details.php">Contact
                        details</a><a class="nav-link py-1 link-600 fw-medium"
                        href="app/support-desk/tickets-preview.php">Tickets preview</a><a
                        class="nav-link py-1 link-600 fw-medium" href="app/support-desk/quick-links.php">Quick links</a>
                    </div>
                  </div>
                  <div class="col-6 col-md-4">
                    <div class="nav flex-column">
                      <p class="nav-link text-700 mb-0 fw-bold">E-Learning</p><a
                        class="nav-link py-1 link-600 fw-medium" href="app/e-learning/course/course-list.html">Course
                        list</a><a class="nav-link py-1 link-600 fw-medium"
                        href="app/e-learning/course/course-grid.html">Course grid</a><a
                        class="nav-link py-1 link-600 fw-medium" href="app/e-learning/course/course-details.html">Course
                        details</a><a class="nav-link py-1 link-600 fw-medium"
                        href="app/e-learning/course/create-a-course.html">Create a course</a><a
                        class="nav-link py-1 link-600 fw-medium" href="app/e-learning/student-overview.html">Student
                        overview</a><a class="nav-link py-1 link-600 fw-medium"
                        href="app/e-learning/trainer-profile.html">Trainer profile</a>
                      <p class="nav-link text-700 mb-0 fw-bold">Events</p><a class="nav-link py-1 link-600 fw-medium"
                        href="app/events/create-an-event.html">Create an event</a><a
                        class="nav-link py-1 link-600 fw-medium" href="app/events/event-detail.html">Event detail</a><a
                        class="nav-link py-1 link-600 fw-medium" href="app/events/event-list.html">Event list</a>
                      <p class="nav-link text-700 mb-0 fw-bold">Email</p><a class="nav-link py-1 link-600 fw-medium"
                        href="app/email/inbox.html">Inbox</a><a class="nav-link py-1 link-600 fw-medium"
                        href="app/email/email-detail.html">Email detail</a><a class="nav-link py-1 link-600 fw-medium"
                        href="app/email/compose.html">Compose</a>
                    </div>
                  </div>
                  <div class="col-6 col-md-4">
                    <div class="nav flex-column">
                      <p class="nav-link text-700 mb-0 fw-bold">E-Commerce</p><a
                        class="nav-link py-1 link-600 fw-medium" href="app/e-commerce/product/product-list.html">Product
                        list</a><a class="nav-link py-1 link-600 fw-medium"
                        href="app/e-commerce/product/product-grid.html">Product grid</a><a
                        class="nav-link py-1 link-600 fw-medium"
                        href="app/e-commerce/product/product-details.html">Product details</a><a
                        class="nav-link py-1 link-600 fw-medium" href="app/e-commerce/product/add-product.html">Add
                        product</a><a class="nav-link py-1 link-600 fw-medium"
                        href="app/e-commerce/orders/order-list.html">Order list</a><a
                        class="nav-link py-1 link-600 fw-medium" href="app/e-commerce/orders/order-details.html">Order
                        details</a><a class="nav-link py-1 link-600 fw-medium"
                        href="app/e-commerce/customers.html">Customers</a><a class="nav-link py-1 link-600 fw-medium"
                        href="app/e-commerce/customer-details.html">Customer details</a><a
                        class="nav-link py-1 link-600 fw-medium" href="app/e-commerce/shopping-cart.html">Shopping
                        cart</a><a class="nav-link py-1 link-600 fw-medium"
                        href="app/e-commerce/checkout.html">Checkout</a><a class="nav-link py-1 link-600 fw-medium"
                        href="app/e-commerce/billing.html">Billing</a><a class="nav-link py-1 link-600 fw-medium"
                        href="app/e-commerce/invoice.html">Invoice</a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </li>
        <li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" role="button"
            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" id="pagess">Pages</a>
          <div class="dropdown-menu dropdown-caret dropdown-menu-card border-0 mt-0" aria-labelledby="pagess">
            <div class="card navbar-card-pages shadow-none dark__bg-1000">
              <div class="card-body scrollbar max-h-dropdown"><img class="img-dropdown"
                  src="assets/img/icons/spot-illustrations/authentication-corner.png" width="130" alt="" />
                <div class="row">
                  <div class="col-6 col-xxl-3">
                    <div class="nav flex-column">
                      <p class="nav-link text-700 mb-0 fw-bold">Simple Auth</p><a
                        class="nav-link py-1 link-600 fw-medium"
                        href="pages/authentication/simple/login.html">Login</a><a
                        class="nav-link py-1 link-600 fw-medium"
                        href="pages/authentication/simple/logout.html">Logout</a><a
                        class="nav-link py-1 link-600 fw-medium"
                        href="pages/authentication/simple/register.html">Register</a><a
                        class="nav-link py-1 link-600 fw-medium"
                        href="pages/authentication/simple/forgot-password.html">Forgot password</a><a
                        class="nav-link py-1 link-600 fw-medium"
                        href="pages/authentication/simple/confirm-mail.html">Confirm mail</a><a
                        class="nav-link py-1 link-600 fw-medium"
                        href="pages/authentication/simple/reset-password.html">Reset password</a><a
                        class="nav-link py-1 link-600 fw-medium"
                        href="pages/authentication/simple/lock-screen.html">Lock screen</a>
                    </div>
                  </div>
                  <div class="col-6 col-xxl-3">
                    <div class="nav flex-column">
                      <p class="nav-link text-700 mb-0 fw-bold">Card Auth</p><a class="nav-link py-1 link-600 fw-medium"
                        href="pages/authentication/card/login.html">Login</a><a class="nav-link py-1 link-600 fw-medium"
                        href="pages/authentication/card/logout.html">Logout</a><a
                        class="nav-link py-1 link-600 fw-medium"
                        href="pages/authentication/card/register.html">Register</a><a
                        class="nav-link py-1 link-600 fw-medium"
                        href="pages/authentication/card/forgot-password.html">Forgot password</a><a
                        class="nav-link py-1 link-600 fw-medium"
                        href="pages/authentication/card/confirm-mail.html">Confirm mail</a><a
                        class="nav-link py-1 link-600 fw-medium"
                        href="pages/authentication/card/reset-password.html">Reset password</a><a
                        class="nav-link py-1 link-600 fw-medium" href="pages/authentication/card/lock-screen.html">Lock
                        screen</a>
                    </div>
                  </div>
                  <div class="col-6 col-xxl-3">
                    <div class="nav flex-column">
                      <p class="nav-link text-700 mb-0 fw-bold">Split Auth</p><a
                        class="nav-link py-1 link-600 fw-medium"
                        href="pages/authentication/split/login.html">Login</a><a
                        class="nav-link py-1 link-600 fw-medium"
                        href="pages/authentication/split/logout.html">Logout</a><a
                        class="nav-link py-1 link-600 fw-medium"
                        href="pages/authentication/split/register.html">Register</a><a
                        class="nav-link py-1 link-600 fw-medium"
                        href="pages/authentication/split/forgot-password.html">Forgot password</a><a
                        class="nav-link py-1 link-600 fw-medium"
                        href="pages/authentication/split/confirm-mail.html">Confirm mail</a><a
                        class="nav-link py-1 link-600 fw-medium"
                        href="pages/authentication/split/reset-password.html">Reset password</a><a
                        class="nav-link py-1 link-600 fw-medium" href="pages/authentication/split/lock-screen.html">Lock
                        screen</a>
                    </div>
                  </div>
                  <div class="col-6 col-xxl-3">
                    <div class="nav flex-column">
                      <p class="nav-link text-700 mb-0 fw-bold">Layouts</p><a class="nav-link py-1 link-600 fw-medium"
                        href="demo/navbar-vertical.html">Navbar vertical</a><a class="nav-link py-1 link-600 fw-medium"
                        href="demo/navbar-top.html">Top nav</a><a class="nav-link py-1 link-600 fw-medium"
                        href="demo/navbar-double-top.html">Double top<span
                          class="badge rounded-pill ms-2 badge-subtle-success">New</span></a><a
                        class="nav-link py-1 link-600 fw-medium" href="demo/combo-nav.html">Combo nav</a>
                      <p class="nav-link text-700 mb-0 fw-bold">Others</p><a class="nav-link py-1 link-600 fw-medium"
                        href="pages/starter.html">Starter</a><a class="nav-link py-1 link-600 fw-medium"
                        href="pages/landing.html">Landing</a>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-6 col-xxl-3">
                    <div class="nav flex-column">
                      <p class="nav-link text-700 mb-0 fw-bold">User</p><a class="nav-link py-1 link-600 fw-medium"
                        href="pages/user/profile.html">Profile</a><a class="nav-link py-1 link-600 fw-medium"
                        href="pages/user/settings.html">Settings</a>
                    </div>
                  </div>
                  <div class="col-6 col-xxl-3">
                    <div class="nav flex-column">
                      <p class="nav-link text-700 mb-0 fw-bold">Pricing</p><a class="nav-link py-1 link-600 fw-medium"
                        href="pages/pricing/pricing-default.html">Pricing default</a><a
                        class="nav-link py-1 link-600 fw-medium" href="pages/pricing/pricing-alt.html">Pricing alt</a>
                    </div>
                  </div>
                  <div class="col-6 col-xxl-3">
                    <div class="nav flex-column">
                      <p class="nav-link text-700 mb-0 fw-bold">Errors</p><a class="nav-link py-1 link-600 fw-medium"
                        href="pages/errors/404.html">404</a><a class="nav-link py-1 link-600 fw-medium"
                        href="pages/errors/500.html">500</a>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-6 col-xxl-3">
                    <div class="nav flex-column">
                      <p class="nav-link text-700 mb-0 fw-bold">Miscellaneous</p><a
                        class="nav-link py-1 link-600 fw-medium"
                        href="pages/miscellaneous/associations.html">Associations</a><a
                        class="nav-link py-1 link-600 fw-medium" href="pages/miscellaneous/invite-people.html">Invite
                        people</a><a class="nav-link py-1 link-600 fw-medium"
                        href="pages/miscellaneous/privacy-policy.html">Privacy policy</a>
                    </div>
                  </div>
                  <div class="col-6 col-xxl-3">
                    <div class="nav flex-column">
                      <p class="nav-link text-700 mb-0 fw-bold">FAQ</p><a class="nav-link py-1 link-600 fw-medium"
                        href="pages/faq/faq-basic.html">Faq basic</a><a class="nav-link py-1 link-600 fw-medium"
                        href="pages/faq/faq-alt.html">Faq alt</a><a class="nav-link py-1 link-600 fw-medium"
                        href="pages/faq/faq-accordion.html">Faq accordion</a>
                    </div>
                  </div>
                  <div class="col-6 col-xxl-3">
                    <div class="nav flex-column">
                      <p class="nav-link text-700 mb-0 fw-bold">Other Auth</p><a
                        class="nav-link py-1 link-600 fw-medium" href="pages/authentication/wizard.html">Wizard</a><a
                        class="nav-link py-1 link-600 fw-medium" href="#authentication-modal"
                        data-bs-toggle="modal">Modal</a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </li>
        <li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" role="button"
            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" id="moduless">Modules</a>
          <div class="dropdown-menu dropdown-caret dropdown-menu-card border-0 mt-0" aria-labelledby="moduless">
            <div class="card navbar-card-components shadow-none dark__bg-1000">
              <div class="card-body scrollbar max-h-dropdown"><img class="img-dropdown"
                  src="assets/img/icons/spot-illustrations/authentication-corner.png" width="130" alt="" />
                <div class="row">
                  <div class="col-6 col-xxl-3">
                    <div class="nav flex-column">
                      <p class="nav-link text-700 mb-0 fw-bold">Components</p><a
                        class="nav-link py-1 link-600 fw-medium"
                        href="modules/components/accordion.html">Accordion</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/components/alerts.html">Alerts</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/components/anchor.html">Anchor</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/components/animated-icons.html">Animated
                        icons</a><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/components/background.html">Background</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/components/badges.html">Badges</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/components/bottom-bar.html">Bottom
                        bar<span class="badge rounded-pill ms-2 badge-subtle-success">New</span></a><a
                        class="nav-link py-1 link-600 fw-medium"
                        href="modules/components/breadcrumbs.html">Breadcrumbs</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/components/buttons.html">Buttons</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/components/calendar.html">Calendar</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/components/cards.html">Cards</a><a
                        class="nav-link py-1 link-600 fw-medium"
                        href="modules/components/carousel/bootstrap.html">Bootstrap carousel</a>
                    </div>
                  </div>
                  <div class="col-6 col-xxl-3">
                    <div class="nav flex-column mt-md-4 pt-md-1"><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/components/carousel/swiper.html">Swiper</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/components/collapse.html">Collapse</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/components/cookie-notice.html">Cookie
                        notice</a><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/components/countup.html">Countup</a><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/components/dropdowns.html">Dropdowns</a><a
                        class="nav-link py-1 link-600 fw-medium"
                        href="modules/components/jquery-components.html">Jquery<span
                          class="badge rounded-pill ms-2 badge-subtle-success">New</span></a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/components/list-group.html">List
                        group</a><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/components/modals.html">Modals</a><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/components/navs-and-tabs/navs.html">Navs</a><a
                        class="nav-link py-1 link-600 fw-medium"
                        href="modules/components/navs-and-tabs/navbar.html">Navbar</a><a
                        class="nav-link py-1 link-600 fw-medium"
                        href="modules/components/navs-and-tabs/vertical-navbar.html">Navbar vertical</a><a
                        class="nav-link py-1 link-600 fw-medium"
                        href="modules/components/navs-and-tabs/top-navbar.html">Top nav</a></div>
                  </div>
                  <div class="col-6 col-xxl-3">
                    <div class="nav flex-column mt-xxl-4 pt-xxl-1"><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/components/navs-and-tabs/double-top-navbar.html">Double top<span
                          class="badge rounded-pill ms-2 badge-subtle-success">New</span></a><a
                        class="nav-link py-1 link-600 fw-medium"
                        href="modules/components/navs-and-tabs/combo-navbar.html">Combo nav</a><a
                        class="nav-link py-1 link-600 fw-medium"
                        href="modules/components/navs-and-tabs/tabs.html">Tabs</a><a
                        class="nav-link py-1 link-600 fw-medium"
                        href="modules/components/offcanvas.html">Offcanvas</a><a
                        class="nav-link py-1 link-600 fw-medium"
                        href="modules/components/pictures/avatar.html">Avatar</a><a
                        class="nav-link py-1 link-600 fw-medium"
                        href="modules/components/pictures/images.html">Images</a><a
                        class="nav-link py-1 link-600 fw-medium"
                        href="modules/components/pictures/figures.html">Figures</a><a
                        class="nav-link py-1 link-600 fw-medium"
                        href="modules/components/pictures/hoverbox.html">Hoverbox</a><a
                        class="nav-link py-1 link-600 fw-medium"
                        href="modules/components/pictures/lightbox.html">Lightbox</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/components/progress-bar.html">Progress
                        bar</a><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/components/placeholder.html">Placeholder</a><a
                        class="nav-link py-1 link-600 fw-medium"
                        href="modules/components/pagination.html">Pagination</a></div>
                  </div>
                  <div class="col-6 col-xxl-3">
                    <div class="nav flex-column mt-xxl-4 pt-xxl-1"><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/components/popovers.html">Popovers</a><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/components/scrollspy.html">Scrollspy</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/components/search.html">Search</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/components/sortable.html">Sortable</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/components/spinners.html">Spinners</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/components/timeline.html">Timeline</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/components/toasts.html">Toasts</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/components/tooltips.html">Tooltips</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/components/treeview.html">Treeview</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/components/typed-text.html">Typed
                        text</a><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/components/videos/embed.html">Embed</a><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/components/videos/plyr.html">Plyr</a></div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-6 col-xxl-3">
                    <div class="nav flex-column">
                      <p class="nav-link text-700 mb-0 fw-bold">Utilities</p><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/utilities/background.html">Background</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/utilities/borders.html">Borders</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/utilities/clearfix.html">Clearfix</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/utilities/colors.html">Colors</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/utilities/colored-links.html">Colored
                        links</a><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/utilities/display.html">Display</a><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/utilities/flex.html">Flex</a><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/utilities/float.html">Float</a><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/utilities/focus-ring.html">Focus ring</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/utilities/grid.html">Grid</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/utilities/icon-link.html">Icon link</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/utilities/overlayscrollbar.html">Overlay
                        scrollbar</a><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/utilities/position.html">Position</a><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/utilities/ratio.html">Ratio</a><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/utilities/spacing.html">Spacing</a><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/utilities/sizing.html">Sizing</a><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/utilities/stretched-link.html">Stretched link</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/utilities/text-truncation.html">Text
                        truncation</a><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/utilities/typography.html">Typography</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/utilities/vertical-align.html">Vertical
                        align</a><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/utilities/vertical-rule.html">Vertical rule</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/utilities/visibility.html">Visibility</a>
                    </div>
                  </div>
                  <div class="col-6 col-xxl-3">
                    <div class="nav flex-column">
                      <p class="nav-link text-700 mb-0 fw-bold">Tables</p><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/tables/basic-tables.html">Basic tables</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/tables/advance-tables.html">Advance
                        tables</a><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/tables/bulk-select.html">Bulk select</a>
                      <p class="nav-link text-700 mb-0 fw-bold">Charts</p><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/charts/chartjs.html">Chartjs</a><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/charts/d3js.html">D3js<span
                          class="badge rounded-pill ms-2 badge-subtle-success">New</span></a>
                      <p class="nav-link text-700 mb-0 fw-bold">ECharts</p><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/charts/echarts/line-charts.html">Line charts</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/charts/echarts/bar-charts.html">Bar
                        charts</a><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/charts/echarts/candlestick-charts.html">Candlestick charts</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/charts/echarts/geo-map.html">Geo
                        map</a><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/charts/echarts/scatter-charts.html">Scatter charts</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/charts/echarts/pie-charts.html">Pie
                        charts</a><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/charts/echarts/gauge-charts.html">Gauge charts</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/charts/echarts/radar-charts.html">Radar
                        charts</a><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/charts/echarts/heatmap-charts.html">Heatmap charts</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/charts/echarts/how-to-use.html">How to
                        use</a>
                    </div>
                  </div>
                  <div class="col-6 col-xxl-3">
                    <div class="nav flex-column">
                      <p class="nav-link text-700 mb-0 fw-bold">Forms</p><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/forms/basic/form-control.html">Form control</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/forms/basic/input-group.html">Input
                        group</a><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/forms/basic/select.html">Select</a><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/forms/basic/checks.html">Checks</a><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/forms/basic/range.html">Range</a><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/forms/basic/layout.html">Layout</a><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/forms/advance/advance-select.html">Advance select</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/forms/advance/date-picker.html">Date
                        picker</a><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/forms/advance/editor.html">Editor</a><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/forms/advance/emoji-button.html">Emoji button</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/forms/advance/file-uploader.html">File
                        uploader</a><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/forms/advance/input-mask.html">Input mask</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/forms/advance/range-slider.html">Range
                        slider</a><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/forms/advance/rating.html">Rating</a><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/forms/floating-labels.html">Floating labels</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/forms/wizard.html">Wizard</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/forms/validation.html">Validation</a>
                    </div>
                  </div>
                  <div class="col-6 col-xxl-3">
                    <div class="nav flex-column pt-xxl-1">
                      <p class="nav-link text-700 mb-0 fw-bold">Icons</p><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/icons/font-awesome.html">Font awesome</a><a
                        class="nav-link py-1 link-600 fw-medium" href="modules/icons/bootstrap-icons.html">Bootstrap
                        icons</a><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/icons/feather.html">Feather</a><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/icons/material-icons.html">Material icons</a>
                      <p class="nav-link text-700 mb-0 fw-bold">Maps</p><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/maps/google-map.html">Google map</a><a class="nav-link py-1 link-600 fw-medium"
                        href="modules/maps/leaflet-map.html">Leaflet map</a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </li>
        <li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" role="button"
            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" id="documentations">Documentation</a>
          <div class="dropdown-menu dropdown-caret dropdown-menu-card border-0 mt-0" aria-labelledby="documentations">
            <div class="bg-white dark__bg-1000 rounded-3 py-2"><a class="dropdown-item link-600 fw-medium"
                href="documentation/getting-started.html">Getting started</a><a class="dropdown-item link-600 fw-medium"
                href="documentation/customization/configuration.html">Configuration</a><a
                class="dropdown-item link-600 fw-medium" href="documentation/customization/styling.html">Styling<span
                  class="badge rounded-pill ms-2 badge-subtle-success">Updated</span></a><a
                class="dropdown-item link-600 fw-medium" href="documentation/customization/dark-mode.html">Dark
                mode</a><a class="dropdown-item link-600 fw-medium"
                href="documentation/customization/plugin.html">Plugin</a><a class="dropdown-item link-600 fw-medium"
                href="documentation/faq.html">Faq</a><a class="dropdown-item link-600 fw-medium"
                href="documentation/gulp.html">Gulp</a><a class="dropdown-item link-600 fw-medium"
                href="documentation/design-file.html">Design file</a><a class="dropdown-item link-600 fw-medium"
                href="changelog.html">Changelog</a></div>
          </div>
        </li>
      </ul>
    </div>
    <ul class="navbar-nav navbar-nav-icons ms-auto flex-row align-items-center">
      <li class="nav-item ps-2 pe-0">
        <div class="dropdown theme-control-dropdown"><a
            class="nav-link d-flex align-items-center dropdown-toggle fa-icon-wait fs-9 pe-1 py-0" href="#"
            role="button" id="themeSwitchDropdown" data-bs-toggle="dropdown" aria-haspopup="true"
            aria-expanded="false"><span class="fas fa-sun fs-7" data-fa-transform="shrink-2"
              data-theme-dropdown-toggle-icon="light"></span><span class="fas fa-moon fs-7" data-fa-transform="shrink-3"
              data-theme-dropdown-toggle-icon="dark"></span></a>
          <div class="dropdown-menu dropdown-menu-end dropdown-caret border py-0 mt-3"
            aria-labelledby="themeSwitchDropdown">
            <div class="bg-white dark__bg-1000 rounded-2 py-2"><button
                class="dropdown-item d-flex align-items-center gap-2" type="button" value="light"
                data-theme-control="theme"><span class="fas fa-sun"></span>Light<span
                  class="fas fa-check dropdown-check-icon ms-auto text-600"></span></button>
              <button class="dropdown-item d-flex align-items-center gap-2" type="button" value="dark"
                data-theme-control="theme"><span class="fas fa-moon" data-fa-transform=""></span>Dark<span
                  class="fas fa-check dropdown-check-icon ms-auto text-600"></span></button>
            </div>
          </div>
        </div>
      </li>
      <li class="nav-item d-none d-sm-block">
        <a class="nav-link px-0 notification-indicator notification-indicator-warning notification-indicator-fill fa-icon-wait"
          href="app/e-commerce/shopping-cart.html"><span class="fas fa-shopping-cart" data-fa-transform="shrink-7"
            style="font-size: 33px;"></span><span class="notification-indicator-number">1</span></a>
      </li>
      <li class="nav-item dropdown">
        <a class="nav-link notification-indicator notification-indicator-primary px-0 fa-icon-wait"
          id="navbarDropdownNotification" role="button" data-bs-toggle="dropdown" aria-haspopup="true"
          aria-expanded="false" data-hide-on-body-scroll="data-hide-on-body-scroll"><span class="fas fa-bell"
            data-fa-transform="shrink-6" style="font-size: 33px;"></span></a>
        <div
          class="dropdown-menu dropdown-caret dropdown-caret dropdown-menu-end dropdown-menu-card dropdown-menu-notification dropdown-caret-bg"
          aria-labelledby="navbarDropdownNotification">
          <div class="card card-notification shadow-none">
            <div class="card-header">
              <div class="row justify-content-between align-items-center">
                <div class="col-auto">
                  <h6 class="card-header-title mb-0">Notifications</h6>
                </div>
                <div class="col-auto ps-0 ps-sm-3"><a class="card-link fw-normal" href="#">Mark all as read</a></div>
              </div>
            </div>
            <div class="scrollbar-overlay" style="max-height:19rem">
              <div class="list-group list-group-flush fw-normal fs-10">
                <div class="list-group-title border-bottom">NEW</div>
                <div class="list-group-item">
                  <a class="notification notification-flush notification-unread" href="#!">
                    <div class="notification-avatar">
                      <div class="avatar avatar-2xl me-3">
                        <img class="rounded-circle" src="assets/img/team/1-thumb.png" alt="" />
                      </div>
                    </div>
                    <div class="notification-body">
                      <p class="mb-1"><strong>Emma Watson</strong> replied to your comment : "Hello world 😍"</p>
                      <span class="notification-time"><span class="me-2" role="img" aria-label="Emoji">💬</span>Just
                        now</span>
                    </div>
                  </a>
                </div>
                <div class="list-group-item">
                  <a class="notification notification-flush notification-unread" href="#!">
                    <div class="notification-avatar">
                      <div class="avatar avatar-2xl me-3">
                        <div class="avatar-name rounded-circle"><span>AB</span></div>
                      </div>
                    </div>
                    <div class="notification-body">
                      <p class="mb-1"><strong>Albert Brooks</strong> reacted to <strong>Mia Khalifa's</strong> status
                      </p>
                      <span class="notification-time"><span class="me-2 fab fa-gratipay text-danger"></span>9hr</span>
                    </div>
                  </a>
                </div>
                <div class="list-group-title border-bottom">EARLIER</div>
                <div class="list-group-item">
                  <a class="notification notification-flush" href="#!">
                    <div class="notification-avatar">
                      <div class="avatar avatar-2xl me-3">
                        <img class="rounded-circle" src="assets/img/icons/weather-sm.jpg" alt="" />
                      </div>
                    </div>
                    <div class="notification-body">
                      <p class="mb-1">The forecast today shows a low of 20&#8451; in California. See today's weather.
                      </p>
                      <span class="notification-time"><span class="me-2" role="img"
                          aria-label="Emoji">🌤️</span>1d</span>
                    </div>
                  </a>
                </div>
                <div class="list-group-item">
                  <a class="border-bottom-0 notification-unread  notification notification-flush" href="#!">
                    <div class="notification-avatar">
                      <div class="avatar avatar-xl me-3">
                        <img class="rounded-circle" src="assets/img/logos/oxford.png" alt="" />
                      </div>
                    </div>
                    <div class="notification-body">
                      <p class="mb-1"><strong>University of Oxford</strong> created an event : "Causal Inference Hilary
                        2019"</p>
                      <span class="notification-time"><span class="me-2" role="img"
                          aria-label="Emoji">✌️</span>1w</span>
                    </div>
                  </a>
                </div>
                <div class="list-group-item">
                  <a class="border-bottom-0 notification notification-flush" href="#!">
                    <div class="notification-avatar">
                      <div class="avatar avatar-xl me-3">
                        <img class="rounded-circle" src="assets/img/team/10.jpg" alt="" />
                      </div>
                    </div>
                    <div class="notification-body">
                      <p class="mb-1"><strong>James Cameron</strong> invited to join the group: United Nations
                        International Children's Fund</p>
                      <span class="notification-time"><span class="me-2" role="img"
                          aria-label="Emoji">🙋‍</span>2d</span>
                    </div>
                  </a>
                </div>
              </div>
            </div>
            <div class="card-footer text-center border-top"><a class="card-link d-block"
                href="app/social/notifications.html">View all</a></div>
          </div>
        </div>
      </li>
      <li class="nav-item dropdown px-1">
        <a class="nav-link fa-icon-wait nine-dots p-1" id="navbarDropdownMenu" role="button"
          data-hide-on-body-scroll="data-hide-on-body-scroll" data-bs-toggle="dropdown" aria-haspopup="true"
          aria-expanded="false"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="43" viewBox="0 0 16 16"
            fill="none">
            <circle cx="2" cy="2" r="2" fill="#6C6E71"></circle>
            <circle cx="2" cy="8" r="2" fill="#6C6E71"></circle>
            <circle cx="2" cy="14" r="2" fill="#6C6E71"></circle>
            <circle cx="8" cy="8" r="2" fill="#6C6E71"></circle>
            <circle cx="8" cy="14" r="2" fill="#6C6E71"></circle>
            <circle cx="14" cy="8" r="2" fill="#6C6E71"></circle>
            <circle cx="14" cy="14" r="2" fill="#6C6E71"></circle>
            <circle cx="8" cy="2" r="2" fill="#6C6E71"></circle>
            <circle cx="14" cy="2" r="2" fill="#6C6E71"></circle>
          </svg></a>
        <div class="dropdown-menu dropdown-caret dropdown-caret dropdown-menu-end dropdown-menu-card dropdown-caret-bg"
          aria-labelledby="navbarDropdownMenu">
          <div class="card shadow-none">
            <div class="scrollbar-overlay nine-dots-dropdown">
              <div class="card-body px-3">
                <div class="row text-center gx-0 gy-0">
                  <div class="col-4"><a
                      class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none"
                      href="pages/user/profile.html" target="_blank">
                      <div class="avatar avatar-2xl"> <img class="rounded-circle" src="assets/img/team/3.jpg" alt="" />
                      </div>
                      <p class="mb-0 fw-medium text-800 text-truncate fs-11">Account</p>
                    </a></div>
                  <div class="col-4"><a
                      class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none"
                      href="https://themewagon.com/" target="_blank"><img class="rounded"
                        src="assets/img/nav-icons/themewagon.png" alt="" width="40" height="40" />
                      <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">Themewagon</p>
                    </a></div>
                  <div class="col-4"><a
                      class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none"
                      href="https://mailbluster.com/" target="_blank"><img class="rounded"
                        src="assets/img/nav-icons/mailbluster.png" alt="" width="40" height="40" />
                      <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">Mailbluster</p>
                    </a></div>
                  <div class="col-4"><a
                      class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="#!"
                      target="_blank"><img class="rounded" src="assets/img/nav-icons/google.png" alt="" width="40"
                        height="40" />
                      <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">Google</p>
                    </a></div>
                  <div class="col-4"><a
                      class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="#!"
                      target="_blank"><img class="rounded" src="assets/img/nav-icons/spotify.png" alt="" width="40"
                        height="40" />
                      <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">Spotify</p>
                    </a></div>
                  <div class="col-4"><a
                      class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="#!"
                      target="_blank"><img class="rounded" src="assets/img/nav-icons/steam.png" alt="" width="40"
                        height="40" />
                      <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">Steam</p>
                    </a></div>
                  <div class="col-4"><a
                      class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="#!"
                      target="_blank"><img class="rounded" src="assets/img/nav-icons/github-light.png" alt="" width="40"
                        height="40" />
                      <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">Github</p>
                    </a></div>
                  <div class="col-4"><a
                      class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="#!"
                      target="_blank"><img class="rounded" src="assets/img/nav-icons/discord.png" alt="" width="40"
                        height="40" />
                      <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">Discord</p>
                    </a></div>
                  <div class="col-4"><a
                      class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="#!"
                      target="_blank"><img class="rounded" src="assets/img/nav-icons/xbox.png" alt="" width="40"
                        height="40" />
                      <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">xbox</p>
                    </a></div>
                  <div class="col-4"><a
                      class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="#!"
                      target="_blank"><img class="rounded" src="assets/img/nav-icons/trello.png" alt="" width="40"
                        height="40" />
                      <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">Kanban</p>
                    </a></div>
                  <div class="col-4"><a
                      class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="#!"
                      target="_blank"><img class="rounded" src="assets/img/nav-icons/hp.png" alt="" width="40"
                        height="40" />
                      <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">Hp</p>
                    </a></div>
                  <div class="col-12">
                    <hr class="my-3 mx-n3 bg-200" />
                  </div>
                  <div class="col-4"><a
                      class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="#!"
                      target="_blank"><img class="rounded" src="assets/img/nav-icons/linkedin.png" alt="" width="40"
                        height="40" />
                      <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">Linkedin</p>
                    </a></div>
                  <div class="col-4"><a
                      class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="#!"
                      target="_blank"><img class="rounded" src="assets/img/nav-icons/twitter.png" alt="" width="40"
                        height="40" />
                      <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">Twitter</p>
                    </a></div>
                  <div class="col-4"><a
                      class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="#!"
                      target="_blank"><img class="rounded" src="assets/img/nav-icons/facebook.png" alt="" width="40"
                        height="40" />
                      <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">Facebook</p>
                    </a></div>
                  <div class="col-4"><a
                      class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="#!"
                      target="_blank"><img class="rounded" src="assets/img/nav-icons/instagram.png" alt="" width="40"
                        height="40" />
                      <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">Instagram</p>
                    </a></div>
                  <div class="col-4"><a
                      class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="#!"
                      target="_blank"><img class="rounded" src="assets/img/nav-icons/pinterest.png" alt="" width="40"
                        height="40" />
                      <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">Pinterest</p>
                    </a></div>
                  <div class="col-4"><a
                      class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="#!"
                      target="_blank"><img class="rounded" src="assets/img/nav-icons/slack.png" alt="" width="40"
                        height="40" />
                      <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">Slack</p>
                    </a></div>
                  <div class="col-4"><a
                      class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none" href="#!"
                      target="_blank"><img class="rounded" src="assets/img/nav-icons/deviantart.png" alt="" width="40"
                        height="40" />
                      <p class="mb-0 fw-medium text-800 text-truncate fs-11 pt-1">Deviantart</p>
                    </a></div>
                  <div class="col-4"><a
                      class="d-block hover-bg-200 px-2 py-3 rounded-3 text-center text-decoration-none"
                      href="app/events/event-detail.html" target="_blank">
                      <div class="avatar avatar-2xl">
                        <div class="avatar-name rounded-circle bg-primary-subtle text-primary"><span
                            class="fs-7">E</span></div>
                      </div>
                      <p class="mb-0 fw-medium text-800 text-truncate fs-11">Events</p>
                    </a></div>
                  <div class="col-12"><a class="btn btn-outline-primary btn-sm mt-4" href="#!">Show more</a></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </li>
      <li class="nav-item dropdown"><a class="nav-link pe-0 ps-2" id="navbarDropdownUser" role="button"
          data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <div class="avatar avatar-xl">
            <img class="rounded-circle" src="assets/img/team/3-thumb.png" alt="" />
          </div>
        </a>
        <div class="dropdown-menu dropdown-caret dropdown-caret dropdown-menu-end py-0"
          aria-labelledby="navbarDropdownUser">
          <div class="bg-white dark__bg-1000 rounded-2 py-2">
            <a class="dropdown-item fw-bold text-warning" href="#!"><span class="fas fa-crown me-1"></span><span>Go
                Pro</span></a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="#!">Set status</a>
            <a class="dropdown-item" href="pages/user/profile.html">Profile &amp; account</a>
            <a class="dropdown-item" href="#!">Feedback</a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="pages/user/settings.html">Settings</a>
            <a class="dropdown-item" href="pages/authentication/split/logout.php">Logout</a>
          </div>
        </div>
      </li>
    </ul>
  </nav>
  <script>
    var navbarVertical = document.querySelector('.navbar-vertical');
    var navbarTopVertical = document.querySelector('.content .navbar-top');
    var navbarDoubleTop = document.querySelector('[data-double-top-nav]');
    var navbarTopCombo = document.querySelector('.content [data-navbar-top="combo"]');

    // Keep the primary top navbar and sidebar visible, remove other layout variants.
    navbarVertical && navbarVertical.removeAttribute('style');
    navbarTopVertical && navbarTopVertical.removeAttribute('style');
    navbarDoubleTop && navbarDoubleTop.remove();
    navbarTopCombo && navbarTopCombo.remove();

    var syncThemeControls = function (themeValue) {
      document.documentElement.setAttribute('data-bs-theme', themeValue);
      localStorage.setItem('theme', themeValue);

      document.querySelectorAll('[data-theme-control="theme"]').forEach(function (button) {
        button.classList.toggle('active', button.value === themeValue);
      });

      document.querySelectorAll('[data-theme-dropdown-toggle-icon]').forEach(function (icon) {
        icon.classList.toggle('d-none', icon.getAttribute('data-theme-dropdown-toggle-icon') !== themeValue);
      });
    };

    document.addEventListener('click', function (event) {
      var themeButton = event.target.closest('[data-theme-control="theme"]');

      if (!themeButton) {
        return;
      }

      event.preventDefault();
      syncThemeControls(themeButton.value);
    });

    syncThemeControls(localStorage.getItem('theme') === 'dark' ? 'dark' : 'light');
  </script>
  <div class="row g-3 mb-3">
    <div class="col-md-6 col-xxl-3">
      <div class="card h-md-100 ecommerce-card-min-width">
        <div class="card-header pb-0">
          <h6 class="mb-0 mt-2 d-flex align-items-center">Total Payment</h6>
        </div>
        <div class="card-body d-flex flex-column justify-content-end">
          <div class="row">
            <div class="col">
              <p class="font-sans-serif lh-1 mb-1 fs-5">
                <?= number_format($totalPaymentAllTime, 2) ?>
              </p>
              <span class="badge badge-subtle-success rounded-pill fs-11">Last 7 days:
                <?= number_format($weeklyPaymentTotal, 2) ?></span>
            </div>
            <div class="col-auto ps-0">
              <div class="echart-bar-weekly-sales h-100" id="weekly-payment-chart"
                style="min-height: 60px; min-width: 80px;"
                data-options='{"xAxis":{"data":<?= $weeklyLabelsJson ?>},"series":[{"data":<?= $weeklyPaymentJson ?>}]}'>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-xxl-3">
      <div class="card h-md-100">
        <div class="card-header pb-0">
          <h6 class="mb-0 mt-2">Total Expense</h6>
        </div>
        <div class="card-body d-flex flex-column justify-content-end">
          <div class="row">
            <div class="col">
              <p class="font-sans-serif lh-1 mb-1 fs-5">
                <?= number_format($totalExpenseAllTime, 2) ?>
              </p>
              <span class="badge badge-subtle-danger rounded-pill fs-11">Last 7 days:
                <?= number_format($weeklyExpenseTotal, 2) ?></span>
            </div>
            <div class="col-auto ps-0">
              <div class="echart-bar-weekly-sales h-100" id="weekly-expense-chart"
                style="min-height: 60px; min-width: 80px;"
                data-options='{"xAxis":{"data":<?= $weeklyExpenseLabelsJson ?>},"series":[{"data":<?= $weeklyExpenseJson ?>,"itemStyle":{"color":"#f50000"}}]}'>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-xxl-3">
      <div class="card h-md-100">
        <div class="card-body">
          <div class="row h-100 justify-content-between g-0">
            <div class="col-5 col-sm-6 col-xxl pe-2">
              <h6 class="mt-1">Total Customers</h6>
              <div class="fs-11 mt-3">
                <div class="d-flex flex-between-center mb-1">
                  <div class="d-flex align-items-center"><span class="dot bg-primary"></span><span
                      class="fw-semi-bold">Friendsonline: <?= number_format($friendsonlineActive) ?></span></div>
                </div>
                <div class="d-flex flex-between-center mb-1">
                  <div class="d-flex align-items-center"><span class="dot bg-info"></span><span
                      class="fw-semi-bold">Bestnet: <?= number_format($bestnetActive) ?></span></div>
                </div>

              </div>
            </div>
            <div class="col-auto position-relative">
              <div class="echart-market-share"
                data-chart-data='<?= htmlspecialchars($marketShareJson, ENT_QUOTES, 'UTF-8') ?>'></div>
              <div class="position-absolute top-50 start-50 translate-middle text-1100 fs-7">
                <?= number_format($totalActiveCustomers) ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-xxl-3">
      <div class="card h-md-100">
        <div class="card-header d-flex flex-between-center pb-0">
          <h6 class="mb-0">Weather</h6>
          <div class="dropdown font-sans-serif btn-reveal-trigger">
            <button class="btn btn-link text-600 btn-sm dropdown-toggle dropdown-caret-none btn-reveal" type="button"
              id="dropdown-weather-update" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <span class="fas fa-ellipsis-h fs-11"></span>
            </button>
            <div class="dropdown-menu dropdown-menu-end border py-2" aria-labelledby="dropdown-weather-update">
              <a class="dropdown-item weather-city-select" href="#!" data-city="Dhaka" data-lat="23.8103" data-lon="90.4125">Dhaka</a>
              <a class="dropdown-item weather-city-select" href="#!" data-city="Bogura" data-lat="24.8481" data-lon="89.3730">Bogura</a>
            </div>
          </div>
        </div>
        <div class="card-body pt-2">
          <div class="row g-0 h-100 align-items-center">
            <div class="col">
              <div class="d-flex align-items-center">
                <img class="me-3" id="weather-icon" src="assets/img/icons/weather-icon.png" alt="" height="60" />
                <div>
                  <h6 class="mb-2" id="weather-city-name">Dhaka</h6>
                  <div class="fs-11 fw-semi-bold">
                    <div class="text-warning" id="weather-status">Loading...</div>
                    <span id="weather-precip">Precipitation: --%</span>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-auto text-center ps-2">
              <div class="fs-5 fw-normal font-sans-serif text-primary mb-1 lh-1" id="weather-temp">--&deg;</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="row g-0">
    <div class="col-lg-6 pe-lg-2 mb-3">
      <div class="card h-lg-100">
        <div class="card-header">
          <div class="row flex-between-center">
            <div class="col-auto">
              <h6 class="mb-0">Total Expenses this month</h6>
            </div>
            <div class="col-auto d-flex">
              <select class="form-select form-select-sm me-2"
                onchange="const url = new URL(window.location); url.searchParams.set('account_id', this.value); window.location.href=url.href;">
                <?php foreach ($accounts as $account): ?>
                  <option value="<?= $account['account_id'] ?>" <?= (int) $account['account_id'] === $selectedAccountId ? 'selected' : '' ?>>
                    <?= htmlspecialchars($account['account_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <select class="form-select form-select-sm select-month me-2">
                <?php
                $currentMonth = (int) date('n') - 1; // 0-11
                $months = [
                  0 => 'January',
                  1 => 'February',
                  2 => 'March',
                  3 => 'April',
                  4 => 'May',
                  5 => 'Jun',
                  6 => 'July',
                  7 => 'August',
                  8 => 'September',
                  9 => 'October',
                  10 => 'November',
                  11 => 'December'
                ];
                foreach ($months as $val => $name) {
                  $sel = ($val == $currentMonth) ? 'selected' : '';
                  echo "<option value=\"$val\" $sel>$name</option>";
                }
                ?>
              </select>

            </div>
          </div>
        </div>
        <div class="card-body h-100 pe-0">
          <div class="echart-line-total-expenses h-100" style="min-height: 300px;" data-echart-responsive="true"
            data-chart-data='<?= htmlspecialchars($expenseChartDataJson, ENT_QUOTES, 'UTF-8') ?>'></div>
        </div>
      </div>
    </div>
    <div class="col-lg-6 ps-lg-2 mb-3">
      <div class="card h-lg-100">
        <div class="card-header">
          <div class="row flex-between-center">
            <div class="col-auto">
              <h6 class="mb-0">Total Payment this month</h6>
            </div>
            <div class="col-auto d-flex">
              <select class="form-select form-select-sm me-2"
                onchange="const url = new URL(window.location); url.searchParams.set('account_id', this.value); window.location.href=url.href;">
                <?php foreach ($accounts as $account): ?>
                  <option value="<?= $account['account_id'] ?>" <?= (int) $account['account_id'] === $selectedAccountId ? 'selected' : '' ?>>
                    <?= htmlspecialchars($account['account_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <select class="form-select form-select-sm select-month me-2">
                <?php
                $currentMonth = (int) date('n') - 1; // 0-11
                $months = [
                  0 => 'January',
                  1 => 'February',
                  2 => 'March',
                  3 => 'April',
                  4 => 'May',
                  5 => 'Jun',
                  6 => 'July',
                  7 => 'August',
                  8 => 'September',
                  9 => 'October',
                  10 => 'November',
                  11 => 'December'
                ];
                foreach ($months as $val => $name) {
                  $sel = ($val == $currentMonth) ? 'selected' : '';
                  echo "<option value=\"$val\" $sel>$name</option>";
                }
                ?>
              </select>

            </div>
          </div>
        </div>
        <div class="card-body h-100 pe-0">
          <div class="echart-line-total-payment h-100" style="min-height: 300px;" data-echart-responsive="true"
            data-chart-data='<?= htmlspecialchars($chartDataJson, ENT_QUOTES, 'UTF-8') ?>'></div>
        </div>
      </div>
    </div>
  </div>
  <div class="row g-0">
    <div class="col-lg-6 col-xl-7 col-xxl-8 mb-3 pe-lg-2 mb-3">
      <div class="card h-lg-100">
        <div class="card-body d-flex align-items-center">
          <div class="w-100">
            <h6 class="mb-3 text-800">Using Storage <strong class="text-1100">1775.06 MB </strong>of 2 GB</h6>
            <div class="progress-stacked mb-3 rounded-3" style="height: 10px;">
              <div class="progress" style="width: 43.72%;" role="progressbar" aria-valuenow="43.72" aria-valuemin="0"
                aria-valuemax="100">
                <div class="progress-bar bg-progress-gradient border-end border-100 border-2"></div>
              </div>
              <div class="progress" style="width: 18.76%;" role="progressbar" aria-valuenow="18.76" aria-valuemin="0"
                aria-valuemax="100">
                <div class="progress-bar bg-info border-end border-100 border-2"></div>
              </div>
              <div class="progress" style="width: 9.38%;" role="progressbar" aria-valuenow="9.38" aria-valuemin="0"
                aria-valuemax="100">
                <div class="progress-bar bg-success border-end border-100 border-2"></div>
              </div>
              <div class="progress" style="width: 28.14%;" role="progressbar" aria-valuenow="28.14" aria-valuemin="0"
                aria-valuemax="100">
                <div class="progress-bar bg-200"></div>
              </div>
            </div>
            <div class="row fs-10 fw-semi-bold text-500 g-0">
              <div class="col-auto d-flex align-items-center pe-3"><span
                  class="dot bg-primary"></span><span>Regular</span><span
                  class="d-none d-md-inline-block d-lg-none d-xxl-inline-block">(895MB)</span></div>
              <div class="col-auto d-flex align-items-center pe-3"><span
                  class="dot bg-info"></span><span>System</span><span
                  class="d-none d-md-inline-block d-lg-none d-xxl-inline-block">(379MB)</span></div>
              <div class="col-auto d-flex align-items-center pe-3"><span
                  class="dot bg-success"></span><span>Shared</span><span
                  class="d-none d-md-inline-block d-lg-none d-xxl-inline-block">(192MB)</span></div>
              <div class="col-auto d-flex align-items-center"><span class="dot bg-200"></span><span>Free</span><span
                  class="d-none d-md-inline-block d-lg-none d-xxl-inline-block">(576MB)</span></div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-6 col-xl-5 col-xxl-4 mb-3 ps-lg-2">
      <div class="card h-lg-100">
        <div class="bg-holder bg-card" style="background-image:url(assets/img/icons/spot-illustrations/corner-1.png);">
        </div><!--/.bg-holder-->
        <div class="card-body position-relative">
          <h5 class="text-warning">Running out of your space?</h5>
          <p class="fs-10 mb-0">Your storage will be running out soon. Get more space and powerful productivity
            features.</p><a class="btn btn-link fs-10 text-warning mt-lg-3 ps-0" href="#!">Upgrade storage<span
              class="fas fa-chevron-right ms-1" data-fa-transform="shrink-4 down-1"></span></a>
        </div>
      </div>
    </div>
  </div>
  <div class="row g-0">
    <div class="col-lg-7 col-xl-8 pe-lg-2 mb-3">
      <div class="card h-lg-100 overflow-hidden">
        <div class="card-body p-0">
          <div class="table-responsive scrollbar">
            <table class="table table-dashboard mb-0 table-borderless fs-10 border-200">
              <thead class="bg-body-tertiary">
                <tr>
                  <th class="text-900">Best Selling Products</th>
                  <th class="text-900 text-end">Revenue ($3333)</th>
                  <th class="text-900 pe-x1 text-end" style="width: 8rem">Revenue (%)</th>
                </tr>
              </thead>
              <tbody>
                <tr class="border-bottom border-200">
                  <td>
                    <div class="d-flex align-items-center position-relative"><img class="rounded-1 border border-200"
                        src="assets/img/products/12.png" width="60" alt="" />
                      <div class="flex-1 ms-3">
                        <h6 class="mb-1 fw-semi-bold"><a class="text-1100 stretched-link" href="#!">Raven Pro</a></h6>
                        <p class="fw-semi-bold mb-0 text-500">Landing</p>
                      </div>
                    </div>
                  </td>
                  <td class="align-middle text-end fw-semi-bold">$1311</td>
                  <td class="align-middle pe-x1">
                    <div class="d-flex align-items-center">
                      <div class="progress me-3 rounded-3 bg-200" style="height: 5px; width:80px;" role="progressbar"
                        aria-valuenow="39" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar rounded-pill" style="width: 39%;"></div>
                      </div>
                      <div class="fw-semi-bold ms-2">39%</div>
                    </div>
                  </td>
                </tr>
                <tr class="border-bottom border-200">
                  <td>
                    <div class="d-flex align-items-center position-relative"><img class="rounded-1 border border-200"
                        src="assets/img/products/10.png" width="60" alt="" />
                      <div class="flex-1 ms-3">
                        <h6 class="mb-1 fw-semi-bold"><a class="text-1100 stretched-link" href="#!">Boots4</a></h6>
                        <p class="fw-semi-bold mb-0 text-500">Portfolio</p>
                      </div>
                    </div>
                  </td>
                  <td class="align-middle text-end fw-semi-bold">$860</td>
                  <td class="align-middle pe-x1">
                    <div class="d-flex align-items-center">
                      <div class="progress me-3 rounded-3 bg-200" style="height: 5px; width:80px;" role="progressbar"
                        aria-valuenow="26" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar rounded-pill" style="width: 26%;"></div>
                      </div>
                      <div class="fw-semi-bold ms-2">26%</div>
                    </div>
                  </td>
                </tr>
                <tr class="border-bottom border-200">
                  <td>
                    <div class="d-flex align-items-center position-relative"><img class="rounded-1 border border-200"
                        src="assets/img/products/11.png" width="60" alt="" />
                      <div class="flex-1 ms-3">
                        <h6 class="mb-1 fw-semi-bold"><a class="text-1100 stretched-link" href="#!">Falcon</a></h6>
                        <p class="fw-semi-bold mb-0 text-500">Admin</p>
                      </div>
                    </div>
                  </td>
                  <td class="align-middle text-end fw-semi-bold">$539</td>
                  <td class="align-middle pe-x1">
                    <div class="d-flex align-items-center">
                      <div class="progress me-3 rounded-3 bg-200" style="height: 5px; width:80px;" role="progressbar"
                        aria-valuenow="16" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar rounded-pill" style="width: 16%;"></div>
                      </div>
                      <div class="fw-semi-bold ms-2">16%</div>
                    </div>
                  </td>
                </tr>
                <tr class="border-bottom border-200">
                  <td>
                    <div class="d-flex align-items-center position-relative"><img class="rounded-1 border border-200"
                        src="assets/img/products/14.png" width="60" alt="" />
                      <div class="flex-1 ms-3">
                        <h6 class="mb-1 fw-semi-bold"><a class="text-1100 stretched-link" href="#!">Slick</a></h6>
                        <p class="fw-semi-bold mb-0 text-500">Builder</p>
                      </div>
                    </div>
                  </td>
                  <td class="align-middle text-end fw-semi-bold">$343</td>
                  <td class="align-middle pe-x1">
                    <div class="d-flex align-items-center">
                      <div class="progress me-3 rounded-3 bg-200" style="height: 5px; width:80px;" role="progressbar"
                        aria-valuenow="10" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar rounded-pill" style="width: 10%;"></div>
                      </div>
                      <div class="fw-semi-bold ms-2">10%</div>
                    </div>
                  </td>
                </tr>
                <tr>
                  <td>
                    <div class="d-flex align-items-center position-relative"><img class="rounded-1 border border-200"
                        src="assets/img/products/13.png" width="60" alt="" />
                      <div class="flex-1 ms-3">
                        <h6 class="mb-1 fw-semi-bold"><a class="text-1100 stretched-link" href="#!">Reign Pro</a></h6>
                        <p class="fw-semi-bold mb-0 text-500">Agency</p>
                      </div>
                    </div>
                  </td>
                  <td class="align-middle text-end fw-semi-bold">$280</td>
                  <td class="align-middle pe-x1">
                    <div class="d-flex align-items-center">
                      <div class="progress me-3 rounded-3 bg-200" style="height: 5px; width:80px;" role="progressbar"
                        aria-valuenow="8" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar rounded-pill" style="width: 8%;"></div>
                      </div>
                      <div class="fw-semi-bold ms-2">8%</div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="card-footer bg-body-tertiary py-2">
          <div class="row flex-between-center">
            <div class="col-auto"><select class="form-select form-select-sm">
                <option>Last 7 days</option>
                <option>Last Month</option>
                <option>Last Year</option>
              </select></div>
            <div class="col-auto"><a class="btn btn-sm btn-falcon-default" href="#!">View All</a></div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-5 col-xl-4 ps-lg-2 mb-3">
      <div class="card h-100">
        <div class="card-header d-flex flex-between-center bg-body-tertiary py-2">
          <h6 class="mb-0">Shared Files</h6><a class="py-1 fs-10 font-sans-serif" href="#!">View All</a>
        </div>
        <div class="card-body pb-0">
          <div class="d-flex mb-3 hover-actions-trigger align-items-center">
            <div class="file-thumbnail"><img class="border h-100 w-100 object-fit-cover rounded-2"
                src="assets/img/products/5-thumb.png" alt="" /></div>
            <div class="ms-3 flex-shrink-1 flex-grow-1">
              <h6 class="mb-1"><a class="stretched-link text-900 fw-semi-bold" href="#!">apple-smart-watch.png</a></h6>
              <div class="fs-10"><span class="fw-semi-bold">Antony</span><span class="fw-medium text-600 ms-2">Just
                  Now</span></div>
              <div class="hover-actions end-0 top-50 translate-middle-y"><a
                  class="btn btn-tertiary border-300 btn-sm me-1 text-600" data-bs-toggle="tooltip"
                  data-bs-placement="top" title="Download" href="assets/img/icons/cloud-download.svg"
                  download="download"><img src="assets/img/icons/cloud-download.svg" alt="" width="15" /></a><button
                  class="btn btn-tertiary border-300 btn-sm me-1 text-600 shadow-none" type="button"
                  data-bs-toggle="tooltip" data-bs-placement="top" title="Edit"><img src="assets/img/icons/edit-alt.svg"
                    alt="" width="15" /></button></div>
            </div>
          </div>
          <hr class="text-200" />
          <div class="d-flex mb-3 hover-actions-trigger align-items-center">
            <div class="file-thumbnail"><img class="border h-100 w-100 object-fit-cover rounded-2"
                src="assets/img/products/3-thumb.png" alt="" /></div>
            <div class="ms-3 flex-shrink-1 flex-grow-1">
              <h6 class="mb-1"><a class="stretched-link text-900 fw-semi-bold" href="#!">iphone.jpg</a></h6>
              <div class="fs-10"><span class="fw-semi-bold">Antony</span><span class="fw-medium text-600 ms-2">Yesterday
                  at 1:30 PM</span></div>
              <div class="hover-actions end-0 top-50 translate-middle-y"><a
                  class="btn btn-tertiary border-300 btn-sm me-1 text-600" data-bs-toggle="tooltip"
                  data-bs-placement="top" title="Download" href="assets/img/icons/cloud-download.svg"
                  download="download"><img src="assets/img/icons/cloud-download.svg" alt="" width="15" /></a><button
                  class="btn btn-tertiary border-300 btn-sm me-1 text-600 shadow-none" type="button"
                  data-bs-toggle="tooltip" data-bs-placement="top" title="Edit"><img src="assets/img/icons/edit-alt.svg"
                    alt="" width="15" /></button></div>
            </div>
          </div>
          <hr class="text-200" />
          <div class="d-flex mb-3 hover-actions-trigger align-items-center">
            <div class="file-thumbnail"><img class="img-fluid" src="assets/img/icons/zip.png" alt="" /></div>
            <div class="ms-3 flex-shrink-1 flex-grow-1">
              <h6 class="mb-1"><a class="stretched-link text-900 fw-semi-bold" href="#!">Falcon v1.8.2</a></h6>
              <div class="fs-10"><span class="fw-semi-bold">Jane</span><span class="fw-medium text-600 ms-2">27 Sep at
                  10:30 AM</span></div>
              <div class="hover-actions end-0 top-50 translate-middle-y"><a
                  class="btn btn-tertiary border-300 btn-sm me-1 text-600" data-bs-toggle="tooltip"
                  data-bs-placement="top" title="Download" href="assets/img/icons/cloud-download.svg"
                  download="download"><img src="assets/img/icons/cloud-download.svg" alt="" width="15" /></a><button
                  class="btn btn-tertiary border-300 btn-sm me-1 text-600 shadow-none" type="button"
                  data-bs-toggle="tooltip" data-bs-placement="top" title="Edit"><img src="assets/img/icons/edit-alt.svg"
                    alt="" width="15" /></button></div>
            </div>
          </div>
          <hr class="text-200" />
          <div class="d-flex mb-3 hover-actions-trigger align-items-center">
            <div class="file-thumbnail"><img class="border h-100 w-100 object-fit-cover rounded-2"
                src="assets/img/products/2-thumb.png" alt="" /></div>
            <div class="ms-3 flex-shrink-1 flex-grow-1">
              <h6 class="mb-1"><a class="stretched-link text-900 fw-semi-bold" href="#!">iMac.jpg</a></h6>
              <div class="fs-10"><span class="fw-semi-bold">Rowen</span><span class="fw-medium text-600 ms-2">23 Sep at
                  6:10 PM</span></div>
              <div class="hover-actions end-0 top-50 translate-middle-y"><a
                  class="btn btn-tertiary border-300 btn-sm me-1 text-600" data-bs-toggle="tooltip"
                  data-bs-placement="top" title="Download" href="assets/img/icons/cloud-download.svg"
                  download="download"><img src="assets/img/icons/cloud-download.svg" alt="" width="15" /></a><button
                  class="btn btn-tertiary border-300 btn-sm me-1 text-600 shadow-none" type="button"
                  data-bs-toggle="tooltip" data-bs-placement="top" title="Edit"><img src="assets/img/icons/edit-alt.svg"
                    alt="" width="15" /></button></div>
            </div>
          </div>
          <hr class="text-200" />
          <div class="d-flex mb-3 hover-actions-trigger align-items-center">
            <div class="file-thumbnail"><img class="img-fluid" src="assets/img/icons/docs.png" alt="" /></div>
            <div class="ms-3 flex-shrink-1 flex-grow-1">
              <h6 class="mb-1"><a class="stretched-link text-900 fw-semi-bold" href="#!">functions.php</a></h6>
              <div class="fs-10"><span class="fw-semi-bold">John</span><span class="fw-medium text-600 ms-2">1 Oct at
                  4:30 PM</span></div>
              <div class="hover-actions end-0 top-50 translate-middle-y"><a
                  class="btn btn-tertiary border-300 btn-sm me-1 text-600" data-bs-toggle="tooltip"
                  data-bs-placement="top" title="Download" href="assets/img/icons/cloud-download.svg"
                  download="download"><img src="assets/img/icons/cloud-download.svg" alt="" width="15" /></a><button
                  class="btn btn-tertiary border-300 btn-sm me-1 text-600 shadow-none" type="button"
                  data-bs-toggle="tooltip" data-bs-placement="top" title="Edit"><img src="assets/img/icons/edit-alt.svg"
                    alt="" width="15" /></button></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="row g-0">
    <div class="col-md-6 col-xxl-3 pe-md-2 mb-3 mb-xxl-0">
      <div class="card">
        <div class="card-header d-flex flex-between-center bg-body-tertiary py-2">
          <h6 class="mb-0">Active Users</h6>
          <div class="dropdown font-sans-serif btn-reveal-trigger"><button
              class="btn btn-link text-600 btn-sm dropdown-toggle dropdown-caret-none btn-reveal" type="button"
              id="dropdown-active-user" data-bs-toggle="dropdown" data-boundary="viewport" aria-haspopup="true"
              aria-expanded="false"><span class="fas fa-ellipsis-h fs-11"></span></button>
            <div class="dropdown-menu dropdown-menu-end border py-2" aria-labelledby="dropdown-active-user"><a
                class="dropdown-item" href="#!">View</a><a class="dropdown-item" href="#!">Export</a>
              <div class="dropdown-divider"></div><a class="dropdown-item text-danger" href="#!">Remove</a>
            </div>
          </div>
        </div>
        <div class="card-body py-2">
          <div class="d-flex align-items-center position-relative mb-3">
            <div class="avatar avatar-2xl status-online">
              <img class="rounded-circle" src="assets/img/team/1.jpg" alt="" />
            </div>
            <div class="flex-1 ms-3">
              <h6 class="mb-0 fw-semi-bold"><a class="stretched-link text-900" href="pages/user/profile.html">Emma
                  Watson</a></h6>
              <p class="text-500 fs-11 mb-0">Admin</p>
            </div>
          </div>
          <div class="d-flex align-items-center position-relative mb-3">
            <div class="avatar avatar-2xl status-online">
              <img class="rounded-circle" src="assets/img/team/2.jpg" alt="" />
            </div>
            <div class="flex-1 ms-3">
              <h6 class="mb-0 fw-semi-bold"><a class="stretched-link text-900" href="pages/user/profile.html">Antony
                  Hopkins</a></h6>
              <p class="text-500 fs-11 mb-0">Moderator</p>
            </div>
          </div>
          <div class="d-flex align-items-center position-relative mb-3">
            <div class="avatar avatar-2xl status-away">
              <img class="rounded-circle" src="assets/img/team/3.jpg" alt="" />
            </div>
            <div class="flex-1 ms-3">
              <h6 class="mb-0 fw-semi-bold"><a class="stretched-link text-900" href="pages/user/profile.html">Anna
                  Karinina</a></h6>
              <p class="text-500 fs-11 mb-0">Editor</p>
            </div>
          </div>
          <div class="d-flex align-items-center position-relative mb-3">
            <div class="avatar avatar-2xl status-offline">
              <img class="rounded-circle" src="assets/img/team/4.jpg" alt="" />
            </div>
            <div class="flex-1 ms-3">
              <h6 class="mb-0 fw-semi-bold"><a class="stretched-link text-900" href="pages/user/profile.html">John
                  Lee</a></h6>
              <p class="text-500 fs-11 mb-0">Admin</p>
            </div>
          </div>
          <div class="d-flex align-items-center position-relative false">
            <div class="avatar avatar-2xl status-offline">
              <img class="rounded-circle" src="assets/img/team/5.jpg" alt="" />
            </div>
            <div class="flex-1 ms-3">
              <h6 class="mb-0 fw-semi-bold"><a class="stretched-link text-900" href="pages/user/profile.html">Rowen
                  Atkinson</a></h6>
              <p class="text-500 fs-11 mb-0">Editor</p>
            </div>
          </div>
        </div>
        <div class="card-footer bg-body-tertiary p-0"><a class="btn btn-sm btn-link d-block w-100 py-2"
            href="app/social/followers.html">All active users<span class="fas fa-chevron-right ms-1 fs-11"></span></a>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-xxl-3 ps-md-2 order-xxl-1 mb-3 mb-xxl-0">
      <div class="card h-100">
        <div class="card-header bg-body-tertiary d-flex flex-between-center py-2">
          <h6 class="mb-0">Bandwidth Saved</h6>
          <div class="dropdown font-sans-serif btn-reveal-trigger"><button
              class="btn btn-link text-600 btn-sm dropdown-toggle dropdown-caret-none btn-reveal" type="button"
              id="dropdown-bandwidth-saved" data-bs-toggle="dropdown" data-boundary="viewport" aria-haspopup="true"
              aria-expanded="false"><span class="fas fa-ellipsis-h fs-11"></span></button>
            <div class="dropdown-menu dropdown-menu-end border py-2" aria-labelledby="dropdown-bandwidth-saved"><a
                class="dropdown-item" href="#!">View</a><a class="dropdown-item" href="#!">Export</a>
              <div class="dropdown-divider"></div><a class="dropdown-item text-danger" href="#!">Remove</a>
            </div>
          </div>
        </div>
        <div class="card-body d-flex flex-center flex-column">
          <!-- Find the JS file for the following chart at: src/js/charts/echarts/bandwidth-saved.js--><!-- If you are not using gulp based workflow, you can find the transpiled code at: public/assets/js/theme.js-->
          <div class="echart-bandwidth-saved" data-echart-responsive="true"></div>
          <div class="text-center mt-3">
            <h6 class="fs-9 mb-1"><span class="fas fa-check text-success me-1" data-fa-transform="shrink-2"></span>35.75
              GB saved</h6>
            <p class="fs-10 mb-0">38.44 GB total bandwidth</p>
          </div>
        </div>
        <div class="card-footer bg-body-tertiary py-2">
          <div class="row flex-between-center">
            <div class="col-auto"><select class="form-select form-select-sm">
                <option>Last 6 Months</option>
                <option>Last Year</option>
                <option>Last 2 Year</option>
              </select></div>
            <div class="col-auto"><a class="fs-10 font-sans-serif" href="#!">Help</a></div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-xxl-6 px-xxl-2">
      <div class="card h-100">
        <div class="card-header bg-body-tertiary py-2">
          <div class="row flex-between-center">
            <div class="col-auto">
              <h6 class="mb-0">Top Products</h6>
            </div>
            <div class="col-auto d-flex"><a class="btn btn-link btn-sm me-2" href="#!">View Details</a>
              <div class="dropdown font-sans-serif btn-reveal-trigger"><button
                  class="btn btn-link text-600 btn-sm dropdown-toggle dropdown-caret-none btn-reveal" type="button"
                  id="dropdown-top-products" data-bs-toggle="dropdown" data-boundary="viewport" aria-haspopup="true"
                  aria-expanded="false"><span class="fas fa-ellipsis-h fs-11"></span></button>
                <div class="dropdown-menu dropdown-menu-end border py-2" aria-labelledby="dropdown-top-products"><a
                    class="dropdown-item" href="#!">View</a><a class="dropdown-item" href="#!">Export</a>
                  <div class="dropdown-divider"></div><a class="dropdown-item text-danger" href="#!">Remove</a>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="card-body h-100">
          <!-- Find the JS file for the following chart at: src/js/charts/echarts/top-products.js--><!-- If you are not using gulp based workflow, you can find the transpiled code at: public/assets/js/theme.js-->
          <div class="echart-bar-top-products h-100" data-echart-responsive="true"></div>
        </div>
      </div>
    </div>
  </div>

  <?php
  require 'includes/footer.php';
  ?>
  <script>
    (function () {
      let attempts = 0;
      function waitForLibraries() {
        // Check for required global variables
        const libsReady = window.echarts && (window._ || window.lodash) && (window.utils || typeof utils !== 'undefined');

        if (libsReady) {
          initCustomCharts();
        } else if (attempts < 20) {
          attempts++;
          setTimeout(waitForLibraries, 200);
        } else {
          console.warn('Charts/Utils libraries failed to load after 4 seconds');
        }
      }

      function initCustomCharts() {
        const _utils = window.utils || utils;
        const _lodash = window._ || window.lodash;

        // Configuration for different chart types
        const barCharts = [
          { id: 'weekly-payment-chart', color: _utils.getColors().primary },
          { id: 'weekly-expense-chart', color: '#f50000' }
        ];

        const lineCharts = [
          { class: 'echart-line-total-payment', color: _utils.getColors().primary },
          { class: 'echart-line-total-expenses', color: '#f50000' }
        ];

        // Init Bar Charts
        barCharts.forEach(config => {
          const chartEl = document.getElementById(config.id);
          if (chartEl) {
            try {
              const existing = window.echarts.getInstanceByDom(chartEl);
              if (existing) existing.dispose();

              const userOptions = _utils.getData(chartEl, 'options');
              const chart = window.echarts.init(chartEl);
              const getDefaultOptions = () => ({
                tooltip: {
                  trigger: 'axis',
                  padding: [7, 10],
                  formatter: '{b0} : {c0}',
                  transitionDuration: 0,
                  backgroundColor: _utils.getGrays()['100'],
                  borderColor: _utils.getGrays()['300'],
                  textStyle: { color: _utils.getGrays()['1100'] },
                  borderWidth: 1,
                  position: (pos) => [pos[0], '10%']
                },
                xAxis: {
                  type: 'category',
                  boundaryGap: false,
                  axisLine: { show: false },
                  axisLabel: { show: false },
                  axisTick: { show: false },
                  axisPointer: { type: 'none' }
                },
                yAxis: {
                  type: 'value',
                  splitLine: { show: false },
                  axisLine: { show: false },
                  axisLabel: { show: false },
                  axisTick: { show: false },
                  axisPointer: { type: 'none' }
                },
                series: [{
                  type: 'bar',
                  showBackground: true,
                  backgroundStyle: { borderRadius: 10 },
                  barWidth: '5px',
                  itemStyle: { barBorderRadius: 10, color: config.color },
                  z: 10
                }],
                grid: { right: 5, left: 10, top: 0, bottom: 0 }
              });

              const options = _lodash.merge(getDefaultOptions(), userOptions);
              chart.setOption(options);
              chart.resize();
            } catch (err) {
              console.error('Error initializing bar chart ' + config.id + ':', err);
            }
          }
        });

        // Init Line Charts
        lineCharts.forEach(config => {
          const elements = document.querySelectorAll('.' + config.class);
          elements.forEach(chartEl => {
            try {
              const existing = window.echarts.getInstanceByDom(chartEl);
              if (existing) existing.dispose();

              const dataStr = chartEl.getAttribute('data-chart-data');
              if (!dataStr) {
                console.warn('No data found for line chart:', config.class);
                return;
              }

              const fullData = JSON.parse(dataStr);
              const monthSelect = chartEl.closest('.card').querySelector('.select-month');
              const chart = window.echarts.init(chartEl);

              console.log('Initializing line chart:', config.class, 'with month:', monthSelect ? monthSelect.value : 'default');

              const updateChart = (monthIndex) => {
                const monthData = fullData[monthIndex] || { labels: [], values: [] };

                const options = {
                  tooltip: {
                    trigger: 'axis',
                    padding: [7, 10],
                    backgroundColor: _utils.getGrays()['100'],
                    borderColor: _utils.getGrays()['300'],
                    textStyle: { color: _utils.getGrays()['1100'] },
                    borderWidth: 1,
                    transitionDuration: 0,
                    formatter: (params) => {
                      if (!params.length) return '';
                      const { name, value } = params[0];
                      const date = new Date(name);
                      return `${date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} : ${value}`;
                    }
                  },
                  xAxis: {
                    type: 'category',
                    data: monthData.labels,
                    boundaryGap: false,
                    axisPointer: { lineStyle: { color: _utils.getGrays()['300'], type: 'dashed' } },
                    splitLine: { show: false },
                    axisLine: { lineStyle: { color: _utils.getGrays()['300'], type: 'dashed' } },
                    axisTick: { show: false },
                    axisLabel: {
                      color: _utils.getGrays()['400'],
                      formatter: (value) => {
                        const date = new Date(value);
                        return date.getDate();
                      },
                      margin: 15,
                      showMinLabel: true,
                      showMaxLabel: true
                    }
                  },
                  yAxis: {
                    type: 'value',
                    axisPointer: { show: false },
                    splitLine: { lineStyle: { color: _utils.getGrays()['300'], type: 'dashed' } },
                    boundaryGap: [0, '20%'],
                    axisLabel: { show: true, color: _utils.getGrays()['400'], margin: 15 },
                    axisTick: { show: false },
                    axisLine: { show: false }
                  },
                  series: [{
                    type: 'line',
                    data: monthData.values,
                    symbol: 'circle',
                    symbolSize: 10,
                    showSymbol: false,
                    itemStyle: { color: config.color, borderColor: _utils.getGrays()['100'], borderWidth: 2 },
                    lineStyle: { color: config.color, width: 3 },
                    areaStyle: {
                      color: {
                        type: 'linear',
                        x: 0, y: 0, x2: 0, y2: 1,
                        colorStops: [
                          { offset: 0, color: _utils.rgbaColor(config.color, 0.2) },
                          { offset: 1, color: _utils.rgbaColor(config.color, 0) }
                        ]
                      }
                    },
                    smooth: true
                  }],
                  grid: { right: '20px', left: '40px', bottom: '30px', top: '10px' }
                };
                chart.setOption(options);
              };

              // Initial load
              const initialMonth = monthSelect ? parseInt(monthSelect.value) : new Date().getMonth();
              updateChart(initialMonth);

              // Update on month change
              if (monthSelect) {
                // Remove old listeners if any (by replacing node if needed, or just being careful)
                monthSelect.onchange = (e) => {
                  updateChart(parseInt(e.target.value));
                };
              }

              chart.resize();
            } catch (err) {
              console.error('Error initializing line chart ' + config.class + ':', err);
            }
          });
        });
      }

      window.addEventListener('load', () => {
        waitForLibraries();
        initWeather();
      });
      if (document.readyState === 'complete') {
        waitForLibraries();
        initWeather();
      }

      async function initWeather() {
        const cityElements = document.querySelectorAll('.weather-city-select');
        const cityNameEl = document.getElementById('weather-city-name');
        const statusEl = document.getElementById('weather-status');
        const precipEl = document.getElementById('weather-precip');
        const tempEl = document.getElementById('weather-temp');

        const fetchWeather = async (city, lat, lon) => {
          try {
            statusEl.textContent = 'Loading...';
            const res = await fetch(`https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lon}&current_weather=true&hourly=precipitation_probability`);
            const data = await res.json();
            
            if (!data.current_weather) throw new Error('Invalid data');
            
            const weather = data.current_weather;
            const precip = (data.hourly && data.hourly.precipitation_probability) ? data.hourly.precipitation_probability[0] : 0;
            
            cityNameEl.textContent = city;
            tempEl.innerHTML = `${Math.round(weather.temperature)}&deg;`;
            precipEl.textContent = `Precipitation: ${precip}%`;
            
            // WMO Weather interpretation codes (WW)
            const code = weather.weathercode;
            let status = 'Clear';
            if (code >= 1 && code <= 3) status = 'Partly Cloudy';
            else if (code >= 45 && code <= 48) status = 'Foggy';
            else if (code >= 51 && code <= 67) status = 'Rainy';
            else if (code >= 71 && code <= 77) status = 'Snowy';
            else if (code >= 80 && code <= 82) status = 'Showers';
            else if (code >= 95) status = 'Thunderstorm';
            
            statusEl.textContent = status;
          } catch (err) {
            console.error('Weather fetch error:', err);
            statusEl.textContent = 'Offline';
          }
        };

        cityElements.forEach(el => {
          el.addEventListener('click', (e) => {
            e.preventDefault();
            const city = el.getAttribute('data-city');
            const lat = el.getAttribute('data-lat');
            const lon = el.getAttribute('data-lon');
            fetchWeather(city, lat, lon);
          });
        });

        // Default load (Dhaka)
        fetchWeather('Dhaka', '23.8103', '90.4125');
      }
    })();
  </script>