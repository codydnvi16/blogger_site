<?php
session_start();
require '../include/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT posts.*,users.username,users.photo
    FROM posts JOIN users ON posts.user_id=users.id
    JOIN follows ON follows.following_id=posts.user_id
    WHERE follows.follower_id=? AND posts.status='published'
    ORDER BY posts.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$posts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Following — MyBlog</title>
  <link href="../assets/style.css" rel="stylesheet">
</head>
<body>

  <?php include '../include/navbar.php'; ?>

  <div class="container" style="padding-top:40px;padding-bottom:60px">

    <h1 style="font-size:26px;font-weight:900;letter-spacing:-0.5px;margin-bottom:28px">
      Following Feed
    </h1>

    <?php if (count($posts)==0): ?>
      <div class="empty-state card">
        <span class="material-icons">feed</span>
        <h3>Your feed is empty</h3>
        <p>Follow writers to see their latest stories here.</p>
        <a href="home.php" class="btn btn-primary btn-sm">Discover Writers</a>
      </div>
    <?php else: ?>
      <?php foreach ($posts as $post): ?>
      <a href="post.php?id=<?= $post['id'] ?>" class="blog-list-card">
        <div class="info">
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
            <div class="avatar avatar-xs">
              <?php if ($post['photo']): ?>
                <img src="../uploads/<?= htmlspecialchars($post['photo']) ?>" alt="">
              <?php else: ?>
                <?= strtoupper(substr($post['username'],0,1)) ?>
              <?php endif; ?>
            </div>
            <span style="font-size:12px;font-weight:600;color:var(--text-sub)">
              <?= htmlspecialchars($post['username']) ?>
            </span>
            <?php if ($post['domain']): ?>
              <span style="font-size:10px;font-weight:800;letter-spacing:1px;
                           text-transform:uppercase;color:var(--primary)">
                · <?= htmlspecialchars($post['domain']) ?>
              </span>
            <?php endif; ?>
          </div>
          <div class="title"><?= htmlspecialchars($post['title']) ?></div>
          <div style="font-size:12px;color:var(--text-sub);margin-bottom:5px">
            <?= htmlspecialchars(substr(strip_tags($post['content']),0,100)) ?>...
          </div>
          <div class="meta">
            <span><?= date('d M Y', strtotime($post['created_at'])) ?></span>
            <span>·</span>
            <span class="material-icons">visibility</span>
            <span><?= $post['views'] ?></span>
          </div>
        </div>
        <?php if ($post['image']): ?>
          <img class="thumb" src="../uploads/<?= htmlspecialchars($post['image']) ?>" alt="">
        <?php else: ?>
          <div class="no-thumb"><span class="material-icons">article</span></div>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
    <?php endif; ?>

  </div>

</body>
</html>