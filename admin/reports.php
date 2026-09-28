<?php
session_name('admin_session');
session_start();
require '../include/database.php';
if (!isset($_SESSION['user_id'])||$_SESSION['role']!='admin') { header("Location: login.php"); exit; }

$pageTitle = "Reports";

if (isset($_GET['dismiss'])) {
    $pdo->prepare("DELETE FROM reports WHERE id=?")->execute([$_GET['dismiss']]);
    header("Location: reports.php"); exit;
}

if (isset($_GET['delete_post'])) {
    $pid=$_GET['delete_post'];
    $cs=$pdo->prepare("SELECT id FROM comments WHERE post_id=?"); $cs->execute([$pid]);
    foreach($cs->fetchAll() as $c) { $pdo->prepare("DELETE FROM replies WHERE comment_id=?")->execute([$c['id']]); }
    foreach(['comments','likes','reports','bookmarks'] as $t) { $pdo->prepare("DELETE FROM $t WHERE post_id=?")->execute([$pid]); }
    $pdo->prepare("DELETE FROM posts WHERE id=?")->execute([$pid]);
    header("Location: reports.php"); exit;
}

$reports = $pdo->query("SELECT reports.*,posts.title as post_title,posts.id as post_id,users.username as reporter,p2.username as author FROM reports JOIN posts ON reports.post_id=posts.id JOIN users ON reports.user_id=users.id JOIN users p2 ON posts.user_id=p2.id ORDER BY reports.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <title>Reports — Admin</title>
  <?php include 'head.php'; ?>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="admin-main">
  <?php include 'topbar.php'; ?>
  <div class="admin-content">

    <div class="page-header">
      <h1>Reports</h1>
      <?php if (count($reports)>0): ?>
        <span class="badge badge-banned" style="font-size:12px"><?= count($reports) ?> pending</span>
      <?php endif; ?>
    </div>

    <div class="admin-card">
      <?php if (count($reports)==0): ?>
        <div class="empty-state">
          <span class="material-icons" style="color:#16a34a;opacity:1">check_circle</span>
          <h3 style="color:#16a34a">All Clear!</h3>
          <p>No reports to review. Everything looks good.</p>
        </div>
      <?php else: ?>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <tr><th>Blog</th><th>Author</th><th>Reported By</th><th>Reason</th><th>Date</th><th>Actions</th></tr>
          <?php foreach ($reports as $r): ?>
          <tr>
            <td style="font-weight:600;font-size:13px;max-width:160px;
                       white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
              <?= htmlspecialchars($r['post_title']) ?>
            </td>
            <td style="font-size:12px;color:var(--text-sub)"><?= htmlspecialchars($r['author']) ?></td>
            <td style="font-size:12px;color:var(--text-sub)"><?= htmlspecialchars($r['reporter']) ?></td>
            <td><span class="badge badge-banned"><?= htmlspecialchars($r['reason']) ?></span></td>
            <td style="font-size:12px;color:var(--text-muted);white-space:nowrap">
              <?= date('d M Y',strtotime($r['created_at'])) ?>
            </td>
            <td>
              <div style="display:flex;gap:4px">
                <a href="../user/post.php?id=<?= $r['post_id'] ?>" class="btn btn-info btn-sm" target="_blank">
                  <span class="material-icons">visibility</span>
                </a>
                <a href="reports.php?dismiss=<?= $r['id'] ?>" class="btn btn-success btn-sm">
                  <span class="material-icons">check</span> Dismiss
                </a>
                <a href="reports.php?delete_post=<?= $r['post_id'] ?>"
                   class="btn btn-danger btn-sm"
                   onclick="return confirm('Delete this blog permanently?')">
                  <span class="material-icons">delete</span> Delete Blog
                </a>
              </div>
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