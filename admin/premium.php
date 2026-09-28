<?php
session_start();
require '../include/database.php';

if(!isset($_SESSION['user_id'])){header("Location: login.php");exit;}

$user=$pdo->prepare("SELECT * FROM users WHERE id=?");
$user->execute([$_SESSION['user_id']]);
$user=$user->fetch();

$success="";

// fake payment processing
if(isset($_POST['pay'])){
    $pdo->prepare("UPDATE users SET is_premium=1 WHERE id=?")->execute([$_SESSION['user_id']]);
    $pdo->prepare("INSERT INTO subscriptions (user_id,plan) VALUES (?,?) ON DUPLICATE KEY UPDATE plan='premium'")->execute([$_SESSION['user_id'],'premium']);
    $success="🎉 Payment successful! You are now a Premium member!";
    $user['is_premium']=1;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><title>Premium — MyBlog</title>
  <style>
    *{margin:0;padding:0;box-sizing:border-box}
    body{background:#f4f4f8;font-family:sans-serif;color:#1a1a2e}
    nav{background:#1a1a2e;padding:14px 30px;display:flex;justify-content:space-between;align-items:center}
    nav .logo{font-size:22px;font-weight:bold;color:white}
    nav .logo span{color:#7f77dd}
    nav .links a{color:#ccc;text-decoration:none;margin-left:20px;font-size:14px}
    .container{max-width:900px;margin:40px auto;padding:0 20px}
    h2{font-size:28px;text-align:center;margin-bottom:8px}
    .subtitle{text-align:center;color:#888;margin-bottom:40px}

    /* plan cards */
    .plans{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:40px}
    .plan-card{background:white;border-radius:16px;padding:30px;box-shadow:0 2px 8px rgba(0,0,0,0.07);text-align:center;position:relative}
    .plan-card.premium{border:2px solid #f39c12}
    .plan-card .badge-plan{position:absolute;top:-12px;left:50%;transform:translateX(-50%);background:#f39c12;color:white;padding:4px 16px;border-radius:20px;font-size:12px;font-weight:bold}
    .plan-card h3{font-size:22px;margin-bottom:8px}
    .plan-card .price{font-size:36px;font-weight:bold;color:#1a1a2e;margin:12px 0}
    .plan-card .price span{font-size:14px;color:#888;font-weight:normal}
    .plan-card ul{list-style:none;text-align:left;margin:16px 0 24px}
    .plan-card ul li{padding:8px 0;font-size:14px;border-bottom:1px solid #f4f4f8;display:flex;align-items:center;gap:8px}
    .btn-plan{display:block;padding:13px;border-radius:8px;font-size:15px;font-weight:bold;text-decoration:none;border:none;cursor:pointer;width:100%}
    .btn-free   {background:#f4f4f8;color:#555}
    .btn-upgrade{background:#f39c12;color:white}
    .btn-upgrade:hover{background:#d68910}
    .btn-current{background:#27ae60;color:white;cursor:default}

    /* fake payment form */
    .payment-form{background:white;border-radius:16px;padding:30px;box-shadow:0 2px 8px rgba(0,0,0,0.07);max-width:500px;margin:0 auto}
    .payment-form h3{font-size:20px;margin-bottom:20px;text-align:center}
    .form-group{margin-bottom:16px}
    .form-group label{display:block;font-size:13px;font-weight:bold;color:#444;margin-bottom:6px}
    .form-group input{width:100%;padding:11px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px}
    .form-group input:focus{outline:none;border-color:#f39c12}
    .form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .btn-pay{width:100%;padding:14px;background:#f39c12;color:white;border:none;border-radius:8px;font-size:16px;font-weight:bold;cursor:pointer;margin-top:8px}
    .btn-pay:hover{background:#d68910}
    .demo-note{text-align:center;font-size:12px;color:#aaa;margin-top:10px}
    .success-box{background:#e6f9f0;color:#27ae60;padding:16px;border-radius:8px;text-align:center;font-weight:bold;margin-bottom:20px}
  </style>
</head>
<body>
  <nav>
    <div class="logo">My<span>Blog</span></div>
    <div class="links">
      <a href="home.php">🏠 Home</a>
      <a href="profile.php">👤 <?=htmlspecialchars($_SESSION['username'])?></a>
      <a href="logout.php">Logout</a>
    </div>
  </nav>
  <div class="container">
    <h2>💎 Upgrade to Premium</h2>
    <p class="subtitle">Unlock all features and support MyBlog</p>

    <?php if($success): ?><div class="success-box"><?=$success?></div><?php endif; ?>

    <!-- plan comparison -->
    <div class="plans">
      <div class="plan-card">
        <h3>Free</h3>
        <div class="price">₹0 <span>/month</span></div>
        <ul>
          <li>✅ Write & publish blogs</li>
          <li>✅ Like & comment</li>
          <li>✅ Follow writers</li>
          <li>❌ Premium badge</li>
          <li>❌ Priority support</li>
          <li>❌ Featured posts</li>
        </ul>
        <?php if(!$user['is_premium']): ?>
          <button class="btn-plan btn-current">Current Plan</button>
        <?php else: ?>
          <button class="btn-plan btn-free">Free Plan</button>
        <?php endif; ?>
      </div>

      <div class="plan-card premium">
        <div class="badge-plan">⭐ POPULAR</div>
        <h3>💎 Premium</h3>
        <div class="price">₹99 <span>/month</span></div>
        <ul>
          <li>✅ Write & publish blogs</li>
          <li>✅ Like & comment</li>
          <li>✅ Follow writers</li>
          <li>✅ 💎 Premium badge</li>
          <li>✅ Priority support</li>
          <li>✅ Featured posts</li>
        </ul>
        <?php if($user['is_premium']): ?>
          <button class="btn-plan btn-current">✅ Current Plan</button>
        <?php else: ?>
          <button class="btn-plan btn-upgrade" onclick="document.getElementById('payForm').scrollIntoView({behavior:'smooth'})">Upgrade Now →</button>
        <?php endif; ?>
      </div>
    </div>

    <!-- fake payment form -->
    <?php if(!$user['is_premium']): ?>
    <div class="payment-form" id="payForm">
      <h3>💳 Payment Details</h3>
      <form method="POST">
        <div class="form-group">
          <label>Cardholder Name</label>
          <input type="text" placeholder="John Doe" required>
        </div>
        <div class="form-group">
          <label>Card Number</label>
          <input type="text" placeholder="1234 5678 9012 3456" maxlength="19" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Expiry Date</label>
            <input type="text" placeholder="MM/YY" maxlength="5" required>
          </div>
          <div class="form-group">
            <label>CVV</label>
            <input type="text" placeholder="123" maxlength="3" required>
          </div>
        </div>
        <button type="submit" name="pay" class="btn-pay">💎 Pay ₹99 — Upgrade Now</button>
        <p class="demo-note">🔒 Demo only — no real payment is processed</p>
      </form>
    </div>
    <?php endif; ?>

  </div>
</body>
</html>