<?php
session_start();
require '../include/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$author_id = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$author_id]);
$author = $stmt->fetch();

if (!$author) { die("User not found."); }

if (isset($_GET['action'])) {
    if ($_GET['action']=='follow') {
        $pdo->prepare("INSERT IGNORE INTO follows (follower_id,following_id) VALUES (?,?)")
            ->execute([$_SESSION['user_id'], $author_id]);
        $pdo->prepare("INSERT INTO notifications (user_id,from_user_id,type) VALUES (?,?,?)")
            ->execute([$author_id, $_SESSION['user_id'], 'follow']);
    } elseif ($_GET['action']=='unfollow') {
        $pdo->prepare("DELETE FROM follows WHERE follower_id=? AND following_id=?")
            ->execute([$_SESSION['user_id'], $author_id]);
    }
    header("Location: author.php?id=$author_id"); exit;
}

$check = $pdo->prepare("SELECT id FROM follows WHERE follower_id=? AND following_id=?");
$check->execute([$_SESSION['user_id'], $author_id]);
$isFollowing = $check->fetch();

$fc = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE following_id=?");
$fc->execute([$author_id]);
$followerCount = $fc->fetchColumn();

$tv = $pdo->prepare("SELECT SUM(views) FROM posts WHERE user_id=? AND status='published'");
$tv->execute([$author_id]);
$totalViews = $tv->fetchColumn() ?? 0;

$stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id=? AND status='published' ORDER BY created_at DESC");
$stmt->execute([$author_id]);
$posts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($author['username']) ?> — MyBlog</title>
  <link href="../assets/style.css" rel="stylesheet">
</head>
<body>

  <?php include '../include/navbar.php'; ?>

  <!-- author header -->
  <div style="background:var(--bg-card);border-bottom:1px solid var(--border);padding:40px 0">
    <div class="container">
      <div style="display:flex;align-items:flex-start;gap:24px;flex-wrap:wrap">

        <div class="avatar avatar-xl">
          <?php if ($author['photo']): ?>
            <img src="../uploads/<?= htmlspecialchars($author['photo']) ?>" alt="">
          <?php else: ?>
            <?= strtoupper(substr($author['username'],0,1)) ?>
          <?php endif; ?>
        </div>

        <div style="flex:1">
          <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:8px">
            <h1 style="font-size:28px;font-weight:900;letter-spacing:-0.5px">
              <?= htmlspecialchars($author['username']) ?>
            </h1>
            <?php if ($author['is_premium']): ?>
              <span class="badge badge-premium">
                <span class="material-icons" style="font-size:12px">workspace_premium</span>
                Premium
              </span>
            <?php endif; ?>
          </div>

          <?php if (!empty($author['bio'])): ?>
            <p style="font-size:15px;color:var(--text-sub);max-width:500px;
                      line-height:1.6;margin-bottom:12px">
              <?= htmlspecialchars($author['bio']) ?>
            </p>
          <?php endif; ?>

          <div style="display:flex;gap:20px;font-size:13px;color:var(--text-sub);flex-wrap:wrap">
            <span><strong style="color:var(--text)"><?= $followerCount ?></strong> Followers</span>
            <span><strong style="color:var(--text)"><?= count($posts) ?></strong> Stories</span>
            <span>
              <span class="material-icons" style="font-size:14px;color:var(--primary);vertical-align:middle">visibility</span>
              <strong style="color:var(--text)"><?= number_format($totalViews) ?></strong> Views
            </span>
          </div>
        </div>

        <div style="display:flex;gap:10px">
          <?php if ($author_id != $_SESSION['user_id']): ?>
            <?php if ($isFollowing): ?>
              <a href="author.php?id=<?= $author_id ?>&action=unfollow"
                 class="btn btn-secondary">
                <span class="material-icons" style="font-size:16px">how_to_reg</span>
                Following
              </a>
            <?php else: ?>
              <a href="author.php?id=<?= $author_id ?>&action=follow"
                 class="btn btn-primary">
                <span class="material-icons" style="font-size:16px">person_add</span>
                Follow
              </a>
            <?php endif; ?>
          <?php else: ?>

         <!--   <a href="edit-profile.php" class="btn btn-outline">
              <span class="material-icons" style="font-size:16px">settings</span>
              Edit Profile
            </a> !-->

          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>

  <!-- author's stories -->
  <div class="container" style="padding-top:36px;padding-bottom:60px">

    <div class="section-title" style="margin-bottom:20px">
      <span class="material-icons">auto_stories</span>
      Stories by <?= htmlspecialchars($author['username']) ?>
    </div>

    <?php if (count($posts)==0): ?>
      <div class="empty-state card">
        <span class="material-icons">edit_note</span>
        <h3>No stories yet</h3>
        <p><?= htmlspecialchars($author['username']) ?> hasn't published any stories yet.</p>
      </div>
    <?php else: ?>
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:24px">
        <?php foreach ($posts as $p): ?>
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
            <span class="material-icons">visibility</span>
            <span><?= number_format($p['views']) ?></span>
            <span>·</span>
            <span><?= date('d M Y', strtotime($p['created_at'])) ?></span>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>

</body>
</html>