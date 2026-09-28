<?php
session_name('admin_session');
session_start();
require '../include/database.php';

if (isset($_SESSION['user_id'])&&$_SESSION['role']=='admin') {
    header("Location: dashboard.php"); exit;
}

$success=""; $error="";

if ($_SERVER['REQUEST_METHOD']=='POST') {
    $email   = trim($_POST['email']);
    $password= $_POST['password'];
    $secret  = $_POST['secret_key'];
    $correct = 'myblog@admin2024';

    if ($secret!==$correct) {
        $error = "Wrong secret key! Contact the site owner.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email=?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user) { $error="No account found with that email. Register first."; }
        elseif (!password_verify($password,$user['password'])) { $error="Wrong password."; }
        elseif ($user['role']=='admin') { $error="This account is already an admin!"; }
        else {
            $pdo->prepare("UPDATE users SET role='admin' WHERE email=?")->execute([$email]);
            $success="Admin access granted successfully!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <title>Request Admin Access — MyBlog</title>
  <?php include 'head.php'; ?>
  <style>
    body { background:var(--sidebar-bg); display:flex; align-items:center; justify-content:center; min-height:100vh; padding:24px; }
    .box { background:var(--bg-card); border-radius:12px; padding:40px 44px; max-width:460px; width:100%; box-shadow:0 20px 60px rgba(0,0,0,0.3); }
  </style>
</head>
<body>

  <div class="box">

    <div style="text-align:center;margin-bottom:28px">
      <div style="display:inline-flex;align-items:center;justify-content:center;
                  width:52px;height:52px;background:var(--primary);
                  border-radius:10px;margin-bottom:14px">
        <span class="material-icons" style="color:white;font-size:26px">manage_accounts</span>
      </div>
      <h1 style="font-size:22px;font-weight:900;color:var(--text);letter-spacing:-0.5px;margin-bottom:4px">
        Request Admin Access
      </h1>
      <p style="font-size:13px;color:var(--text-muted)">
        Enter your account details and the secret key.
      </p>
    </div>

    <!-- steps -->
    <div style="display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:24px">
      <div style="width:28px;height:28px;border-radius:50%;
                  background:<?= $success?'#059669':'var(--primary)' ?>;
                  display:flex;align-items:center;justify-content:center;
                  color:white;font-size:12px;font-weight:700">
        <?= $success?'✓':'1' ?>
      </div>
      <div style="width:40px;height:2px;background:<?= $success?'var(--primary)':'var(--border)' ?>"></div>
      <div style="width:28px;height:28px;border-radius:50%;
                  background:<?= $success?'var(--primary)':'var(--border)' ?>;
                  display:flex;align-items:center;justify-content:center;
                  color:<?= $success?'white':'var(--text-muted)' ?>;font-size:12px;font-weight:700">
        2
      </div>
    </div>

    <?php if ($success): ?>
      <div class="alert alert-success"><span class="material-icons">verified</span><?= htmlspecialchars($success) ?></div>
      <a href="login.php" class="btn btn-primary btn-block btn-lg">
        <span class="material-icons">login</span> Login to Admin Panel
      </a>
    <?php else: ?>
      <?php if ($error): ?><div class="alert alert-error"><span class="material-icons">error</span><?= htmlspecialchars($error) ?></div><?php endif; ?>

      <div style="background:var(--bg-input);border-radius:6px;padding:12px 14px;
                  font-size:12px;color:var(--text-muted);margin-bottom:20px;line-height:1.5">
        <span class="material-icons" style="font-size:14px;vertical-align:middle;color:var(--primary)">info</span>
        You must already have a registered account. Enter your site email, password and the secret key.
      </div>

      <form method="POST">
        <div class="form-group">
          <label class="form-label">Your Registered Email</label>
          <div class="input-wrap">
            <span class="material-icons">mail</span>
            <input type="email" name="email" placeholder="email used on site"
                   value="<?= isset($_POST['email'])?htmlspecialchars($_POST['email']):'' ?>" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Your Password</label>
          <div class="input-wrap">
            <span class="material-icons">lock</span>
            <input type="password" name="password" placeholder="your account password" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Secret Key</label>
          <div class="input-wrap">
            <span class="material-icons">vpn_key</span>
            <input type="password" name="secret_key" placeholder="given by site owner" required>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg">
          <span class="material-icons">admin_panel_settings</span>
          Request Admin Access
        </button>
      </form>
    <?php endif; ?>

    <div style="text-align:center;margin-top:20px;padding-top:16px;border-top:1px solid var(--border)">
      <a href="login.php"
         style="font-size:13px;color:var(--text-muted);text-decoration:none;
                display:inline-flex;align-items:center;gap:4px">
        <span class="material-icons" style="font-size:15px">arrow_back</span>
        Back to Admin Login
      </a>
    </div>

    <div style="position:fixed;bottom:20px;right:20px">
      <button class="topbar-btn" id="themeToggle" onclick="toggleAdminTheme()">
        <span class="material-icons">dark_mode</span>
      </button>
    </div>

  </div>

</body>
</html>