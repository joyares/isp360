<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../includes/auth.php';

$documentRootPath = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
$projectRootPath = realpath(dirname(__DIR__, 4));

$appBasePath = '';
if ($documentRootPath && $projectRootPath) {
    $normalizedDocumentRoot = rtrim(str_replace('\\', '/', $documentRootPath), '/');
    $normalizedProjectRoot = rtrim(str_replace('\\', '/', $projectRootPath), '/');
    if ($normalizedDocumentRoot !== '' && strpos($normalizedProjectRoot, $normalizedDocumentRoot) === 0) {
        $appBasePath = substr($normalizedProjectRoot, strlen($normalizedDocumentRoot));
    }
}

if ($appBasePath === '') {
    $appBasePath = '';
}

$appBasePath = '/' . trim($appBasePath, '/');
if ($appBasePath === '/') {
    $appBasePath = '';
}

ispts_start_session();

if (ispts_is_authenticated()) {
    header('Location: ' . $appBasePath . '/index.php');
    exit;
}

$alert = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = (string) ($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    $result = ispts_attempt_admin_login($username, $password);

    if (($result['success'] ?? false) === true) {
        header('Location: ' . $appBasePath . '/index.php');
        exit;
    }

    $alert = (string) ($result['message'] ?? 'Login failed.');
}
?>
<!DOCTYPE html>
<html data-bs-theme="light" lang="en-US" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login | ISP360</title>

    <link rel="apple-touch-icon" sizes="180x180" href="<?= $appBasePath ?>/assets/img/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $appBasePath ?>/assets/img/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= $appBasePath ?>/assets/img/favicons/favicon-16x16.png">
    <link rel="shortcut icon" type="image/x-icon" href="<?= $appBasePath ?>/assets/img/favicons/favicon.ico">
    <script src="<?= $appBasePath ?>/assets/js/config.js"></script>
    <script src="<?= $appBasePath ?>/vendors/simplebar/simplebar.min.js"></script>

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
    <div id="canvas-wrapper" style="position: fixed; inset: 0; width: 100%; height: 100vh; background: #0f172a; z-index: -1; overflow: hidden;">
      <canvas id="demo-canvas" style="display: block; width: 100%; height: 100%;"></canvas>
    </div>

    <main class="main login-content" id="top" style="position: relative; z-index: 10;">
      <div class="container-fluid" data-layout="container">
        <div class="row min-vh-100" style="background: transparent;">
          <div class="col-sm-10 col-md-6 px-sm-0 align-self-center mx-auto py-5">
            <div class="row justify-content-center g-0">
              <div class="col-lg-9 col-xl-8 col-xxl-6">
                <div class="card">
                  <div class="card-header bg-circle-shape bg-shape text-center p-2">
                    <a class="font-sans-serif fw-bolder fs-5 z-1 position-relative link-light" href="<?= $appBasePath ?>/index.php" data-bs-theme="light">isp360</a>
                  </div>
                  <div class="card-body p-4">
                    <div class="row flex-between-center mb-3">
                      <div class="col-auto"><h3 class="mb-0">Admin Login</h3></div>
                    </div>

                    <?php if ($alert): ?>
                      <div class="alert alert-danger py-2" role="alert"><?= htmlspecialchars($alert) ?></div>
                    <?php endif; ?>

                    <form method="post" action="">
                      <div class="mb-3">
                        <label class="form-label" for="login-username">Username</label>
                        <input class="form-control" id="login-username" name="username" type="text" autocomplete="username" required>
                      </div>
                      <div class="mb-3">
                        <label class="form-label" for="login-password">Password</label>
                        <input class="form-control" id="login-password" name="password" type="password" autocomplete="current-password" required>
                      </div>
                      <div class="mb-0">
                        <button class="btn btn-primary d-block w-100 mt-3" type="submit">Login</button>
                      </div>
                    </form>
                  </div>
                </div>
                <div class="text-center mt-3">
                  <p class="mb-0 fs-10 text-600">isp360 Copyright 2026 &copy; mostafaJoy</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <script src="<?= $appBasePath ?>/vendors/popper/popper.min.js"></script>
    <script src="<?= $appBasePath ?>/vendors/bootstrap/bootstrap.min.js"></script>
    <script src="<?= $appBasePath ?>/vendors/anchorjs/anchor.min.js"></script>
    <script src="<?= $appBasePath ?>/vendors/is/is.min.js"></script>
    <script src="<?= $appBasePath ?>/vendors/fontawesome/all.min.js"></script>
    <script src="<?= $appBasePath ?>/vendors/lodash/lodash.min.js"></script>
    <script src="<?= $appBasePath ?>/vendors/list.js/list.min.js"></script>
    <script src="<?= $appBasePath ?>/assets/js/theme.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/1.13.1/TweenLite.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/1.13.1/easing/EasePack.min.js"></script>
    <script src="js/rAF.js"></script>
    <script src="js/login.js"></script>
    <script>
      (function () {
        if (typeof CanvasBG !== 'undefined' && CanvasBG.init) {
          CanvasBG.init({
            Loc: { x: window.innerWidth / 2, y: window.innerHeight / 2 }
          });
        }
      })();
    </script>
</body>
</html>
