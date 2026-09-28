<?php
session_start();
require '../include/database.php';

$error   = "";
$success = "";
$token   = $_GET['token'] ?? '';
$validToken = false;
$userEmail  = "";

// check if token exists and not expired (within 1 hour)
if ($token) {
    $stmt = $pdo->prepare("
        SELECT * FROM password_resets
        WHERE token = ?
        AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $stmt->execute([$token]);
    $reset = $stmt->fetch();

    if ($reset) {
        $validToken = true;
        $userEmail  = $reset['email'];
    } else {
        $error = "This reset link has expired or is invalid. Please request a new one.";
    }
}

// handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $validToken) {
    $new_password     = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($new_password)) {
        $error = "Password cannot be empty.";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // update password
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE email = ?")
            ->execute([$hashed, $userEmail]);

        // delete used token
        $pdo->prepare("DELETE FROM password_resets WHERE email = ?")
            ->execute([$userEmail]);

        $success = "Password changed successfully! You can now login.";
        $validToken = false;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password — MyBlog</title>
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="../assets/style.css" rel="stylesheet">
  <script src="../assets/theme.js"></script>
  <style>
    body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--bg);
      padding: 24px;
    }

    .reset-box {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 40px 44px;
      max-width: 460px;
      width: 100%;
      box-shadow: var(--shadow-lg);
    }

    .reset-icon {
      width: 56px; height: 56px;
      background: var(--primary-light);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 16px;
    }

    .reset-icon .material-icons { font-size: 28px; color: var(--primary); }
  </style>
</head>
<body>

  <div class="reset-box">

    <!-- icon + title -->
    <div style="text-align:center;margin-bottom:28px">
      <div class="reset-icon">
        <span class="material-icons">lock_reset</span>
      </div>
      <h1 style="font-size:22px;font-weight:900;color:var(--text);
                  letter-spacing:-0.5px;margin-bottom:6px">
        Reset Password
      </h1>
      <p style="font-size:13px;color:var(--text-muted)">
        Enter your new password below.
      </p>
    </div>

    <!-- success message -->
    <?php if ($success): ?>
      <div class="alert alert-success" style="margin-bottom:20px">
        <span class="material-icons">check_circle</span>
        <?= htmlspecialchars($success) ?>
      </div>
      <a href="login.php"
         class="btn btn-primary btn-block btn-lg">
        <span class="material-icons">login</span>
        Go to Login
      </a>

    <!-- error with no valid token -->
    <?php elseif ($error && !$validToken): ?>
      <div class="alert alert-error" style="margin-bottom:20px">
        <span class="material-icons">error</span>
        <?= htmlspecialchars($error) ?>
      </div>
      <a href="forgot-password.php"
         class="btn btn-primary btn-block btn-lg">
        <span class="material-icons">arrow_back</span>
        Request New Reset Link
      </a>

    <!-- no token provided -->
    <?php elseif (!$token): ?>
      <div class="alert alert-error" style="margin-bottom:20px">
        <span class="material-icons">error</span>
        Invalid reset link. Please request a new one.
      </div>
      <a href="forgot-password.php"
         class="btn btn-primary btn-block btn-lg">
        <span class="material-icons">arrow_back</span>
        Request Reset Link
      </a>

    <!-- valid token — show form -->
    <?php else: ?>

      <!-- inline error -->
      <?php if ($error): ?>
        <div class="alert alert-error" style="margin-bottom:16px">
          <span class="material-icons">error</span>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST">

        <div class="form-group">
          <label class="form-label">New Password</label>
          <div class="input-wrap">
            <span class="material-icons">lock</span>
            <input type="password"
                   name="new_password"
                   id="newPass"
                   placeholder="Min 6 characters"
                   required
                   oninput="checkStrength(this.value)">
          </div>
          <!-- password strength bar -->
          <div style="margin-top:8px">
            <div style="height:4px;background:var(--border);border-radius:2px;overflow:hidden">
              <div id="strengthBar"
                   style="height:100%;width:0%;border-radius:2px;transition:all 0.3s"></div>
            </div>
            <div id="strengthText"
                 style="font-size:11px;color:var(--text-muted);margin-top:4px"></div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Confirm New Password</label>
          <div class="input-wrap">
            <span class="material-icons">lock</span>
            <input type="password"
                   name="confirm_password"
                   id="confirmPass"
                   placeholder="Repeat new password"
                   required
                   oninput="checkMatch()">
          </div>
          <div id="matchHint" style="font-size:12px;margin-top:5px"></div>
        </div>

        <button type="submit"
                class="btn btn-primary btn-block btn-lg"
                style="margin-top:4px">
          <span class="material-icons">lock_reset</span>
          Reset Password
        </button>

      </form>

    <?php endif; ?>

    <!-- back to login -->
    <div style="text-align:center;margin-top:20px;padding-top:16px;
                border-top:1px solid var(--border)">
      <a href="login.php"
         style="font-size:13px;color:var(--text-muted);text-decoration:none;
                display:inline-flex;align-items:center;gap:4px">
        <span class="material-icons" style="font-size:15px">arrow_back</span>
        Back to Login
      </a>
    </div>

    <!-- theme toggle -->
    <div style="position:fixed;bottom:20px;right:20px">
      <button class="theme-toggle" id="themeToggle" onclick="toggleTheme()">
        <span class="material-icons">dark_mode</span>
      </button>
    </div>

  </div>

  <script>
    // password strength checker
    function checkStrength(val) {
      var bar  = document.getElementById('strengthBar');
      var text = document.getElementById('strengthText');
      var score = 0;

      if (val.length >= 6)  score++;
      if (val.length >= 10) score++;
      if (/[A-Z]/.test(val)) score++;
      if (/[0-9]/.test(val)) score++;
      if (/[^A-Za-z0-9]/.test(val)) score++;

      var configs = [
        { pct:'0%',   color:'',        label:'' },
        { pct:'25%',  color:'#dc2626', label:'Weak' },
        { pct:'50%',  color:'#f59e0b', label:'Fair' },
        { pct:'75%',  color:'#2563eb', label:'Good' },
        { pct:'100%', color:'#059669', label:'Strong' },
      ];

      var c = configs[Math.min(score, 4)];
      bar.style.width            = c.pct;
      bar.style.backgroundColor  = c.color;
      text.textContent           = c.label;
      text.style.color           = c.color;
    }

    // password match checker
    function checkMatch() {
      var np   = document.getElementById('newPass').value;
      var cp   = document.getElementById('confirmPass').value;
      var hint = document.getElementById('matchHint');

      if (!cp) { hint.innerHTML = ''; return; }

      if (np === cp) {
        hint.innerHTML = '<span style="color:#059669;display:flex;align-items:center;gap:4px"><span class="material-icons" style="font-size:14px">check_circle</span> Passwords match</span>';
      } else {
        hint.innerHTML = '<span style="color:#dc2626;display:flex;align-items:center;gap:4px"><span class="material-icons" style="font-size:14px">cancel</span> Passwords do not match</span>';
      }
    }
  </script>

</body>
</html>