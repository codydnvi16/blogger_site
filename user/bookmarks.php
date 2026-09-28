<?php
session_start();
require '../include/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['toggle'])) {
    $pid = $_GET['toggle'];
    $check = $pdo->prepare("SELECT id FROM bookmarks WHERE post_id=? AND user_id=?");
    $check->execute([$pid, $_SESSION['user_id']]);
    if ($check->fetch()) {
        $pdo->prepare("DELETE FROM bookmarks WHERE post_id=? AND user_id=?")->execute([$pid, $_SESSION['user_id']]);
    } else {
        $pdo->prepare("INSERT IGNORE INTO bookmarks (post_id,user_id) VALUES (?,?)")->execute([$pid, $_SESSION['user_id']]);
    }
    $ref = $_GET['ref'] ?? 'bookmarks.php';
    header("Location: $ref"); exit;
}

$bookmarks = $pdo->prepare("
    SELECT posts.*,users.username,users.photo
    FROM bookmarks JOIN posts ON bookmarks.post_id=posts.id
    JOIN users ON posts.user_id=users.id
    WHERE bookmarks.user_id=? ORDER BY bookmarks.created_at DESC
");
$bookmarks->execute([$_SESSION['user_id']]);
$bookmarks = $bookmarks->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Saved Stories — MyBlog</title>
  <link href="../assets/style.css" rel="stylesheet">
</head>
<body>

  <?php include '../include/navbar.php'; ?>

  <div class="container" style="padding-top:40px;padding-bottom:60px">

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px">
      <h1 style="font-size:26px;font-weight:900;letter-spacing:-0.5px">
        Saved Stories
      </h1>
      <span style="font-size:13px;color:var(--text-muted)">
        <?= count($bookmarks) ?> saved
      </span>
    </div>

    <?php if (count($bookmarks)==0): ?>
      <div class="empty-state card">
        <span class="material-icons">bookmark_border</span>
        <h3>No saved stories yet</h3>
        <p>Bookmark stories you want to read later.</p>
        <a href="home.php" class="btn btn-primary btn-sm">Discover Stories</a>
      </div>
    <?php else: ?>
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:24px">
        <?php foreach ($bookmarks as $p): ?>
        <div style="position:relative">
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
            <div class="excerpt"><?= htmlspecialchars(substr(strip_tags($p['content']),0,80)) ?>...</div>
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
          <a href="bookmarks.php?toggle=<?= $p['id'] ?>&ref=bookmarks.php"
             style="position:absolute;top:8px;right:8px;background:rgba(0,0,0,0.6);
                    color:white;border-radius:50%;width:28px;height:28px;
                    display:flex;align-items:center;justify-content:center;
                    text-decoration:none"
             title="Remove bookmark">
            <span class="material-icons" style="font-size:15px">close</span>
          </a>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>

</body>
</html>