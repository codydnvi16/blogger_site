<?php
session_start();
require '../include/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$success = ""; $error = "";

if (isset($_POST['action']) && $_POST['action']=='profile') {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $bio      = trim($_POST['bio']);
    $photo    = $user['photo'];

    if (empty($username)) { $error = "Username required."; }
    elseif (empty($email)) { $error = "Email required."; }
    else {
        $cu = $pdo->prepare("SELECT id FROM users WHERE username=? AND id!=?");
        $cu->execute([$username, $_SESSION['user_id']]);
        if ($cu->fetch()) { $error = "Username already taken."; }
        else {
            $ce = $pdo->prepare("SELECT id FROM users WHERE email=? AND id!=?");
            $ce->execute([$email, $_SESSION['user_id']]);
            if ($ce->fetch()) { $error = "Email already used."; }
            else {
                if (!empty($_FILES['photo']['name'])) {
                    $allowed = ['jpg','jpeg','png','webp'];
                    $ext = strtolower(pathinfo($_FILES['photo']['name'],PATHINFO_EXTENSION));
                    if (in_array($ext,$allowed)) {
                        $dir = '../uploads/';
                        if (!is_dir($dir)) mkdir($dir,0777,true);
                        $filename = 'avatar_'.$_SESSION['user_id'].'_'.time().'.'.$ext;
                        if (move_uploaded_file($_FILES['photo']['tmp_name'],$dir.$filename)) {
                            $photo = $filename;
                        }
                    }
                }
                $pdo->prepare("UPDATE users SET username=?,email=?,bio=?,photo=? WHERE id=?")
                    ->execute([$username,$email,$bio,$photo,$_SESSION['user_id']]);
                $_SESSION['username'] = $username;
                $success = "Profile updated!";
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
            }
        }
    }
}

if (isset($_POST['action']) && $_POST['action']=='password') {
    $current = $_POST['current_password'];
    $new     = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    if (!password_verify($current,$user['password'])) { $error = "Current password incorrect."; }
    elseif (strlen($new)<6) { $error = "Password min 6 characters."; }
    elseif ($new!==$confirm) { $error = "Passwords do not match."; }
    else {
        $pdo->prepare("UPDATE users SET password=? WHERE id=?")
            ->execute([password_hash($new,PASSWORD_DEFAULT),$_SESSION['user_id']]);
        $success = "Password changed!";
    }
}

if (isset($_GET['remove_photo'])) {
    $pdo->prepare("UPDATE users SET photo='' WHERE id=?")->execute([$_SESSION['user_id']]);
    header("Location: edit-profile.php?msg=photo_removed"); exit;
}
if (isset($_GET['msg']) && $_GET['msg']=='photo_removed') {
    $success = "Profile photo removed.";
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Profile — MyBlog</title>
  <link href="../assets/style.css" rel="stylesheet">
</head>
<body>

  <?php include '../include/navbar.php'; ?>

  <div style="max-width:720px;margin:40px auto;padding:0 24px 60px">

    <div style="margin-bottom:28px">
      <a href="profile.php"
         style="display:inline-flex;align-items:center;gap:4px;
                color:var(--text-muted);text-decoration:none;
                font-size:13px;margin-bottom:14px">
        <span class="material-icons" style="font-size:16px">arrow_back</span>
        Back to Profile
      </a>
      <h1 style="font-size:26px;font-weight:900;letter-spacing:-0.5px">Edit Profile</h1>
    </div>

    <?php if ($success): ?>
      <div class="alert alert-success"><span class="material-icons">check_circle</span><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-error"><span class="material-icons">error</span><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- profile info -->
    <div class="card" style="margin-bottom:20px">
      <div style="font-size:14px;font-weight:800;margin-bottom:20px;
                  padding-bottom:14px;border-bottom:1px solid var(--border);
                  display:flex;align-items:center;gap:8px">
        <span class="material-icons" style="color:var(--primary)">person</span>
        Profile Information
      </div>

      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="profile">

        <!-- avatar -->
        <div style="display:flex;align-items:center;gap:20px;
                    margin-bottom:24px;padding:16px;
                    background:var(--bg-input);border-radius:4px">
          <div class="avatar avatar-lg" id="avatarBox">
            <?php if ($user['photo']): ?>
              <img src="../uploads/<?= htmlspecialchars($user['photo']) ?>" alt="" id="currentPhoto">
            <?php else: ?>
              <span id="avatarLetter"><?= strtoupper(substr($user['username'],0,1)) ?></span>
            <?php endif; ?>
          </div>
          <div>
            <div style="font-weight:700;font-size:14px;margin-bottom:4px">Profile Photo</div>
            <div style="font-size:12px;color:var(--text-muted);margin-bottom:10px">
              JPG, PNG or WEBP. Recommended square.
            </div>
            <div style="display:flex;gap:8px">
              <label for="photo" class="btn btn-primary btn-sm" style="cursor:pointer">
                <span class="material-icons" style="font-size:15px">photo_camera</span>
                Change Photo
              </label>
              <input type="file" id="photo" name="photo" accept="image/*"
                     style="display:none" onchange="previewPhoto(this)">
              <?php if ($user['photo']): ?>
                <a href="edit-profile.php?remove_photo=1"
                   class="btn btn-danger btn-sm"
                   onclick="return confirm('Remove photo?')">
                  <span class="material-icons" style="font-size:15px">delete</span>
                  Remove
                </a>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
          <div class="form-group">
            <label class="form-label">Username *</label>
            <div class="input-wrap">
              <span class="material-icons">person</span>
              <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Email *</label>
            <div class="input-wrap">
              <span class="material-icons">mail</span>
              <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Bio</label>
          <div class="input-wrap no-icon">
            <textarea name="bio" placeholder="Tell others about yourself..."
                      style="min-height:90px;resize:vertical"><?= htmlspecialchars($user['bio']??'') ?></textarea>
          </div>
        </div>

        <button type="submit" class="btn btn-primary">
          <span class="material-icons" style="font-size:17px">save</span>
          Save Profile
        </button>
      </form>
    </div>

    <!-- change password -->
    <div class="card" style="margin-bottom:20px">
      <div style="font-size:14px;font-weight:800;margin-bottom:20px;
                  padding-bottom:14px;border-bottom:1px solid var(--border);
                  display:flex;align-items:center;gap:8px">
        <span class="material-icons" style="color:var(--primary)">lock</span>
        Change Password
      </div>
      <form method="POST">
        <input type="hidden" name="action" value="password">
        <div class="form-group">
          <label class="form-label">Current Password</label>
          <div class="input-wrap">
            <span class="material-icons">lock_outline</span>
            <input type="password" name="current_password" placeholder="Current password" required>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
          <div class="form-group">
            <label class="form-label">New Password</label>
            <div class="input-wrap">
              <span class="material-icons">lock</span>
              <input type="password" name="new_password" id="newPass" placeholder="Min 6 chars" required>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Confirm Password</label>
            <div class="input-wrap">
              <span class="material-icons">lock</span>
              <input type="password" name="confirm_password" id="confirmPass" placeholder="Repeat password" required>
            </div>
            <div id="matchHint" style="font-size:12px;margin-top:5px"></div>
          </div>
        </div>
        <button type="submit" class="btn btn-primary">
          <span class="material-icons" style="font-size:17px">lock_reset</span>
          Change Password
        </button>
      </form>
    </div>

    <!-- account info -->
    <div class="card">
      <div style="font-size:14px;font-weight:800;margin-bottom:16px;
                  padding-bottom:14px;border-bottom:1px solid var(--border);
                  display:flex;align-items:center;gap:8px">
        <span class="material-icons" style="color:var(--primary)">info</span>
        Account Information
      </div>
      <?php
      $rows = [
        ['badge','Account ID','#'.$user['id']],
        ['calendar_today','Member Since',date('d M Y',strtotime($user['created_at']))],
        ['admin_panel_settings','Role',ucfirst($user['role'])],
        ['workspace_premium','Plan',$user['is_premium']?'Premium':'Free'],
      ];
      foreach ($rows as [$icon,$label,$value]):
      ?>
      <div style="display:flex;align-items:center;padding:12px 0;
                  border-bottom:1px solid var(--border)">
        <span class="material-icons" style="font-size:17px;color:var(--text-muted);
                                             margin-right:10px;width:24px"><?= $icon ?></span>
        <span style="font-size:13px;color:var(--text-muted);width:140px"><?= $label ?></span>
        <span style="font-size:14px;font-weight:600;color:var(--text)"><?= $value ?></span>
      </div>
      <?php endforeach; ?>
    </div>

  </div>

  <script>
    function previewPhoto(input) {
      if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
          document.getElementById('avatarBox').innerHTML =
            '<img src="'+e.target.result+'" style="width:100%;height:100%;object-fit:cover;border-radius:50%">';
        };
        reader.readAsDataURL(input.files[0]);
      }
    }

    document.getElementById('confirmPass').addEventListener('input', function() {
      var hint = document.getElementById('matchHint');
      if (!this.value) { hint.textContent=''; return; }
      if (this.value===document.getElementById('newPass').value) {
        hint.innerHTML='<span style="color:#16a34a">✓ Passwords match</span>';
      } else {
        hint.innerHTML='<span style="color:#dc2626">✗ Passwords do not match</span>';
      }
    });
  </script>

</body>
</html>