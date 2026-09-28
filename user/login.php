<?php
session_start();
require '../include/database.php';

if (isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit;
}

$error   = "";
$success = "";

if (isset($_POST['action']) && $_POST['action'] == 'login') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if ($user['is_banned'] == 1) {
            $error = "Your account has been banned. Contact support.";
        } else {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];
            header("Location: home.php");
            exit;
        }
    } else {
        $error = "Invalid email or password.";
    }
}

if (isset($_POST['action']) && $_POST['action'] == 'register') {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);

    if ($check->fetch()) {
        $error = "Email already registered!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $password]);
        $success = "Account created! Please login.";
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

  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In — MyBlog</title>
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="../assets/style.css" rel="stylesheet">
  <script src="../assets/theme.js"></script>
  <style>
    body { min-height:100vh; display:flex; }

    .login-left {
      flex: 1;
      background: var(--primary);
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 10px;
      position: relative;
      overflow: hidden;
    }

    .login-left::before {
      content: '';
      position: absolute;
      width: 400px; height: 400px;
      background: rgba(255,255,255,0.05);
      border-radius: 50%;
      top: -100px; right: -100px;
    }

    .login-left::after {
      content: '';
      position: absolute;
      width: 300px; height: 300px;
      background: rgba(255,255,255,0.05);
      border-radius: 50%;
      bottom: -80px; left: -80px;
    }

    .login-right {
      width: 480px;
      flex-shrink: 0;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 30px 48px;
      background: var(--bg-card);
      overflow-y: auto;
    }

    .tab-btn {
      flex: 1;
      padding: 12px;
      border: none;
      background: none;
      font-size: 14px;
      font-weight: 700;
      cursor: pointer;
      font-family: 'Inter', sans-serif;
      color: var(--text-muted);
      border-bottom: 2px solid transparent;
      margin-bottom: -2px;
      transition: all 0.2s;
    }

    .tab-btn.active {
      color: var(--primary);
      border-bottom-color: var(--primary);
    }

    @media (max-width: 768px) {
      .login-left { display: none; }
      .login-right { width: 100%; padding: 40px 24px; }
    }
  </style>
</head>
<body>

  <!-- left branding -->
  <div class="login-left">
    <div style="position:relative;z-index:1;text-align:center;color:white">
      <div style="font-size:48px;font-weight:900;letter-spacing:-2px;margin-bottom:20px">
        MY<span style="opacity:0.6">BLOG</span>
      </div>
      <p style="font-size:18px;line-height:1.7;opacity:0.9;max-width:340px;margin:0 auto 32px">
        Share your stories, ideas and expertise with the world.
      </p>
      <div style="display:flex;flex-direction:column;gap:14px;text-align:left">
        <?php foreach([
          ['edit','Write & publish your blogs'],
          ['people','Connect with other writers'],
          ['trending_up','Track your views & likes'],
          ['workspace_premium','Unlock premium features'],
        ] as [$icon,$text]): ?>
        <div style="display:flex;align-items:center;gap:12px;opacity:0.9">
          <div style="width:36px;height:36px;background:rgba(255,255,255,0.2);
                      border-radius:50%;display:flex;align-items:center;justify-content:center">
            <span class="material-icons" style="font-size:18px"><?= $icon ?></span>
          </div>
          <span style="font-size:14px;font-weight:500"><?= $text ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- right form -->
  <div class="login-right">

    <!-- logo -->
    <a href="home.php"
       style="font-size:24px;font-weight:900;color:var(--text);
              letter-spacing:-1px;margin-bottom:36px;display:block;
              text-decoration:none">
      MY<span style="color:var(--primary)">BLOG</span>
    </a>

    <!-- tabs -->
    <div style="display:flex;border-bottom:2px solid var(--border);margin-bottom:28px">
      <button class="tab-btn active" id="tab-login" onclick="showTab('login',this)">
        Sign In
      </button>
      <button class="tab-btn" id="tab-register" onclick="showTab('register',this)">
        Create Account
      </button>
    </div>

    <!-- alerts -->
    <?php if ($error): ?>
      <div class="alert alert-error">
        <span class="material-icons">error</span>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="alert alert-success">
        <span class="material-icons">check_circle</span>
        <?= htmlspecialchars($success) ?>
      </div>
    <?php endif; ?>

    <!-- login form -->
    <div id="form-login">
      <form method="POST">
        <input type="hidden" name="action" value="login">

        <div class="form-group">
          <label class="form-label">Email Address</label>
          <div class="input-wrap">
            <span class="material-icons">mail</span>
            <input type="email" name="email" placeholder="your@gmail.com" required
                   value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Password</label>
          <div class="input-wrap">
            <span class="material-icons">lock</span>
            <input type="password" name="password" placeholder="Enter your password" required>
          </div>
          <div style="text-align:right;margin-top:8px">
            <a href="forgot-password.php"
               style="font-size:12px;color:var(--primary);font-weight:600;text-decoration:none">
              Forgot password?
            </a>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:4px">
          Sign In
          <span class="material-icons">arrow_forward</span>
        </button>

      </form>
    </div>

    <!-- register form -->
    <div id="form-register" style="display:none">
      <form method="POST">
        <input type="hidden" name="action" value="register">

        <div class="form-group">
          <label class="form-label">Username</label>
          <div class="input-wrap">
            <span class="material-icons">person</span>
            <input type="text" name="username" placeholder="Choose a username" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Email Address</label>
          <div class="input-wrap">
            <span class="material-icons">mail</span>
            <input type="email" name="email" placeholder="your@gmail.com" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Password</label>
          <div class="input-wrap">
            <span class="material-icons">lock</span>
            <input type="password" name="password" placeholder="Min 6 characters" required>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:4px">
          Create Account
          <span class="material-icons">arrow_forward</span>
        </button>

      </form>
    </div>

    <!-- theme toggle -->
    <div style="position:fixed;bottom:24px;right:24px">
      <button class="theme-toggle" id="themeToggle" onclick="toggleTheme()">
        <span class="material-icons">dark_mode</span>
      </button>
    </div>

  </div>

  <script>
    function showTab(tab, btn) {
      document.getElementById('form-login').style.display    = tab=='login'    ? 'block':'none';
      document.getElementById('form-register').style.display = tab=='register' ? 'block':'none';
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
    }
    <?php if ($success): ?>
      showTab('login', document.getElementById('tab-login'));
    <?php endif; ?>
  </script>

</body>
</html>