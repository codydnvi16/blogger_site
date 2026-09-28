<?php
session_start();
require '../include/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user = $pdo->prepare("SELECT * FROM users WHERE id=?");
$user->execute([$_SESSION['user_id']]);
$user = $user->fetch();

$success = "";

if (isset($_POST['pay'])) {
    $pdo->prepare("UPDATE users SET is_premium=1 WHERE id=?")->execute([$_SESSION['user_id']]);
    $pdo->prepare("INSERT INTO subscriptions (user_id,plan) VALUES (?,?) ON DUPLICATE KEY UPDATE plan='premium'")->execute([$_SESSION['user_id'],'premium']);
    $success = "Payment successful! You are now a Premium member!";
    $user['is_premium'] = 1;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Premium — MyBlog</title>
  <link href="../assets/style.css" rel="stylesheet">
</head>
<body>

  <?php include '../include/navbar.php'; ?>

  <div class="container" style="padding-top:60px;padding-bottom:80px;max-width:900px">

    <!-- header -->
    <div style="text-align:center;margin-bottom:48px">
      <div style="display:inline-flex;align-items:center;justify-content:center;
                  width:64px;height:64px;background:var(--primary-light);
                  border-radius:50%;margin-bottom:16px">
        <span class="material-icons" style="font-size:32px;color:var(--primary)">workspace_premium</span>
      </div>
      <h1 style="font-size:34px;font-weight:900;letter-spacing:-1px;margin-bottom:10px">
        Go Premium
      </h1>
      <p style="font-size:16px;color:var(--text-sub);max-width:440px;margin:0 auto">
        Unlock all features and support the MyBlog community.
      </p>
    </div>

    <?php if ($success): ?>
      <div class="alert alert-success" style="max-width:500px;margin:0 auto 32px;text-align:center">
        <span class="material-icons">verified</span>
        <?= htmlspecialchars($success) ?>
      </div>
    <?php endif; ?>

    <!-- plan cards -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:40px">

      <!-- free plan -->
      <div class="card">
        <div style="margin-bottom:20px">
          <div style="font-size:13px;font-weight:700;color:var(--text-muted);
                      letter-spacing:1px;text-transform:uppercase;margin-bottom:8px">Free</div>
          <div style="font-size:36px;font-weight:900;color:var(--text)">
            ₹0 <span style="font-size:14px;font-weight:400;color:var(--text-muted)">/month</span>
          </div>
        </div>
        <div style="border-top:1px solid var(--border);padding-top:20px">
          <?php foreach(['Write & publish blogs','Like & comment','Follow writers','Basic profile'] as $f): ?>
          <div style="display:flex;align-items:center;gap:10px;padding:8px 0;font-size:14px;color:var(--text-sub)">
            <span class="material-icons" style="font-size:18px;color:#16a34a">check</span>
            <?= $f ?>
          </div>
          <?php endforeach; ?>
          <?php foreach(['Premium badge','Priority support','Featured posts'] as $f): ?>
          <div style="display:flex;align-items:center;gap:10px;padding:8px 0;font-size:14px;color:var(--text-muted)">
            <span class="material-icons" style="font-size:18px;color:var(--border)">close</span>
            <?= $f ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php if (!$user['is_premium']): ?>
          <div class="btn btn-secondary btn-block" style="margin-top:24px;cursor:default;opacity:0.6">
            Current Plan
          </div>
        <?php endif; ?>
      </div>

      <!-- premium plan -->
      <div style="background:var(--primary);border-radius:4px;padding:24px;
                  position:relative;overflow:hidden">
        <div style="position:absolute;top:-30px;right:-30px;width:120px;height:120px;
                    background:rgba(255,255,255,0.1);border-radius:50%"></div>
        <div style="position:absolute;bottom:-20px;left:-20px;width:80px;height:80px;
                    background:rgba(255,255,255,0.08);border-radius:50%"></div>
        <div style="position:relative;z-index:1">
          <div style="display:inline-block;background:white;color:var(--primary);
                      padding:3px 12px;border-radius:2px;font-size:10px;
                      font-weight:800;letter-spacing:1px;text-transform:uppercase;
                      margin-bottom:12px">
            Most Popular
          </div>
          <div style="font-size:13px;font-weight:700;color:rgba(255,255,255,0.7);
                      letter-spacing:1px;text-transform:uppercase;margin-bottom:8px">Premium</div>
          <div style="font-size:36px;font-weight:900;color:white;margin-bottom:20px">
            ₹99 <span style="font-size:14px;font-weight:400;opacity:0.7">/month</span>
          </div>
          <div style="border-top:1px solid rgba(255,255,255,0.2);padding-top:20px">
            <?php foreach(['Write & publish blogs','Like & comment','Follow writers','Basic profile','Premium badge','Priority support','Featured posts'] as $f): ?>
            <div style="display:flex;align-items:center;gap:10px;padding:7px 0;font-size:14px;color:white">
              <span class="material-icons" style="font-size:18px">check</span>
              <?= $f ?>
            </div>
            <?php endforeach; ?>
          </div>
          <?php if ($user['is_premium']): ?>
            <div style="margin-top:24px;padding:13px;background:rgba(255,255,255,0.2);
                        border-radius:4px;text-align:center;color:white;
                        font-weight:700;font-size:14px">
              ✓ You are Premium
            </div>
          <?php else: ?>
            <button onclick="document.getElementById('payForm').scrollIntoView({behavior:'smooth'})"
                    style="margin-top:24px;width:100%;padding:13px;
                           background:white;color:var(--primary);border:none;
                           border-radius:4px;font-size:14px;font-weight:800;
                           cursor:pointer;font-family:Inter,sans-serif">
              Upgrade Now →
            </button>
          <?php endif; ?>
        </div>
      </div>

    </div>

    <!-- payment form -->
    <?php if (!$user['is_premium']): ?>
    <div class="card" id="payForm" style="max-width:500px;margin:0 auto">
      <div style="text-align:center;margin-bottom:24px">
        <h3 style="font-size:18px;font-weight:800;margin-bottom:4px">Payment Details</h3>
        <p style="font-size:13px;color:var(--text-muted)">Demo only — no real payment processed</p>
      </div>
      <form method="POST">
        <div class="form-group">
          <label class="form-label">Cardholder Name</label>
          <div class="input-wrap">
            <span class="material-icons">person</span>
            <input type="text" placeholder="Full name" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Card Number</label>
          <div class="input-wrap">
            <span class="material-icons">credit_card</span>
            <input type="text" placeholder="1234 5678 9012 3456" maxlength="19" required>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
          <div class="form-group">
            <label class="form-label">Expiry</label>
            <div class="input-wrap">
              <span class="material-icons">date_range</span>
              <input type="text" placeholder="MM/YY" maxlength="5" required>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">CVV</label>
            <div class="input-wrap">
              <span class="material-icons">lock</span>
              <input type="text" placeholder="123" maxlength="3" required>
            </div>
          </div>
        </div>
        <button type="submit" name="pay" class="btn btn-primary btn-block btn-lg">
          <span class="material-icons">workspace_premium</span>
          Pay ₹99 — Upgrade Now
        </button>
        <p style="text-align:center;font-size:11px;color:var(--text-muted);margin-top:12px">
          <span class="material-icons" style="font-size:13px;vertical-align:middle">lock</span>
          Secure demo payment — no real charge
        </p>
      </form>
    </div>
    <?php endif; ?>

  </div>

</body>
</html>