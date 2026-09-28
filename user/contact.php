<?php
session_start();
require '../include/database.php';

$success = ""; $error = "";

if ($_SERVER['REQUEST_METHOD']=='POST') {
    $name    = trim($_POST['name']);
    $email   = trim($_POST['email']);
    $message = trim($_POST['message']);
    if ($name && $email && $message) {
        $pdo->prepare("INSERT INTO contacts (name,email,message) VALUES (?,?,?)")
            ->execute([$name,$email,$message]);
        $success = "Message sent! We'll get back to you soon.";
    } else {
        $error = "Please fill all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact — MyBlog</title>
  <link href="../assets/style.css" rel="stylesheet">
</head>
<body>

  <?php include '../include/navbar.php'; ?>

  <div class="container" style="padding-top:60px;padding-bottom:80px;max-width:900px">

    <div style="text-align:center;margin-bottom:48px">
      <div class="section-title" style="justify-content:center">
        <span class="material-icons">mail</span>
        Contact Us
      </div>
      <h1 style="font-size:34px;font-weight:900;letter-spacing:-1px;margin-bottom:10px">
        Get In Touch
      </h1>
      <p style="font-size:15px;color:var(--text-sub)">
        Have a question or feedback? We'd love to hear from you.
      </p>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:40px;align-items:start">

      <!-- form -->
      <div class="card">
        <?php if ($success): ?>
          <div class="alert alert-success" style="margin-bottom:20px">
            <span class="material-icons">check_circle</span>
            <?= htmlspecialchars($success) ?>
          </div>
        <?php endif; ?>
        <?php if ($error): ?>
          <div class="alert alert-error" style="margin-bottom:20px">
            <span class="material-icons">error</span>
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <form method="POST">
          <div class="form-group">
            <label class="form-label">Your Name</label>
            <div class="input-wrap">
              <span class="material-icons">person</span>
              <input type="text" name="name" placeholder="Full name" required>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Email Address</label>
            <div class="input-wrap">
              <span class="material-icons">mail</span>
              <input type="email" name="email" placeholder="your@email.com" required>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Message</label>
            <div class="input-wrap no-icon">
              <textarea name="message" placeholder="Write your message..."
                        style="min-height:140px;resize:vertical" required></textarea>
            </div>
          </div>
          <button type="submit" class="btn btn-primary btn-block">
            <span class="material-icons" style="font-size:17px">send</span>
            Send Message
          </button>
        </form>
      </div>

      <!-- contact info -->
      <div>
        <div style="font-size:11px;font-weight:800;letter-spacing:2px;
                    text-transform:uppercase;color:var(--text-muted);
                    margin-bottom:20px">Contact Information</div>

        <?php
        $contacts = [
          ['mail','Email','contect@myblog.in'],
          ['location_on','Location','Rajkot, Gujarat, India'],
          ['schedule','Response Time','Within 24 hours'],
          ['language','Website','myblog.in'],
        ];
        foreach ($contacts as [$icon,$label,$value]):
        ?>
        <div style="display:flex;gap:14px;align-items:flex-start;
                    padding:16px 0;border-bottom:1px solid var(--border)">
          <div style="width:40px;height:40px;background:var(--primary-light);border-radius:4px;
                      display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <span class="material-icons" style="font-size:19px;color:#d4bc9b;"><?= $icon ?></span>
          </div>
          <div>
            <div style="font-size:11px;font-weight:700;color:var(--text-muted);
                        text-transform:uppercase;letter-spacing:0.5px;margin-bottom:3px">
              <?= $label ?>
            </div>
            <div style="font-size:14px;font-weight:600;color:var(--text)"><?= $value ?></div>
          </div>
        </div>
        <?php endforeach; ?>

        <div style="margin-top:28px;padding:20px;background:#d4bc9b;
                    border-left:3px solid #d4bc9b;border-radius:0 4px 4px 0">
          <div style="font-size:13px;font-weight:700;color:var(--primary);margin-bottom:4px">
            Pro Tip
          </div>
          <p style="font-size:13px;color:var(--text-sub);line-height:1.6">
            For faster support, include your username and a detailed description of your issue.
          </p>
        </div>
      </div>

    </div>
  </div>

</body>
</html>