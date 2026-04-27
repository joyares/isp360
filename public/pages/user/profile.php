<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../../app/Core/Database.php';

use App\Core\Database;

$pdo = Database::getConnection();

// Ensure the admin_user_notes table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS admin_user_notes (
    note_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_user_id BIGINT UNSIGNED NOT NULL,
    note_content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_admin_user_notes_user (admin_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

// Ensure avatar column exists in admin_users
$checkAvatar = $pdo->query("SHOW COLUMNS FROM admin_users LIKE 'avatar'")->fetch();
if (!$checkAvatar) {
  $pdo->exec("ALTER TABLE admin_users ADD COLUMN avatar VARCHAR(255) NULL AFTER mobile");
}

$adminUserId = $_SESSION['admin_user_id'] ?? 0;

$alert = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $adminUserId > 0) {
  $action = $_POST['action'];

  if ($action === 'add_note') {
    $content = trim((string) ($_POST['note_content'] ?? ''));
    if ($content !== '') {
      $stmt = $pdo->prepare("INSERT INTO admin_user_notes (admin_user_id, note_content) VALUES (:user_id, :content)");
      $stmt->execute([':user_id' => $adminUserId, ':content' => $content]);
      $alert = ['type' => 'success', 'message' => 'Note added successfully!'];
    }
  } elseif ($action === 'update_note') {
    $noteId = (int) ($_POST['note_id'] ?? 0);
    $isDelete = isset($_POST['delete_note']);

    if ($isDelete) {
      $stmt = $pdo->prepare("DELETE FROM admin_user_notes WHERE note_id = :id AND admin_user_id = :user_id");
      $stmt->execute([':id' => $noteId, ':user_id' => $adminUserId]);
      $alert = ['type' => 'success', 'message' => 'Note deleted successfully!'];
    } else {
      $content = trim((string) ($_POST['note_content'] ?? ''));
      if ($content !== '') {
        $stmt = $pdo->prepare("UPDATE admin_user_notes SET note_content = :content WHERE note_id = :id AND admin_user_id = :user_id");
        $stmt->execute([':content' => $content, ':id' => $noteId, ':user_id' => $adminUserId]);
        $alert = ['type' => 'success', 'message' => 'Note updated successfully!'];
      }
    }
  } elseif ($action === 'update_avatar') {
    if (!empty($_FILES['profile_image']['name'])) {
      $uploadDir = __DIR__ . '/../../assets/uploads/avatars/';
      if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
      }
      $fileName = $adminUserId . '_' . time() . '_' . basename($_FILES['profile_image']['name']);
      $target = $uploadDir . $fileName;
      if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target)) {
        $stmt = $pdo->prepare("UPDATE admin_users SET avatar = :avatar WHERE admin_user_id = :id");
        $stmt->execute([':avatar' => $fileName, ':id' => $adminUserId]);
        $alert = ['type' => 'success', 'message' => 'Profile picture updated successfully!'];
      } else {
        $alert = ['type' => 'danger', 'message' => 'Failed to upload profile picture.'];
      }
    }
  } elseif ($action === 'update_profile') {
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $mobile = trim((string) ($_POST['mobile'] ?? ''));

    try {
      $stmt = $pdo->prepare("UPDATE admin_users SET full_name = :name, email = :email, mobile = :mobile WHERE admin_user_id = :id");
      $stmt->execute([':name' => $fullName, ':email' => $email, ':mobile' => $mobile, ':id' => $adminUserId]);
      $alert = ['type' => 'success', 'message' => 'Profile updated successfully!'];
    } catch (PDOException $e) {
      $alert = ['type' => 'danger', 'message' => 'Failed to update profile. Email might already be in use.'];
    }
  }
}

// Fetch current admin user data
$userData = null;
if ($adminUserId > 0) {
  $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE admin_user_id = :id");
  $stmt->execute([':id' => $adminUserId]);
  $userData = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch user notes
$userNotes = [];
if ($adminUserId > 0) {
  $stmt = $pdo->prepare("SELECT * FROM admin_user_notes WHERE admin_user_id = :id ORDER BY updated_at DESC");
  $stmt->execute([':id' => $adminUserId]);
  $userNotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<nav class="mb-2" aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= $appBasePath ?>/index.php">Home</a></li>
    <li class="breadcrumb-item"><a href="#">User</a></li>
    <li class="breadcrumb-item active">My Profile</li>
  </ol>
</nav>

<div class="page-header mb-3">
  <div class="row align-items-center">
    <div class="col">
      <h1 class="page-header-title">My Profile</h1>
    </div>
  </div>
</div>

<?php if ($alert): ?>
  <div class="alert alert-<?= htmlspecialchars($alert['type']) ?> py-2 mb-3" role="alert">
    <?= htmlspecialchars($alert['message']) ?>
  </div>
<?php endif; ?>

<div class="row g-3">
  <!-- col-lg-4: Payment Methods -->
  <div class="col-lg-4">
    <div class="card mb-3 position-relative overflow-hidden">
      <div class="bg-holder bg-card"
        style="background-image:url(<?= $appBasePath ?>/assets/img/icons/spot-illustrations/corner-2.png);"></div>
      <div class="card-header bg-transparent d-flex flex-between-center py-2 position-relative z-1">
        <h6 class="mb-0">My Profile</h6>
        <div class="dropdown font-sans-serif position-static d-inline-block btn-reveal-trigger">
          <button class="btn btn-link text-600 btn-sm dropdown-toggle btn-reveal dropdown-caret-none" type="button"
            id="dropdown-my-profile" data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true"
            aria-expanded="false" data-bs-reference="parent">
            <span class="fas fa-ellipsis-h fs-10"></span>
          </button>
          <div class="dropdown-menu dropdown-menu-end border py-2" aria-labelledby="dropdown-my-profile">
            <a class="dropdown-item" href="#!" data-bs-toggle="modal" data-bs-target="#editProfileModal">Edit</a>
          </div>
        </div>
      </div>
      <div class="card-body position-relative z-1">
        <?php
        $avatarSrc = !empty($userData['avatar'])
          ? $appBasePath . '/assets/uploads/avatars/' . htmlspecialchars($userData['avatar'])
          : $appBasePath . '/assets/img/team/2.jpg';
        ?>

        <!-- Row 1: Avatar -->
        <div class="row align-items-center mb-3">
          <div class="col-6 text-center">
            <form action="" method="post" enctype="multipart/form-data">
              <input type="hidden" name="action" value="update_avatar">
              <div class="avatar avatar-5xl avatar-profile shadow-sm img-thumbnail rounded-circle mx-auto"
                style="background: #fff;">
                <div class="h-100 w-100 rounded-circle overflow-hidden position-relative">
                  <img src="<?= $avatarSrc ?>" width="200" alt="">
                  <input class="d-none" id="profile-image" type="file" name="profile_image" accept="image/*"
                    onchange="this.form.submit()">
                  <label class="mb-0 overlay-icon d-flex flex-center" for="profile-image">
                    <span class="bg-holder overlay overlay-0"></span>
                    <span class="z-1 text-white dark__text-white text-center fs-10">
                      <span class="fas fa-camera"></span><span class="d-block">Update</span>
                    </span>
                  </label>
                </div>
              </div>
            </form>
          </div>
          <div class="col-6"></div>
        </div>

        <!-- Row 2: Name -->
        <div class="row mb-4">
          <div class="col-6 text-center">
            <h5 class="mb-0 text-800"><?= htmlspecialchars($userData['full_name'] ?? 'User Name') ?></h5>
          </div>
          <div class="col-6"></div>
        </div>

        <!-- Row 3: Info Table -->
        <div class="row">
          <div class="col-12 text-start">
            <table class="table table-borderless fw-medium font-sans-serif fs-10 mb-0">
              <tbody>
                <tr>
                  <td class="p-1" style="width: 35%;">Username:</td>
                  <td class="p-1 text-600"><?= htmlspecialchars($userData['username'] ?? '') ?></td>
                </tr>
                <tr>
                  <td class="p-1" style="width: 35%;">Email:</td>
                  <td class="p-1 text-600"><?= htmlspecialchars($userData['email'] ?? '') ?></td>
                </tr>
                <tr>
                  <td class="p-1" style="width: 35%;">Mobile:</td>
                  <td class="p-1 text-600"><?= htmlspecialchars($userData['mobile'] ?? '') ?></td>
                </tr>
                <tr>
                  <td class="p-1" style="width: 35%;">Joined:</td>
                  <td class="p-1 text-600">
                    <?= !empty($userData['created_at']) ? date('M d, Y', strtotime($userData['created_at'])) : 'N/A' ?>
                  </td>
                </tr>
                <tr>
                  <td class="p-1" style="width: 35%;">ID:</td>
                  <td class="p-1 text-600">user_<?= substr(md5((string) $adminUserId), 0, 12) ?></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- col-lg-8: Personal Notes -->
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-header bg-body-tertiary">
        <h6 class="mb-0">My Personal Notes</h6>
      </div>
      <div class="card-body bg-light">
        <!-- Add Note Form -->
        <form action="" method="post" class="mb-4 bg-white p-3 border rounded shadow-sm">
          <input type="hidden" name="action" value="add_note">
          <div class="mb-2">
            <label class="form-label fw-semi-bold" for="newNote">Create New Note</label>
            <textarea class="form-control" id="newNote" name="note_content" rows="3"
              placeholder="Write something down..." required></textarea>
          </div>
          <div class="d-flex justify-content-end">
            <button class="btn btn-primary btn-sm" type="submit">
              <span class="fas fa-plus me-1"></span>Add Note
            </button>
          </div>
        </form>

        <hr class="text-300 mb-4">

        <h6 class="mb-3 text-700"><span class="fas fa-list me-2"></span>Saved Notes</h6>

        <!-- List of Notes -->
        <div class="d-flex flex-column gap-3">
          <?php if (empty($userNotes)): ?>
            <div class="text-center py-4 text-500">
              <span class="fas fa-inbox fs-3 mb-2"></span>
              <p class="mb-0">You don't have any saved notes yet.</p>
            </div>
          <?php else: ?>
            <?php foreach ($userNotes as $n): ?>
              <form action="" method="post" class="mb-3 position-relative">
                <input type="hidden" name="action" value="update_note">
                <input type="hidden" name="note_id" value="<?= $n['note_id'] ?>">

                <div class="d-flex justify-content-between align-items-center mb-2">
                  <div class="fs-11 text-500">
                    <span class="fas fa-clock me-1"></span>
                    Updated: <span class="fw-semi-bold"><?= date('M d, Y g:i A', strtotime($n['updated_at'])) ?></span>
                  </div>
                  <button type="submit" name="delete_note" value="1" class="btn btn-link text-danger p-0 ms-2"
                    title="Delete Note" onclick="return confirm('Are you sure you want to delete this note?');">
                    <span class="fas fa-trash-alt fs-10"></span>
                  </button>
                </div>

                <textarea class="form-control border-300 bg-white mb-2" name="note_content" rows="3"
                  required><?= htmlspecialchars($n['note_content']) ?></textarea>

                <div class="d-flex justify-content-end">
                  <button class="btn btn-falcon-default btn-sm" type="submit">
                    <span class="fas fa-save me-1"></span>Update Note
                  </button>
                </div>
              </form>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1" role="dialog" aria-labelledby="editProfileModalLabel"
  aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="" method="post">
        <input type="hidden" name="action" value="update_profile">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label" for="full_name">Full Name</label>
            <input class="form-control" id="full_name" name="full_name" type="text"
              value="<?= htmlspecialchars($userData['full_name'] ?? '') ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label" for="email">Email Address</label>
            <input class="form-control" id="email" name="email" type="email"
              value="<?= htmlspecialchars($userData['email'] ?? '') ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label" for="mobile">Mobile Number</label>
            <input class="form-control" id="mobile" name="mobile" type="text"
              value="<?= htmlspecialchars($userData['mobile'] ?? '') ?>">
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Close</button>
          <button class="btn btn-primary" type="submit">Save changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
require_once __DIR__ . '/../../includes/footer.php';
?>