<?php
require_once __DIR__ . '/auth.php';
$appBasePath = ispts_resolve_app_base_path(dirname(__DIR__));

// When the application is served from /public, keep generated links aligned with that web path.
$requestPath = explode('?', $_SERVER['REQUEST_URI'] ?? '')[0];
if (is_string($requestPath) && $requestPath !== '') {
  if (preg_match('#^(.*?/public)(?:/|$)#i', $requestPath, $publicMatch) === 1 && !empty($publicMatch[1])) {
    $appBasePath = rtrim((string) $publicMatch[1], '/');
    if ($appBasePath === '') {
      $appBasePath = '/public';
    }
  }
}

ispts_require_authentication($appBasePath);

// Set default timezone from .env or fallback to Asia/Dhaka
if (isset($_ENV['APP_TIMEZONE'])) {
  date_default_timezone_set($_ENV['APP_TIMEZONE']);
} elseif (getenv('APP_TIMEZONE')) {
  date_default_timezone_set(getenv('APP_TIMEZONE'));
} else {
  date_default_timezone_set('Asia/Dhaka');
}
?>
<!DOCTYPE html>
<html data-bs-theme="light" lang="en-US" dir="ltr">

  
<meta http-equiv="content-type" content="text/html;charset=utf-8" />
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- ===============================================--><!--    Document Title--><!-- ===============================================-->
    <title><?= htmlspecialchars($pageTitle ?? 'ISP360 Admin') ?></title>
    <meta name="csrf-token" content="<?= htmlspecialchars(ispts_csrf_token()) ?>">

    <!-- ===============================================--><!--    Favicons--><!-- ===============================================-->
    <link rel="apple-touch-icon" sizes="180x180" href="<?= $appBasePath ?>/assets/img/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $appBasePath ?>/assets/img/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= $appBasePath ?>/assets/img/favicons/favicon-16x16.png">
    <link rel="shortcut icon" type="image/x-icon" href="<?= $appBasePath ?>/assets/img/favicons/favicon.ico">
    <link rel="manifest" href="<?= $appBasePath ?>/assets/img/favicons/manifest.json">
    <meta name="msapplication-TileImage" content="<?= $appBasePath ?>/assets/img/favicons/mstile-150x150.png">
    <meta name="theme-color" content="#ffffff">
    <script src="<?= $appBasePath ?>/assets/js/config.js"></script>
    <script src="<?= $appBasePath ?>/vendors/simplebar/simplebar.min.js"></script>

    <!-- ===============================================--><!--    Stylesheets--><!-- ===============================================-->
    <link rel="preconnect" href="https://fonts.gstatic.com/">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,500,600,700%7cPoppins:300,400,500,600,700,800,900&amp;display=swap" rel="stylesheet">
    <link href="<?= $appBasePath ?>/vendors/simplebar/simplebar.min.css" rel="stylesheet">
    <link href="<?= $appBasePath ?>/assets/css/theme-rtl.min.css" rel="stylesheet" id="style-rtl">
    <link href="<?= $appBasePath ?>/assets/css/theme.min.css" rel="stylesheet" id="style-default">
    <link href="<?= $appBasePath ?>/assets/css/user-rtl.min.css" rel="stylesheet" id="user-style-rtl">
    <link href="<?= $appBasePath ?>/assets/css/user.min.css" rel="stylesheet" id="user-style-default">
    <script>
      var isRTL = JSON.parse(localStorage.getItem('isRTL'));
      if (isRTL) {
        var linkDefault = document.getElementById('style-default');
        var userLinkDefault = document.getElementById('user-style-default');
        linkDefault.setAttribute('disabled', true);
        userLinkDefault.setAttribute('disabled', true);
        document.querySelector('html').setAttribute('dir', 'rtl');
      } else {
        var linkRTL = document.getElementById('style-rtl');
        var userLinkRTL = document.getElementById('user-style-rtl');
        linkRTL.setAttribute('disabled', true);
        userLinkRTL.setAttribute('disabled', true);
      }
    </script>
  </head>

  <body>
    <!-- ===============================================--><!--    Main Content--><!-- ===============================================-->
    <main class="main" id="top">
      <div class="container-fluid" data-layout="container">
          <script>
            var isFluid = JSON.parse(localStorage.getItem('isFluid'));
            if (isFluid) {
            var container = document.querySelector('[data-layout]');
            container.classList.remove('container');
            container.classList.add('container-fluid');
          }
        </script>
<?php if (empty($skipSharedChrome)): ?>
        <?php require __DIR__ . '/sidenav.php'; ?>
        <?php require __DIR__ . '/topnav.php'; ?>
<?php endif; ?>
