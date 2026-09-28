<?php
session_start();
require '../include/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")
    ->execute([$_SESSION['user_id']]);

$notifications = $pdo->prepare("
    SELECT notifications.*,users.username as from_username,users.photo as from_photo
    FROM notifications JOIN users ON notifications.from_user_id=users.id
    WHERE notifications.user_id=?
    ORDER BY notifications.created_at DESC LIMIT 50
");
$notifications->execute([$_SESSION['user_id']]);
$notifications = $notifications->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notifications — MyBlog</title>
  <link href="../assets/style.css" rel="stylesheet">
</head>
<body>

  <?php include '../include/navbar.php'; ?>

  <div class="container" style="padding-top:40px;padding-bottom:60px">
    <div style="max-width:680px">

      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px">
        <h1 style="font-size:26px;font-weight:900;letter-spacing:-0.5px">Notifications</h1>
        <span style="font-size:12px;color:var(--text-muted)">
          <?= count($notifications) ?> total
        </span>
      </div>

      <?php if (count($notifications)==0): ?>
        <div class="empty-state card">
          <span class="material-icons">notifications_none</span>
          <h3>No notifications yet</h3>
          <p>When someone likes, comments or follows you — it shows here.</p>
        </div>
      <?php else: ?>

        <?php
        $icons = ['like'=>'favorite','comment'=>'chat_bubble','follow'=>'person_add','reply'=>'reply'];
        $texts = ['like'=>'liked your story','comment'=>'commented on your story','follow'=>'started following you','reply'=>'replied to your comment'];
        ?>

        <?php foreach ($notifications as $n): ?>
        <div style="display:flex;align-items:flex-start;gap:14px;padding:16px;
                    background:var(--bg-card);border:1px solid var(--border);
                    border-radius:4px;margin-bottom:10px;transition:border 0.2s"
             onmouseover="this.style.borderColor='var(--primary)'"
             onmouseout="this.style.borderColor='var(--border)'">

          <div class="avatar avatar-sm">
            <?php if ($n['from_photo']): ?>
              <img src="../uploads/<?= htmlspecialchars($n['from_photo']) ?>" alt="">
            <?php else: ?>
              <?= strtoupper(substr($n['from_username'],0,1)) ?>
            <?php endif; ?>
          </div>

          <div style="width:32px;height:32px;border-radius:50%;flex-shrink:0;
                      background:var(--primary-light);display:flex;
                      align-items:center;justify-content:center">
            <span class="material-icons" style="font-size:16px;color:var(--primary)">
              <?= $icons[$n['type']] ?? 'notifications' ?>
            </span>
          </div>

          <div style="flex:1">
            <p style="font-size:14px;color:var(--text);line-height:1.5;margin-bottom:4px">
              <strong><?= htmlspecialchars($n['from_username']) ?></strong>
              <?= $texts[$n['type']] ?? 'interacted with you' ?>.
            </p>
            <?php if ($n['post_id']): ?>
              <a href="post.php?id=<?= $n['post_id'] ?>"
                 style="font-size:12px;color:var(--primary);font-weight:600;text-decoration:none">
                View Story →
              </a>
            <?php endif; ?>
            <div style="font-size:11px;color:var(--text-muted);margin-top:3px">
              <?= date('d M Y, h:i A', strtotime($n['created_at'])) ?>
            </div>
          </div>

        </div>
        <?php endforeach; ?>

      <?php endif; ?>
    </div>
  </div>

</body>
</html>