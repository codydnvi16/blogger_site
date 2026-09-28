<?php
session_start();
require '../include/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT posts.*,users.username,users.photo
    FROM likes JOIN posts ON likes.post_id=posts.id
    JOIN users ON posts.user_id=users.id
    WHERE likes.user_id=? ORDER BY likes.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$liked = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Liked Stories — MyBlog</title>
  <link href="../assets/style.css" rel="stylesheet">
</head>
<body>

  <?php include '../include/navbar.php'; ?>

  <div class="container" style="padding-top:40px;padding-bottom:60px">

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px">
      <h1 style="font-size:26px;font-weight:900;letter-spacing:-0.5px">Liked Stories</h1>
      <span style="font-size:13px;color:var(--text-muted)"><?= count($liked) ?> liked</span>
    </div>

    <?php if (count($liked)==0): ?>
      <div class="empty-state card">
        <span class="material-icons">favorite_border</span>
        <h3>No liked stories yet</h3>
        <p>Stories you like will appear here.</p>
        <a href="home.php" class="btn btn-primary btn-sm">Browse Stories</a>
      </div>
    <?php else: ?>
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:24px">
        <?php foreach ($liked as $p): ?>
        <a href="post.php?id=<?= $p['id'] ?>" class="blog-grid-card">
          <?php if ($p['image']): ?>
            <img class="thumb" src="../uploads/<?= htmlspecialchars($p['image']) ?>" alt="">
          <?php else: ?>
            <div class="no-thumb"><span class="material-icons">article</span></div>
          <?php endif; ?>
          <?php if ($p['domain']): ?>
            <div class="cat"><?= htmlspecialchars($p['domain']) ?></div>
          <?php endif; ?>
          <div class="title"><?= htmlspecialchars($p['title']) ?></div>
          <div class="meta">
            <div class="avatar avatar-xs">
              <?php if ($p['photo']): ?>
                <img src="../uploads/<?= htmlspecialchars($p['photo']) ?>" alt="">
              <?php else: ?>
                <?= strtoupper(substr($p['username'],0,1)) ?>
              <?php endif; ?>
            </div>
            <span><?= htmlspecialchars($p['username']) ?></span>
            <span>·</span>
            <span class="material-icons">visibility</span>
            <span><?= $p['views'] ?></span>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>

</body>
</html>