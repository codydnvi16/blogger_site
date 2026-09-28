<?php
session_start();
require '../include/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id=? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$posts = $stmt->fetchAll();

$total = count($posts);
$published = $pending = $rejected = $drafts = 0;
foreach ($posts as $p) {
    if ($p['status']=='published') $published++;
    if ($p['status']=='pending')   $pending++;
    if ($p['status']=='rejected')  $rejected++;
    if ($p['status']=='draft')     $drafts++;
}

$fc = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE following_id=?");
$fc->execute([$_SESSION['user_id']]);
$followerCount = $fc->fetchColumn();

$fg = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_id=?");
$fg->execute([$_SESSION['user_id']]);
$followingCount = $fg->fetchColumn();

$tv = $pdo->prepare("SELECT SUM(views) FROM posts WHERE user_id=?");
$tv->execute([$_SESSION['user_id']]);
$totalViews = $tv->fetchColumn() ?? 0;

$tl = $pdo->prepare("SELECT COUNT(*) FROM likes JOIN posts ON likes.post_id=posts.id WHERE posts.user_id=?");
$tl->execute([$_SESSION['user_id']]);
$totalLikes = $tl->fetchColumn();

$tc = $pdo->prepare("SELECT COUNT(*) FROM comments JOIN posts ON comments.post_id=posts.id WHERE posts.user_id=?");
$tc->execute([$_SESSION['user_id']]);
$totalComments = $tc->fetchColumn();

// chart data
$viewsPerBlog = $pdo->prepare("SELECT title,views FROM posts WHERE user_id=? AND status='published' ORDER BY views DESC LIMIT 6");
$viewsPerBlog->execute([$_SESSION['user_id']]);
$viewsPerBlog = $viewsPerBlog->fetchAll();

$likesOverTime = $pdo->prepare("SELECT DATE_FORMAT(likes.created_at,'%b %Y') as month, COUNT(*) as total FROM likes JOIN posts ON likes.post_id=posts.id WHERE posts.user_id=? GROUP BY DATE_FORMAT(likes.created_at,'%Y-%m') ORDER BY MIN(likes.created_at) DESC LIMIT 6");
$likesOverTime->execute([$_SESSION['user_id']]);
$likesOverTime = array_reverse($likesOverTime->fetchAll());

$commentsOverTime = $pdo->prepare("SELECT DATE_FORMAT(comments.created_at,'%b %Y') as month, COUNT(*) as total FROM comments JOIN posts ON comments.post_id=posts.id WHERE posts.user_id=? GROUP BY DATE_FORMAT(comments.created_at,'%Y-%m') ORDER BY MIN(comments.created_at) DESC LIMIT 6");
$commentsOverTime->execute([$_SESSION['user_id']]);
$commentsOverTime = array_reverse($commentsOverTime->fetchAll());

$followersGrowth = $pdo->prepare("SELECT DATE_FORMAT(created_at,'%b %Y') as month, COUNT(*) as total FROM follows WHERE following_id=? GROUP BY DATE_FORMAT(created_at,'%Y-%m') ORDER BY MIN(created_at) DESC LIMIT 6");
$followersGrowth->execute([$_SESSION['user_id']]);
$followersGrowth = array_reverse($followersGrowth->fetchAll());

$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile — MyBlog</title>
  <link href="../assets/style.css" rel="stylesheet">
  <style>
    .stat-pill {
      display:flex;flex-direction:column;align-items:center;
      padding:16px 20px;background:var(--bg-card);
      border:1px solid var(--border);border-radius:4px;min-width:100px;
    }
    .stat-pill .num { font-size:24px;font-weight:800;color:var(--primary); }
    .stat-pill .lbl { font-size:11px;color:var(--text-muted);margin-top:3px;text-transform:uppercase;letter-spacing:0.5px; }

    .blog-row {
      display:flex;align-items:center;gap:16px;padding:16px;
      background:var(--bg-card);border:1px solid var(--border);
      border-radius:4px;margin-bottom:10px;transition:border 0.2s;
    }
    .blog-row:hover { border-color:var(--primary); }

    .chart-card {
      background:var(--bg-card);border:1px solid var(--border);
      border-radius:4px;padding:20px;
    }

    .chart-card h4 {
      font-size:11px;font-weight:800;letter-spacing:1.5px;
      text-transform:uppercase;color:var(--text-sub);
      margin-bottom:14px;padding-bottom:10px;
      border-bottom:1px solid var(--border);
      display:flex;align-items:center;gap:6px;
    }

    .chart-card h4 .material-icons { font-size:15px;color:var(--primary); }
  </style>
</head>
<body>

  <?php include '../include/navbar.php'; ?>

  <!-- profile header -->
  <div style="background:var(--bg-card);border-bottom:1px solid var(--border);padding:40px 0">
    <div class="container">
      <div style="display:flex;align-items:flex-start;gap:28px;flex-wrap:wrap">

        <div class="avatar avatar-xl">
          <?php if ($user['photo']): ?>
            <img src="../uploads/<?= htmlspecialchars($user['photo']) ?>" alt="">
          <?php else: ?>
            <?= strtoupper(substr($user['username'],0,1)) ?>
          <?php endif; ?>
        </div>

        <div style="flex:1">
          <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:8px">
            <h1 style="font-size:30px;font-weight:900;letter-spacing:-1px">
              <?= htmlspecialchars($user['username']) ?>
            </h1>
            <?php if ($user['is_premium']): ?>
              <span class="badge badge-premium">
                <span class="material-icons" style="font-size:12px">workspace_premium</span>
                Premium
              </span>
            <?php endif; ?>
            <?php if ($user['role']=='admin'): ?>
              <span class="badge badge-primary">Admin</span>
            <?php endif; ?>
          </div>

          <?php if (!empty($user['bio'])): ?>
            <p style="font-size:15px;color:var(--text-sub);max-width:500px;
                      line-height:1.6;margin-bottom:16px">
              <?= htmlspecialchars($user['bio']) ?>
            </p>
          <?php endif; ?>

          <div style="display:flex;gap:16px;font-size:13px;color:var(--text-sub);
                      flex-wrap:wrap;margin-bottom:16px">
            <span><strong style="color:var(--text)"><?= $followerCount ?></strong> Followers</span>
            <span><strong style="color:var(--text)"><?= $followingCount ?></strong> Following</span>
            <span>
              <span class="material-icons" style="font-size:14px;color:var(--primary);vertical-align:middle">visibility</span>
              <strong style="color:var(--text)"><?= number_format($totalViews) ?></strong> Views
            </span>
            <span>
              <span class="material-icons" style="font-size:14px;color:var(--primary);vertical-align:middle">favorite</span>
              <strong style="color:var(--text)"><?= $totalLikes ?></strong> Likes
            </span>
            <span>
              <span class="material-icons" style="font-size:14px;color:var(--text-muted);vertical-align:middle">calendar_today</span>
              Joined <?= date('M Y', strtotime($user['created_at'])) ?>
            </span>
          </div>

          <div style="display:flex;gap:10px;flex-wrap:wrap">
            <a href="edit-profile.php" class="btn btn-outline btn-sm">
              <span class="material-icons" style="font-size:15px">settings</span>
              Edit Profile
            </a>
            <a href="liked-blogs.php" class="btn btn-secondary btn-sm">
              <span class="material-icons" style="font-size:15px">favorite</span>
              Liked
            </a>
            <a href="bookmarks.php" class="btn btn-secondary btn-sm">
              <span class="material-icons" style="font-size:15px">bookmark</span>
              Saved
            </a>
            <a href="premium.php"
               class="btn btn-sm"
               style="background:var(--primary-light);color:var(--primary)">
              <span class="material-icons" style="font-size:15px">workspace_premium</span>
              <?= $user['is_premium'] ? 'Premium ✓' : 'Go Premium' ?>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="container" style="padding-top:32px;padding-bottom:60px">

    <!-- message bar -->
    <?php if ($msg=='draft_saved'): ?>
      <div class="alert alert-success" style="margin-bottom:24px">
        <span class="material-icons">save</span> Draft saved!
      </div>
    <?php elseif ($msg=='deleted'): ?>
      <div class="alert alert-error" style="margin-bottom:24px">
        <span class="material-icons">delete</span> Blog deleted.
      </div>
    <?php elseif ($msg=='submitted'): ?>
      <div class="alert alert-success" style="margin-bottom:24px">
        <span class="material-icons">publish</span> Blog submitted for review!
      </div>
    <?php endif; ?>

    <!-- stats pills -->
    <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:32px">
      <div class="stat-pill"><div class="num"><?= $total ?></div><div class="lbl">Total</div></div>
      <div class="stat-pill"><div class="num"><?= $published ?></div><div class="lbl">Published</div></div>
      <div class="stat-pill"><div class="num"><?= $pending ?></div><div class="lbl">Pending</div></div>
      <div class="stat-pill"><div class="num"><?= $drafts ?></div><div class="lbl">Drafts</div></div>
      <div class="stat-pill"><div class="num"><?= $rejected ?></div><div class="lbl">Rejected</div></div>
      <div class="stat-pill"><div class="num"><?= number_format($totalViews) ?></div><div class="lbl">Views</div></div>
      <div class="stat-pill"><div class="num"><?= $totalLikes ?></div><div class="lbl">Likes</div></div>
      <div class="stat-pill"><div class="num"><?= $totalComments ?></div><div class="lbl">Comments</div></div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 360px;gap:32px">

      <!-- left: blogs list -->
      <div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
          <div class="section-title" style="margin-bottom:0">
            <span class="material-icons">article</span>
            My Stories
          </div>
          <a href="write-blog.php" class="btn btn-primary btn-sm">
            <span class="material-icons" style="font-size:15px">edit</span>
            Write New
          </a>
        </div>

        <?php if ($total==0): ?>
          <div class="empty-state card">
            <span class="material-icons">edit_note</span>
            <h3>No stories yet</h3>
            <p>Start writing your first blog post!</p>
            <a href="write-blog.php" class="btn btn-primary btn-sm">Write a Story</a>
          </div>
        <?php else: ?>
          <?php foreach ($posts as $p): ?>
          <div class="blog-row">

            <?php if ($p['image']): ?>
              <img src="../uploads/<?= htmlspecialchars($p['image']) ?>"
                   style="width:64px;height:52px;object-fit:cover;
                          border-radius:3px;flex-shrink:0" alt="">
            <?php else: ?>
              <div style="width:64px;height:52px;background:var(--bg-input);
                          border-radius:3px;flex-shrink:0;display:flex;
                          align-items:center;justify-content:center">
                <span class="material-icons" style="color:var(--text-muted)">article</span>
              </div>
            <?php endif; ?>

            <div style="flex:1;min-width:0">
              <div style="font-size:14px;font-weight:700;color:var(--text);
                          margin-bottom:3px;white-space:nowrap;
                          overflow:hidden;text-overflow:ellipsis">
                <?= htmlspecialchars($p['title']) ?>
              </div>
              <div style="font-size:11px;color:var(--text-muted);
                          display:flex;align-items:center;gap:6px">
                <span class="material-icons" style="font-size:12px">folder</span>
                <span><?= htmlspecialchars($p['domain']) ?></span>
                <span>·</span>
                <span><?= date('d M Y', strtotime($p['created_at'])) ?></span>
                <span>·</span>
                <span class="material-icons" style="font-size:12px">visibility</span>
                <span><?= number_format($p['views']) ?></span>
              </div>
            </div>

            <span class="badge badge-<?= $p['status'] ?>">
              <?= ucfirst($p['status']) ?>
            </span>

            <div style="display:flex;gap:6px;flex-shrink:0">
              <?php if ($p['status']=='published'): ?>
                <a href="post.php?id=<?= $p['id'] ?>"
                   class="btn btn-success btn-sm">View</a>
              <?php endif; ?>
              <?php if ($p['status']=='draft'): ?>
                <a href="publish-draft.php?id=<?= $p['id'] ?>"
                   class="btn btn-success btn-sm">Publish</a>
              <?php endif; ?>
              <a href="edit-blog.php?id=<?= $p['id'] ?>"
                 class="btn btn-secondary btn-sm">Edit</a>
              <a href="delete-blog.php?id=<?= $p['id'] ?>"
                 class="btn btn-danger btn-sm">Delete</a>
            </div>

          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- right: analytics -->
      <div>
        <div class="section-title" style="margin-bottom:16px">
          <span class="material-icons">bar_chart</span>
          Analytics
        </div>

        <div style="display:flex;flex-direction:column;gap:16px">

          <div class="chart-card">
            <h4><span class="material-icons">visibility</span> Views Per Blog</h4>
            <canvas id="viewsChart" height="180"></canvas>
          </div>

          <div class="chart-card">
            <h4><span class="material-icons">people</span> Followers Growth</h4>
            <canvas id="followersChart" height="180"></canvas>
          </div>

          <div class="chart-card">
            <h4><span class="material-icons">favorite</span> Likes vs Comments</h4>
            <canvas id="likesCommentsChart" height="180"></canvas>
          </div>

        </div>
      </div>

    </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
  <script>
    Chart.defaults.font.family = 'Inter, sans-serif';
    Chart.defaults.color = getComputedStyle(document.documentElement).getPropertyValue('--text-muted');

    const orange  = '#ea580c';
    const orangeT = 'rgba(234,88,12,0.1)';
    const green   = '#059669';
    const greenT  = 'rgba(5,150,105,0.1)';
    const blue    = '#2563eb';
    const blueT   = 'rgba(37,99,235,0.1)';

    const opts = {
      responsive:true,
      plugins:{ legend:{ display:false } },
      scales:{
        y:{ beginAtZero:true, ticks:{ stepSize:1 }, grid:{ color:'rgba(0,0,0,0.05)' } },
        x:{ grid:{ display:false } }
      }
    };

    new Chart(document.getElementById('viewsChart'), {
      type:'line',
      data:{
        labels: <?= json_encode(array_map(fn($b)=>substr($b['title'],0,14).'...',$viewsPerBlog)) ?>,
        datasets:[{ data:<?= json_encode(array_column($viewsPerBlog,'views')) ?>,
          borderColor:orange,backgroundColor:orangeT,borderWidth:2,fill:true,tension:0.4,pointRadius:4 }]
      }, options:opts
    });

    new Chart(document.getElementById('followersChart'), {
      type:'line',
      data:{
        labels:<?= json_encode(array_column($followersGrowth,'month')) ?>,
        datasets:[{ data:<?= json_encode(array_column($followersGrowth,'total')) ?>,
          borderColor:green,backgroundColor:greenT,borderWidth:2,fill:true,tension:0.4,pointRadius:4 }]
      }, options:opts
    });

    new Chart(document.getElementById('likesCommentsChart'), {
      type:'line',
      data:{
        labels:<?= json_encode(array_column($likesOverTime,'month')) ?>,
        datasets:[
          { label:'Likes', data:<?= json_encode(array_column($likesOverTime,'total')) ?>,
            borderColor:orange,backgroundColor:orangeT,borderWidth:2,fill:true,tension:0.4,pointRadius:3 },
          { label:'Comments', data:<?= json_encode(array_column($commentsOverTime,'total')) ?>,
            borderColor:blue,backgroundColor:blueT,borderWidth:2,fill:true,tension:0.4,pointRadius:3 }
        ]
      },
      options:{ ...opts, plugins:{ legend:{ display:true, position:'top',
        labels:{ usePointStyle:true, padding:16, font:{ size:11 } } } } }
    });
  </script>

</body>
</html>