<?php
session_start();
require '../include/database.php';

$error   = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        $error = "No account found with that email address.";
    } else {
        // generate token
        $token = bin2hex(random_bytes(32));

        // delete old tokens for this email
        $pdo->prepare("DELETE FROM password_resets WHERE email = ?")
            ->execute([$email]);

        // save new token
        $pdo->prepare("INSERT INTO password_resets (email, token) VALUES (?, ?)")
            ->execute([$email, $token]);

        // show reset link (in real app this would be emailed)
        $resetLink = "http://localhost/myblog/user/reset-password.php?token=" . $token;
        $success   = $resetLink;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password — MyBlog</title>
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

    .forgot-box {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 40px 44px;
      max-width: 460px;
      width: 100%;
      box-shadow: var(--shadow-lg);
    }
  </style>
</head>
<body>

  <div class="forgot-box">

    <div style="text-align:center;margin-bottom:28px">
      <div style="width:56px;height:56px;background:var(--primary-light);
                  border-radius:50%;display:flex;align-items:center;
                  justify-content:center;margin:0 auto 16px">
        <span class="material-icons" style="font-size:28px;color:var(--primary)">lock_open</span>
      </div>
      <h1 style="font-size:22px;font-weight:900;color:var(--text);
                  letter-spacing:-0.5px;margin-bottom:6px">
        Forgot Password?
      </h1>
      <p style="font-size:13px;color:var(--text-muted)">
        Enter your email and we'll generate a reset link.
      </p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error" style="margin-bottom:16px">
        <span class="material-icons">error</span>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div style="background:var(--bg-input);border:1px solid var(--border);
                  border-radius:6px;padding:16px;margin-bottom:20px">
        <div style="font-size:12px;font-weight:700;color:var(--text);
                    margin-bottom:8px;display:flex;align-items:center;gap:6px">
          <span class="material-icons" style="font-size:16px;color:var(--primary)">link</span>
          Your Password Reset Link:
        </div>
        <a href="<?= htmlspecialchars($success) ?>"
           style="font-size:12px;color:var(--primary);word-break:break-all;
                  text-decoration:none;line-height:1.5">
          <?= htmlspecialchars($success) ?>
        </a>
        <div style="margin-top:12px">
          <a href="<?= htmlspecialchars($success) ?>"
             class="btn btn-primary btn-block">
            <span class="material-icons">lock_reset</span>
            Click to Reset Password
          </a>
        </div>
        <p style="font-size:11px;color:var(--text-muted);margin-top:10px;text-align:center">
          This link expires in 1 hour.
        </p>
      </div>
    <?php else: ?>

      <form method="POST">
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <div class="input-wrap">
            <span class="material-icons">mail</span>
            <input type="email" name="email"
                   placeholder="Enter your registered email"
                   value="<?= isset($_POST['email'])?htmlspecialchars($_POST['email']):'' ?>"
                   required autofocus>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg">
          <span class="material-icons">send</span>
          Get Reset Link
        </button>
      </form>

    <?php endif; ?>

    <div style="text-align:center;margin-top:20px;padding-top:16px;
                border-top:1px solid var(--border)">
      <a href="login.php"
         style="font-size:13px;color:var(--text-muted);text-decoration:none;
                display:inline-flex;align-items:center;gap:4px">
        <span class="material-icons" style="font-size:15px">arrow_back</span>
        Back to Login
      </a>
    </div>

    <div style="position:fixed;bottom:20px;right:20px">
      <button class="theme-toggle" id="themeToggle" onclick="toggleTheme()">
        <span class="material-icons">dark_mode</span>
      </button>
    </div>

  </div>

</body>
</html>