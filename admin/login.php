<?php
session_name('admin_session');
session_start();
require '../include/database.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role']=='admin') { header("Location: dashboard.php"); exit; }
    else { session_destroy(); session_start(); }
}

$error = "";

if ($_SERVER['REQUEST_METHOD']=='POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email=? AND role='admin'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password,$user['password'])) {
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role']     = $user['role'];
        header("Location: dashboard.php"); exit;
    } else {
        $error = "Invalid admin credentials.";
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>

<script>
(function () {
history.pushState(null, "", location.href);

window.onpopstate = function () {
history.go(1);
};
})();
</script>

  <title>Admin Login — MyBlog</title>
  <?php include 'head.php'; ?>
  <style>
    body { display:flex; align-items:stretch; min-height:100vh; background:var(--sidebar-bg); }
    .login-left {
      flex:1; background:var(--sidebar-bg);
      display:flex; flex-direction:column;
      justify-content:center; align-items:center; padding:60px;
      position:relative; overflow:hidden;
    }
    .login-left::before {
      content:''; position:absolute;
      width:400px; height:400px; border-radius:50%;
      background:rgba(234,88,12,0.08);
      top:-100px; right:-100px;
    }
    .login-left::after {
      content:''; position:absolute;
      width:300px; height:300px; border-radius:50%;
      background:rgba(234,88,12,0.05);
      bottom:-80px; left:-80px;
    }
    .login-right {
      width:440px; flex-shrink:0;
      background:var(--bg-card);
      display:flex; flex-direction:column;
      justify-content:center; padding:56px 48px;
      overflow-y:auto;
    }
    @media(max-width:768px) {
      .login-left { display:none; }
      .login-right { width:100%; padding:40px 24px; }
    }
  </style>
</head>
<body>

  <!-- left -->
  <div class="login-left">
    <div style="position:relative;z-index:1;text-align:center">
      <div style="font-size:42px;font-weight:900;color:white;
                  letter-spacing:-1.5px;margin-bottom:8px">
        MY<span style="color:var(--primary)">BLOG</span>
      </div>
      <div style="font-size:11px;font-weight:700;letter-spacing:3px;
                  text-transform:uppercase;color:#4b5563;margin-bottom:32px">
        Admin Panel
      </div>
      <div style="display:flex;flex-direction:column;gap:16px;text-align:left">
        <?php foreach([
          ['dashboard','Full Dashboard Analytics'],
          ['article','Manage All Blog Posts'],
          ['group','Control User Accounts'],
          ['flag','Review & Resolve Reports'],
        ] as [$icon,$text]): ?>
        <div style="display:flex;align-items:center;gap:12px">
          <div style="width:36px;height:36px;background:rgba(234,88,12,0.15);
                      border-radius:8px;display:flex;align-items:center;justify-content:center">
            <span class="material-icons" style="font-size:18px;color:var(--primary)"><?= $icon ?></span>
          </div>
          <span style="font-size:14px;color:#9ca3af;font-weight:500"><?= $text ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- right -->
  <div class="login-right">

    <div style="margin-bottom:36px">
      <div style="display:inline-flex;align-items:center;justify-content:center;
                  width:48px;height:48px;background:var(--primary);
                  border-radius:10px;margin-bottom:16px">
        <span class="material-icons" style="color:white;font-size:24px">admin_panel_settings</span>
      </div>
      <h1 style="font-size:24px;font-weight:900;color:var(--text);
                  letter-spacing:-0.5px;margin-bottom:4px">
        Admin Sign In
      </h1>
      <p style="font-size:13px;color:var(--text-muted)">
        Sign in with your administrator credentials.
      </p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label class="form-label">Email Address</label>
        <div class="input-wrap">
          <span class="material-icons">mail</span>
          <input type="email" name="email" placeholder="admin@email.com" required
                 value="<?= isset($_POST['email'])?htmlspecialchars($_POST['email']):'' ?>">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <div class="input-wrap">
          <span class="material-icons">lock</span>
          <input type="password" name="password" placeholder="Admin password" required>
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:4px">
        <span class="material-icons">login</span>
        Sign In to Admin Panel
      </button>
    </form>

    <div style="margin-top:24px;padding-top:20px;border-top:1px solid var(--border)">
      <p style="font-size:12px;color:var(--text-muted);margin-bottom:10px;text-align:center">
        Want admin access?
      </p>
      <a href="setup-admin.php"
         style="display:flex;align-items:center;justify-content:center;gap:6px;
                padding:9px;border:1px solid var(--border);border-radius:6px;
                font-size:12px;font-weight:600;color:var(--text-muted);
                text-decoration:none;transition:all 0.2s"
         onmouseover="this.style.borderColor='var(--primary)';this.style.color='var(--primary)'"
         onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--text-muted)'">
        <span class="material-icons" style="font-size:16px">manage_accounts</span>
        Request Admin Access
      </a>
    </div>

    <!-- theme toggle -->
    <div style="position:fixed;bottom:20px;right:20px">
      <button class="topbar-btn" id="themeToggle" onclick="toggleAdminTheme()">
        <span class="material-icons">dark_mode</span>
      </button>
    </div>

  </div>

</body>
</html>