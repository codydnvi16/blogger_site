<?php
session_name('admin_session');
session_start();
require '../include/database.php';
if (!isset($_SESSION['user_id'])||$_SESSION['role']!='admin') { header("Location: login.php"); exit; }

$pageTitle = "My Profile";

$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch();

$totalBlogs = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id=?");
$totalBlogs->execute([$_SESSION['user_id']]);
$totalBlogs = $totalBlogs->fetchColumn();

$totalViews = $pdo->prepare("SELECT SUM(views) FROM posts WHERE user_id=?");
$totalViews->execute([$_SESSION['user_id']]);
$totalViews = $totalViews->fetchColumn() ?? 0;

$totalLikes = $pdo->prepare("SELECT COUNT(*) FROM likes JOIN posts ON likes.post_id=posts.id WHERE posts.user_id=?");
$totalLikes->execute([$_SESSION['user_id']]);
$totalLikes = $totalLikes->fetchColumn();

$totalFollowers = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE following_id=?");
$totalFollowers->execute([$_SESSION['user_id']]);
$totalFollowers = $totalFollowers->fetchColumn();

$siteUsers    = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$siteBlogs    = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$sitePending  = $pdo->query("SELECT COUNT(*) FROM posts WHERE status='pending'")->fetchColumn();
$siteReports  = $pdo->query("SELECT COUNT(*) FROM reports")->fetchColumn();

$blogs = $pdo->prepare("SELECT * FROM posts WHERE user_id=? ORDER BY created_at DESC");
$blogs->execute([$_SESSION['user_id']]);
$blogs = $blogs->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <title>My Profile — Admin</title>
  <?php include 'head.php'; ?>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="admin-main">
  <?php include 'topbar.php'; ?>
  <div class="admin-content">

    <div class="page-header">
      <h1>My Profile</h1>
      <a href="../user/edit-profile.php" class="btn btn-primary" target="_blank">
        <span class="material-icons">edit</span> Edit Profile
      </a>
    </div>

    <!-- profile card -->
    <div class="admin-card" style="margin-bottom:20px">
      <div class="admin-card-body">
        <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap">
          <div class="avatar avatar-lg">
            <?php if ($admin['photo']): ?>
              <img src="../uploads/<?= htmlspecialchars($admin['photo']) ?>" alt="">
            <?php else: ?>
              <?= strtoupper(substr($admin['username'],0,1)) ?>
            <?php endif; ?>
          </div>
          <div style="flex:1">
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:6px">
              <h2 style="font-size:22px;font-weight:800"><?= htmlspecialchars($admin['username']) ?></h2>
              <span class="badge badge-admin">Admin</span>
              <?php if ($admin['is_premium']): ?><span class="badge badge-premium">💎 Premium</span><?php endif; ?>
            </div>
            <div style="font-size:13px;color:var(--text-muted);margin-bottom:8px;
                        display:flex;align-items:center;gap:6px">
              <span class="material-icons" style="font-size:15px">mail</span>
              <?= htmlspecialchars($admin['email']) ?>
            </div>
            <?php if (!empty($admin['bio'])): ?>
              <p style="font-size:13px;color:var(--text-sub);line-height:1.5;max-width:500px">
                <?= htmlspecialchars($admin['bio']) ?>
              </p>
            <?php endif; ?>
            <div style="font-size:12px;color:var(--text-muted);margin-top:8px;
                        display:flex;align-items:center;gap:4px">
              <span class="material-icons" style="font-size:14px">calendar_today</span>
              Member since <?= date('d M Y',strtotime($admin['created_at'])) ?>
            </div>
          </div>
          <div style="display:flex;flex-direction:column;gap:10px">
            <div style="text-align:center;padding:12px 20px;background:var(--bg-input);border-radius:6px">
              <div style="font-size:22px;font-weight:800;color:var(--primary)"><?= $totalFollowers ?></div>
              <div style="font-size:11px;color:var(--text-muted);font-weight:600">Followers</div>
            </div>
            <div style="text-align:center;padding:12px 20px;background:var(--bg-input);border-radius:6px">
              <div style="font-size:22px;font-weight:800;color:var(--primary)"><?= number_format($totalViews) ?></div>
              <div style="font-size:11px;color:var(--text-muted);font-weight:600">Views</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- my activity stats -->
    <div style="font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;
                color:var(--text-muted);margin-bottom:12px">My Activity</div>
    <div class="stats-grid stats-grid-4" style="margin-bottom:20px">
      <div class="stat-card"><div class="stat-icon orange"><span class="material-icons">article</span></div><div class="stat-info"><div class="num"><?= $totalBlogs ?></div><div class="lbl">My Blogs</div></div></div>
      <div class="stat-card"><div class="stat-icon blue"><span class="material-icons">visibility</span></div><div class="stat-info"><div class="num"><?= number_format($totalViews) ?></div><div class="lbl">Total Views</div></div></div>
      <div class="stat-card"><div class="stat-icon pink"><span class="material-icons">favorite</span></div><div class="stat-info"><div class="num"><?= $totalLikes ?></div><div class="lbl">Likes</div></div></div>
      <div class="stat-card"><div class="stat-icon green"><span class="material-icons">people</span></div><div class="stat-info"><div class="num"><?= $totalFollowers ?></div><div class="lbl">Followers</div></div></div>
    </div>

    <!-- site management stats -->
    <div style="font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;
                color:var(--text-muted);margin-bottom:12px">Site Management</div>
    <div class="stats-grid stats-grid-4" style="margin-bottom:24px">
      <div class="stat-card"><div class="stat-icon blue"><span class="material-icons">group</span></div><div class="stat-info"><div class="num"><?= $siteUsers ?></div><div class="lbl">Total Users</div></div></div>
      <div class="stat-card"><div class="stat-icon green"><span class="material-icons">article</span></div><div class="stat-info"><div class="num"><?= $siteBlogs ?></div><div class="lbl">Total Blogs</div></div></div>
      <div class="stat-card"><div class="stat-icon yellow"><span class="material-icons">pending</span></div><div class="stat-info"><div class="num"><?= $sitePending ?></div><div class="lbl">Pending</div></div></div>
      <div class="stat-card"><div class="stat-icon red"><span class="material-icons">flag</span></div><div class="stat-info"><div class="num"><?= $siteReports ?></div><div class="lbl">Reports</div></div></div>
    </div>

    <!-- my blogs -->
    <div class="admin-card">
      <div class="admin-card-header">
        <div class="admin-card-title"><span class="material-icons">article</span> My Blogs</div>
        <a href="../user/write-blog.php" class="btn btn-primary btn-sm" target="_blank">
          <span class="material-icons">edit</span> Write New
        </a>
      </div>

      <?php if (count($blogs)==0): ?>
        <div class="empty-state"><span class="material-icons">edit_note</span><h3>No blogs yet</h3></div>
      <?php else: ?>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <tr><th>Blog</th><th>Category</th><th>Views</th><th>Status</th><th>Date</th><th>Action</th></tr>
          <?php foreach ($blogs as $b): ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:10px">
                <?php if ($b['image']): ?>
                  <img src="../uploads/<?= htmlspecialchars($b['image']) ?>"
                       style="width:36px;height:30px;object-fit:cover;border-radius:3px;flex-shrink:0" alt="">
                <?php else: ?>
                  <div style="width:36px;height:30px;background:var(--bg-input);border-radius:3px;
                              flex-shrink:0;display:flex;align-items:center;justify-content:center">
                    <span class="material-icons" style="font-size:14px;color:var(--text-muted)">article</span>
                  </div>
                <?php endif; ?>
                <span style="font-weight:600;font-size:13px;max-width:200px;
                             white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                  <?= htmlspecialchars($b['title']) ?>
                </span>
              </div>
            </td>
            <td>
              <?php if ($b['domain']): ?>
                <span class="badge badge-primary"><?= htmlspecialchars($b['domain']) ?></span>
              <?php else: ?>
                <span style="color:var(--text-muted);font-size:12px">—</span>
              <?php endif; ?>
            </td>
            <td style="font-size:12px;color:var(--text-sub)"><?= number_format($b['views']) ?></td>
            <td><span class="badge badge-<?= $b['status'] ?>"><?= ucfirst($b['status']) ?></span></td>
            <td style="font-size:12px;color:var(--text-muted)"><?= date('d M Y',strtotime($b['created_at'])) ?></td>
            <td>
              <?php if ($b['status']=='published'): ?>
                <a href="../user/post.php?id=<?= $b['id'] ?>" class="btn btn-info btn-sm" target="_blank">
                  <span class="material-icons">visibility</span>
                </a>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </table>
      </div>
      <?php endif; ?>
    </div>

  </div>
</div>

</body>
</html>