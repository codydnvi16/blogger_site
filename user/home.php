<?php
session_start();
require '../include/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$selectedCategory = $_GET['category'] ?? '';

// get posts
if ($selectedCategory) {
    $stmt = $pdo->prepare("
        SELECT posts.*, users.username, users.photo
        FROM posts JOIN users ON posts.user_id = users.id
        WHERE posts.status='published' AND users.is_banned=0 AND posts.domain=?
        ORDER BY posts.created_at DESC
    ");
    $stmt->execute([$selectedCategory]);
    $posts = $stmt->fetchAll();
} else {
    $posts = $pdo->query("
        SELECT posts.*, users.username, users.photo
        FROM posts JOIN users ON posts.user_id = users.id
        WHERE posts.status='published' AND users.is_banned=0
        ORDER BY posts.created_at DESC
    ")->fetchAll();
}

// featured post — most viewed
$featured = $pdo->query("
    SELECT posts.*, users.username, users.photo
    FROM posts JOIN users ON posts.user_id = users.id
    WHERE posts.status='published' AND users.is_banned=0 AND posts.featured=1
    ORDER BY posts.views DESC LIMIT 1
")->fetch();

// if no featured use most viewed
if (!$featured) {
    $featured = $pdo->query("
        SELECT posts.*, users.username, users.photo
        FROM posts JOIN users ON posts.user_id = users.id
        WHERE posts.status='published' AND users.is_banned=0
        ORDER BY posts.views DESC LIMIT 1
    ")->fetch();
}

// trending
$trending = $pdo->query("
    SELECT posts.*, users.username
    FROM posts JOIN users ON posts.user_id = users.id
    WHERE posts.status='published' AND users.is_banned=0
    ORDER BY posts.views DESC LIMIT 5
")->fetchAll();

// grid posts — next 3 after featured
$gridPosts = $pdo->query("
    SELECT posts.*, users.username, users.photo
    FROM posts JOIN users ON posts.user_id = users.id
    WHERE posts.status='published' AND users.is_banned=0
    ORDER BY posts.created_at DESC LIMIT 3 OFFSET 1
")->fetchAll();

// categories
// categories
$categories = $pdo->query("
    SELECT DISTINCT domain FROM posts
    WHERE status='published' AND domain != ''
    ORDER BY domain ASC
")->fetchAll(PDO::FETCH_COLUMN);

// like counts
$likeCounts = [];
foreach ($posts as $p) 
  {
    $lc = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id=?");
    $lc->execute([$p['id']]);
    $likeCounts[$p['id']] = $lc->fetchColumn();
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Home — MyBlog</title>
  <link href="../assets/style.css" rel="stylesheet">
</head>
<body>

  <?php include '../include/navbar.php'; ?>

  <div class="container" style="padding-top:36px;padding-bottom:60px">

    <?php if (!$selectedCategory && $featured): ?>

    <!-- ===== HERO SECTION ===== -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:28px;margin-bottom:36px;padding-bottom:36px;border-bottom:1px solid var(--border)">

      <!-- featured big card -->
      <a href="post.php?id=<?= $featured['id'] ?>" class="featured-card">

        <?php if ($featured['image']): ?>
          <img src="../uploads/<?= htmlspecialchars($featured['image']) ?>" alt="">

        <?php else: ?>
          <div class="no-img">
            <span class="material-icons">article</span>
          </div>
        <?php endif; ?>

        <div class="overlay">
          <?php if ($featured['domain']): ?>
            <span class="cat"><?= htmlspecialchars($featured['domain']) ?></span>
          <?php endif; ?>

          <h2><?= htmlspecialchars($featured['title']) ?></h2>
          <div class="meta">
            <span><?= htmlspecialchars($featured['username']) ?></span>
            <span>·</span>
            <span><?= date('d M Y', strtotime($featured['created_at'])) ?></span>
            <span>·</span>
            <span class="material-icons" style="font-size:13px">visibility</span>
            <span><?= number_format($featured['views']) ?></span>
          </div>
        </div>
      </a>

      <!-- right side: 3 latest posts -->
     
      <div>
        <?php
        $rightPosts = array_slice($posts, 1, 3);
        foreach ($rightPosts as $rp):
        ?>

        <a href="post.php?id=<?= $rp['id'] ?>"
           style="display:flex;gap:14px;padding:14px 0;
                  border-bottom:1px solid var(--border);
                  text-decoration:none;color:inherit;
                  transition:opacity 0.2s"
           onmouseover="this.style.opacity=0.7"
           onmouseout="this.style.opacity=1">
          <div style="flex:1">

            <?php if ($rp['domain']): ?>
              <div style="font-size:10px;font-weight:800;letter-spacing:1.5px;
                          text-transform:uppercase;color:var(--primary);margin-bottom:5px">
                <?= htmlspecialchars($rp['domain']) ?>
              </div>
            <?php endif; ?>


        <div style="font-size:14px;font-weight:700;line-height:1.4;
          color:var(--text);margin-bottom:6px">
         <?= htmlspecialchars($rp['title']) ?>
        </div>
            <div style="font-size:11px;color:var(--text-muted);
                        display:flex;align-items:center;gap:6px">
              <span><?= htmlspecialchars($rp['username']) ?></span>
              <span>·</span>
              <span class="material-icons" style="font-size:12px">visibility</span>
              <span><?= $rp['views'] ?></span>
            </div>
          </div>

          <?php if ($rp['image']): ?>
            <img src="../uploads/<?= htmlspecialchars($rp['image']) ?>"
                 style="width:80px;height:65px;object-fit:cover;
                        border-radius:3px;flex-shrink:0" alt="">
          <?php else: ?>

            <div style="width:80px;height:65px;background:(--bg-input);
                        border-radius:3px;flex-shrink:0;display:flex;
                        align-items:center;justify-content:center">
              <span class="material-icons" style="color:var(--text-muted);font-size:20px">article</span>
            </div>

          <?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>

    </div>

    <!-- ===== 3 COLUMN GRID ===== -->
    <?php if (count($gridPosts) > 0): ?>
    <div style="margin-bottom:36px;padding-bottom:36px;border-bottom:1px solid var(--border)">
      <div class="section-title">
        <span class="material-icons">auto_stories</span>
        Latest Stories
      </div>

      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:24px">
        <?php foreach ($gridPosts as $gp): ?>
        <a href="post.php?id=<?= $gp['id'] ?>" class="blog-grid-card">
          <?php if ($gp['image']): ?>
            <img class="thumb" src="../uploads/<?= htmlspecialchars($gp['image']) ?>" alt="">
        
            <?php else: ?>
            <div class="no-thumb">
              <span class="material-icons">article</span>
            </div>
          <?php endif; ?>

          <?php if ($gp['domain']): ?>
            <div class="cat"><?= htmlspecialchars($gp['domain']) ?></div>

          <?php endif; ?>
          <div class="title"><?= htmlspecialchars($gp['title']) ?></div>
          <div class="excerpt"><?= htmlspecialchars(substr(strip_tags($gp['content']), 0, 100)) ?>...</div>
          <div class="meta">
            <div class="avatar avatar-xs">

              <?php if ($gp['photo']): ?>
                <img src="../uploads/<?= htmlspecialchars($gp['photo']) ?>" alt="">
              <?php else: ?>
                <?= strtoupper(substr($gp['username'], 0, 1)) ?>
              <?php endif; ?>
            </div>
            <span><?= htmlspecialchars($gp['username']) ?></span>
            <span>·</span>
            <span class="material-icons">visibility</span>
            <span><?= $gp['views'] ?></span>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>

    <!-- ===== MAIN CONTENT + SIDEBAR ===== -->
    <div style="display:grid;grid-template-columns:1fr 300px;gap:40px">

      <!-- LEFT: all posts list -->
      <div>
        <div class="section-title">
          <span class="material-icons">article</span>
          <?= $selectedCategory ? htmlspecialchars($selectedCategory) : 'All Stories' ?>
        </div>

        <?php if (count($posts) == 0): ?>
          <div class="empty-state card">
            <span class="material-icons">article</span>
            <h3>No stories found</h3>
            <p><?= $selectedCategory ? "No blogs in this category yet." : "Be the first to write one!" ?></p>
            <a href="write-blog.php" class="btn btn-primary">
              <span class="material-icons">edit</span>
              Write a Story
            </a>
          </div>
        <?php else: ?>
          <?php foreach ($posts as $i => $post): ?>
          <a href="post.php?id=<?= $post['id'] ?>" class="blog-list-card">
            <div class="number"><?= str_pad($i+1, 2, '0', STR_PAD_LEFT) ?></div>
            <div class="info">
              <?php if ($post['domain']): ?>
                <div class="cat"><?= htmlspecialchars($post['domain']) ?></div>
              <?php endif; ?>
              <div class="title"><?= htmlspecialchars($post['title']) ?></div>
              <div style="font-size:12px;color:var(--text-sub);margin-bottom:5px">
                <?= htmlspecialchars(substr(strip_tags($post['content']), 0, 90)) ?>...
              </div>
              <div class="meta">
                <div class="avatar avatar-xs">
                  <?php if ($post['photo']): ?>
                    <img src="../uploads/<?= htmlspecialchars($post['photo']) ?>" alt="">
                  <?php else: ?>
                    <?= strtoupper(substr($post['username'], 0, 1)) ?>
                  <?php endif; ?>
                </div>
                <span><?= htmlspecialchars($post['username']) ?></span>
                <span>·</span>
                <span><?= date('d M', strtotime($post['created_at'])) ?></span>
                <span>·</span>
                <span class="material-icons">visibility</span>
                <span><?= number_format($post['views']) ?></span>
                <span>·</span>
                <span class="material-icons">favorite_border</span>
                <span><?= $likeCounts[$post['id']] ?? 0 ?></span>
              </div>
            </div>
            <?php if ($post['image']): ?>
              <img class="thumb"
                   src="../uploads/<?= htmlspecialchars($post['image']) ?>"
                   alt="">
            <?php else: ?>
              <div class="no-thumb">
                <span class="material-icons">article</span>
              </div>
            <?php endif; ?>
          </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- RIGHT SIDEBAR -->
      <div>

        <!-- trending -->
        <?php if (count($trending) > 0): ?>
        <div class="sidebar-card">
          <div class="section-title">
            <span class="material-icons">trending_up</span>
            Trending
          </div>
          <?php foreach ($trending as $i => $t): ?>
          <a href="post.php?id=<?= $t['id'] ?>" class="sidebar-trending-item">
            <div class="num"><?= $i+1 ?></div>
            <div>
              <div class="title"><?= htmlspecialchars(substr($t['title'], 0, 50)) ?><?= strlen($t['title'])>50?'...':'' ?></div>
              <div class="views">
                <?= htmlspecialchars($t['username']) ?> · <?= number_format($t['views']) ?> views
              </div>
            </div>
          </a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- browse topics -->
        <div class="sidebar-card">
          <div class="section-title">
            <span class="material-icons">tag</span>
            Browse Topics
          </div>
          
          <div class="topic-tags">
            <?php foreach ($categories as $cat): ?>
              <a href="home.php?category=<?= urlencode($cat) ?>" class="topic-tag">
                <?= htmlspecialchars($cat) ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- quick links -->
        <div class="sidebar-card">
          <div class="section-title">
            <span class="material-icons">link</span>
            Quick Links
          </div>
          <div style="display:flex;flex-direction:column;gap:6px">
            <?php
            $links = [
              ['profile.php',        'account_circle', 'My Profile'],
              ['bookmarks.php',      'bookmark',       'Saved Stories'],
              ['following-feed.php', 'feed',           'Following Feed'],
              ['notifications.php',  'notifications',  'Notifications'],
              ['premium.php',        'workspace_premium', 'Go Premium'],
            ];
            foreach ($links as [$url, $icon, $label]):
            ?>
            <a href="<?= $url ?>"
               style="display:flex;align-items:center;gap:8px;
                      padding:9px 12px;border-radius:3px;
                      font-size:13px;font-weight:500;
                      color:var(--text-sub);text-decoration:none;
                      border:1px solid var(--border);
                      background:#000000(--bg-input);
                      transition:all 0.2s"
               onmouseover="this.style.borderColor='var(--primary)';this.style.color='var(--primary)'"
               onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--text-sub)'">
              <span class="material-icons" style="font-size:17px;color:var(--primary)"><?= $icon ?></span>
              <?= $label ?>
            </a>
            <?php endforeach; ?>
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- footer -->
  <footer style="border-top:10px solid var(--border); padding:24px 40px;
                 background:var(--bg-card); margin-top:40px">
    <div style="max-width:1300px; margin:0 auto; display:flex;
                justify-content:space-between; align-items:center;
                font-size:12px;
                color:var(--text-muted)">

      <span>© <?= date('Y') ?> <strong style="color:var(--text)">MYBLOG</strong> — Share Your Story</span>
      <div style="display:flex;gap:20px">

        <a href="about.php" style="color:var(--primary);text-decoration:none">About</a>
        <a href="contact.php" style="color:var(--primary);text-decoration:none">Contact</a>
        <a href="premium.php" style="color:var(--primary);text-decoration:none;font-weight:600">Premium</a>
      </div>
    </div>
  </footer>

</body>
</html>