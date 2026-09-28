<?php
session_name('admin_session');
session_start();
require '../include/database.php';
if (!isset($_SESSION['user_id'])||$_SESSION['role']!='admin') { header("Location: login.php"); exit; }

$pageTitle = "Dashboard";

// stats
$totalUsers    = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalBlogs    = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$pendingBlogs  = $pdo->query("SELECT COUNT(*) FROM posts WHERE status='pending'")->fetchColumn();
$publishedBlogs= $pdo->query("SELECT COUNT(*) FROM posts WHERE status='published'")->fetchColumn();
$totalComments = $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();
$totalViews    = $pdo->query("SELECT SUM(views) FROM posts")->fetchColumn() ?? 0;
$totalLikes    = $pdo->query("SELECT COUNT(*) FROM likes")->fetchColumn();
$totalReports  = $pdo->query("SELECT COUNT(*) FROM reports")->fetchColumn();
$bannedUsers   = $pdo->query("SELECT COUNT(*) FROM users WHERE is_banned=1")->fetchColumn();
$premiumUsers  = $pdo->query("SELECT COUNT(*) FROM users WHERE is_premium=1")->fetchColumn();
$totalBookmarks= $pdo->query("SELECT COUNT(*) FROM bookmarks")->fetchColumn();

$mostActive = $pdo->query("SELECT users.username,COUNT(posts.id) as total FROM users LEFT JOIN posts ON posts.user_id=users.id GROUP BY users.id ORDER BY total DESC LIMIT 1")->fetch();
$mostViewed = $pdo->query("SELECT posts.title,posts.views,users.username FROM posts JOIN users ON posts.user_id=users.id WHERE posts.status='published' ORDER BY posts.views DESC LIMIT 1")->fetch();

$recentUsers = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 6")->fetchAll();
$recentBlogs = $pdo->query("SELECT posts.*,users.username FROM posts JOIN users ON posts.user_id=users.id ORDER BY posts.created_at DESC LIMIT 6")->fetchAll();

// chart data
$userGrowth = array_reverse($pdo->query("SELECT DATE_FORMAT(created_at,'%b %Y') as month, COUNT(*) as total FROM users GROUP BY DATE_FORMAT(created_at,'%Y-%m') ORDER BY MIN(created_at) DESC LIMIT 7")->fetchAll());
$blogGrowth = array_reverse($pdo->query("SELECT DATE_FORMAT(created_at,'%b %Y') as month, COUNT(*) as total FROM posts WHERE status='published' GROUP BY DATE_FORMAT(created_at,'%Y-%m') ORDER BY MIN(created_at) DESC LIMIT 7")->fetchAll());
$likesPerMonth = array_reverse($pdo->query("SELECT DATE_FORMAT(created_at,'%b %Y') as month, COUNT(*) as total FROM likes GROUP BY DATE_FORMAT(created_at,'%Y-%m') ORDER BY MIN(created_at) DESC LIMIT 6")->fetchAll());
$commentsPerMonth = array_reverse($pdo->query("SELECT DATE_FORMAT(created_at,'%b %Y') as month, COUNT(*) as total FROM comments GROUP BY DATE_FORMAT(created_at,'%Y-%m') ORDER BY MIN(created_at) DESC LIMIT 6")->fetchAll());
$topBlogs = $pdo->query("SELECT title,views FROM posts WHERE status='published' ORDER BY views DESC LIMIT 5")->fetchAll();
$blogsByCategory = $pdo->query("SELECT domain, COUNT(*) as total FROM posts WHERE status='published' AND domain!='' GROUP BY domain ORDER BY total DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <title>Dashboard — Admin</title>
  <?php include 'head.php'; ?>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="admin-main">
  <?php include 'topbar.php'; ?>

  <div class="admin-content">

    <!-- page header -->
    <div class="page-header">
      <h1>Dashboard</h1>
      <span style="font-size:12px;color:var(--text-muted)">
        <?= date('l, F j, Y') ?>
      </span>
    </div>

    <!-- stat cards row 1 -->
    <div class="stats-grid stats-grid-5" style="margin-bottom:16px">
      <div class="stat-card">
        <div class="stat-icon blue"><span class="material-icons">group</span></div>
        <div class="stat-info"><div class="num"><?= number_format($totalUsers) ?></div><div class="lbl">Users</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon orange"><span class="material-icons">article</span></div>
        <div class="stat-info"><div class="num"><?= number_format($totalBlogs) ?></div><div class="lbl">Total Blogs</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon yellow"><span class="material-icons">pending</span></div>
        <div class="stat-info"><div class="num"><?= number_format($pendingBlogs) ?></div><div class="lbl">Pending</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green"><span class="material-icons">check_circle</span></div>
        <div class="stat-info"><div class="num"><?= number_format($publishedBlogs) ?></div><div class="lbl">Published</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon red"><span class="material-icons">flag</span></div>
        <div class="stat-info"><div class="num"><?= number_format($totalReports) ?></div><div class="lbl">Reports</div></div>
      </div>
    </div>

    <!-- stat cards row 2 -->
    <div class="stats-grid stats-grid-5" style="margin-bottom:24px">
      <div class="stat-card">
        <div class="stat-icon teal"><span class="material-icons">visibility</span></div>
        <div class="stat-info"><div class="num"><?= number_format($totalViews) ?></div><div class="lbl">Total Views</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon pink"><span class="material-icons">favorite</span></div>
        <div class="stat-info"><div class="num"><?= number_format($totalLikes) ?></div><div class="lbl">Total Likes</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon purple"><span class="material-icons">chat_bubble</span></div>
        <div class="stat-info"><div class="num"><?= number_format($totalComments) ?></div><div class="lbl">Comments</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon orange"><span class="material-icons">workspace_premium</span></div>
        <div class="stat-info"><div class="num"><?= number_format($premiumUsers) ?></div><div class="lbl">Premium</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon red"><span class="material-icons">block</span></div>
        <div class="stat-info"><div class="num"><?= number_format($bannedUsers) ?></div><div class="lbl">Banned</div></div>
      </div>
    </div>

    <!-- highlights -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px">
      <div class="highlight-card">
        <div class="highlight-icon orange"><span class="material-icons">emoji_events</span></div>
        <div class="highlight-info">
          <h4>Most Active User</h4>
          <div class="val"><?= $mostActive ? htmlspecialchars($mostActive['username']) : 'N/A' ?></div>
          <div class="sub"><?= $mostActive ? $mostActive['total'].' blogs published' : '' ?></div>
        </div>
      </div>
      <div class="highlight-card">
        <div class="highlight-icon purple"><span class="material-icons">trending_up</span></div>
        <div class="highlight-info">
          <h4>Most Viewed Blog</h4>
          <div class="val"><?= $mostViewed ? htmlspecialchars(substr($mostViewed['title'],0,35)).'...' : 'N/A' ?></div>
          <div class="sub"><?= $mostViewed ? number_format($mostViewed['views']).' views — by '.htmlspecialchars($mostViewed['username']) : '' ?></div>
        </div>
      </div>
    </div>

    <!-- charts -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">

      <div class="admin-card">
        <div class="admin-card-header">
          <div class="admin-card-title"><span class="material-icons">people</span> User Growth</div>
        </div>
        <div class="admin-card-body">
          <canvas id="userGrowthChart" height="200"></canvas>
        </div>
      </div>

      <div class="admin-card">
        <div class="admin-card-header">
          <div class="admin-card-title"><span class="material-icons">article</span> Blog Growth</div>
        </div>
        <div class="admin-card-body">
          <canvas id="blogGrowthChart" height="200"></canvas>
        </div>
      </div>

    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">

      <div class="admin-card">
        <div class="admin-card-header">
          <div class="admin-card-title"><span class="material-icons">trending_up</span> Top 5 Blogs</div>
        </div>
        <div class="admin-card-body">
          <canvas id="topBlogsChart" height="200"></canvas>
        </div>
      </div>

      <div class="admin-card">
        <div class="admin-card-header">
          <div class="admin-card-title"><span class="material-icons">folder</span> By Category</div>
        </div>
        <div class="admin-card-body">
          <canvas id="categoryChart" height="200"></canvas>
        </div>
      </div>

    </div>

    <div class="admin-card" style="margin-bottom:24px">
      <div class="admin-card-header">
        <div class="admin-card-title"><span class="material-icons">favorite</span> Likes vs Comments</div>
      </div>
      <div class="admin-card-body">
        <canvas id="likesCommentsChart" height="80"></canvas>
      </div>
    </div>

    <!-- recent tables -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">

      <div class="admin-card">
        <div class="admin-card-header">
          <div class="admin-card-title"><span class="material-icons">group</span> Recent Users</div>
          <a href="users.php" class="btn btn-secondary btn-sm">View All</a>
        </div>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <tr><th>User</th><th>Role</th><th>Joined</th></tr>
            <?php foreach ($recentUsers as $u): ?>
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:8px">
                  <div class="avatar avatar-sm">
                    <?php if ($u['photo']): ?><img src="../uploads/<?= htmlspecialchars($u['photo']) ?>" alt=""><?php else: ?><?= strtoupper(substr($u['username'],0,1)) ?><?php endif; ?>
                  </div>
                  <div>
                    <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($u['username']) ?> <?= $u['is_premium']?'💎':'' ?></div>
                    <div style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($u['email']) ?></div>
                  </div>
                </div>
              </td>
              <td><span class="badge badge-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
              <td style="font-size:12px;color:var(--text-muted)"><?= date('d M Y',strtotime($u['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
          </table>
        </div>
      </div>

      <div class="admin-card">
        <div class="admin-card-header">
          <div class="admin-card-title"><span class="material-icons">article</span> Recent Blogs</div>
          <a href="blogs.php" class="btn btn-secondary btn-sm">View All</a>
        </div>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <tr><th>Title</th><th>Author</th><th>Status</th></tr>
            <?php foreach ($recentBlogs as $b): ?>
            <tr>
              <td style="font-weight:600;font-size:13px;max-width:180px;
                         white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                <?= htmlspecialchars($b['title']) ?>
              </td>
              <td style="font-size:12px;color:var(--text-sub)"><?= htmlspecialchars($b['username']) ?></td>
              <td><span class="badge badge-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
            </tr>
            <?php endforeach; ?>
          </table>
        </div>
      </div>

    </div>

  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script>
  Chart.defaults.font.family = 'Inter, sans-serif';
  Chart.defaults.color = '#9ca3af';

  const orange  = '#ea580c';
  const orangeT = 'rgba(234,88,12,0.1)';
  const blue    = '#2563eb';
  const blueT   = 'rgba(37,99,235,0.1)';
  const green   = '#059669';
  const greenT  = 'rgba(5,150,105,0.1)';
  const purple  = '#7c3aed';
  const purpleT = 'rgba(124,58,237,0.1)';
  const red     = '#dc2626';
  const redT    = 'rgba(220,38,38,0.1)';

  const baseOpts = {
    responsive:true,
    plugins:{ legend:{ display:false } },
    scales:{
      y:{ beginAtZero:true, ticks:{ stepSize:1 }, grid:{ color:'rgba(0,0,0,0.04)' } },
      x:{ grid:{ display:false } }
    }
  };

  new Chart(document.getElementById('userGrowthChart'), {
    type:'line',
    data:{
      labels:<?= json_encode(array_column($userGrowth,'month')) ?>,
      datasets:[{ data:<?= json_encode(array_column($userGrowth,'total')) ?>, borderColor:blue, backgroundColor:blueT, borderWidth:2, fill:true, tension:0.4, pointRadius:3 }]
    }, options:baseOpts
  });

  new Chart(document.getElementById('blogGrowthChart'), {
    type:'line',
    data:{
      labels:<?= json_encode(array_column($blogGrowth,'month')) ?>,
      datasets:[{ data:<?= json_encode(array_column($blogGrowth,'total')) ?>, borderColor:orange, backgroundColor:orangeT, borderWidth:2, fill:true, tension:0.4, pointRadius:3 }]
    }, options:baseOpts
  });

  new Chart(document.getElementById('topBlogsChart'), {
    type:'line',
    data:{
      labels:<?= json_encode(array_map(fn($b)=>substr($b['title'],0,14).'...',$topBlogs)) ?>,
      datasets:[{ data:<?= json_encode(array_column($topBlogs,'views')) ?>, borderColor:green, backgroundColor:greenT, borderWidth:2, fill:true, tension:0.4, pointRadius:4 }]
    }, options:baseOpts
  });

  new Chart(document.getElementById('categoryChart'), {
    type:'line',
    data:{
      labels:<?= json_encode(array_column($blogsByCategory,'domain')) ?>,
      datasets:[{ data:<?= json_encode(array_column($blogsByCategory,'total')) ?>, borderColor:purple, backgroundColor:purpleT, borderWidth:2, fill:true, tension:0.4, pointRadius:4 }]
    }, options:baseOpts
  });

  new Chart(document.getElementById('likesCommentsChart'), {
    type:'line',
    data:{
      labels:<?= json_encode(array_column($likesPerMonth,'month')) ?>,
      datasets:[
        { label:'Likes',    data:<?= json_encode(array_column($likesPerMonth,'total')) ?>, borderColor:orange, backgroundColor:orangeT, borderWidth:2, fill:true, tension:0.4, pointRadius:3 },
        { label:'Comments', data:<?= json_encode(array_column($commentsPerMonth,'total')) ?>, borderColor:purple, backgroundColor:purpleT, borderWidth:2, fill:true, tension:0.4, pointRadius:3 }
      ]
    },
    options:{ ...baseOpts, plugins:{ legend:{ display:true, position:'top', labels:{ usePointStyle:true, padding:16, font:{ size:11 } } } } }
  });
</script>

</body>
</html>